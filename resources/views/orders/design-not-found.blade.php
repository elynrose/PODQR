@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Design Not Found</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    
                    <h5 class="text-muted mb-3">Design #{{ $designId }} could not be found</h5>
                    
                    <p class="text-muted mb-4">
                        The design you're looking for doesn't exist or may have been removed.
                    </p>

                    @if($availableDesigns->count() > 0)
                        <div class="mb-4">
                            <h6>Available Designs:</h6>
                            <div class="row">
                                @foreach($availableDesigns as $design)
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">{{ $design->name }}</h6>
                                                <a href="{{ route('orders.create', $design->id) }}" class="btn btn-primary btn-sm">
                                                    Order This Design
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('designs.gallery') }}" class="btn btn-outline-primary">
                            <i class="fas fa-images"></i> Browse Designs
                        </a>
                        <a href="{{ route('design') }}" class="btn btn-primary">
                            <i class="fas fa-paint-brush"></i> Create New Design
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 