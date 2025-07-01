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
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
        </a>
        
        <!-- Navigation Links -->
        <div class="navbar-nav me-auto" style="display: flex !important; flex-direction: row !important;">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }}
            </a>
            <a class="nav-link {{ request()->routeIs('wall.*') ? 'active' : '' }}" href="{{ route('wall.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-grid"></i> {{ __('Wall') }}
            </a>
            <a class="nav-link {{ request()->routeIs('design') ? 'active' : '' }}" href="{{ route('design') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-tshirt"></i> {{ __('T-Shirt Designer') }}
            </a>
            <a class="nav-link {{ request()->routeIs('qr-generator') ? 'active' : '' }}" href="{{ route('qr-generator') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-qr-code"></i> {{ __('QR Generator') }}
            </a>
            <a class="nav-link {{ request()->routeIs('designs.index') ? 'active' : '' }}" href="{{ route('designs.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-palette"></i> Designs
            </a>
            <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.history') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-cart"></i> Orders
            </a>
            
            @if(Auth::check() && Auth::user()->is_admin)
            <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-people"></i> {{ __('Users') }}
            </a>
            <a class="nav-link {{ request()->routeIs('admin.clothes-categories*') ? 'active' : '' }}" href="{{ route('admin.clothes-categories.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-list"></i> {{ __('Categories') }}
            </a>
            <a class="nav-link {{ request()->routeIs('admin.clothes-types*') ? 'active' : '' }}" href="{{ route('admin.clothes-types.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-tshirt"></i> {{ __('Clothes Types') }}
            </a>
            <a class="nav-link {{ request()->routeIs('admin.shirt-sizes*') ? 'active' : '' }}" href="{{ route('admin.shirt-sizes.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-rulers"></i> {{ __('Shirt Sizes') }}
            </a>
            @endif
        </div>
        
        <!-- User Menu -->
        @auth
        <div class="navbar-nav ms-auto" style="display: flex !important; flex-direction: row !important;">
            <a class="nav-link" href="{{ route('profile.edit') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="nav-link" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important; background: none !important; border: none !important;">
                    <i class="bi bi-box-arrow-right"></i> {{ __('Log Out') }}
                </button>
            </form>
        </div>
        @else
        <div class="navbar-nav ms-auto" style="display: flex !important; flex-direction: row !important;">
            <a class="nav-link" href="{{ route('login') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-box-arrow-in-right"></i> {{ __('Login') }}
            </a>
            <a class="nav-link" href="{{ route('register') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                <i class="bi bi-person-plus"></i> {{ __('Register') }}
            </a>
        </div>
        @endauth
    </div>
</nav>

<!-- NAVIGATION END DEBUG -->
<div style="background: green; color: white; padding: 5px; text-align: center;">NAVIGATION END</div>
