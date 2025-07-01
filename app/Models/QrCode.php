<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'size',
        'color',
        'background_color',
        'format',
        'file_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the QR code
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active QR codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full file URL
     */
    public function getFileUrlAttribute()
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    /**
     * Generate a unique filename for the QR code
     */
    public function generateFilename()
    {
        return 'qr-codes/' . $this->user_id . '_' . time() . '_' . uniqid() . '.' . $this->format;
    }
}
