<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Design;
use App\Http\Controllers\DesignManagementController;

class GenerateDesignImages extends Command
{
    protected $signature = 'designs:generate-images {--design-id= : Generate for specific design ID} {--all : Generate for all designs}';
    protected $description = 'Generate design-only images from canvas data for existing designs';

    public function handle()
    {
        $designId = $this->option('design-id');
        $all = $this->option('all');

        if ($designId) {
            // Generate for specific design
            $design = Design::find($designId);
            if (!$design) {
                $this->error("Design #{$designId} not found");
                return 1;
            }
            $this->generateForDesign($design);
        } elseif ($all) {
            // Generate for all designs
            $designs = Design::where(function($query) {
                $query->whereNull('front_image_path')
                      ->orWhereNull('back_image_path');
            })->get();

            if ($designs->isEmpty()) {
                $this->info('No designs found that need image generation');
                return 0;
            }

            $this->info("Found {$designs->count()} designs that need image generation");
            
            $bar = $this->output->createProgressBar($designs->count());
            $bar->start();

            foreach ($designs as $design) {
                $this->generateForDesign($design, false);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Image generation completed for all designs');
        } else {
            $this->error('Please specify --design-id=X or --all');
            return 1;
        }

        return 0;
    }

    private function generateForDesign(Design $design, $verbose = true)
    {
        if ($verbose) {
            $this->info("Generating images for design #{$design->id}: {$design->name}");
        }

        $controller = new DesignManagementController();
        
        // Use reflection to access the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('generatePreviewImages');
        $method->setAccessible(true);
        
        try {
            $method->invoke($controller, $design);
            
            // Reload the design to get updated paths
            $design->refresh();
            
            if ($verbose) {
                $this->info("  Front image: " . ($design->front_image_path ?? 'Not generated'));
                $this->info("  Back image: " . ($design->back_image_path ?? 'Not generated'));
            }
        } catch (\Exception $e) {
            if ($verbose) {
                $this->error("  Error generating images: " . $e->getMessage());
            }
        }
    }
} 