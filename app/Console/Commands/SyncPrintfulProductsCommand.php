<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncPrintfulProducts;

class SyncPrintfulProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printful:sync-products {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all products from Printful to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Printful products sync...');

        try {
            // Dispatch the sync job
            SyncPrintfulProducts::dispatch();

            $this->info('Printful sync job has been queued successfully!');
            $this->info('You can monitor the progress in the logs.');
            
            if (!$this->option('force')) {
                $this->info('Note: Use --force to sync even if products were recently synced.');
            }

        } catch (\Exception $e) {
            $this->error('Failed to queue Printful sync job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
