<!-- NAVIGATION FILE LOADED SUCCESSFULLY -->
<div style="background: purple; color: white; padding: 5px; text-align: center; font-weight: bold;">NAVIGATION FILE IS LOADING</div>

<!-- NAVIGATION DEBUG: Navigation file is loading -->
<div style="background: red; color: white; padding: 5px; text-align: center;">NAVIGATION LOADED</div>

<!-- ROUTE DEBUG -->
<div style="background: blue; color: white; padding: 5px; text-align: center;">
    Dashboard: {{ route('dashboard') }} | 
    Wall: {{ route('wall.index') }} | 
    Design: {{ route('design') }} | 
    QR: {{ route('qr-generator') }} | 
    Designs: {{ route('designs.index') }} | 
    Orders: {{ route('orders.history') }}
</div>

<!-- SIMPLIFIED NAVIGATION FOR TESTING -->
<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm" style="display: block !important; visibility: visible !important; background: white !important; border-bottom: 1px solid #dee2e6 !important; min-height: 60px !important;">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('dashboard') }}" style="display: block !important; padding: 0.3125rem 0 !important; margin-right: 1rem !important; font-size: 1.25rem !important; color: #212529 !important; text-decoration: none !important;">
            PODQR LOGO
        </a>
        
        <!-- Simple Navigation Links -->
        <div class="navbar-nav" style="display: flex !important; flex-direction: row !important;">
            <a class="nav-link" href="{{ route('dashboard') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important; background: yellow !important; border: 2px solid red !important; cursor: pointer !important;">
                Dashboard
            </a>
            <a class="nav-link" href="{{ route('wall.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                Wall
            </a>
            <a class="nav-link" href="{{ route('design') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                Design
            </a>
            <a class="nav-link" href="{{ route('qr-generator') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                QR Generator
            </a>
            <a class="nav-link" href="{{ route('designs.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                Designs
            </a>
            <a class="nav-link" href="{{ route('orders.history') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                Orders
            </a>
        </div>
        
        <!-- User Menu -->
        @auth
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="{{ route('profile.edit') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                {{ Auth::user()->name }}
            </a>
        </div>
        @else
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="{{ route('login') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                Login
            </a>
        </div>
        @endauth
    </div>
</nav>

<!-- NAVIGATION END DEBUG -->
<div style="background: green; color: white; padding: 5px; text-align: center;">NAVIGATION END</div>
