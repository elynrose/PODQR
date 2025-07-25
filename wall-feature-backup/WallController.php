<?php

namespace App\Http\Controllers;

use App\Models\WallPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WallController extends Controller
{
    /**
     * Display the wall with posts
     */
    public function index()
    {
        $posts = WallPost::with('user')
            ->active()
            ->recent(50)
            ->get();

        return view('wall.index', compact('posts'));
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
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Determine if it's an image or file
            $mimeType = $file->getMimeType();
            $isImage = Str::startsWith($mimeType, 'image/');
            
            // Store file
            $path = $file->storeAs('wall-attachments', $fileName, 'public');
            
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
        $post->incrementViewCount();
        
        return response()->json([
            'success' => true,
            'post' => $post->load('user'),
            'view_count' => $post->view_count
        ]);
    }

    /**
     * Delete a post
     */
    public function destroy(WallPost $post)
    {
        $user = Auth::user();
        
        if ($post->user_id !== $user->id && !$user->can('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete attachment file if exists
        if ($post->attachment_path) {
            Storage::disk('public')->delete($post->attachment_path);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }

    /**
     * Get posts for AJAX loading
     */
    public function getPosts(Request $request)
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
}
