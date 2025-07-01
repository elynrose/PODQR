<?php

namespace App\Http\Controllers;

use App\Models\WallPost;
use App\Services\CloudStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WallController extends Controller
{
    /**
     * Display the wall with posts (only user's own posts)
     */
    public function index()
    {
        $user = Auth::user();
        
        $posts = WallPost::with('user')
            ->where('user_id', $user->id)
            ->active()
            ->recent(50)
            ->get();

        return view('wall.index', compact('posts', 'user'));
    }

    /**
     * Store a new wall post
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:255',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $user = Auth::user();
        
        $postData = [
            'user_id' => $user->id,
            'content' => $request->input('content'),
        ];

        // Handle file attachment
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            
            // Determine if it's an image or file
            $mimeType = $file->getMimeType();
            $isImage = Str::startsWith($mimeType, 'image/');
            
            // Store file to cloud storage
            $cloudStorage = new CloudStorageService();
            $path = $cloudStorage->storeFile($file, 'wall-attachments');
            
            $postData['attachment_path'] = $path;
            $postData['attachment_type'] = $isImage ? 'image' : 'file';
            $postData['attachment_name'] = $file->getClientOriginalName();
        }

        $post = WallPost::create($postData);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Post created successfully!',
                'post' => $post->load('user')
            ]);
        }

        return redirect()->back()->with('success', 'Post created successfully!');
    }

    /**
     * View a specific post (increment view count)
     */
    public function show(WallPost $post)
    {
        // Increment view count
        $post->incrementViewCount(request());
        
        return response()->json([
            'success' => true,
            'post' => $post->load('user'),
            'view_count' => $post->view_count
        ]);
    }

    /**
     * Delete a post (users can only delete their own posts)
     */
    public function destroy(WallPost $post)
    {
        $user = Auth::user();
        
        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - You can only delete your own posts'
            ], 403);
        }

        // Delete attachment file if exists
        if ($post->attachment_path) {
            $cloudStorage = new CloudStorageService();
            $cloudStorage->deleteFile($post->attachment_path);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }

    /**
     * Get posts for AJAX loading (only user's own posts)
     */
    public function getPosts(Request $request)
    {
        $user = Auth::user();
        
        $posts = WallPost::with('user')
            ->where('user_id', $user->id)
            ->active()
            ->recent(20)
            ->get();

        return response()->json([
            'success' => true,
            'posts' => $posts
        ]);
    }

    /**
     * Display the admin wall with all posts
     */
    public function adminIndex()
    {
        $posts = WallPost::with('user')
            ->active()
            ->recent(50)
            ->get();

        return view('admin.wall.index', compact('posts'));
    }

    /**
     * Get all posts for admin AJAX loading
     */
    public function adminGetPosts(Request $request)
    {
        $posts = WallPost::with('user')
            ->active()
            ->recent(20)
            ->get();

        return response()->json([
            'success' => true,
            'posts' => $posts
        ]);
    }

    /**
     * Delete any post (admin only)
     */
    public function adminDestroy(WallPost $post)
    {
        // Delete attachment file if exists
        if ($post->attachment_path) {
            $cloudStorage = new CloudStorageService();
            $cloudStorage->deleteFile($post->attachment_path);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
}
