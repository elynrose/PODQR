<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <span class="text-white fw-bold fs-4">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div>
                    <h2 class="fs-3 fw-bold mb-0">
                        {{ $user->name }}
                    </h2>
                    <p class="text-muted mb-0">{{ $user->email }}</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary d-flex align-items-center">
                    <i class="bi bi-pencil-square me-2"></i>
                    {{ __('Edit Profile') }}
                </a>
                <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary d-flex align-items-center">
                    <i class="bi bi-arrow-left me-2"></i>
                    {{ __('Back to Users') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <!-- Messages -->
                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                <!-- User Status Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <!-- Status Badges -->
                        <div class="mb-4">
                            @if($user->is_admin)
                                <span class="badge bg-primary d-inline-flex align-items-center">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Administrator
                                </span>
                            @endif
                            @if($user->isBanned())
                                <span class="badge bg-danger d-inline-flex align-items-center ms-2">
                                    <i class="bi bi-slash-circle me-1"></i>
                                    Account Banned
                                </span>
                            @else
                                <span class="badge bg-success d-inline-flex align-items-center ms-2">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Active Account
                                </span>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-uppercase fw-bold mb-4">Account Details</h5>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Member Since</dt>
                                            <dd class="col-sm-8">{{ $user->created_at->format('F j, Y') }}</dd>

                                            <dt class="col-sm-4">Email Verification</dt>
                                            <dd class="col-sm-8">
                                                @if($user->email_verified_at)
                                                    <span class="text-success d-inline-flex align-items-center">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Verified on {{ $user->email_verified_at->format('F j, Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-warning d-inline-flex align-items-center">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        Not verified
                                                    </span>
                                                @endif
                                            </dd>

                                            <dt class="col-sm-4">Last Updated</dt>
                                            <dd class="col-sm-8">{{ $user->updated_at->format('F j, Y H:i') }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            @if($user->isBanned())
                            <div class="col-md-6">
                                <div class="card bg-danger bg-opacity-10">
                                    <div class="card-body">
                                        <h5 class="card-title text-uppercase fw-bold mb-4 text-danger">Ban Information</h5>
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4 text-danger">Banned Since</dt>
                                            <dd class="col-sm-8 text-danger">{{ $user->banned_at->format('F j, Y H:i') }}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if(!$user->is_admin)
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Account Actions</h5>
                            <div class="d-flex gap-2">
                                @if($user->isBanned())
                                    <form action="{{ route('admin.users.unban', $user) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-warning d-flex align-items-center">
                                            <i class="bi bi-unlock me-2"></i>
                                            {{ __('Unban User') }}
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.users.ban', $user) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-warning d-flex align-items-center">
                                            <i class="bi bi-slash-circle me-2"></i>
                                            {{ __('Ban User') }}
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger d-flex align-items-center">
                                        <i class="bi bi-trash me-2"></i>
                                        {{ __('Delete User') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 