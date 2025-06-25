<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0 text-gray-800">
                {{ $design->name }}
            </h2>
            <div class="d-flex gap-2">
                <a href="{{ route('designs.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="{{ route('designs.edit', $design->id) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Design Preview -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="h6 mb-0 text-gray-700">Design Preview</h5>
                    </div>
                    <div class="card-body p-4">
                        @if($design->cover_image_url)
                            <div class="text-center">
                                <h6 class="text-muted mb-2">Cover Image</h6>
                                <img src="{{ $design->cover_image_url }}" 
                                     alt="{{ $design->name }}" 
                                     class="img-fluid border rounded mx-auto d-block" >
                            </div>
                        @elseif($design->front_image_path || $design->back_image_path)
                            <div class="row g-3">
                                @if($design->front_image_path)
                                    <div class="col-md-6">
                                        <div class="text-center">
                                            <h6 class="text-muted mb-2">Front</h6>
                                            <img src="{{ $design->front_image_url }}" 
                                                 alt="Front Design" 
                                                 class="img-fluid border rounded" 
                                                 style="max-height: 400px;">
                                        </div>
                                    </div>
                                @endif
                                @if($design->back_image_path)
                                    <div class="col-md-6">
                                        <div class="text-center">
                                            <h6 class="text-muted mb-2">Back</h6>
                                            <img src="{{ $design->back_image_url }}" 
                                                 alt="Back Design" 
                                                 class="img-fluid border rounded" 
                                                 style="max-height: 400px;">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">No preview available</p>
                                <small class="text-muted">Canvas data is stored and can be loaded in the designer</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Design Info -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="h6 mb-0 text-gray-700">Design Information</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-12">
                                <small class="text-muted d-block">Name</small>
                                <span class="fw-medium">{{ $design->name }}</span>
                            </div>
                            
                            @if($design->description)
                                <div class="col-12">
                                    <small class="text-muted d-block">Description</small>
                                    <span class="fw-medium">{{ $design->description }}</span>
                                </div>
                            @endif
                            
                            <div class="col-6">
                                <small class="text-muted d-block">Type</small>
                                <span class="fw-medium">{{ $design->clothesType->name }}</span>
                            </div>
                            
                            <div class="col-6">
                                <small class="text-muted d-block">Size</small>
                                <span class="fw-medium">{{ $design->shirtSize->name }}</span>
                            </div>
                            
                            <div class="col-6">
                                <small class="text-muted d-block">Color</small>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded" style="width: 16px; height: 16px; background-color: {{ $design->color_code }};"></div>
                                    <span class="fw-medium">{{ $design->color_code }}</span>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <small class="text-muted d-block">Status</small>
                                <span class="badge bg-{{ $design->status === 'published' ? 'success' : ($design->status === 'saved' ? 'primary' : 'secondary') }} fs-6">
                                    {{ ucfirst($design->status) }}
                                </span>
                            </div>
                            
                            <div class="col-12">
                                <small class="text-muted d-block">Created</small>
                                <span class="fw-medium">{{ $design->created_at->format('M d, Y g:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Design Elements Summary -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="h6 mb-0 text-gray-700">Elements</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex flex-column gap-2">
                            @if($design->hasQrCode())
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-qr-code text-primary"></i>
                                    <span class="small">QR Code</span>
                                    @if($design->qrCode)
                                        <small class="text-muted ms-auto">{{ $design->qrCode->name }}</small>
                                    @endif
                                </div>
                            @endif
                            
                            @if($design->hasPhotos())
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-image text-success"></i>
                                    <span class="small">{{ $design->photo_count }} Photos</span>
                                </div>
                            @endif
                            
                            @if($design->hasTexts())
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-type text-info"></i>
                                    <span class="small">{{ $design->text_count }} Text Elements</span>
                                </div>
                            @endif
                            
                            @if(!$design->hasQrCode() && !$design->hasPhotos() && !$design->hasTexts())
                                <span class="text-muted small">No elements added</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="h6 mb-0 text-gray-700">Actions</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-grid gap-2">
                            <a href="{{ route('designs.edit', $design->id) }}" 
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-palette"></i> Continue Editing
                            </a>
                            <button type="button" 
                                    class="btn btn-outline-danger btn-sm delete-design" 
                                    data-design-id="{{ $design->id }}"
                                    data-design-name="{{ $design->name }}">
                                <i class="bi bi-trash"></i> Delete Design
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                                window.location.href = '{{ route("designs.index") }}';
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