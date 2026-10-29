<?php

namespace App\Console\Commands;

use App\Services\Permit\PermitVerificationService;
use Illuminate\Console\Command;

class PurgeOrphanScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permits:purge-orphan-screenshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge unattached permit screenshot uploads older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(PermitVerificationService $service): int
    {
        $this->info('Scanning private storage for orphaned permit screenshots older than 24 hours...');
        $purgedCount = $service->purgeOrphanScreenshots();
        $this->info("Purged {$purgedCount} orphaned screenshot(s).");

        return Command::SUCCESS;
    }
}
