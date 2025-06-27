<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\DesignManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\WallController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\DalleController;
use Illuminate\Support\Facades\Storage;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/design', [DesignController::class, 'index'])->name('design');

// Public profile routes (for QR code scanning) - moved to be more specific
Route::get('/p/{identifier}', [PublicProfileController::class, 'show'])->name('public.profile');
Route::get('/p/{identifier}/post/{postId}', [PublicProfileController::class, 'showPost'])->name('public.post');

// QR Code routes
Route::get('/qr-generator', [QrCodeController::class, 'show'])->middleware(['auth'])->name('qr-generator');
Route::post('/qr-generate', [QrCodeController::class, 'generate'])->middleware(['auth'])->name('qr-generate');
Route::post('/qr-generate-data-url', [QrCodeController::class, 'generateDataUrl'])->middleware(['auth'])->name('qr-generate-data-url');
Route::post('/qr-generate-and-save', [QrCodeController::class, 'generateAndSaveFromDesigner'])->middleware(['auth'])->name('qr-generate-and-save');
Route::post('/qr-save-and-design', [QrCodeController::class, 'saveAndDesign'])->middleware(['auth'])->name('qr-save-and-design');
Route::delete('/qr-codes/{qrCode}', [QrCodeController::class, 'destroy'])->middleware(['auth'])->name('qr-codes.destroy');
Route::get('/qr-codes/user/list', [QrCodeController::class, 'getUserQrCodes'])->middleware(['auth'])->name('qr-codes.user.list');

// Serve QR code images with CORS headers
Route::get('/qr-codes/{filename}', function ($filename) {
    $path = 'qr-codes/' . $filename;
    if (Storage::disk('public')->exists($path)) {
        $content = Storage::disk('public')->get($path);
        return response($content)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
    }
    abort(404);
})->where('filename', '.*');

// Personal Wall routes
Route::middleware(['auth'])->group(function () {
    Route::get('/wall', [WallController::class, 'index'])->name('wall.index');
    Route::post('/wall', [WallController::class, 'store'])->name('wall.store');
    Route::get('/wall/posts', [WallController::class, 'getPosts'])->name('wall.posts');
    Route::get('/wall/{post}', [WallController::class, 'show'])->name('wall.show');
    Route::delete('/wall/{post}', [WallController::class, 'destroy'])->name('wall.destroy');
    
    // Admin Wall routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/wall', [WallController::class, 'adminIndex'])->name('admin.wall.index');
        Route::get('/admin/wall/posts', [WallController::class, 'adminGetPosts'])->name('admin.wall.posts');
        Route::delete('/admin/wall/{post}', [WallController::class, 'adminDestroy'])->name('admin.wall.destroy');
    });
});

// Design Management routes
Route::middleware(['auth'])->group(function () {
    Route::get('/designs', [DesignManagementController::class, 'index'])->name('designs.index');
    Route::get('/designs/create', [DesignManagementController::class, 'create'])->name('designs.create');
    Route::post('/designs', [DesignManagementController::class, 'store'])->name('designs.store');
    Route::get('/designs/{design}', [DesignManagementController::class, 'show'])->name('designs.show');
    Route::get('/designs/{design}/edit', [DesignManagementController::class, 'edit'])->name('designs.edit');
    Route::put('/designs/{design}', [DesignManagementController::class, 'update'])->name('designs.update');
    Route::delete('/designs/{design}', [DesignManagementController::class, 'destroy'])->name('designs.destroy');
    
    // Save design from designer page
    Route::post('/designs/save-from-designer', [DesignManagementController::class, 'saveFromDesigner'])->name('designs.save-from-designer');
    
    // Design preview for order form
    Route::get('/designs/{design}/preview', [DesignManagementController::class, 'preview'])->name('designs.preview');
});

// Order routes
Route::get('/orders/create/{designId?}', [OrderController::class, 'showOrderForm'])->name('orders.create');
Route::get('/api/products', [OrderController::class, 'getProducts'])->name('api.products');

Route::middleware(['auth'])->group(function () {
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/history', [OrderController::class, 'orderHistory'])->name('orders.history');
    Route::get('/orders/success', [OrderController::class, 'handleSuccess'])->name('orders.success');
    Route::get('/orders/{order}', [OrderController::class, 'showOrder'])->name('orders.show');
    Route::post('/orders/{order}/send-to-printful', [OrderController::class, 'sendToPrintful'])->name('orders.send-to-printful');
    Route::post('/orders/{order}/cancel-discontinued', [OrderController::class, 'cancelOrderDueToDiscontinuedVariants'])->name('orders.cancel-discontinued');
    Route::post('/orders/{order}/cancel-regional', [OrderController::class, 'cancelOrderDueToRegionalRestrictions'])->name('orders.cancel-regional');
    
    // API routes that need session authentication
    Route::get('/api/load-more-products', [OrderController::class, 'loadMoreProducts'])->name('api.load-more-products');
    Route::post('/api/calculate-total', [OrderController::class, 'calculateTotal'])->name('api.calculate-total');
    Route::post('/api/sync-products', [OrderController::class, 'syncProducts'])->name('api.sync-products');
    Route::post('/api/validate-product-shipping', [OrderController::class, 'validateProductShipping'])->name('api.validate-product-shipping');
    Route::get('/api/more-products', [OrderController::class, 'getMoreProducts'])->name('api.more-products');
});

// Public design gallery
Route::get('/designs/gallery', [DesignManagementController::class, 'gallery'])->name('designs.gallery');

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        
        // User Management
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/ban', [AdminController::class, 'banUser'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminController::class, 'unbanUser'])->name('users.unban');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        
        // Clothes Categories Management
        Route::resource('clothes-categories', \App\Http\Controllers\Admin\ClothesCategoryController::class);
        
        // Clothes Types Management
        Route::resource('clothes-types', \App\Http\Controllers\Admin\ClothesTypeController::class);
        
        // Shirt Sizes Management
        Route::resource('shirt-sizes', \App\Http\Controllers\Admin\ShirtSizeController::class);
        
        // Designs Management (Admin)
        Route::get('/designs', [DesignManagementController::class, 'index'])->name('designs.index');
        Route::get('/designs/{design}', [DesignManagementController::class, 'show'])->name('designs.show');
        Route::get('/designs/{design}/edit', [DesignManagementController::class, 'edit'])->name('designs.edit');
        Route::put('/designs/{design}', [DesignManagementController::class, 'update'])->name('designs.update');
    });
});

// DALL-E AI Image Generation
Route::middleware(['auth'])->group(function () {
    Route::get('/dalle/test', [DalleController::class, 'testApi'])->name('dalle.test');
    Route::post('/dalle/generate', [DalleController::class, 'generateImage'])->name('dalle.generate');
});

// Debug route for testing navigation
Route::get('/debug-nav', function () {
    return view('debug-nav');
})->middleware(['auth'])->name('debug.nav');

// Test Printful API
Route::get('/test-printful-api', [App\Http\Controllers\OrderController::class, 'testPrintfulApi'])->name('test.printful.api');

require __DIR__.'/auth.php';
