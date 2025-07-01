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
<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
        </a>
        
        <!-- Hamburger Menu Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Primary Navigation Menu -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('wall.*') ? 'active' : '' }}" href="{{ route('wall.index') }}">
                        <i class="bi bi-grid"></i> {{ __('Wall') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('design') ? 'active' : '' }}" href="{{ route('design') }}">
                        <i class="bi bi-tshirt"></i> {{ __('T-Shirt Designer') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('qr-generator') ? 'active' : '' }}" href="{{ route('qr-generator') }}">
                        <i class="bi bi-qr-code"></i> {{ __('QR Generator') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('designs.index') ? 'active' : '' }}" href="{{ route('designs.index') }}">
                        <i class="bi bi-palette"></i> Designs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.history') }}">
                        <i class="bi bi-cart"></i> Orders
                    </a>
                </li>
                
                @if(Auth::check() && Auth::user()->is_admin)
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                        <i class="bi bi-people"></i> {{ __('Users') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.clothes-categories*') ? 'active' : '' }}" href="{{ route('admin.clothes-categories.index') }}">
                        <i class="bi bi-list"></i> {{ __('Categories') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.clothes-types*') ? 'active' : '' }}" href="{{ route('admin.clothes-types.index') }}">
                        <i class="bi bi-tshirt"></i> {{ __('Clothes Types') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.shirt-sizes*') ? 'active' : '' }}" href="{{ route('admin.shirt-sizes.index') }}">
                        <i class="bi bi-rulers"></i> {{ __('Shirt Sizes') }}
                    </a>
                </li>
                @endif
            </ul>

            <!-- Settings Dropdown -->
            @auth
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person"></i> {{ __('Profile') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right"></i> {{ __('Log Out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
            @else
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}">
                        <i class="bi bi-box-arrow-in-right"></i> {{ __('Login') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('register') }}">
                        <i class="bi bi-person-plus"></i> {{ __('Register') }}
                    </a>
                </li>
            </ul>
            @endauth
        </div>
    </div>
</nav>

<!-- NAVIGATION END DEBUG -->
<div style="background: green; color: white; padding: 5px; text-align: center;">NAVIGATION END</div>
