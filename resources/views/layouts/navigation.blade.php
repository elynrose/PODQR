<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
        </a>
        
        <!-- Navigation Links -->
        <div class="navbar-nav me-auto">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }}
            </a>
            <a class="nav-link {{ request()->routeIs('wall.*') ? 'active' : '' }}" href="{{ route('wall.index') }}">
                <i class="bi bi-grid"></i> {{ __('Wall') }}
            </a>
            <a class="nav-link {{ request()->routeIs('design') ? 'active' : '' }}" href="{{ route('design') }}">
                <i class="bi bi-tshirt"></i> {{ __('T-Shirt Designer') }}
            </a>
            <a class="nav-link {{ request()->routeIs('qr-generator') ? 'active' : '' }}" href="{{ route('qr-generator') }}">
                <i class="bi bi-qr-code"></i> {{ __('QR Generator') }}
            </a>
            <a class="nav-link {{ request()->routeIs('designs.index') ? 'active' : '' }}" href="{{ route('designs.index') }}">
                <i class="bi bi-palette"></i> Designs
            </a>
            <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.history') }}">
                <i class="bi bi-cart"></i> Orders
            </a>
            
            @if(Auth::check() && Auth::user()->is_admin)
            <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                <i class="bi bi-people"></i> {{ __('Users') }}
            </a>
            <a class="nav-link {{ request()->routeIs('admin.clothes-categories*') ? 'active' : '' }}" href="{{ route('admin.clothes-categories.index') }}">
                <i class="bi bi-list"></i> {{ __('Categories') }}
            </a>
            <a class="nav-link {{ request()->routeIs('admin.clothes-types*') ? 'active' : '' }}" href="{{ route('admin.clothes-types.index') }}">
                <i class="bi bi-tshirt"></i> {{ __('Clothes Types') }}
            </a>
            <a class="nav-link {{ request()->routeIs('admin.shirt-sizes*') ? 'active' : '' }}" href="{{ route('admin.shirt-sizes.index') }}">
                <i class="bi bi-rulers"></i> {{ __('Shirt Sizes') }}
            </a>
            @endif
        </div>
        
        <!-- User Menu -->
        @auth
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="{{ route('profile.edit') }}">
                <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="nav-link" style="background: none; border: none;">
                    <i class="bi bi-box-arrow-right"></i> {{ __('Log Out') }}
                </button>
            </form>
        </div>
        @else
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="{{ route('login') }}">
                <i class="bi bi-box-arrow-in-right"></i> {{ __('Login') }}
            </a>
            <a class="nav-link" href="{{ route('register') }}">
                <i class="bi bi-person-plus"></i> {{ __('Register') }}
            </a>
        </div>
        @endauth
    </div>
</nav>
