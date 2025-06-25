<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Register') }}
        </h2>
    </x-slot>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-4">
                <div class="card shadow-sm">
                 
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fa fa-user"></i> {{ __('Full Name') }}
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required 
                                       autofocus 
                                       autocomplete="name"
                                       placeholder="Enter your full name">
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fa fa-at"></i> {{ __('Username') }}
                                </label>
                                <input type="text" 
                                       class="form-control @error('username') is-invalid @enderror" 
                                       id="username" 
                                       name="username" 
                                       value="{{ old('username') }}" 
                                       required 
                                       autocomplete="username"
                                       placeholder="Choose a unique username">
                                <div class="form-text">
                                    <i class="fa fa-info-circle"></i> This will be your unique profile URL: <strong>mymoodtee.com/yourusername</strong>
                                </div>
                                @error('username')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Email Address -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fa fa-envelope"></i> {{ __('Email Address') }}
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required 
                                       autocomplete="email"
                                       placeholder="Enter your email address">
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fa fa-lock"></i> {{ __('Password') }}
                                </label>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       autocomplete="new-password"
                                       placeholder="Create a strong password">
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">
                                    <i class="fa fa-lock"></i> {{ __('Confirm Password') }}
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required 
                                       autocomplete="new-password"
                                       placeholder="Confirm your password">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-user-plus"></i> {{ __('Create Account') }}
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <p class="text-muted mb-0">
                                    {{ __('Already have an account?') }}
                                    <a href="{{ route('login') }}" class="text-decoration-none">
                                        <i class="fa fa-sign-in"></i> {{ __('Sign In') }}
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
