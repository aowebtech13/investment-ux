<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Investment;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Display admin dashboard stats.
     */
    public function index()
    {
        return response()->json([
            'stats' => [
                'total_users' => User::count(),
                'total_investments' => Investment::count(),
                'total_invested_amount' => Investment::sum('amount'),
                'pending_withdrawals' => Withdrawal::where('status', 'pending')->count(),
            ],
            'message' => 'Admin dashboard data retrieved successfully.'
        ]);
    }

    public function getAllUsers()
    {
        return response()->json(User::where('role', '!=', 'admin')->latest()->get());
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot delete admin user.'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function getAllInvestments()
    {
        return response()->json(Investment::with(['user', 'plan'])->latest()->get());
    }

    public function cancelInvestment($id)
    {
        $investment = Investment::findOrFail($id);
        if ($investment->status !== 'active') {
            return response()->json(['message' => 'Only active investments can be cancelled.'], 422);
        }

        return DB::transaction(function () use ($investment) {
            $investment->status = 'cancelled';
            $investment->save();

            // Return 90% of principal to user balance
            $refundAmount = $investment->amount * 0.9;
            $penaltyAmount = $investment->amount * 0.1;

            $investment->user->increment('balance', $refundAmount);

            Transaction::create([
                'user_id' => $investment->user_id,
                'type' => 'cancellation_refund',
                'amount' => $refundAmount,
                'status' => 'completed',
                'description' => "Refund from cancelled investment #{$investment->id} (10% penalty applied: -\${$penaltyAmount})",
            ]);

            return response()->json(['message' => "Investment cancelled. \${$refundAmount} (90%) refunded to user balance."]);
        });
    }

    public function extendInvestmentDate(Request $request, $id)
    {
        $request->validate([
            'end_date' => 'required|date|after:today',
        ]);

        $investment = Investment::findOrFail($id);
        $investment->end_date = $request->end_date;
        $investment->save();

        return response()->json(['message' => 'Investment end date extended successfully.', 'investment' => $investment]);
    }

    public function getAllWithdrawals()
    {
        return response()->json(Withdrawal::with('user')->latest()->get());
    }

    public function getAllDeposits()
    {
        return response()->json(Transaction::with('user')->where('type', 'deposit')->latest()->get());
    }

    public function updateDepositStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:completed,failed',
            'admin_note' => 'nullable|string'
        ]);

        $transaction = Transaction::where('type', 'deposit')->findOrFail($id);
        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Only pending deposits can be updated.'], 422);
        }

        return DB::transaction(function () use ($transaction, $request) {
            $transaction->status = $request->status;
            if ($request->admin_note) {
                $transaction->description = ($transaction->description ? $transaction->description . " | " : "") . "Admin Note: " . $request->admin_note;
            }
            $transaction->save();

if ($request->status === 'completed') {
                // Add the amount to user balance
                $transaction->user->increment('balance', $transaction->amount);
            }

            return response()->json(['message' => "Deposit {$request->status} successfully."]);
        });
    }

    public function updateWithdrawalStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'pending') {
            return response()->json(['message' => 'Only pending withdrawals can be updated.'], 422);
        }

        return DB::transaction(function () use ($withdrawal, $request) {
            $withdrawal->status = $request->status;
            if ($request->status === 'rejected') {
                $withdrawal->rejection_reason = $request->rejection_reason;
                // Refund the amount to user balance if it was deducted upon request
                $withdrawal->user->increment('balance', $withdrawal->amount);
            }
            $withdrawal->save();

            Transaction::create([
                'user_id' => $withdrawal->user_id,
                'type' => 'withdrawal_' . $request->status,
                'amount' => $request->status === 'approved' ? -$withdrawal->amount : $withdrawal->amount,
                'status' => 'completed',
                'description' => "Withdrawal request #{$withdrawal->id} was {$request->status}",
            ]);

            return response()->json(['message' => "Withdrawal {$request->status} successfully."]);
        });
    }

    public function addUserMoney(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:deposit,bonus,adjustment,profit',
            'description' => 'nullable|string'
        ]);

        $user = User::findOrFail($id);

        return DB::transaction(function () use ($user, $request) {
            $user->increment('balance', $request->amount);

            Transaction::create([
                'user_id' => $user->id,
                'type' => $request->type,
                'amount' => $request->amount,
                'status' => 'completed',
                'method' => 'admin_added',
                'description' => $request->description ?? "Admin added \${$request->amount} ({$request->type})",
            ]);

            return response()->json(['message' => "Successfully added \${$request->amount} to {$user->name}'s balance."]);
        });
    }
}
