<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Shirt Size Details') }}
            </h2>
            <a href="{{ route('admin.shirt-sizes.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Sizes
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="mb-4">{{ $shirtSize->name }}</h3>
                            
                            <div class="mb-3">
                                <strong>ID:</strong> {{ $shirtSize->id }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p>{{ $shirtSize->description ?: 'No description available' }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Sort Order:</strong> {{ $shirtSize->sort_order }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge {{ $shirtSize->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $shirtSize->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Created:</strong> {{ $shirtSize->created_at->format('M d, Y H:i') }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Last Updated:</strong> {{ $shirtSize->updated_at->format('M d, Y H:i') }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('admin.shirt-sizes.edit', $shirtSize) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="{{ route('admin.shirt-sizes.index') }}" class="btn btn-secondary">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 