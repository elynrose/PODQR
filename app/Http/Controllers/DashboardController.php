<?php

namespace App\Http\Controllers;

use App\Models\WallPost;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with charts and statistics.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get post views data for the last 30 days
        $viewsData = $this->getPostViewsData();
        
        // Get most popular posts
        $popularPosts = $this->getPopularPosts();
        
        // Get user statistics
        $userStats = $this->getUserStats($user);
        
        return view('dashboard', compact('viewsData', 'popularPosts', 'userStats'));
    }
    
    /**
     * Get post views data for chart (only user's own posts)
     */
    private function getPostViewsData()
    {
        $user = auth()->user();
        $days = 30;
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            
            // Get total views for this date (only user's posts)
            $views = WallPost::where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->sum('view_count');
            
            $data[] = [
                'date' => Carbon::now()->subDays($i)->format('M j'),
                'views' => $views
            ];
        }
        
        return $data;
    }
    
    /**
     * Get most popular posts (only user's own posts)
     */
    private function getPopularPosts()
    {
        $user = auth()->user();
        
        return WallPost::with('user')
            ->where('user_id', $user->id)
            ->active()
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => \Str::limit($post->content, 100),
                    'user_name' => $post->user->name,
                    'views' => $post->view_count,
                    'unique_views' => $post->getUniqueViewCount(),
                    'created_at' => $post->created_at->format('M j, Y'),
                    'has_attachment' => $post->hasAttachment(),
                    'attachment_type' => $post->attachment_type
                ];
            });
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats($user)
    {
        $totalPosts = WallPost::where('user_id', $user->id)->count();
        $totalViews = WallPost::where('user_id', $user->id)->sum('view_count');
        
        // Get unique views (by IP) for user's posts
        $uniqueViews = \App\Models\PostView::whereHas('wallPost', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
        
        $postsThisMonth = WallPost::where('user_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();
        $viewsThisMonth = WallPost::where('user_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('view_count');
        
        return [
            'total_posts' => $totalPosts,
            'total_views' => $totalViews,
            'unique_views' => $uniqueViews,
            'posts_this_month' => $postsThisMonth,
            'views_this_month' => $viewsThisMonth
        ];
    }
} 