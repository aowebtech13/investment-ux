<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralsBackfill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --dry-run: does not credit bonuses, only prints what would be credited.
     * --limit: cap number of deposits processed.
     */
    protected $signature = 'referrals:backfill {--dry-run : Do not create any referral bonuses} {--limit=500 : Max number of deposit transactions to process}';

    protected $description = 'Backfill missing referral_bonus transactions for deposits that were already completed before referral-crediting fixes.';

    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $limit = (int)$this->option('limit');
        if ($limit <= 0) {
            $limit = 500;
        }

        $this->info('Starting referral backfill' . ($dryRun ? ' (dry-run)' : '') . '...');
        $this->line('Limit: ' . $limit);

        // Consider only deposit transactions that are already completed.
        $deposits = Transaction::query()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'user_id', 'amount']);

        $processed = 0;
        $credited = 0;
        $skippedMissingSponsor = 0;
        $skippedAlreadyCredited = 0;
        $skippedNoDepositUser = 0;
        $errors = 0;

        foreach ($deposits as $deposit) {
            $processed++;

            $depositUser = User::query()->where('id', $deposit->user_id)->first(['id', 'referred_by']);
            if (!$depositUser) {
                $skippedNoDepositUser++;
                $this->warn("[skip] Deposit #{$deposit->id}: deposit user not found (user_id={$deposit->user_id})");
                continue;
            }

            $sponsor = $depositUser->referrer()->first(['id', 'referred_by']); // referrer() is the sponsor relation
            if (!$sponsor) {
                $skippedMissingSponsor++;
                $this->warn("[skip] Deposit #{$deposit->id}: sponsor not found (referred_by is empty)");
                continue;
            }

            // If a completed referral_bonus exists for this sponsor and it appears to be tied to this deposit,
            // then skip.
            // We mirror the description format created in User::creditReferralBonus().
            $depositAmount = (float)$deposit->amount;

            // Ensure we only match on the deposit id embedded in description.
            // creditReferralBonus description: "Referral bonus from {$this->id} (deposited {$depositAmount})"
            // It does NOT include deposit transaction id, so we need a safer uniqueness rule.
            // We check sponsor's existing referral bonuses with matching description that contains the depositUser id.
            $alreadyCredited = Transaction::query()
                ->where('user_id', $sponsor->id)
                ->where('type', 'referral_bonus')
                ->where('status', 'completed')
                ->whereRaw('description like ?', ['%Referral bonus from ' . $depositUser->id . '%'])
                ->where('amount', $depositAmount)
                ->exists();


            if ($alreadyCredited) {
                $skippedAlreadyCredited++;
                $this->line("[skip] Deposit #{$deposit->id}: referral bonus already exists");
                continue;
            }

            if ($dryRun) {
                $this->info("[dry-run] Would credit sponsor #{$sponsor->id} referral bonus: ₦" . number_format($depositAmount, 2));
                $credited++;
                continue;
            }

// Referral bonuses are now flat $0.20 per signup, credited at
            // registration. Deposit-based referral crediting has been removed,
            // so there is nothing to backfill for deposits here.
            $credited++;
        }

        $this->info('Backfill complete');
        $this->line("Processed: {$processed}");
        $this->line("Would credit/credited: {$credited}");
        $this->line("Skipped (missing sponsor): {$skippedMissingSponsor}");
        $this->line("Skipped (already credited): {$skippedAlreadyCredited}");
        $this->line("Skipped (deposit user missing): {$skippedNoDepositUser}");

        if ($dryRun) {
            $this->warn('DRY-RUN mode was enabled; no referral credits were created.');
        }

        if ($errors > 0) {
            $this->warn("Errors: {$errors}");
        }

        return 0;
    }
}

