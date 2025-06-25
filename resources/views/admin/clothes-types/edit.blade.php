<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Clothes Type') }}
            </h2>
            <a href="{{ route('admin.clothes-types.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Clothes Types
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.clothes-types.update', $clothesType) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $clothesType->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" 
                                            id="category_id" name="category_id" required>
                                        <option value="">Select a category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ old('category_id', $clothesType->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $clothesType->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="colors" class="form-label">Colors (comma-separated)</label>
                                    <input type="text" class="form-control @error('colors') is-invalid @enderror" 
                                           id="colors" name="colors" 
                                           value="{{ old('colors', $clothesType->colors ? implode(', ', $clothesType->colors) : '') }}"
                                           placeholder="White, Black, Navy, Gray">
                                    @error('colors')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $clothesType->sort_order) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                               {{ old('is_active', $clothesType->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="front_image" class="form-label">Front Image</label>
                                    <input type="file" class="form-control @error('front_image') is-invalid @enderror" 
                                           id="front_image" name="front_image" accept="image/*">
                                    @error('front_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($clothesType->front_image)
                                        <div class="mt-2">
                                            <strong>Current Front Image:</strong>
                                            <img src="{{ $clothesType->front_image_url }}" 
                                                 alt="{{ $clothesType->name }} - Front" 
                                                 class="img-thumbnail d-block mt-2" style="max-width: 200px;">
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="back_image" class="form-label">Back Image</label>
                                    <input type="file" class="form-control @error('back_image') is-invalid @enderror" 
                                           id="back_image" name="back_image" accept="image/*">
                                    @error('back_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($clothesType->back_image)
                                        <div class="mt-2">
                                            <strong>Current Back Image:</strong>
                                            <img src="{{ $clothesType->back_image_url }}" 
                                                 alt="{{ $clothesType->name }} - Back" 
                                                 class="img-thumbnail d-block mt-2" style="max-width: 200px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.clothes-types.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Clothes Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 