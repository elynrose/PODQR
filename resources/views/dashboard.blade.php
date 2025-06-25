<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fs-4 fw-semibold mb-0">
                <i class="bi bi-graph-up me-2"></i>{{ __('Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="container-fluid py-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Posts
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($userStats['total_posts']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-calendar3 fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Views
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($userStats['total_views']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-eye fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Unique Views
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($userStats['unique_views']) }}</div>
                                <div class="text-xs text-muted">By IP Address</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Posts This Month
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($userStats['posts_this_month']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-calendar-day fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Views This Month
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($userStats['views_this_month']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Post Views Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-graph-up me-2"></i>My Post Views (Last 30 Days)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="postViewsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Posts -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-fire me-2"></i>My Most Popular Posts (Unique Views)
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($popularPosts->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($popularPosts as $index => $post)
                                    <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold">
                                                <span class="badge bg-primary me-2">#{{ $index + 1 }}</span>
                                                {{ $post['content'] }}
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i>{{ $post['user_name'] }}
                                                @if($post['has_attachment'])
                                                    <i class="bi bi-paperclip ms-2 me-1"></i>{{ ucfirst($post['attachment_type']) }}
                                                @endif
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3 me-1"></i>{{ $post['created_at'] }}
                                            </small>
                                        </div>
                                        <span class="badge bg-success rounded-pill">
                                            <i class="bi bi-eye me-1"></i>{{ number_format($post['unique_views']) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fa-3x mb-3"></i>
                                <p>No posts found</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('wall.index') }}" class="btn btn-primary btn-block w-100">
                                    <i class="bi bi-plus me-2"></i>Create Post
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('qr-generator') }}" class="btn btn-success btn-block w-100">
                                    <i class="bi bi-qr-code me-2"></i>Generate QR Code
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('design') }}" class="btn btn-warning btn-block w-100">
                                    <i class="bi bi-tshirt me-2"></i>T-Shirt Designer
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('designs.index') }}" class="btn btn-info btn-block w-100">
                                    <i class="bi bi-images me-2"></i>My Designs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        .text-gray-300 {
            color: #dddfeb !important;
        }
        .chart-area {
            position: relative;
            height: 20rem;
            width: 100%;
        }
        .list-group-item:hover {
            background-color: #f8f9fc;
        }
        .fa-2x {
            font-size: 2em;
        }
        .fa-3x {
            font-size: 3em;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Post Views Chart
        const ctx = document.getElementById('postViewsChart').getContext('2d');
        const postViewsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json(collect($viewsData)->pluck('date')),
                datasets: [{
                    label: 'Post Views',
                    data: @json(collect($viewsData)->pluck('views')),
                    borderColor: 'rgb(78, 115, 223)',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(78, 115, 223)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        hoverBackgroundColor: 'rgb(78, 115, 223)'
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
