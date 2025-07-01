<?php

namespace App\Console\Commands;

use App\Models\Design;
use App\Models\ClothesType;
use App\Models\WallPost;
use App\Models\QrCode;
use App\Services\CloudStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MigrateImagesToCloudStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:migrate-to-cloud {--dry-run : Show what would be migrated without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing images from local storage to cloud storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cloudStorage = new CloudStorageService();
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No files will actually be migrated');
        }

        $this->info('Starting image migration to cloud storage...');
        
        $totalMigrated = 0;
        $totalErrors = 0;

        // Migrate Design images
        $this->info('Migrating Design images...');
        $designCount = $this->migrateDesignImages($cloudStorage, $dryRun);
        $totalMigrated += $designCount['migrated'];
        $totalErrors += $designCount['errors'];

        // Migrate ClothesType images
        $this->info('Migrating ClothesType images...');
        $clothesTypeCount = $this->migrateClothesTypeImages($cloudStorage, $dryRun);
        $totalMigrated += $clothesTypeCount['migrated'];
        $totalErrors += $clothesTypeCount['errors'];

        // Migrate WallPost attachments
        $this->info('Migrating WallPost attachments...');
        $wallPostCount = $this->migrateWallPostAttachments($cloudStorage, $dryRun);
        $totalMigrated += $wallPostCount['migrated'];
        $totalErrors += $wallPostCount['errors'];

        // Migrate QR Code files
        $this->info('Migrating QR Code files...');
        $qrCodeCount = $this->migrateQrCodeFiles($cloudStorage, $dryRun);
        $totalMigrated += $qrCodeCount['migrated'];
        $totalErrors += $qrCodeCount['errors'];

        // Migrate DALL-E images
        $this->info('Migrating DALL-E images...');
        $dalleCount = $this->migrateDalleImages($cloudStorage, $dryRun);
        $totalMigrated += $dalleCount['migrated'];
        $totalErrors += $dalleCount['errors'];

        $this->info("Migration completed!");
        $this->info("Total files migrated: {$totalMigrated}");
        $this->info("Total errors: {$totalErrors}");

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to actually migrate files.');
        }
    }

    private function migrateDesignImages(CloudStorageService $cloudStorage, bool $dryRun): array
    {
        $migrated = 0;
        $errors = 0;

        $designs = Design::whereNotNull('front_image_path')
            ->orWhereNotNull('back_image_path')
            ->orWhereNotNull('cover_image')
            ->get();

        foreach ($designs as $design) {
            $images = [
                'front_image_path' => $design->front_image_path,
                'back_image_path' => $design->back_image_path,
                'cover_image' => $design->cover_image,
            ];

            foreach ($images as $field => $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    try {
                        if (!$dryRun) {
                            $success = $cloudStorage->migrateFromLocal($path, $path);
                            if ($success) {
                                $design->update([$field => $path]);
                                $migrated++;
                                $this->line("✓ Migrated: {$path}");
                            } else {
                                $errors++;
                                $this->error("✗ Failed to migrate: {$path}");
                            }
                        } else {
                            $migrated++;
                            $this->line("Would migrate: {$path}");
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("✗ Error migrating {$path}: " . $e->getMessage());
                        Log::error("Migration error for {$path}: " . $e->getMessage());
                    }
                }
            }
        }

        return ['migrated' => $migrated, 'errors' => $errors];
    }

    private function migrateClothesTypeImages(CloudStorageService $cloudStorage, bool $dryRun): array
    {
        $migrated = 0;
        $errors = 0;

        $clothesTypes = ClothesType::whereNotNull('front_image')
            ->orWhereNotNull('back_image')
            ->get();

        foreach ($clothesTypes as $clothesType) {
            $images = [
                'front_image' => $clothesType->front_image,
                'back_image' => $clothesType->back_image,
            ];

            foreach ($images as $field => $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    try {
                        if (!$dryRun) {
                            $success = $cloudStorage->migrateFromLocal($path, $path);
                            if ($success) {
                                $clothesType->update([$field => $path]);
                                $migrated++;
                                $this->line("✓ Migrated: {$path}");
                            } else {
                                $errors++;
                                $this->error("✗ Failed to migrate: {$path}");
                            }
                        } else {
                            $migrated++;
                            $this->line("Would migrate: {$path}");
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("✗ Error migrating {$path}: " . $e->getMessage());
                        Log::error("Migration error for {$path}: " . $e->getMessage());
                    }
                }
            }
        }

        return ['migrated' => $migrated, 'errors' => $errors];
    }

    private function migrateWallPostAttachments(CloudStorageService $cloudStorage, bool $dryRun): array
    {
        $migrated = 0;
        $errors = 0;

        $wallPosts = WallPost::whereNotNull('attachment_path')->get();

        foreach ($wallPosts as $post) {
            if ($post->attachment_path && Storage::disk('public')->exists($post->attachment_path)) {
                try {
                    if (!$dryRun) {
                        $success = $cloudStorage->migrateFromLocal($post->attachment_path, $post->attachment_path);
                        if ($success) {
                            $migrated++;
                            $this->line("✓ Migrated: {$post->attachment_path}");
                        } else {
                            $errors++;
                            $this->error("✗ Failed to migrate: {$post->attachment_path}");
                        }
                    } else {
                        $migrated++;
                        $this->line("Would migrate: {$post->attachment_path}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Error migrating {$post->attachment_path}: " . $e->getMessage());
                    Log::error("Migration error for {$post->attachment_path}: " . $e->getMessage());
                }
            }
        }

        return ['migrated' => $migrated, 'errors' => $errors];
    }

    private function migrateQrCodeFiles(CloudStorageService $cloudStorage, bool $dryRun): array
    {
        $migrated = 0;
        $errors = 0;

        $qrCodes = QrCode::whereNotNull('file_path')->get();

        foreach ($qrCodes as $qrCode) {
            if ($qrCode->file_path && Storage::disk('public')->exists($qrCode->file_path)) {
                try {
                    if (!$dryRun) {
                        $success = $cloudStorage->migrateFromLocal($qrCode->file_path, $qrCode->file_path);
                        if ($success) {
                            $migrated++;
                            $this->line("✓ Migrated: {$qrCode->file_path}");
                        } else {
                            $errors++;
                            $this->error("✗ Failed to migrate: {$qrCode->file_path}");
                        }
                    } else {
                        $migrated++;
                        $this->line("Would migrate: {$qrCode->file_path}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Error migrating {$qrCode->file_path}: " . $e->getMessage());
                    Log::error("Migration error for {$qrCode->file_path}: " . $e->getMessage());
                }
            }
        }

        return ['migrated' => $migrated, 'errors' => $errors];
    }

    private function migrateDalleImages(CloudStorageService $cloudStorage, bool $dryRun): array
    {
        $migrated = 0;
        $errors = 0;

        // Get all files in the dalle-images directory
        $files = Storage::disk('public')->files('dalle-images');

        foreach ($files as $file) {
            try {
                if (!$dryRun) {
                    $success = $cloudStorage->migrateFromLocal($file, $file);
                    if ($success) {
                        $migrated++;
                        $this->line("✓ Migrated: {$file}");
                    } else {
                        $errors++;
                        $this->error("✗ Failed to migrate: {$file}");
                    }
                } else {
                    $migrated++;
                    $this->line("Would migrate: {$file}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error migrating {$file}: " . $e->getMessage());
                Log::error("Migration error for {$file}: " . $e->getMessage());
            }
        }

        return ['migrated' => $migrated, 'errors' => $errors];
    }
}