<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Fallback for Vite assets in production -->
        @if(app()->environment('production'))
            <script>
                // Check if Vite assets loaded, if not, load fallback
                if (typeof window.Alpine === 'undefined') {
                    console.log('Vite assets not loaded, using fallback');
                }
            </script>
        @endif

        <!-- Custom styles -->
        <style>
            .navbar-height {
                height: 60px;
            }
            .content-wrapper {
                min-height: calc(100vh - 60px);
            }
            .dropdown-menu {
                border-radius: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            
            /* Fallback styles in case Vite assets fail to load */
            .navbar {
                display: flex !important;
                position: relative;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }
            
            .navbar-nav {
                display: flex;
                flex-direction: row;
                padding-left: 0;
                margin-bottom: 0;
                list-style: none;
            }
            
            .nav-item {
                margin-right: 0.5rem;
            }
            
            .nav-link {
                display: block;
                padding: 0.5rem 1rem;
                color: #6c757d;
                text-decoration: none;
                transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            }
            
            .nav-link:hover {
                color: #495057;
            }
            
            .navbar-brand {
                padding-top: 0.3125rem;
                padding-bottom: 0.3125rem;
                margin-right: 1rem;
                font-size: 1.25rem;
                white-space: nowrap;
                color: #212529;
                text-decoration: none;
            }
            
            .navbar-toggler {
                padding: 0.25rem 0.75rem;
                font-size: 1rem;
                line-height: 1;
                color: #6c757d;
                background-color: transparent;
                border: 1px solid #dee2e6;
                border-radius: 0.375rem;
                transition: box-shadow 0.15s ease-in-out;
            }
            
            .navbar-collapse {
                flex-basis: 100%;
                flex-grow: 1;
                align-items: center;
            }
            
            @media (min-width: 768px) {
                .navbar-expand-md .navbar-nav {
                    flex-direction: row;
                }
                
                .navbar-expand-md .navbar-collapse {
                    display: flex !important;
                    flex-basis: auto;
                }
                
                .navbar-expand-md .navbar-toggler {
                    display: none;
                }
            }
        </style>
        
        @stack('styles')
    </head>
    <body class="bg-light">
        <div class="min-vh-100 d-flex flex-column">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow-sm">
                    <div class="container py-3">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="content-wrapper py-4">
                @yield('content')
                @isset($slot)
                    {{ $slot }}
                @endisset
            </main>
        </div>

        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Global Alert System -->
        <div id="globalAlertContainer" class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999; margin-top: 80px;">
            <!-- Alerts will be dynamically inserted here -->
        </div>
        
        <script>
            // Global Alert System
            window.showAlert = function(message, type = 'success', duration = 5000) {
                const alertContainer = document.getElementById('globalAlertContainer');
                
                // Create alert element
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
                alertDiv.style.minWidth = '300px';
                alertDiv.style.maxWidth = '500px';
                alertDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            ${getAlertIcon(type)}
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <span class="fw-medium">${message}</span>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                // Add to container
                alertContainer.appendChild(alertDiv);
                
                // Auto-dismiss after duration
                if (duration > 0) {
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            const bsAlert = new bootstrap.Alert(alertDiv);
                            bsAlert.close();
                        }
                    }, duration);
                }
                
                // Remove from DOM after animation
                alertDiv.addEventListener('closed.bs.alert', function() {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                });
                
                return alertDiv;
            };
            
            function getAlertIcon(type) {
                const icons = {
                    'success': '<i class="bi bi-check-circle-fill text-success"></i>',
                    'error': '<i class="bi bi-exclamation-triangle-fill text-danger"></i>',
                    'warning': '<i class="bi bi-exclamation-triangle-fill text-warning"></i>',
                    'info': '<i class="bi bi-info-circle-fill text-info"></i>',
                    'danger': '<i class="bi bi-x-circle-fill text-danger"></i>'
                };
                return icons[type] || icons['info'];
            }
            
            // Convert Laravel session alerts to global alerts
            document.addEventListener('DOMContentLoaded', function() {
                // Check for session alerts
                const sessionAlerts = document.querySelectorAll('.alert');
                sessionAlerts.forEach(alert => {
                    const message = alert.textContent.trim();
                    const type = getAlertTypeFromClass(alert.className);
                    
                    // Show global alert
                    showAlert(message, type);
                    
                    // Remove original alert
                    alert.remove();
                });
            });
            
            function getAlertTypeFromClass(className) {
                if (className.includes('alert-success')) return 'success';
                if (className.includes('alert-danger')) return 'danger';
                if (className.includes('alert-warning')) return 'warning';
                if (className.includes('alert-info')) return 'info';
                return 'info';
            }
        </script>
        
        @stack('scripts')
    </body>
</html>
