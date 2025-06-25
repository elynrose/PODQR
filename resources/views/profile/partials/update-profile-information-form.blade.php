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

        <div class="d-flex align-items-center gap-4">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            @if (session('status') === 'profile-updated')
                <p class="text-success mb-0">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
