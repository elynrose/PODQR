<section>
    <header>
        <h3 class="fs-4 fw-semibold mb-1">
            {{ __('Profile Information') }}
        </h3>

        <p class="text-muted mb-3">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-4">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @if($errors->get('name'))
                <div class="alert alert-danger mt-2">
                    <ul class="mb-0">
                        @foreach ($errors->get('name') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @if($errors->get('email'))
                <div class="alert alert-danger mt-2">
                    <ul class="mb-0">
                        @foreach ($errors->get('email') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="alert alert-warning mt-2">
                    <p class="mb-0">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="btn btn-link p-0 m-0 align-baseline">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div class="alert alert-success mt-2">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Location Information -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="country_code" class="form-label">{{ __('Country') }}</label>
                <select name="country_code" id="country_code" class="form-select">
                    <option value="">Select Country</option>
                    <option value="US" {{ old('country_code', $user->country_code) == 'US' ? 'selected' : '' }}>United States</option>
                    <option value="CA" {{ old('country_code', $user->country_code) == 'CA' ? 'selected' : '' }}>Canada</option>
                    <option value="GB" {{ old('country_code', $user->country_code) == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                    <option value="AU" {{ old('country_code', $user->country_code) == 'AU' ? 'selected' : '' }}>Australia</option>
                </select>
                @if($errors->get('country_code'))
                    <div class="alert alert-danger mt-2">
                        <ul class="mb-0">
                            @foreach ($errors->get('country_code') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="col-md-6 mb-3">
                <label for="state_code" class="form-label">{{ __('State/Province') }}</label>
                <input type="text" class="form-control" id="state_code" name="state_code" value="{{ old('state_code', $user->state_code) }}" placeholder="e.g., CA, NY, ON">
                @if($errors->get('state_code'))
                    <div class="alert alert-danger mt-2">
                        <ul class="mb-0">
                            @foreach ($errors->get('state_code') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="city" class="form-label">{{ __('City') }}</label>
                <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $user->city) }}" placeholder="e.g., New York, Toronto">
                @if($errors->get('city'))
                    <div class="alert alert-danger mt-2">
                        <ul class="mb-0">
                            @foreach ($errors->get('city') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="col-md-6 mb-3">
                <label for="zip_code" class="form-label">{{ __('ZIP/Postal Code') }}</label>
                <input type="text" class="form-control" id="zip_code" name="zip_code" value="{{ old('zip_code', $user->zip_code) }}" placeholder="e.g., 10001, M5V 3A8">
                @if($errors->get('zip_code'))
                    <div class="alert alert-danger mt-2">
                        <ul class="mb-0">
                            @foreach ($errors->get('zip_code') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-4">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            @if (session('status') === 'profile-updated')
                <p class="text-success mb-0">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
