<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\WholesaleQuotation;
use Illuminate\Console\Command;

class ExpireWholesaleQuotations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wholesale:expire-quotations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire wholesale quotations with status SENT whose valid_until date is past';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->toDateString();
        $expiredCount = 0;

        $quotations = WholesaleQuotation::where('status', 'SENT')
            ->whereDate('valid_until', '<', $today)
            ->get();

        foreach ($quotations as $quote) {
            $quote->status = 'EXPIRED';
            $quote->save();

            AuditLog::create([
                'user_id' => null,
                'module_name' => 'Retail & Wholesale Sales',
                'action' => 'AUTO_EXPIRE_QUOTATION',
                'record_table' => 'wholesale_quotations',
                'record_id' => $quote->quotation_id,
                'details_json' => [
                    'quote_no' => $quote->quote_no,
                    'valid_until' => $quote->valid_until ? $quote->valid_until->toDateString() : null,
                    'expired_at' => now()->toIso8601String(),
                ],
                'ip_address' => '127.0.0.1',
            ]);

            $expiredCount++;
        }

        $this->info("Expired {$expiredCount} quotation(s).");

        return Command::SUCCESS;
    }
}
