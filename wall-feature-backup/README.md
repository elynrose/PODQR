# Wall Feature Backup Documentation

## Overview
This directory contains a complete backup of the Community Wall feature for the PODQR application. The wall feature allows users to create posts with text content and file attachments, view posts from other users, and manage their own posts.

## Feature Components

### 1. Database Structure
- **Migration File**: `2025_06_25_180414_create_wall_posts_table.php`
- **Table**: `wall_posts`
- **Fields**:
  - `id` (Primary Key)
  - `user_id` (Foreign Key to users table)
  - `content` (Text content of the post)
  - `attachment_path` (Path to uploaded file)
  - `attachment_type` (Type: 'image' or 'file')
  - `attachment_name` (Original filename)
  - `view_count` (Number of views)
  - `is_active` (Boolean for soft deletes)
  - `created_at`, `updated_at` (Timestamps)

### 2. Model
- **File**: `WallPost.php`
- **Location**: `app/Models/WallPost.php`
- **Features**:
  - Relationship with User model
  - File attachment handling
  - View count management
  - Scopes for active and recent posts
  - Helper methods for attachment types

### 3. Controller
- **File**: `WallController.php`
- **Location**: `app/Http/Controllers/WallController.php`
- **Methods**:
  - `index()` - Display wall with posts
  - `store()` - Create new post with file upload
  - `show()` - View specific post (increments view count)
  - `destroy()` - Delete post (with file cleanup)
  - `getPosts()` - AJAX endpoint for loading posts

### 4. Views
- **Directory**: `resources/views/wall/`
- **Main View**: `index.blade.php`
- **Features**:
  - Post creation form with file upload
  - Real-time character counter
  - File attachment preview
  - Post display with user avatars
  - Image and file attachment display
  - Delete functionality for own posts
  - Responsive design with modern UI

### 5. Routes
- **File**: `routes/web.php` (wall routes section)
- **Routes**:
  - `GET /wall` - Wall index page
  - `POST /wall` - Create new post
  - `GET /wall/posts` - Get posts via AJAX
  - `GET /wall/{post}` - View specific post
  - `DELETE /wall/{post}` - Delete post

## Features

### Post Creation
- Text content with 255 character limit
- File attachments (images and documents)
- Real-time character counter
- File type validation
- File size limit (10MB)

### Post Display
- User avatars with initials
- Timestamp display
- View count tracking
- Image preview for image attachments
- File download links for document attachments
- Delete button for own posts

### File Handling
- Automatic file type detection
- Secure file storage in `public/wall-attachments/`
- File cleanup on post deletion
- Support for images and various document types

### Security
- Authentication required for all wall features
- User can only delete their own posts
- Admin users can delete any post
- File upload validation and sanitization

## Dependencies
- Laravel Framework
- jQuery (for AJAX functionality)
- Font Awesome (for icons)
- Bootstrap (for styling)

## File Storage
- Files are stored in `storage/app/public/wall-attachments/`
- Public URL accessible via `Storage::url()`
- Automatic cleanup when posts are deleted

## JavaScript Features
- AJAX post creation
- Real-time character counting
- File upload preview
- Dynamic post addition to DOM
- Post deletion with confirmation
- Error handling and user feedback

## CSS Styling
- Modern card-based design
- Hover effects and transitions
- Responsive layout
- Twitter-like appearance
- Custom styling for file attachments

## Usage Notes
- The wall feature is accessible to authenticated users only
- Posts are displayed in reverse chronological order
- File attachments are optional
- View counts are incremented when posts are viewed individually
- The feature includes soft delete functionality via `is_active` field

## Restoration Instructions
To restore this feature:
1. Copy the migration file to `database/migrations/`
2. Copy the model to `app/Models/`
3. Copy the controller to `app/Http/Controllers/`
4. Copy the views to `resources/views/wall/`
5. Add the routes to `routes/web.php`
6. Run `php artisan migrate` to create the database table
7. Ensure the storage link is created: `php artisan storage:link`

## Backup Date
This backup was created on: Wed Jun 25 15:32:55 EDT 2025 