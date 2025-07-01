<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClothesCategory;
use App\Models\ClothesType;
use App\Services\CloudStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClothesTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clothesTypes = ClothesType::with('category')->orderBy('sort_order')->paginate(10);
        return view('admin.clothes-types.index', compact('clothesTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ClothesCategory::where('is_active', true)->orderBy('name')->get();
        return view('admin.clothes-types.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:clothes_categories,id',
            'front_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'back_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'colors' => 'nullable',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $data = $request->except(['front_image', 'back_image']);

        // Handle colors - convert comma-separated string to array
        if ($request->filled('colors')) {
            $colorsInput = $request->colors;
            
            // If it's already an array, use it directly
            if (is_array($colorsInput)) {
                $colors = array_map('trim', $colorsInput);
            } else {
                // If it's a string, split by comma
                $colors = array_map('trim', explode(',', $colorsInput));
            }
            
            $colors = array_filter($colors); // Remove empty values
            $data['colors'] = !empty($colors) ? $colors : null;
        } else {
            $data['colors'] = null;
        }

        // Handle front image upload
        if ($request->hasFile('front_image')) {
            $cloudStorage = new CloudStorageService();
            $frontImagePath = $cloudStorage->storeFile($request->file('front_image'), 'clothes-types');
            $data['front_image'] = $frontImagePath;
        }

        // Handle back image upload
        if ($request->hasFile('back_image')) {
            $cloudStorage = new CloudStorageService();
            $backImagePath = $cloudStorage->storeFile($request->file('back_image'), 'clothes-types');
            $data['back_image'] = $backImagePath;
        }

        ClothesType::create($data);

        return redirect()->route('admin.clothes-types.index')
            ->with('success', 'Clothes type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ClothesType $clothesType)
    {
        return view('admin.clothes-types.show', compact('clothesType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClothesType $clothesType)
    {
        $categories = ClothesCategory::where('is_active', true)->orderBy('name')->get();
        return view('admin.clothes-types.edit', compact('clothesType', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClothesType $clothesType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:clothes_categories,id',
            'front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'back_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'colors' => 'nullable',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $data = $request->except(['front_image', 'back_image']);

        // Handle colors - convert comma-separated string to array
        if ($request->filled('colors')) {
            $colorsInput = $request->colors;
            
            // If it's already an array, use it directly
            if (is_array($colorsInput)) {
                $colors = array_map('trim', $colorsInput);
            } else {
                // If it's a string, split by comma
                $colors = array_map('trim', explode(',', $colorsInput));
            }
            
            $colors = array_filter($colors); // Remove empty values
            $data['colors'] = !empty($colors) ? $colors : null;
        } else {
            $data['colors'] = null;
        }

        // Handle front image upload
        if ($request->hasFile('front_image')) {
            // Delete old image if exists
            if ($clothesType->front_image) {
                $cloudStorage = new CloudStorageService();
                $cloudStorage->deleteFile($clothesType->front_image);
            }
            $cloudStorage = new CloudStorageService();
            $frontImagePath = $cloudStorage->storeFile($request->file('front_image'), 'clothes-types');
            $data['front_image'] = $frontImagePath;
        }

        // Handle back image upload
        if ($request->hasFile('back_image')) {
            // Delete old image if exists
            if ($clothesType->back_image) {
                $cloudStorage = new CloudStorageService();
                $cloudStorage->deleteFile($clothesType->back_image);
            }
            $cloudStorage = new CloudStorageService();
            $backImagePath = $cloudStorage->storeFile($request->file('back_image'), 'clothes-types');
            $data['back_image'] = $backImagePath;
        }

        $clothesType->update($data);

        return redirect()->route('admin.clothes-types.index')
            ->with('success', 'Clothes type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClothesType $clothesType)
    {
        // Delete images from storage
        if ($clothesType->front_image) {
            $cloudStorage = new CloudStorageService();
            $cloudStorage->deleteFile($clothesType->front_image);
        }
        if ($clothesType->back_image) {
            $cloudStorage = new CloudStorageService();
            $cloudStorage->deleteFile($clothesType->back_image);
        }

        $clothesType->delete();

        return redirect()->route('admin.clothes-types.index')
            ->with('success', 'Clothes type deleted successfully.');
    }
}
