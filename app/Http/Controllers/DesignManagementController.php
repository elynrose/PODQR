<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\ClothesType;
use App\Models\ShirtSize;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DesignManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $designs = Design::with(['clothesType', 'shirtSize', 'qrCode'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('designs.index', compact('designs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clothesTypes = ClothesType::where('is_active', true)->get();
        $shirtSizes = ShirtSize::where('is_active', true)->get();
        $qrCodes = QrCode::where('user_id', Auth::id())->where('is_active', true)->get();

        return view('designs.create', compact('clothesTypes', 'shirtSizes', 'qrCodes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'clothes_type_id' => 'required|exists:clothes_types,id',
            'shirt_size_id' => 'required|exists:shirt_sizes,id',
            'color_code' => 'required|string',
            'qr_code_id' => 'nullable|exists:qr_codes,id',
            'qr_code_position' => 'nullable|array',
            'photos' => 'nullable|array',
            'texts' => 'nullable|array',
            'front_canvas_data' => 'nullable|string',
            'back_canvas_data' => 'nullable|string',
            'status' => 'in:draft,saved,published',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to save designs.'
            ], 401);
        }

        $design = Design::create([
            'user_id' => $user->id,
            'name' => $request->input('name', 'My Design ' . now()->format('Y-m-d H:i')),
            'description' => $request->input('description'),
            'clothes_type_id' => $request->input('clothes_type_id'),
            'shirt_size_id' => $request->input('shirt_size_id'),
            'color_code' => $request->input('color_code'),
            'qr_code_id' => $request->input('qr_code_id'),
            'qr_code_position' => $request->input('qr_code_position'),
            'photos' => $request->input('photos'),
            'texts' => $request->input('texts'),
            'front_canvas_data' => $request->input('front_canvas_data'),
            'back_canvas_data' => $request->input('back_canvas_data'),
            'status' => $request->input('status', 'saved'),
            'is_public' => $request->input('is_public', false),
        ]);

        // Generate and save preview images if canvas data is provided
        if ($request->input('front_canvas_data') || $request->input('back_canvas_data')) {
            $this->generatePreviewImages($design);
        }

        return response()->json([
            'success' => true,
            'message' => 'Design saved successfully!',
            'design' => $design->load(['clothesType', 'shirtSize', 'qrCode']),
            'redirect_url' => route('designs.show', $design->id)
        ]);
    }

    /**
     * Save design from the design page (AJAX endpoint).
     */
    public function saveFromDesigner(Request $request)
    {
        // Debug: Log the incoming request data
        \Log::info('Save design request data:', $request->all());
        
        $validator = Validator::make($request->all(), [
            'clothes_type_id' => 'required|exists:clothes_types,id',
            'shirt_size_id' => 'required|exists:shirt_sizes,id',
            'color_code' => 'required|string',
            'qr_code_id' => 'nullable|exists:qr_codes,id',
            'front_canvas_data' => 'nullable|string',
            'back_canvas_data' => 'nullable|string',
            'cover_image_data' => 'nullable|string',
            'design_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            // Debug: Log validation errors
            \Log::error('Design save validation failed:', $validator->errors()->toArray());
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'debug_data' => $request->all() // Include request data for debugging
            ], 422);
        }

        try {
            $user = auth()->user();
            
            // Create the design
            $design = Design::create([
                'user_id' => $user->id,
                'name' => $request->design_name ?: 'Untitled Design',
                'description' => $request->description,
                'clothes_type_id' => $request->clothes_type_id,
                'shirt_size_id' => $request->shirt_size_id,
                'color_code' => $request->color_code,
                'qr_code_id' => $request->qr_code_id,
                'front_canvas_data' => $request->front_canvas_data,
                'back_canvas_data' => $request->back_canvas_data,
                'status' => 'saved',
                'is_public' => false,
            ]);

            // Save cover image if provided
            if ($request->cover_image_data) {
                \Log::info('=== COVER IMAGE DATA RECEIVED ===');
                \Log::info('Cover image data length: ' . strlen($request->cover_image_data));
                \Log::info('Cover image data starts with: ' . substr($request->cover_image_data, 0, 100));
                
                $coverImagePath = $this->saveCoverImage($request->cover_image_data, $design->id);
                $design->update(['cover_image' => $coverImagePath]);
                \Log::info('Cover image path saved to database: ' . $coverImagePath);
            } else {
                \Log::info('No cover image data provided in request');
            }

            return response()->json([
                'success' => true,
                'message' => 'Design saved successfully!',
                'design_id' => $design->id,
                'redirect_url' => route('designs.show', $design->id)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving design: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving design: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save cover image from base64 data.
     */
    private function saveCoverImage($base64Data, $designId)
    {
        \Log::info('=== COVER IMAGE SAVE STARTED ===');
        \Log::info('Design ID: ' . $designId);
        \Log::info('Base64 data length: ' . strlen($base64Data));
        \Log::info('Base64 data starts with: ' . substr($base64Data, 0, 50));
        
        try {
            // Remove data URL prefix if present
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
            \Log::info('After removing prefix, length: ' . strlen($base64Data));
            
            // Decode base64 data
            $imageData = base64_decode($base64Data);
            
            if ($imageData === false) {
                \Log::error('Failed to decode base64 data');
                throw new \Exception('Invalid base64 image data');
            }
            
            \Log::info('Decoded image data length: ' . strlen($imageData));
            
            // Generate filename
            $filename = 'design-covers/' . $designId . '_' . time() . '_' . uniqid() . '.png';
            \Log::info('Generated filename: ' . $filename);
            
            // Save to storage
            $result = \Storage::disk('public')->put($filename, $imageData);
            \Log::info('Storage put result: ' . ($result ? 'true' : 'false'));
            
            if ($result) {
                \Log::info('Cover image saved successfully: ' . $filename);
                return $filename;
            } else {
                \Log::error('Failed to save cover image to storage');
                throw new \Exception('Failed to save cover image to storage');
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in saveCoverImage: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Design $design)
    {
        // Check if user can view this design
        if ($design->user_id !== Auth::id() && !$design->is_public) {
            abort(403, 'You do not have permission to view this design.');
        }

        $design->load(['clothesType', 'shirtSize', 'qrCode', 'user']);

        return view('designs.show', compact('design'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Design $design)
    {
        // Check if user can edit this design
        if ($design->user_id !== Auth::id()) {
            abort(403, 'You do not have permission to edit this design.');
        }

        // Get all active clothes types with their categories
        $clothesTypes = ClothesType::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Get all active categories
        $categories = \App\Models\ClothesCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get all active shirt sizes
        $shirtSizes = ShirtSize::where('is_active', true)
            ->ordered()
            ->get();

        // Get QR codes for the user
        $qrCodes = QrCode::where('user_id', Auth::id())->where('is_active', true)->get();

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

        $design->load(['clothesType', 'shirtSize', 'qrCode']);

        return view('designs.edit', compact('design', 'clothesTypes', 'categories', 'categoryColorMap', 'shirtSizes', 'qrCodes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Design $design)
    {
        // Check if user can edit this design
        if ($design->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this design.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'clothes_type_id' => 'required|exists:clothes_types,id',
            'shirt_size_id' => 'required|exists:shirt_sizes,id',
            'color_code' => 'required|string',
            'qr_code_id' => 'nullable|exists:qr_codes,id',
            'qr_code_position' => 'nullable|array',
            'photos' => 'nullable|array',
            'texts' => 'nullable|array',
            'front_canvas_data' => 'nullable|string',
            'back_canvas_data' => 'nullable|string',
            'status' => 'in:draft,saved,published',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $design->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'clothes_type_id' => $request->input('clothes_type_id'),
            'shirt_size_id' => $request->input('shirt_size_id'),
            'color_code' => $request->input('color_code'),
            'qr_code_id' => $request->input('qr_code_id'),
            'qr_code_position' => $request->input('qr_code_position'),
            'photos' => $request->input('photos'),
            'texts' => $request->input('texts'),
            'front_canvas_data' => $request->input('front_canvas_data'),
            'back_canvas_data' => $request->input('back_canvas_data'),
            'status' => $request->input('status'),
            'is_public' => $request->input('is_public', false),
        ]);

        // Regenerate preview images if canvas data changed
        if ($request->input('front_canvas_data') || $request->input('back_canvas_data')) {
            $this->generatePreviewImages($design);
        }

        return response()->json([
            'success' => true,
            'message' => 'Design updated successfully!',
            'design' => $design->load(['clothesType', 'shirtSize', 'qrCode'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Design $design)
    {
        // Check if user can delete this design
        if ($design->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this design.'
            ], 403);
        }

        // Delete preview images
        if ($design->front_image_path) {
            Storage::disk('public')->delete($design->front_image_path);
        }
        if ($design->back_image_path) {
            Storage::disk('public')->delete($design->back_image_path);
        }

        $design->delete();

        return response()->json([
            'success' => true,
            'message' => 'Design deleted successfully!'
        ]);
    }

    /**
     * Generate preview images from canvas data.
     */
    private function generatePreviewImages(Design $design)
    {
        // This would typically use a service to convert canvas data to images
        // For now, we'll just store the canvas data and generate images on-demand
        
        // You could implement image generation here using libraries like:
        // - Puppeteer to render the canvas
        // - Canvas API to draw the design
        // - Or use a third-party service
        
        // For now, we'll leave this as a placeholder
        // The actual image generation can be implemented later
    }

    /**
     * Public gallery of designs.
     */
    public function gallery()
    {
        $designs = Design::with(['clothesType', 'shirtSize', 'user'])
            ->public()
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('designs.gallery', compact('designs'));
    }

    /**
     * Get hex color value for a color name
     */
    private function getColorHexValue($colorName)
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
}
