<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Mail\WithdrawalSuccessMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class WithdrawalController extends Controller
{
    public function index()
    {
        return response()->json(Auth::user()->withdrawals()->latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5',
            'method' => 'required|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'account_type' => 'nullable|string|in:checking,savings',
        ]);

        $user = Auth::user();

        // If the request includes bank details, persist them to the user
        // profile so they are available for future withdrawals.
        if ($request->filled('bank_name')) {
            $user->update([
                'bank_name' => $request->bank_name,
                'bank_account_holder' => $request->bank_account_holder ?? $user->bank_account_holder,
                'bank_account_number' => $request->bank_account_number ?? $user->bank_account_number,
                'account_type' => $request->account_type ?? $user->account_type,
            ]);
        }

        // Ensure the user has saved withdrawal/bank details before allowing
        // a withdrawal request.
        if (!$user->bank_name || !$user->bank_account_holder || !$user->bank_account_number) {
            return response()->json([
                'message' => 'Please update your account withdrawal details before requesting a withdrawal.'
            ], 422);
        }

        if ($user->withdrawal_date && now()->lt($user->withdrawal_date)) {
            return response()->json([
                'message' => 'You are not eligible for withdrawal until ' . \Carbon\Carbon::parse($user->withdrawal_date)->format('M d, Y') . '.'
            ], 422);
        }

        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        return DB::transaction(function () use ($user, $request) {
            // Deduct from balance immediately to "lock" the funds
            $user->decrement('balance', $request->amount);

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'method' => $request->method,
                'status' => 'pending',
                'details' => $request->details ?? null,
                // Snapshot the user's saved bank details so the admin
                // can review them even if the user changes them later.
                'bank_name' => $user->bank_name,
                'bank_account_holder' => $user->bank_account_holder,
                'bank_account_number' => $user->bank_account_number,
                'account_type' => $user->account_type,
            ]);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal_request',
                'amount' => -$request->amount,
                'status' => 'pending',
                'description' => "Withdrawal request via {$request->method}",
            ]);

            // Send a congratulatory email to the user (best-effort; never
            // blocks the withdrawal request from completing).
            try {
                Mail::to($user->email)->send(
                    new WithdrawalSuccessMail($user, $withdrawal)
                );
            } catch (\Exception $e) {
                Log::warning('Withdrawal success email failed to send', [
                    'user_id' => $user->id,
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'message' => 'Withdrawal request submitted successfully.',
                'withdrawal' => $withdrawal,
                'user' => $user->fresh(),
            ]);
        });
    }

    public function downloadInvoice($id)
    {
        try {
            $withdrawal = Auth::user()->withdrawals()->findOrFail($id);

            $pdf = Pdf::loadView('withdrawals.invoice', [
                'withdrawal' => $withdrawal,
                'user' => Auth::user(),
            ]);

            return $pdf->download("withdrawal-invoice-{$withdrawal->id}.pdf");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Withdrawal not found.'], 404);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Invoice generation failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json(['message' => 'Unable to generate invoice. Please try again later.'], 500);
        }
    }
}
