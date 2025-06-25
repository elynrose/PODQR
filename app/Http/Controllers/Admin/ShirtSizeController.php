<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShirtSize;
use Illuminate\Http\Request;

class ShirtSizeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shirtSizes = ShirtSize::ordered()->paginate(10);
        return view('admin.shirt-sizes.index', compact('shirtSizes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.shirt-sizes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:shirt_sizes',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        ShirtSize::create($request->all());

        return redirect()->route('admin.shirt-sizes.index')
            ->with('success', 'Shirt size created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ShirtSize $shirtSize)
    {
        return view('admin.shirt-sizes.show', compact('shirtSize'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShirtSize $shirtSize)
    {
        return view('admin.shirt-sizes.edit', compact('shirtSize'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShirtSize $shirtSize)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:shirt_sizes,name,' . $shirtSize->id,
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $shirtSize->update($request->all());

        return redirect()->route('admin.shirt-sizes.index')
            ->with('success', 'Shirt size updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShirtSize $shirtSize)
    {
        $shirtSize->delete();

        return redirect()->route('admin.shirt-sizes.index')
            ->with('success', 'Shirt size deleted successfully.');
    }
}
