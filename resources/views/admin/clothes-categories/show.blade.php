<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Clothes Category Details') }}
            </h2>
            <a href="{{ route('admin.clothes-categories.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Categories
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="mb-4">{{ $clothesCategory->name }}</h3>
                            
                            <div class="mb-3">
                                <strong>ID:</strong> {{ $clothesCategory->id }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>External ID:</strong> 
                                {{ $clothesCategory->external_id ?: 'Not set' }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p>{{ $clothesCategory->description ?: 'No description available' }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge {{ $clothesCategory->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $clothesCategory->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Clothes Types Count:</strong> {{ $clothesCategory->clothesTypes()->count() }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Created:</strong> {{ $clothesCategory->created_at->format('M d, Y H:i') }}
                            </div>
                            
                            <div class="mb-3">
                                <strong>Last Updated:</strong> {{ $clothesCategory->updated_at->format('M d, Y H:i') }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('admin.clothes-categories.edit', $clothesCategory) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="{{ route('admin.clothes-categories.index') }}" class="btn btn-secondary">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 