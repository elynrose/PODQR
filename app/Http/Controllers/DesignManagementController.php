<?php

namespace App\Http\Controllers;

use App\Models\Design;
use App\Models\ClothesType;
use App\Models\ShirtSize;
use App\Models\QrCode;
use App\Services\CloudStorageService;
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
        $request->validate([
            'design_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'clothes_type_id' => 'required|exists:clothes_types,id',
            'shirt_size_id' => 'required|exists:shirt_sizes,id',
            'color_code' => 'nullable|string',
            'qr_code_id' => 'nullable|exists:qr_codes,id',
            'front_canvas_data' => 'nullable|string',
            'back_canvas_data' => 'nullable|string',
            'front_design_image' => 'nullable|string',
            'back_design_image' => 'nullable|string',
            'cover_image_data' => 'nullable|string',
        ]);

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

            // Save design-only images if provided
            if ($request->front_design_image) {
                $frontImagePath = $this->saveDesignImage($request->front_design_image, $design->id, 'front');
                $design->update(['front_image_path' => $frontImagePath]);
                \Log::info('Front design image saved: ' . $frontImagePath);
            }
            
            if ($request->back_design_image) {
                $backImagePath = $this->saveDesignImage($request->back_design_image, $design->id, 'back');
                $design->update(['back_image_path' => $backImagePath]);
                \Log::info('Back design image saved: ' . $backImagePath);
            }

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
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
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
            $cloudStorage = new CloudStorageService();
            $filename = 'design-covers/' . $designId . '_' . time() . '_' . uniqid() . '.png';
            
            $path = $cloudStorage->storeBase64Image($base64Data, 'design-covers', $filename);
            
            \Log::info('Cover image saved to cloud storage: ' . $path);
            return $path;
            
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
        // Check if user owns the design or if it's public
        if ($design->user_id !== Auth::id() && !$design->is_public) {
            abort(403, 'You do not have permission to view this design.');
        }

        $design->load(['clothesType', 'shirtSize', 'qrCode', 'user']);

        return view('designs.show', compact('design'));
    }

    /**
     * Get design preview data for order form.
     */
    public function preview(Design $design)
    {
        // Check if user owns the design
        if ($design->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this design.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'design' => [
                'id' => $design->id,
                'name' => $design->name,
                'description' => $design->description,
                'front_image_path' => $design->front_image_path,
                'front_image_url' => $design->front_image_path ? asset('storage/' . $design->front_image_path) : null,
                'back_image_path' => $design->back_image_path,
                'back_image_url' => $design->back_image_path ? asset('storage/' . $design->back_image_path) : null,
            ]
        ]);
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
            abort(403, 'You do not have permission to edit this design.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'clothes_type_id' => 'required|exists:clothes_types,id',
            'shirt_size_id' => 'required|exists:shirt_sizes,id',
            'color_code' => 'nullable|string',
            'qr_code_id' => 'nullable|exists:qr_codes,id',
            'qr_code_position' => 'nullable|array',
            'photos' => 'nullable|array',
            'texts' => 'nullable|array',
            'front_canvas_data' => 'nullable|string',
            'back_canvas_data' => 'nullable|string',
            'front_design_image' => 'nullable|string',
            'back_design_image' => 'nullable|string',
            'status' => 'required|string|in:draft,saved,published',
            'is_public' => 'boolean',
        ]);

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

        // Save design-only images if provided
        if ($request->front_design_image) {
            $frontImagePath = $this->saveDesignImage($request->front_design_image, $design->id, 'front');
            $design->update(['front_image_path' => $frontImagePath]);
            \Log::info('Front design image updated: ' . $frontImagePath);
        }
        
        if ($request->back_design_image) {
            $backImagePath = $this->saveDesignImage($request->back_design_image, $design->id, 'back');
            $design->update(['back_image_path' => $backImagePath]);
            \Log::info('Back design image updated: ' . $backImagePath);
        }

        // Regenerate preview images if canvas data changed (fallback for existing functionality)
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

        try {
            $cloudStorage = new CloudStorageService();
            
            // Delete preview images from cloud storage
            if ($design->front_image_path) {
                $cloudStorage->deleteFile($design->front_image_path);
            }
            if ($design->back_image_path) {
                $cloudStorage->deleteFile($design->back_image_path);
            }
            if ($design->cover_image) {
                $cloudStorage->deleteFile($design->cover_image);
            }

            $design->delete();

            return response()->json([
                'success' => true,
                'message' => 'Design deleted successfully!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting design: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting design. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate preview images from canvas data.
     */
    private function generatePreviewImages(Design $design)
    {
        try {
            $cloudStorage = new CloudStorageService();
            \Log::info('Generating preview images for design: ' . $design->id);
            
            // Create design directory if it doesn't exist
            $designDir = 'designs/' . $design->id . '/photos';
            $cloudStorage->makeDirectory($designDir);
            
            $timestamp = time();
            $updated = false;
            
            // Generate front image if canvas data exists
            if ($design->front_canvas_data) {
                \Log::info('Generating front image from canvas data');
                $frontImagePath = $this->generateDesignImageFromCanvas($design->front_canvas_data, $design->id, 'front', $timestamp);
                if ($frontImagePath) {
                    $design->front_image_path = $frontImagePath;
                    $updated = true;
                    \Log::info('Front image generated: ' . $frontImagePath);
                }
            }
            
            // Generate back image if canvas data exists
            if ($design->back_canvas_data) {
                \Log::info('Generating back image from canvas data');
                $backImagePath = $this->generateDesignImageFromCanvas($design->back_canvas_data, $design->id, 'back', $timestamp);
                if ($backImagePath) {
                    $design->back_image_path = $backImagePath;
                    $updated = true;
                    \Log::info('Back image generated: ' . $backImagePath);
                }
            }
            
            // Save updates if any images were generated
            if ($updated) {
                $design->save();
                \Log::info('Design preview images updated successfully');
            } else {
                \Log::info('No preview images generated (no canvas data available)');
            }
            
        } catch (\Exception $e) {
            \Log::error('Error generating preview images: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    /**
     * Generate a design-only image from canvas data.
     */
    private function generateDesignImageFromCanvas($canvasData, $designId, $side, $timestamp)
    {
        try {
            // Decode canvas data
            $canvas = json_decode($canvasData, true);
            if (!$canvas || !isset($canvas['objects'])) {
                \Log::warning('Invalid canvas data for design ' . $designId . ' ' . $side);
                return null;
            }
            
            // Extract design elements (exclude background/shirt)
            $designObjects = [];
            foreach ($canvas['objects'] as $object) {
                // Skip background objects (shirt images, backgrounds)
                if (isset($object['type']) && $object['type'] === 'image') {
                    // Check if this is a shirt background image
                    if (isset($object['src']) && (
                        strpos($object['src'], 'shirt') !== false ||
                        strpos($object['src'], 'background') !== false ||
                        strpos($object['src'], 'template') !== false
                    )) {
                        continue; // Skip shirt background
                    }
                }
                
                // Include all other objects (text, graphics, QR codes, etc.)
                $designObjects[] = $object;
            }
            
            if (empty($designObjects)) {
                \Log::info('No design objects found for design ' . $designId . ' ' . $side);
                return null;
            }
            
            // Create a new canvas with only design elements
            $designCanvas = [
                'version' => $canvas['version'] ?? '5.3.0',
                'objects' => $designObjects,
                'background' => 'transparent', // Transparent background for design-only
                'width' => $canvas['width'] ?? 260,
                'height' => $canvas['height'] ?? 350
            ];
            
            // For now, we'll create a placeholder image since we can't render canvas server-side
            // In production, you'd use a service like Puppeteer or a canvas rendering service
            $filename = $side . '_' . $designId . '_' . $timestamp . '.png';
            $filepath = 'designs/' . $designId . '/photos/' . $filename;
            
            // Create a simple placeholder image for testing
            // In production, this would be replaced with actual canvas rendering
            $this->createPlaceholderImage($filepath, $designCanvas);
            
            return $filepath;
            
        } catch (\Exception $e) {
            \Log::error('Error generating design image: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a placeholder image for testing purposes.
     * In production, this would be replaced with actual canvas rendering.
     */
    private function createPlaceholderImage($filepath, $canvasData)
    {
        try {
            $cloudStorage = new CloudStorageService();
            
            // Create a simple placeholder image
            $width = $canvasData['width'] ?? 260;
            $height = $canvasData['height'] ?? 350;
            
            // Create a transparent PNG image
            $image = imagecreatetruecolor($width, $height);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
            
            // Add a border to show the design area
            $borderColor = imagecolorallocate($image, 200, 200, 200);
            imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);
            
            // Add text indicating this is a design placeholder
            $textColor = imagecolorallocate($image, 100, 100, 100);
            $fontSize = 3;
            $text = 'Design Only (' . count($canvasData['objects']) . ' elements)';
            $textWidth = imagefontwidth($fontSize) * strlen($text);
            $textX = ($width - $textWidth) / 2;
            $textY = $height / 2;
            imagestring($image, $fontSize, $textX, $textY, $text, $textColor);
            
            // Save the image
            ob_start();
            imagepng($image);
            $imageData = ob_get_clean();
            imagedestroy($image);
            
            // Convert to base64 and save to cloud storage
            $base64Data = base64_encode($imageData);
            $directory = dirname($filepath);
            $filename = basename($filepath);
            
            $cloudStorage->storeBase64Image($base64Data, $directory, $filename);
            
            \Log::info('Placeholder image created in cloud storage: ' . $filepath);
            
        } catch (\Exception $e) {
            \Log::error('Error creating placeholder image: ' . $e->getMessage());
        }
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

    /**
     * Save a design-only image from base64 data.
     */
    private function saveDesignImage($base64Data, $designId, $side)
    {
        try {
            $cloudStorage = new CloudStorageService();
            
            // Check if this is canvas data format (when toDataURL fails)
            if (strpos($base64Data, 'canvas_data:') === 0) {
                // Extract canvas JSON data
                $canvasJson = substr($base64Data, 12); // Remove 'canvas_data:' prefix
                $canvasData = json_decode($canvasJson, true);
                
                if ($canvasData) {
                    // Generate a placeholder image for canvas data
                    $timestamp = time();
                    $filename = $side . '_' . $designId . '_' . $timestamp . '.png';
                    $filepath = 'designs/' . $designId . '/photos/' . $filename;
                    
                    // Create placeholder image from canvas data
                    $this->createPlaceholderImage($filepath, $canvasData);
                    
                    \Log::info('Canvas data image saved to cloud storage: ' . $filepath);
                    return $filepath;
                } else {
                    \Log::warning('Invalid canvas data format for design ' . $designId . ' ' . $side);
                    return null;
                }
            }
            
            // Create design directory if it doesn't exist
            $designDir = 'designs/' . $designId . '/photos';
            $cloudStorage->makeDirectory($designDir);
            
            // Generate filename
            $timestamp = time();
            $filename = $side . '_' . $designId . '_' . $timestamp . '.png';
            $filepath = $designDir . '/' . $filename;
            
            // Save the image to cloud storage
            $path = $cloudStorage->storeBase64Image($base64Data, $designDir, $filename);
            
            \Log::info('Design image saved to cloud storage: ' . $path);
            
            return $path;
            
        } catch (\Exception $e) {
            \Log::error('Error saving design image: ' . $e->getMessage());
            return null;
        }
    }
}
