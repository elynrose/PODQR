<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Shirt Sizes') }}
            </h2>
            <a href="{{ route('admin.shirt-sizes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus"></i> Add Size
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
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Sort Order</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shirtSizes as $shirtSize)
                                    <tr>
                                        <td>{{ $shirtSize->id }}</td>
                                        <td>{{ $shirtSize->name }}</td>
                                        <td>{{ Str::limit($shirtSize->description, 50) }}</td>
                                        <td>{{ $shirtSize->sort_order }}</td>
                                        <td>
                                            <span class="badge {{ $shirtSize->is_active ? 'bg-success' : 'bg-danger' }}">
                                                {{ $shirtSize->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $shirtSize->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.shirt-sizes.show', $shirtSize) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.shirt-sizes.edit', $shirtSize) }}" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.shirt-sizes.destroy', $shirtSize) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this shirt size?')">
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
                                        <td colspan="7" class="text-center">No shirt sizes found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $shirtSizes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 