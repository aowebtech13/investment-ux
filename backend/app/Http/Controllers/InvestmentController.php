<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\InvestmentPlan;
use App\Models\Investment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InvestmentController extends Controller
{
    public function getPlans(Request $request)
    {
        $query = InvestmentPlan::where('is_active', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->get());
    }

    public function invest(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:investment_plans,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();
        $plan = InvestmentPlan::findOrFail($request->plan_id);

        if ((float)$user->balance < (float)$request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 422);
        }

        return DB::transaction(function () use ($user, $plan, $request) {
            // Deduct from balance
            $user->decrement('balance', $request->amount);
            $user->increment('total_invested', $request->amount);

            // Create investment
            $investment = Investment::create([
                'user_id' => $user->id,
                'investment_plan_id' => $plan->id,
                'amount' => $request->amount,
                'start_date' => now(),
                'end_date' => now()->addDays($plan->duration_days),
                'status' => 'active',
                'last_profit_at' => now(),
            ]);

            // Create transaction
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'investment',
                'amount' => -$request->amount,
                'status' => 'completed',
                'description' => "Invested in {$plan->name}",
            ]);

            return response()->json([
                'message' => 'Investment successful',
                'investment' => $investment->load('plan'),
                'user' => $user->fresh(),
            ]);
        });
    }

    public function getDashboardData()
    {
        $user = Auth::user()->load(['investments.plan', 'transactions' => function($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json([
            'stats' => [
                'balance' => $user->balance,
                'total_profit' => $user->total_profit,
                'total_invested' => $user->total_invested,
                'active_investments_count' => $user->investments()->where('status', 'active')->count(),

                // Referral earnings credited to this user.
                'total_referral_earnings' => Transaction::where('user_id', $user->id)
                    ->where('type', 'referral_bonus')
                    ->where('status', 'completed')
                    ->sum('amount'),

                // Total amount withdrawn by this user.
                'total_withdrawn' => $user->withdrawals()->sum('amount'),

                // Total deposit amount by this user.
                'total_deposits' => $user->transactions()
                    ->where('type', 'deposit')
                    ->where('status', 'completed')
                    ->sum('amount'),

                // Total transaction count by this user.
                'total_transaction_count' => $user->transactions()->count(),
            ],
            'recent_transactions' => $user->transactions,
            'active_investments' => $user->investments()->where('status', 'active')->with('plan')->get(),
        ]);
    }


    public function getTransactions(Request $request)
    {
        $user = Auth::user();
        $query = $user->transactions()->latest();

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('type', 'like', "%{$request->search}%")
                  ->orWhere('id', 'like', "%{$request->search}%");
            });
        }

        // Filter by type
        if ($request->type && $request->type !== 'all') {
            $query->where('type', 'like', "%{$request->type}%");
        }

        $transactions = $query->paginate(20);

        // Calculate totals for all time (filtered by type/search if applicable)
        $statsQuery = $user->transactions();
        if ($request->search) {
            $statsQuery->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('type', 'like', "%{$request->search}%")
                  ->orWhere('id', 'like', "%{$request->search}%");
            });
        }
        if ($request->type && $request->type !== 'all') {
            $statsQuery->where('type', 'like', "%{$request->type}%");
        }

        $inflows = (clone $statsQuery)->where('amount', '>', 0)->sum('amount');
        $outflows = (clone $statsQuery)->where('amount', '<', 0)->sum('amount');

        return response()->json([
            'transactions' => $transactions,
            'stats' => [
                'total_inflows' => $inflows,
                'total_outflows' => abs($outflows),
                'net_flow' => $inflows + $outflows,
            ]
        ]);
    }

    /**
     * Download the user's transaction history as a CSV file.
     * Supports the same search and type filters as getTransactions.
     */
    public function downloadTransactions(Request $request)
    {
        $user = Auth::user();
        $query = $user->transactions()->latest();

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('type', 'like', "%{$request->search}%")
                  ->orWhere('id', 'like', "%{$request->search}%");
            });
        }

        // Filter by type
        if ($request->type && $request->type !== 'all') {
            $query->where('type', 'like', "%{$request->type}%");
        }

        $transactions = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transaction-history-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Date', 'Type', 'Amount', 'Status', 'Method', 'Description']);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->id,
                    $tx->created_at ? $tx->created_at->format('Y-m-d H:i:s') : '',
                    $tx->type,
                    $tx->amount,
                    $tx->status,
                    $tx->method ?? '',
                    $tx->description ?? '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function getInvestments()
    {
        $user = Auth::user();
        return response()->json($user->investments()->with('plan')->latest()->get());
    }

    public function cancelInvestment(Request $request, $id)
    {
        $user = Auth::user();
        $investment = Investment::where('user_id', $user->id)->findOrFail($id);

        if ($investment->status !== 'active') {
            return response()->json(['message' => 'Only active investments can be cancelled.'], 422);
        }

        return DB::transaction(function () use ($investment, $user) {
            $investment->status = 'cancelled';
            $investment->profit = 0; // Explicitly zero out any accrued visualization profit
            $investment->save();

            // Return 90% of principal to user balance
            $refundAmount = $investment->amount * 0.9;
            $penaltyAmount = $investment->amount * 0.1;
            
            $user->increment('balance', $refundAmount);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'cancellation_refund',
                'amount' => $refundAmount,
                'status' => 'completed',
                'description' => "User cancelled investment #{$investment->id} (10% penalty applied: -\${$penaltyAmount})",
            ]);

            return response()->json([
                'message' => "Investment cancelled successfully. 90% Refunded: \${$refundAmount}",
                'user' => $user->fresh()
            ]);
        });
    }
}
