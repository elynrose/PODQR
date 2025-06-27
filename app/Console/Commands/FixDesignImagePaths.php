<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Design;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class FixDesignImagePaths extends Command
{
    protected $signature = 'designs:fix-image-paths';
    protected $description = 'Fix design image paths by finding actual image files in storage';

    public function handle()
    {
        $this->info('Fixing design image paths...');
        
        $designs = Design::all();
        $fixed = 0;
        
        foreach ($designs as $design) {
            $designId = $design->id;
            $designPath = "designs/{$designId}/photos";
            
            // Check if the design photos directory exists
            if (!Storage::disk('public')->exists($designPath)) {
                $this->warn("Design {$designId}: No photos directory found");
                continue;
            }
            
            // Get all files in the photos directory
            $files = Storage::disk('public')->files($designPath);
            
            if (empty($files)) {
                $this->warn("Design {$designId}: No image files found in photos directory");
                continue;
            }
            
            // Find the most recent front image (prefer front_3, then front_2)
            $frontImage = null;
            $front3Files = array_filter($files, function($file) {
                return strpos($file, 'front_3_') !== false;
            });
            
            if (!empty($front3Files)) {
                // Get the most recent front_3 file
                $frontImage = end($front3Files);
            } else {
                // Look for front_2 files
                $front2Files = array_filter($files, function($file) {
                    return strpos($file, 'front_2_') !== false;
                });
                
                if (!empty($front2Files)) {
                    $frontImage = end($front2Files);
                } else {
                    // Use the first file as fallback
                    $frontImage = $files[0];
                }
            }
            
            if ($frontImage) {
                // Update the design with the front image path
                $design->update([
                    'front_image_path' => $frontImage
                ]);
                
                $this->info("Design {$designId}: Updated front_image_path to {$frontImage}");
                $fixed++;
            }
        }
        
        $this->info("Fixed {$fixed} designs out of {$designs->count()} total designs.");
        
        return 0;
    }
} 