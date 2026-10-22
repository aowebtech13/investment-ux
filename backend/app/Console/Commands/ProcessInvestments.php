<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Investment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessInvestments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process investment profits and maturity based on return types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting investment processing...');

        // 1. Process Periodic Profits (Daily, Weekly, Monthly)
        $this->processPeriodicProfits();

        // 2. Process Matured Individual Investments
        $this->processMaturedIndividualInvestments();

        $this->info('Investment processing completed.');
    }

    private function processPeriodicProfits()
    {
        // Get all active investments
        $activeInvestments = Investment::where('status', 'active')
            ->with(['plan', 'user'])
            ->get();

        foreach ($activeInvestments as $investment) {
            if (!$investment->plan || !$investment->user) continue;

            $plan = $investment->plan;
            $now = Carbon::now();
            $lastProfitAt = $investment->last_profit_at ?: $investment->start_date;
            
            // If less than 23 hours have passed since last profit update, skip for this investment
            if ($lastProfitAt->diffInHours($now) < 23) {
                continue;
            }

            $this->distributeDailyProfit($investment);
        }
    }

    private function distributeDailyProfit($investment)
    {
        DB::transaction(function () use ($investment) {
            $plan = $investment->plan;
            $user = $investment->user;
            $now = Carbon::now();

            // Calculate profit for one full interval
            $intervalProfit = $investment->amount * ($plan->interest_rate / 100);
            
            $dailyProfit = 0;
            $shouldPayoutBalance = false;
            $intervalName = '';

            switch ($plan->return_type) {
                case 'daily':
                    $dailyProfit = $intervalProfit;
                    $shouldPayoutBalance = true;
                    $intervalName = 'Daily';
                    break;
                case 'weekly':
                    $dailyProfit = $intervalProfit / 7;
                    $daysSinceStart = $investment->start_date->diffInDays($now);
                    if ($daysSinceStart > 0 && $daysSinceStart % 7 == 0) {
                        $shouldPayoutBalance = true;
                        $intervalName = 'Weekly';
                    }
                    break;
                case 'monthly':
                    $dailyProfit = $intervalProfit / 30;
                    $daysSinceStart = $investment->start_date->diffInDays($now);
                    if ($daysSinceStart > 0 && $daysSinceStart % 30 == 0) {
                        $shouldPayoutBalance = true;
                        $intervalName = 'Monthly';
                    }
                    break;
                case 'end_of_term':
                    $dailyProfit = $intervalProfit / ($plan->duration_days ?: 30);
                    // No periodic payout to balance for end_of_term
                    break;
            }

            if ($dailyProfit > 0) {
                // 1. Accrue profit for visualization (Daily update)
                $investment->increment('profit', $dailyProfit);
                $investment->update(['last_profit_at' => $now]);
                
                // 2. Update user's total_profit stat (Daily update)
                $user->increment('total_profit', $dailyProfit);

                // 3. Payout to balance if interval reached
                if ($shouldPayoutBalance) {
                    // For daily, payout is the daily profit. 
                    // For weekly/monthly, we payout the full interval profit (which has been accrued daily in 'profit' field)
                    $payoutAmount = ($plan->return_type === 'daily') ? $dailyProfit : $intervalProfit;
                    
                    $user->increment('balance', $payoutAmount);

                    // Record transaction
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'profit',
                        'amount' => $payoutAmount,
                        'status' => 'completed',
                        'description' => "{$intervalName} ROI payout from Investment #{$investment->id} ({$plan->name})",
                    ]);

                    Log::info("{$intervalName} ROI payout distributed for investment #{$investment->id} to user #{$user->id}: +₦" . number_format($payoutAmount, 2));
                }
            }
        });
    }

    private function processMaturedIndividualInvestments()
    {
        $maturedInvestments = Investment::where('status', 'active')
            ->where('end_date', '<=', now())
            ->with(['user', 'plan'])
            ->get();

        foreach ($maturedInvestments as $investment) {
            DB::transaction(function () use ($investment) {
                $user = $investment->user;
                $plan = $investment->plan;

                // For end_of_term, we give the accrued profit now. 
                // For others, periodic profit was already given to balance.
                $maturityProfit = 0;
                if ($plan->return_type === 'end_of_term') {
                    $maturityProfit = $investment->profit;
                }
                
                $totalReturn = $investment->amount + $maturityProfit;
                $user->increment('balance', $totalReturn);

                if ($maturityProfit > 0) {
                    // Note: total_profit and investment->profit were already incremented daily
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'profit',
                        'amount' => $maturityProfit,
                        'status' => 'completed',
                        'description' => "Maturity ROI payout from Investment #{$investment->id} ({$plan->name})",
                    ]);
                }

                $investment->update([
                    'status' => 'completed',
                    'last_profit_at' => now()
                ]);

                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'principal_return',
                    'amount' => $investment->amount,
                    'status' => 'completed',
                    'description' => "Investment #{$investment->id} matured. Principal returned.",
                ]);

                Log::info("Individual Investment #{$investment->id} matured. Total returned: ₦" . number_format($totalReturn, 2) . " to user #{$user->id}");
            });
        }
    }
}
