<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_admin',
        'banned_at',
        'is_premium',
        'user_type',
        'country_code',
        'state_code',
        'city',
        'zip_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'banned_at' => 'datetime',
            'is_premium' => 'boolean',
            'user_type' => 'string',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if the user is banned.
     */
    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    /**
     * Ban the user.
     */
    public function ban(): void
    {
        $this->update(['banned_at' => now()]);
    }

    /**
     * Unban the user.
     */
    public function unban(): void
    {
        $this->update(['banned_at' => null]);
    }

    /**
     * Get the user's unique profile URL.
     */
    public function getProfileUrl(): string
    {
        $identifier = $this->username ?: $this->id;
        return url('/p/' . $identifier);
    }

    /**
     * Check if the user is premium.
     */
    public function isPremium(): bool
    {
        return $this->is_premium;
    }

    /**
     * Check if the user is a partner.
     */
    public function isPartner(): bool
    {
        return $this->user_type === 'partner';
    }

    /**
     * Check if the user is a premium user (not partner).
     */
    public function isPremiumUser(): bool
    {
        return $this->user_type === 'premium';
    }

    /**
     * Check if the user is a free user.
     */
    public function isFreeUser(): bool
    {
        return $this->user_type === 'free';
    }

    /**
     * Get the maximum number of QR codes allowed for this user.
     */
    public function getQrCodeLimit(): int
    {
        switch ($this->user_type) {
            case 'partner':
                return 999999; // Unlimited
            case 'premium':
                return 20; // 20 QR codes
            case 'free':
            default:
                return 1; // 1 QR code
        }
    }

    /**
     * Check if the user has dashboard access.
     */
    public function hasDashboardAccess(): bool
    {
        return $this->isPartner();
    }

    /**
     * Get the QR codes for the user.
     */
    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * Get the wall posts for the user.
     */
    public function wallPosts()
    {
        return $this->hasMany(WallPost::class);
    }

    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function designs()
{
    return $this->hasMany(\App\Models\Design::class);
}
}
