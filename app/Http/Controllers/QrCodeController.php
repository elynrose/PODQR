<?php

namespace App\Http\Controllers;

use App\Models\QrCode as QrCodeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class QrCodeController extends Controller
{
    /**
     * Show QR code generator form
     */
    public function show()
    {
        $user = Auth::user();
        $qrCodes = collect();
        $userType = 'free';
        $userUniqueUrl = '';
        
        if ($user) {
            $userType = $user->user_type;
            $userUniqueUrl = $user->getProfileUrl();
            $qrCodes = QrCodeModel::where('user_id', $user->id)
                ->active()
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return view('qr-generator', compact('qrCodes', 'user', 'userType', 'userUniqueUrl'));
    }

    /**
     * Generate a QR code and save it to database
     */
    public function generate(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'size' => 'required|in:200,300,400',
            'qr_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'background_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        try {
            $user = auth()->user();
            
            // Generate QR code
            $qrCode = QrCodeModel::create([
                'user_id' => $user->id,
                'name' => 'QR Code ' . now()->format('Y-m-d H:i:s'),
                'content' => $request->content,
                'size' => $request->size,
                'qr_color' => $request->qr_color,
                'background_color' => $request->background_color,
            ]);

            // Generate the QR code image
            $qrCodeImage = QrCode::format('svg')
                ->size($request->size)
                ->color($request->qr_color)
                ->backgroundColor($request->background_color)
                ->generate($request->content);

            // Save the QR code image
            $filename = $user->id . '_' . time() . '_' . Str::random(12) . '.svg';
            $path = 'qr-codes/' . $filename;
            
            Storage::disk('public')->put($path, $qrCodeImage);
            
            // Update the QR code with the file path
            $qrCode->update(['file_path' => $path]);

            // Return the image with CORS headers
            return response($qrCodeImage)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type');

        } catch (\Exception $e) {
            Log::error('QR Code generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate a QR code and return it as an image (without saving)
     */
    public function generateImage(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'size' => 'integer|min:100|max:4000',
            'color' => 'string|regex:/^#[0-9A-F]{6}$/i',
            'background' => 'string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $text = $request->input('text');
        $size = $request->input('size', 4000);
        $color = $this->hexToRgb($request->input('color', '#000000'));
        $background = $this->hexToRgb($request->input('background', '#FFFFFF'));

        // Generate QR code
        $qrCode = QrCode::size($size)
            ->color($color[0], $color[1], $color[2])
            ->backgroundColor($background[0], $background[1], $background[2])
            ->format('svg')
            ->generate($text);

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=31536000');
    }

    /**
     * Helper to convert hex color to RGB array
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $int = hexdec($hex);
        return [($int >> 16) & 255, ($int >> 8) & 255, $int & 255];
    }

    /**
     * Generate a QR code and return it as a data URL (without saving)
     */
    public function generateDataUrl(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'size' => 'integer|min:100|max:4000',
            'color' => 'string|regex:/^#[0-9A-F]{6}$/i',
            'background' => 'string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $text = $request->input('text');
        $size = $request->input('size', 4000);
        $color = $this->hexToRgb($request->input('color', '#000000'));
        $background = $this->hexToRgb($request->input('background', '#FFFFFF'));

        // Generate QR code as SVG (which doesn't require Imagick)
        $qrCode = QrCode::size($size)
            ->color($color[0], $color[1], $color[2])
            ->backgroundColor($background[0], $background[1], $background[2])
            ->format('svg')
            ->generate($text);

        $dataUrl = 'data:image/svg+xml;base64,' . base64_encode($qrCode);

        return response()->json([
            'success' => true,
            'data_url' => $dataUrl,
            'text' => $text
        ]);
    }

    /**
     * Get user's QR codes
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $qrCodes = QrCodeModel::where('user_id', $user->id)
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('qr-codes.index', compact('qrCodes'));
    }

    /**
     * Delete a QR code
     */
    public function destroy(QrCodeModel $qrCode)
    {
        $user = Auth::user();
        
        if (!$user || $qrCode->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete file from storage
        if ($qrCode->file_path) {
            Storage::disk('public')->delete($qrCode->file_path);
        }

        $qrCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'QR code deleted successfully'
        ]);
    }

    /**
     * Save QR code and redirect to T-shirt designer
     */
    public function saveAndDesign(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'size' => 'integer|min:100|max:4000',
            'color' => 'string|regex:/^#[0-9A-F]{6}$/i',
            'background' => 'string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user already has a QR code with this content
        $existingQrCode = QrCodeModel::where('user_id', $user->id)
            ->where('content', $request->input('text'))
            ->first();

        if ($existingQrCode) {
            // Use existing QR code
            return redirect()->route('design', ['qr_code' => $existingQrCode->id])
                ->with('success', 'QR code loaded and ready for T-shirt design!');
        }

        // Check user limits for new QR codes
        $qrCodeCount = QrCodeModel::where('user_id', $user->id)->count();
        $maxQrCodes = $user->getQrCodeLimit();
        
        if ($qrCodeCount >= $maxQrCodes) {
            $message = match($user->user_type) {
                'partner' => 'You have reached the maximum number of QR codes.',
                'premium' => 'You have reached the limit of 20 QR codes. Upgrade to Partner for unlimited QR codes.',
                'free' => 'Free users can only create 1 QR code. Upgrade to Premium for 20 QR codes or Partner for unlimited QR codes.',
                default => 'You have reached the maximum number of QR codes.'
            };
            
            return redirect()->route('qr-generator')
                ->with('error', $message);
        }

        $text = $request->input('text');
        $size = $request->input('size', 4000);
        $color = $this->hexToRgb($request->input('color', '#000000'));
        $background = $this->hexToRgb($request->input('background', '#FFFFFF'));
        $name = $request->input('name', 'QR Code for T-Shirt');

        // Create QR code record
        $qrCode = QrCodeModel::create([
            'user_id' => $user->id,
            'name' => $name,
            'content' => $text,
            'size' => $size,
            'color' => $request->input('color', '#000000'),
            'background_color' => $request->input('background', '#FFFFFF'),
            'format' => 'svg',
        ]);

        // Generate QR code image
        $qrCodeImage = QrCode::size($size)
            ->color($color[0], $color[1], $color[2])
            ->backgroundColor($background[0], $background[1], $background[2])
            ->format('svg')
            ->generate($text);

        // Save file to storage
        $filename = $qrCode->generateFilename();
        Storage::disk('public')->put($filename, $qrCodeImage);
        
        // Update QR code with file path
        $qrCode->update(['file_path' => $filename]);

        // Redirect to T-shirt designer with QR code as default image
        return redirect()->route('design', ['qr_code' => $qrCode->id])
            ->with('success', 'QR code saved and ready for T-shirt design!');
    }

    /**
     * Generate a QR code from designer and save it to database
     */
    public function generateAndSaveFromDesigner(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'size' => 'integer|min:100|max:4000',
            'color' => 'string|regex:/^#[0-9A-F]{6}$/i',
            'background' => 'string|regex:/^#[0-9A-F]{6}$/i',
            'name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to save QR codes.'
            ], 401);
        }

        // Check user limits
        $qrCodeCount = QrCodeModel::where('user_id', $user->id)->count();
        $maxQrCodes = $user->getQrCodeLimit();
        
        if ($qrCodeCount >= $maxQrCodes) {
            $message = match($user->user_type) {
                'partner' => 'You have reached the maximum number of QR codes.',
                'premium' => 'You have reached the limit of 20 QR codes. Upgrade to Partner for unlimited QR codes.',
                'free' => 'Free users can only create 1 QR code. Upgrade to Premium for 20 QR codes or Partner for unlimited QR codes.',
                default => 'You have reached the maximum number of QR codes.'
            };
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], 403);
        }

        $text = $request->input('text');
        $size = $request->input('size', 4000);
        $color = $this->hexToRgb($request->input('color', '#000000'));
        $background = $this->hexToRgb($request->input('background', '#FFFFFF'));
        $name = $request->input('name', 'Design QR Code ' . now()->format('Y-m-d H:i'));

        // Create QR code record
        $qrCode = QrCodeModel::create([
            'user_id' => $user->id,
            'name' => $name,
            'content' => $text,
            'size' => $size,
            'color' => $request->input('color', '#000000'),
            'background_color' => $request->input('background', '#FFFFFF'),
            'format' => 'svg',
            'is_active' => true,
        ]);

        // Generate QR code image as SVG
        $qrCodeImage = QrCode::size($size)
            ->color($color[0], $color[1], $color[2])
            ->backgroundColor($background[0], $background[1], $background[2])
            ->format('svg')
            ->generate($text);

        // Save file to storage
        $filename = $qrCode->generateFilename();
        Storage::disk('public')->put($filename, $qrCodeImage);
        
        // Update QR code with file path
        $qrCode->update(['file_path' => $filename]);

        // Generate data URL for immediate use in designer
        $dataUrl = 'data:image/svg+xml;base64,' . base64_encode($qrCodeImage);

        return response()->json([
            'success' => true,
            'message' => 'QR code saved successfully!',
            'qr_code' => $qrCode,
            'qr_code_id' => $qrCode->id,
            'data_url' => $dataUrl,
            'file_url' => $qrCode->file_url
        ]);
    }

    /**
     * Get user's QR codes for the design page
     */
    public function getUserQrCodes()
    {
        try {
            $user = auth()->user();
            $qrCodes = QrCode::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($qrCode) {
                    // Use same-domain URL instead of external storage URL
                    $qrCode->file_url = url('/qr-codes/' . basename($qrCode->file_path));
                    return $qrCode;
                });

            return response()->json([
                'success' => true,
                'qr_codes' => $qrCodes
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user QR codes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading QR codes'
            ], 500);
        }
    }
}
