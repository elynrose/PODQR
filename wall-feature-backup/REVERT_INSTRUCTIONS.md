# Wall Feature Revert Instructions

## Files to Remove

### 1. Controller
```bash
rm app/Http/Controllers/WallController.php
```

### 2. Model
```bash
rm app/Models/WallPost.php
```

### 3. Migration
```bash
rm database/migrations/2025_06_25_180414_create_wall_posts_table.php
```

### 4. Views Directory
```bash
rm -rf resources/views/wall/
```

### 5. Routes (Remove from routes/web.php)
Remove the following lines from `routes/web.php`:

**Line 7**: Remove the import
```php
use App\Http\Controllers\WallController;
```

**Lines 26-33**: Remove the entire wall routes section
```php
// Wall routes
Route::middleware(['auth'])->group(function () {
    Route::get('/wall', [WallController::class, 'index'])->name('wall.index');
    Route::post('/wall', [WallController::class, 'store'])->name('wall.store');
    Route::get('/wall/posts', [WallController::class, 'getPosts'])->name('wall.posts');
    Route::get('/wall/{post}', [WallController::class, 'show'])->name('wall.show');
    Route::delete('/wall/{post}', [WallController::class, 'destroy'])->name('wall.destroy');
});
```

### 6. Database (Optional - if you want to remove the table)
```bash
php artisan migrate:rollback --step=1
```

### 7. Storage Files (Optional - if you want to remove uploaded files)
```bash
rm -rf storage/app/public/wall-attachments/
```

## Complete Revert Command
```bash
# Remove all wall feature files
rm app/Http/Controllers/WallController.php
rm app/Models/WallPost.php
rm database/migrations/2025_06_25_180414_create_wall_posts_table.php
rm -rf resources/views/wall/

# Remove routes (manual editing required)
# Edit routes/web.php and remove the wall routes section

# Optional: Remove database table
php artisan migrate:rollback --step=1

# Optional: Remove uploaded files
rm -rf storage/app/public/wall-attachments/
```

## Verification
After reverting, verify that:
1. No wall-related files exist in the application
2. The routes file no longer contains wall routes
3. The database table is removed (if you ran the rollback)
4. No wall-related errors appear in the application

## Restoration
To restore the wall feature later, use the files in this backup directory and follow the restoration instructions in `README.md`. 