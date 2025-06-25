<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClothesCategory;
use Illuminate\Http\Request;

class ClothesCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ClothesCategory::orderBy('name')->paginate(10);
        return view('admin.clothes-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.clothes-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:clothes_categories',
            'description' => 'nullable|string',
            'external_id' => 'nullable|string|max:255|unique:clothes_categories',
            'is_active' => 'boolean',
        ]);

        ClothesCategory::create($request->all());

        return redirect()->route('admin.clothes-categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ClothesCategory $clothesCategory)
    {
        return view('admin.clothes-categories.show', compact('clothesCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClothesCategory $clothesCategory)
    {
        return view('admin.clothes-categories.edit', compact('clothesCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClothesCategory $clothesCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:clothes_categories,name,' . $clothesCategory->id,
            'description' => 'nullable|string',
            'external_id' => 'nullable|string|max:255|unique:clothes_categories,external_id,' . $clothesCategory->id,
            'is_active' => 'boolean',
        ]);

        $clothesCategory->update($request->all());

        return redirect()->route('admin.clothes-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClothesCategory $clothesCategory)
    {
        // Check if category has associated clothes types
        if ($clothesCategory->clothesTypes()->count() > 0) {
            return redirect()->route('admin.clothes-categories.index')
                ->with('error', 'Cannot delete category with associated clothes types.');
        }

        $clothesCategory->delete();

        return redirect()->route('admin.clothes-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
