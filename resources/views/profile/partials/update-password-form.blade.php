<section>
    <header>
        <h3 class="fs-4 fw-semibold mb-1">
            {{ __('Update Password') }}
        </h3>

        <p class="text-muted mb-3">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        <div class="mb-3">
            <label for="current_password" class="form-label">{{ __('Current Password') }}</label>
            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password">
            @if($errors->updatePassword->get('current_password'))
                <div class="alert alert-danger mt-2">
                    <ul class="mb-0">
                        @foreach ($errors->updatePassword->get('current_password') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('New Password') }}</label>
            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
            @if($errors->updatePassword->get('password'))
                <div class="alert alert-danger mt-2">
                    <ul class="mb-0">
                        @foreach ($errors->updatePassword->get('password') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
            @if($errors->updatePassword->get('password_confirmation'))
                <div class="alert alert-danger mt-2">
                    <ul class="mb-0">
                        @foreach ($errors->updatePassword->get('password_confirmation') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-4">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
                <p class="text-success mb-0">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
