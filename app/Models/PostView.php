<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostView extends Model
{
    use HasFactory;

    protected $fillable = [
        'wall_post_id',
        'ip_address',
        'user_agent'
    ];

    /**
     * Get the wall post that was viewed
     */
    public function wallPost()
    {
        return $this->belongsTo(WallPost::class);
    }

    /**
     * Scope to filter by IP address
     */
    public function scopeByIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to filter by wall post
     */
    public function scopeByWallPost($query, $wallPostId)
    {
        return $query->where('wall_post_id', $wallPostId);
    }
}
