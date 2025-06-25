<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Clothes Type') }}
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
                    <form action="{{ route('admin.clothes-types.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
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
                                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="colors" class="form-label">Colors (comma-separated)</label>
                                    <input type="text" class="form-control @error('colors') is-invalid @enderror" 
                                           id="colors" name="colors" value="{{ old('colors') }}" 
                                           placeholder="White, Black, Navy, Gray">
                                    <small class="form-text text-muted">Enter colors separated by commas</small>
                                    @error('colors')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="front_image" class="form-label">Front Image *</label>
                                    <input type="file" class="form-control @error('front_image') is-invalid @enderror" 
                                           id="front_image" name="front_image" accept="image/*" required>
                                    <small class="form-text text-muted">Upload front view image (max 2MB)</small>
                                    @error('front_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="back_image" class="form-label">Back Image</label>
                                    <input type="file" class="form-control @error('back_image') is-invalid @enderror" 
                                           id="back_image" name="back_image" accept="image/*">
                                    <small class="form-text text-muted">Upload back view image (max 2MB)</small>
                                    @error('back_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Image Preview</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="front-preview" class="mb-2" style="display: none;">
                                                <strong>Front Image:</strong>
                                                <img id="front-preview-img" class="img-fluid mt-1" style="max-height: 200px;">
                                            </div>
                                            <div id="back-preview" class="mb-2" style="display: none;">
                                                <strong>Back Image:</strong>
                                                <img id="back-preview-img" class="img-fluid mt-1" style="max-height: 200px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.clothes-types.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Create Clothes Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Image preview functionality
        document.getElementById('front_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('front-preview-img').src = e.target.result;
                    document.getElementById('front-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('back_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('back-preview-img').src = e.target.result;
                    document.getElementById('back-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
    @endpush
</x-app-layout> 