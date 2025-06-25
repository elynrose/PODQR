<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Clothes Type Details') }}
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
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="mb-4">{{ $clothesType->name }}</h3>
                            
                            <div class="mb-3">
                                <strong>Category:</strong> {{ $clothesType->category->name }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p>{{ $clothesType->description ?: 'No description available' }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge {{ $clothesType->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $clothesType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Sort Order:</strong> {{ $clothesType->sort_order }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Colors:</strong>
                                @if($clothesType->colors)
                                    <div class="mt-2">
                                        @foreach($clothesType->colors as $color)
                                            <span class="badge bg-secondary me-1">{{ $color }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">No colors specified</span>
                                @endif
                            </div>
                            
                            <div class="mb-3">
                                <strong>Created:</strong> {{ $clothesType->created_at->format('M d, Y H:i') }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Last Updated:</strong> {{ $clothesType->updated_at->format('M d, Y H:i') }}
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h4 class="mb-3">Images</h4>
                            
                            <div class="mb-4">
                                <strong>Front Image:</strong>
                                @if($clothesType->front_image)
                                    <div class="mt-2">
                                        <img src="{{ $clothesType->front_image_url }}" 
                                             alt="{{ $clothesType->name }} - Front" 
                                             class="img-fluid rounded" style="max-width: 300px;">
                                    </div>
                                @else
                                    <span class="text-muted">No front image available</span>
                                @endif
                            </div>
                            
                            <div class="mb-4">
                                <strong>Back Image:</strong>
                                @if($clothesType->back_image)
                                    <div class="mt-2">
                                        <img src="{{ $clothesType->back_image_url }}" 
                                             alt="{{ $clothesType->name }} - Back" 
                                             class="img-fluid rounded" style="max-width: 300px;">
                                    </div>
                                @else
                                    <span class="text-muted">No back image available</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('admin.clothes-types.edit', $clothesType) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="{{ route('admin.clothes-types.index') }}" class="btn btn-secondary">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 