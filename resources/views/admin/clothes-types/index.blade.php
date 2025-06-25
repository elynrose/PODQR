<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Clothes Types') }}
            </h2>
            <a href="{{ route('admin.clothes-types.create') }}" class="btn btn-primary">
                <i class="bi bi-plus"></i> Add Clothes Type
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="alert alert-success mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Colors</th>
                                    <th>Status</th>
                                    <th>Sort Order</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clothesTypes as $clothesType)
                                    <tr>
                                        <td>{{ $clothesType->id }}</td>
                                        <td>
                                            @if($clothesType->front_image)
                                                <img src="{{ $clothesType->front_image_url }}" 
                                                     alt="{{ $clothesType->name }}" 
                                                     class="img-thumbnail" style="max-width: 50px;">
                                            @else
                                                <span class="text-muted">No image</span>
                                            @endif
                                        </td>
                                        <td>{{ $clothesType->name }}</td>
                                        <td>{{ $clothesType->category->name }}</td>
                                        <td>
                                            @if($clothesType->colors)
                                                @foreach($clothesType->colors as $color)
                                                    <span class="badge bg-secondary me-1">{{ $color }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No colors</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $clothesType->is_active ? 'bg-success' : 'bg-danger' }}">
                                                {{ $clothesType->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $clothesType->sort_order }}</td>
                                        <td>{{ $clothesType->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.clothes-types.show', $clothesType) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.clothes-types.edit', $clothesType) }}" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.clothes-types.destroy', $clothesType) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this clothes type?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No clothes types found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $clothesTypes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 