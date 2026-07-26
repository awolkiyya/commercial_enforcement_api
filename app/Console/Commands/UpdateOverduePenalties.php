<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Penalty;

class UpdateOverduePenalties extends Command
{
    /**
     * Command name
     */
    protected $signature = 'penalties:update-overdue';

    /**
     * Description
     */
    protected $description = 'Mark penalties as overdue when due_date has passed';

    /**
     * Execute command
     */
    public function handle()
    {
        Log::info('PENALTY_OVERDUE_CHECK_STARTED');

        try {
            $now = now()->toDateString();

            /**
             * Only penalties that:
             * - are not already final states
             * - have due_date passed
             */
            $query = Penalty::query()
                ->whereNotIn('status', [
                    'overdue',
                    'paid',
                    'cancelled',
                    'escalated'
                ])
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now);

            $count = $query->count();

            if ($count === 0) {
                Log::info('PENALTY_OVERDUE_CHECK_NO_RECORDS');
                $this->info('No penalties to mark as overdue.');
                return Command::SUCCESS;
            }

            $updated = $query->update([
                'status' => 'overdue',
                'updated_at' => now(),
            ]);

            Log::info('PENALTY_OVERDUE_UPDATED', [
                'updated_count' => $updated,
                'date' => $now,
            ]);

            $this->info("Updated {$updated} penalties to OVERDUE.");

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            Log::error('PENALTY_OVERDUE_CHECK_FAILED', [
                'error' => $e->getMessage(),
            ]);

            $this->error('Failed to update overdue penalties.');

            return Command::FAILURE;
        }
    }
}