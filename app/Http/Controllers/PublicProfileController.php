<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WallPost;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    /**
     * Show the public profile page with the user's most recent post
     */
    public function show($identifier)
    {
        // Find user by username or ID
        $user = User::where('username', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if (!$user) {
            abort(404, 'User not found');
        }

        // Check if user is banned
        if ($user->isBanned()) {
            abort(404, 'User not found');
        }

        // Get the user's most recent active post
        $latestPost = WallPost::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        // Increment view count if post exists
        if ($latestPost) {
            $latestPost->incrementViewCount(request());
        }

        return view('public.profile', compact('user', 'latestPost'));
    }

    /**
     * Show a specific post by ID (for direct links)
     */
    public function showPost($identifier, $postId)
    {
        // Find user by username or ID
        $user = User::where('username', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if (!$user) {
            abort(404, 'User not found');
        }

        // Check if user is banned
        if ($user->isBanned()) {
            abort(404, 'User not found');
        }

        // Get the specific post
        $post = WallPost::where('id', $postId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$post) {
            abort(404, 'Post not found');
        }

        // Increment view count
        $post->incrementViewCount(request());

        return view('public.post', compact('user', 'post'));
    }
} 