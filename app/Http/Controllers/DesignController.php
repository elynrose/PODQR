<?php

namespace App\Http\Controllers;

use App\Models\ClothesType;
use App\Models\ClothesCategory;
use App\Models\ShirtSize;
use App\Models\QrCode as QrCodeModel;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    /**
     * Show the design page with clothes types and categories.
     */
    public function index(Request $request)
    {
        // Get all active clothes types with their categories
        $clothesTypes = ClothesType::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Get all active categories
        $categories = ClothesCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get all active shirt sizes
        $shirtSizes = ShirtSize::where('is_active', true)
            ->ordered()
            ->get();

        // Build a color map for each category
        $categoryColorMap = [];
        foreach ($categories as $category) {
            $types = $clothesTypes->where('category_id', $category->id);
            $colors = $types->pluck('colors')->flatten()->unique()->values()->toArray();
            $map = [];
            foreach ($colors as $color) {
                $map[$color] = $this->getColorHexValue($color);
            }
            $categoryColorMap[$category->id] = $map;
        }

        // Handle QR code parameter or create default QR code
        $defaultQrCode = null;
        $userQrCodes = collect(); // Initialize empty collection
        $user = auth()->user();
        
        if ($request->has('qr_code')) {
            // Use specific QR code from parameter
            $qrCodeId = $request->input('qr_code');
            
            if ($user) {
                $defaultQrCode = QrCodeModel::where('id', $qrCodeId)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->first();
            }
        } elseif ($user) {
            // Check if user has any QR codes
            $defaultQrCode = QrCodeModel::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();
            
            // If no QR code exists, create one automatically
            if (!$defaultQrCode) {
                try {
                    $defaultQrCode = $this->createDefaultQrCode($user);
                } catch (\Exception $e) {
                    // Log the error but don't break the page
                    \Log::error('Failed to create default QR code for user ' . $user->id . ': ' . $e->getMessage());
                }
            }
        }

        // Get all user's QR codes for the selection modal
        if ($user) {
            $userQrCodes = QrCodeModel::where('user_id', $user->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('design', compact('clothesTypes', 'categories', 'categoryColorMap', 'shirtSizes', 'defaultQrCode', 'userQrCodes'));
    }

    /**
     * Get hex color value for a color name
     */
    public function getColorHexValue($colorName)
    {
        $colorMap = [
            'White' => '#ffffff',
            'Black' => '#000000',
            'Navy' => '#000080',
            'Gray' => '#808080',
            'Red' => '#ff0000',
            'Blue' => '#0000ff',
            'Green' => '#008000',
            'Yellow' => '#ffff00',
            'Purple' => '#800080',
            'Orange' => '#ffa500',
            'Pink' => '#ffc0cb',
            'Brown' => '#a52a2a',
            'Burgundy' => '#800020',
            'Charcoal' => '#36454f',
            'Olive' => '#808000',
            'Forest Green' => '#228b22'
        ];
        
        return $colorMap[$colorName] ?? '#000000';
    }

    private function createDefaultQrCode($user)
    {
        // Create a QR code linking to the user's profile
        $profileUrl = url('/user/' . $user->username);
        
        // Generate QR code using Simple QrCode
        $qrCode = \QrCode::format('svg')
            ->size(4000)
            ->margin(0)
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255)
            ->generate($profileUrl);
        
        // Save QR code to storage
        $filename = $user->id . '_' . time() . '_' . uniqid() . '.svg';
        $filePath = 'qr-codes/' . $filename;
        
        \Storage::put('public/' . $filePath, $qrCode);
        
        // Create QR code record in database
        $qrCodeModel = QrCodeModel::create([
            'user_id' => $user->id,
            'name' => 'My Profile QR Code',
            'content' => $profileUrl,
            'file_path' => $filePath,
            'size' => 4000,
            'foreground_color' => '#000000',
            'background_color' => '#FFFFFF',
            'is_active' => true,
        ]);
        
        return $qrCodeModel;
    }
}
