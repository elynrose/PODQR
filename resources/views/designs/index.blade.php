<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0 text-gray-800">
                {{ __('My Designs') }}
            </h2>
            <a href="{{ route('designs.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus"></i> Create New Design
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="container-fluid py-4">
        @if($designs->count() > 0)
            <div class="row g-4">
                @foreach($designs as $design)
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100 shadow-sm">
                            <!-- Image Section -->
                            <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                                @if($design->cover_image_url)
                                    <img src="{{ $design->cover_image_url }}" 
                                         alt="{{ $design->name }}" 
                                         class="w-100 h-100"
                                         style="object-fit: cover;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light" style="display: none;">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                @elseif($design->preview_image_url)
                                    <img src="{{ $design->preview_image_url }}" 
                                         alt="{{ $design->name }}" 
                                         class="w-100 h-100"
                                         style="object-fit: cover;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light" style="display: none;">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                @endif
                                
                                <!-- Status Badge Overlay -->
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-{{ $design->status === 'published' ? 'success' : ($design->status === 'saved' ? 'primary' : 'secondary') }} fs-6">
                                        {{ ucfirst($design->status) }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Card Body -->
                            <div class="card-body d-flex flex-column">
                                <!-- Title and Type -->
                                <div class="mb-2">
                                    <h6 class="card-title mb-1 text-truncate">{{ $design->name }}</h6>
                                    <small class="text-muted">{{ $design->clothesType->name }} - {{ $design->shirtSize->name }}</small>
                                </div>
                                
                                <!-- Elements Summary -->
                                <div class="mb-3">
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($design->hasQrCode())
                                            <span class="badge bg-primary bg-opacity-75 fs-6">
                                                <i class="bi bi-qr-code"></i> QR
                                            </span>
                                        @endif
                                        @if($design->hasPhotos())
                                            <span class="badge bg-success bg-opacity-75 fs-6">
                                                <i class="bi bi-image"></i> {{ $design->photo_count }}
                                            </span>
                                        @endif
                                        @if($design->hasTexts())
                                            <span class="badge bg-info bg-opacity-75 fs-6">
                                                <i class="bi bi-type"></i> {{ $design->text_count }}
                                            </span>
                                        @endif
                                        @if($design->is_public)
                                            <span class="badge bg-warning bg-opacity-75 fs-6">
                                                <i class="bi bi-globe"></i> Public
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Date -->
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i> {{ $design->created_at->format('M d, Y') }}
                                    </small>
                                </div>
                                
                                <!-- Actions - Push to bottom -->
                                <div class="mt-auto">
                                    <div class="d-grid gap-2">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('designs.show', $design->id) }}" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('designs.edit', $design->id) }}" 
                                               class="btn btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="{{ route('orders.create', $design->id) }}" 
                                               class="btn btn-outline-success">
                                                <i class="bi bi-cart"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger delete-design" 
                                                    data-design-id="{{ $design->id }}"
                                                    data-design-name="{{ $design->name }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $designs->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-palette text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">No designs yet</h4>
                <p class="text-muted">Start creating your first T-shirt design!</p>
                <a href="{{ route('design') }}" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Create Your First Design
                </a>
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDesignModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title mb-0">Delete Design</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Delete "<span id="deleteDesignName" class="fw-medium"></span>"?</p>
                    <small class="text-danger">This action cannot be undone.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteDesign">Delete</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        
        .card-img-top {
            border-bottom: 1px solid rgba(0,0,0,0.125);
        }
        
        .badge {
            font-size: 0.75rem !important;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Handle delete design
            $('.delete-design').on('click', function(e) {
                e.preventDefault();
                
                var designId = $(this).data('design-id');
                var designName = $(this).data('design-name');
                
                $('#deleteDesignName').text(designName);
                $('#confirmDeleteDesign').data('design-id', designId);
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteDesignModal'));
                deleteModal.show();
            });
            
            // Confirm delete
            $('#confirmDeleteDesign').on('click', function() {
                var designId = $(this).data('design-id');
                
                $.ajax({
                    url: '/designs/' + designId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('Design deleted successfully!', 'success', 3000);
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showAlert('Error deleting design: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        showAlert('Error deleting design. Please try again.', 'danger');
                    }
                });
            });
        });
    </script>
    @endpush
</x-app-layout> 