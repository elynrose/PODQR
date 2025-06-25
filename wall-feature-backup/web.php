<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\DesignManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\WallController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/design', [DesignController::class, 'index'])->name('design');

// QR Code routes
Route::get('/qr-generator', [QrCodeController::class, 'show'])->middleware(['auth'])->name('qr-generator');
Route::post('/qr-generate', [QrCodeController::class, 'generate'])->middleware(['auth'])->name('qr-generate');
Route::post('/qr-generate-data-url', [QrCodeController::class, 'generateDataUrl'])->middleware(['auth'])->name('qr-generate-data-url');
Route::post('/qr-generate-and-save', [QrCodeController::class, 'generateAndSaveFromDesigner'])->middleware(['auth'])->name('qr-generate-and-save');
Route::post('/qr-save-and-design', [QrCodeController::class, 'saveAndDesign'])->middleware(['auth'])->name('qr-save-and-design');
Route::delete('/qr-codes/{qrCode}', [QrCodeController::class, 'destroy'])->middleware(['auth'])->name('qr-codes.destroy');

// Wall routes
Route::middleware(['auth'])->group(function () {
    Route::get('/wall', [WallController::class, 'index'])->name('wall.index');
    Route::post('/wall', [WallController::class, 'store'])->name('wall.store');
    Route::get('/wall/posts', [WallController::class, 'getPosts'])->name('wall.posts');
    Route::get('/wall/{post}', [WallController::class, 'show'])->name('wall.show');
    Route::delete('/wall/{post}', [WallController::class, 'destroy'])->name('wall.destroy');
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
});

// Public design gallery
Route::get('/designs/gallery', [DesignManagementController::class, 'gallery'])->name('designs.gallery');

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        
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

require __DIR__.'/auth.php';
