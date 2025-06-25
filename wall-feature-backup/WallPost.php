<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WallPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'attachment_path',
        'attachment_type',
        'attachment_name',
        'view_count',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'view_count' => 'integer',
    ];

    /**
     * Get the user that owns the post
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attachment URL
     */
    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment_path) {
            return Storage::url($this->attachment_path);
        }
        return null;
    }

    /**
     * Check if post has an attachment
     */
    public function hasAttachment()
    {
        return !empty($this->attachment_path);
    }

    /**
     * Check if attachment is an image
     */
    public function isImage()
    {
        return $this->attachment_type === 'image';
    }

    /**
     * Check if attachment is a file
     */
    public function isFile()
    {
        return $this->attachment_type === 'file';
    }

    /**
     * Get file extension
     */
    public function getFileExtension()
    {
        if ($this->attachment_name) {
            return pathinfo($this->attachment_name, PATHINFO_EXTENSION);
        }
        return null;
    }

    /**
     * Increment view count
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    /**
     * Scope for active posts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for recent posts
     */
    public function scopeRecent($query, $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
