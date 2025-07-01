<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudStorageService
{
    /**
     * Store an uploaded file to cloud storage
     */
    public function storeFile(UploadedFile $file, string $directory = 'uploads', string $disk = 's3'): string
    {
        try {
            $filename = $this->generateUniqueFilename($file);
            $path = $directory . '/' . $filename;
            
            $result = Storage::disk($disk)->putFileAs($directory, $file, $filename);
            
            if (!$result) {
                throw new \Exception('Failed to store file to cloud storage');
            }
            
            Log::info('File stored to cloud storage', [
                'path' => $path,
                'disk' => $disk,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Error storing file to cloud storage', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'directory' => $directory
            ]);
            throw $e;
        }
    }

    /**
     * Store base64 image data to cloud storage
     */
    public function storeBase64Image(string $base64Data, string $directory = 'images', string $filename = null, string $disk = 's3'): string
    {
        try {
            // Remove data URL prefix if present
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
            
            // Decode base64 data
            $imageData = base64_decode($base64Data);
            
            if ($imageData === false) {
                throw new \Exception('Invalid base64 image data');
            }
            
            // Generate filename if not provided
            if (!$filename) {
                $filename = $this->generateUniqueFilename(null, 'png');
            }
            
            $path = $directory . '/' . $filename;
            
            $result = Storage::disk($disk)->put($path, $imageData);
            
            if (!$result) {
                throw new \Exception('Failed to store base64 image to cloud storage');
            }
            
            Log::info('Base64 image stored to cloud storage', [
                'path' => $path,
                'disk' => $disk,
                'size' => strlen($imageData)
            ]);
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Error storing base64 image to cloud storage', [
                'error' => $e->getMessage(),
                'directory' => $directory
            ]);
            throw $e;
        }
    }

    /**
     * Store image from URL to cloud storage
     */
    public function storeImageFromUrl(string $url, string $directory = 'images', string $filename = null, string $disk = 's3'): string
    {
        try {
            $response = \Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to download image from URL: ' . $response->status());
            }
            
            $imageData = $response->body();
            
            // Generate filename if not provided
            if (!$filename) {
                $extension = $this->getExtensionFromUrl($url) ?: 'png';
                $filename = $this->generateUniqueFilename(null, $extension);
            }
            
            $path = $directory . '/' . $filename;
            
            $result = Storage::disk($disk)->put($path, $imageData);
            
            if (!$result) {
                throw new \Exception('Failed to store image from URL to cloud storage');
            }
            
            Log::info('Image from URL stored to cloud storage', [
                'path' => $path,
                'disk' => $disk,
                'url' => $url,
                'size' => strlen($imageData)
            ]);
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Error storing image from URL to cloud storage', [
                'error' => $e->getMessage(),
                'url' => $url,
                'directory' => $directory
            ]);
            throw $e;
        }
    }

    /**
     * Delete a file from cloud storage
     */
    public function deleteFile(string $path, string $disk = 's3'): bool
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                $result = Storage::disk($disk)->delete($path);
                
                Log::info('File deleted from cloud storage', [
                    'path' => $path,
                    'disk' => $disk,
                    'result' => $result
                ]);
                
                return $result;
            }
            
            Log::warning('File not found for deletion', [
                'path' => $path,
                'disk' => $disk
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error deleting file from cloud storage', [
                'error' => $e->getMessage(),
                'path' => $path,
                'disk' => $disk
            ]);
            return false;
        }
    }

    /**
     * Get the public URL for a file
     */
    public function getUrl(string $path, string $disk = 's3'): ?string
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->url($path);
            }
            
            Log::warning('File not found for URL generation', [
                'path' => $path,
                'disk' => $disk
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error generating URL for file', [
                'error' => $e->getMessage(),
                'path' => $path,
                'disk' => $disk
            ]);
            return null;
        }
    }

    /**
     * Check if a file exists in cloud storage
     */
    public function fileExists(string $path, string $disk = 's3'): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Create a directory in cloud storage
     */
    public function makeDirectory(string $path, string $disk = 's3'): bool
    {
        try {
            if (!Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->makeDirectory($path);
                
                Log::info('Directory created in cloud storage', [
                    'path' => $path,
                    'disk' => $disk
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error creating directory in cloud storage', [
                'error' => $e->getMessage(),
                'path' => $path,
                'disk' => $disk
            ]);
            return false;
        }
    }

    /**
     * Generate a unique filename
     */
    private function generateUniqueFilename(?UploadedFile $file = null, string $extension = null): string
    {
        $timestamp = time();
        $random = Str::random(10);
        
        if ($file) {
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
        }
        
        return $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Get file extension from URL
     */
    private function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            return $extension ?: null;
        }
        return null;
    }

    /**
     * Migrate file from local storage to cloud storage
     */
    public function migrateFromLocal(string $localPath, string $cloudPath, string $disk = 's3'): bool
    {
        try {
            if (!Storage::disk('public')->exists($localPath)) {
                Log::warning('Local file not found for migration', ['path' => $localPath]);
                return false;
            }
            
            $fileContent = Storage::disk('public')->get($localPath);
            $result = Storage::disk($disk)->put($cloudPath, $fileContent);
            
            if ($result) {
                Log::info('File migrated from local to cloud storage', [
                    'local_path' => $localPath,
                    'cloud_path' => $cloudPath,
                    'disk' => $disk
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error migrating file from local to cloud storage', [
                'error' => $e->getMessage(),
                'local_path' => $localPath,
                'cloud_path' => $cloudPath,
                'disk' => $disk
            ]);
            return false;
        }
    }
} 