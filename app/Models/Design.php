<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'clothes_type_id',
        'shirt_size_id',
        'color_code',
        'qr_code_id',
        'qr_code_position',
        'photos',
        'texts',
        'front_canvas_data',
        'back_canvas_data',
        'front_image_path',
        'back_image_path',
        'cover_image',
        'status',
        'is_public',
    ];

    protected $casts = [
        'qr_code_position' => 'array',
        'photos' => 'array',
        'texts' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the user that owns the design.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the clothes type for this design.
     */
    public function clothesType(): BelongsTo
    {
        return $this->belongsTo(ClothesType::class);
    }

    /**
     * Get the shirt size for this design.
     */
    public function shirtSize(): BelongsTo
    {
        return $this->belongsTo(ShirtSize::class);
    }

    /**
     * Get the QR code for this design.
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /**
     * Get the front image URL.
     */
    public function getFrontImageUrlAttribute(): ?string
    {
        return $this->front_image_path ? Storage::url($this->front_image_path) : null;
    }

    /**
     * Get the back image URL.
     */
    public function getBackImageUrlAttribute(): ?string
    {
        if ($this->back_image_path) {
            return asset('storage/' . $this->back_image_path);
        }
        return null;
    }

    /**
     * Get the cover image URL.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? Storage::url($this->cover_image) : null;
    }

    /**
     * Scope to get only public designs.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', 'published');
    }

    /**
     * Scope to get designs by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get designs by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the design preview image (front or back).
     */
    public function getPreviewImageUrlAttribute(): ?string
    {
        return $this->front_image_path ? $this->front_image_url : $this->back_image_url;
    }

    /**
     * Check if design has QR code.
     */
    public function hasQrCode(): bool
    {
        return !is_null($this->qr_code_id);
    }

    /**
     * Check if design has photos.
     */
    public function hasPhotos(): bool
    {
        return !empty($this->photos);
    }

    /**
     * Check if design has texts.
     */
    public function hasTexts(): bool
    {
        return !empty($this->texts);
    }

    /**
     * Get the number of photos in the design.
     */
    public function getPhotoCountAttribute(): int
    {
        return is_array($this->photos) ? count($this->photos) : 0;
    }

    /**
     * Get the number of text elements in the design.
     */
    public function getTextCountAttribute(): int
    {
        return is_array($this->texts) ? count($this->texts) : 0;
    }

    /**
     * Get the order items for this design.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
