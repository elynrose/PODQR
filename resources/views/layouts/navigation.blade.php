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

<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm navbar-height" style="display: block !important; visibility: visible !important; position: relative !important; background: white !important; border-bottom: 1px solid #dee2e6 !important; min-height: 60px !important;">
    <div class="container" style="display: flex !important; align-items: center !important; justify-content: space-between !important; padding: 0 15px !important;">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('dashboard') }}" style="display: block !important; padding: 0.3125rem 0 !important; margin-right: 1rem !important; font-size: 1.25rem !important; color: #212529 !important; text-decoration: none !important;">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
        </a>
        
        <!-- Hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}" style="display: none !important;">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent" style="display: flex !important; flex-basis: auto !important; flex-grow: 1 !important; align-items: center !important;">
            <!-- Primary Navigation Menu -->
            <ul class="navbar-nav me-auto" style="display: flex !important; flex-direction: row !important; padding-left: 0 !important; margin-bottom: 0 !important; list-style: none !important;">
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" onclick="console.log('Dashboard clicked!'); return true;" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important; background: yellow !important; border: 2px solid red !important; cursor: pointer !important;">
                        <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('wall.*') ? 'active' : '' }}" href="{{ route('wall.index') }}" onclick="console.log('Wall clicked!'); return true;" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-grid"></i> {{ __('Wall') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('design') ? 'active' : '' }}" href="{{ route('design') }}" onclick="console.log('Design clicked!'); return true;" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-tshirt"></i> {{ __('T-Shirt Designer') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('qr-generator') ? 'active' : '' }}" href="{{ route('qr-generator') }}" onclick="console.log('QR clicked!'); return true;" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-qr-code"></i> {{ __('QR Generator') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('designs.index') ? 'active' : '' }}" href="{{ route('designs.index') }}" onclick="console.log('Designs clicked!'); return true;" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-palette"></i> Designs
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.history') }}" onclick="console.log('Orders clicked!'); return true;" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-cart"></i> Orders
                    </a>
                </li>
                @if(Auth::check() && Auth::user()->is_admin)
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-people"></i> {{ __('Users') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('admin.clothes-categories*') ? 'active' : '' }}" href="{{ route('admin.clothes-categories.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-list"></i> {{ __('Categories') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('admin.clothes-types*') ? 'active' : '' }}" href="{{ route('admin.clothes-types.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-tshirt"></i> {{ __('Clothes Types') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link {{ request()->routeIs('admin.shirt-sizes*') ? 'active' : '' }}" href="{{ route('admin.shirt-sizes.index') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-rulers"></i> {{ __('Shirt Sizes') }}
                    </a>
                </li>
                @endif
            </ul>

            <!-- Settings Dropdown -->
            @auth
            <ul class="navbar-nav ms-auto" style="display: flex !important; flex-direction: row !important; padding-left: 0 !important; margin-bottom: 0 !important; list-style: none !important;">
                <li class="nav-item dropdown" style="margin-right: 0.5rem !important;">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown" style="position: absolute !important; top: 100% !important; right: 0 !important; z-index: 1000 !important; display: none !important; min-width: 10rem !important; padding: 0.5rem 0 !important; margin: 0.125rem 0 0 !important; background-color: #fff !important; border: 1px solid rgba(0,0,0,.15) !important; border-radius: 0.375rem !important;">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}" style="display: block !important; width: 100% !important; padding: 0.25rem 1rem !important; clear: both !important; font-weight: 400 !important; color: #212529 !important; text-decoration: none !important; background-color: transparent !important; border: 0 !important;">
                                <i class="bi bi-person"></i> {{ __('Profile') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item" style="display: block !important; width: 100% !important; padding: 0.25rem 1rem !important; clear: both !important; font-weight: 400 !important; color: #212529 !important; text-decoration: none !important; background-color: transparent !important; border: 0 !important;">
                                    <i class="bi bi-box-arrow-right"></i> {{ __('Log Out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
            @else
            <ul class="navbar-nav ms-auto" style="display: flex !important; flex-direction: row !important; padding-left: 0 !important; margin-bottom: 0 !important; list-style: none !important;">
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link" href="{{ route('login') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-box-arrow-in-right"></i> {{ __('Login') }}
                    </a>
                </li>
                <li class="nav-item" style="margin-right: 0.5rem !important;">
                    <a class="nav-link" href="{{ route('register') }}" style="display: block !important; padding: 0.5rem 1rem !important; color: #6c757d !important; text-decoration: none !important;">
                        <i class="bi bi-person-plus"></i> {{ __('Register') }}
                    </a>
                </li>
            </ul>
            @endauth
        </div>
    </div>
</nav>
