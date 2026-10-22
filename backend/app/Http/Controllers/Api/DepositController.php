<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $deposits = $request->user()->transactions()
            ->where('type', 'deposit')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($deposits);
    }

    public function verifyPaystack(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'amount' => 'required|numeric|min:5000'
        ]);

        $reference = $request->reference;
        $amount = $request->amount;
        $secretKey = config('services.paystack.secret_key');

        if (!$secretKey) {
            Log::error("Paystack Secret Key is missing in configuration.");
            return response()->json(['message' => 'Payment provider configuration error'], 500);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Cache-Control' => 'no-cache',
            ])->timeout(20)->get(config('services.paystack.payment_url') . '/transaction/verify/' . $reference);

            if (!$response->successful()) {
                Log::warning("Paystack verification request failed for reference: {$reference}. Status: " . $response->status());
                return response()->json(['message' => 'Unable to verify payment with provider'], 400);
            }

            $data = $response->json();

            if (!isset($data['status']) || !$data['status'] || $data['data']['status'] !== 'success') {
                Log::warning("Invalid Paystack payment status for reference: {$reference}", $data);
                return response()->json(['message' => 'Payment was not successful or invalid'], 400);
            }

            // Verify the currency is NGN
            if ($data['data']['currency'] !== 'NGN') {
                Log::error("Paystack currency mismatch for reference: {$reference}. Expected NGN, got: " . $data['data']['currency']);
                return response()->json(['message' => 'Invalid payment currency'], 400);
            }

            return DB::transaction(function () use ($request, $reference, $amount, $data) {
                // Check if reference already exists to prevent duplicate processing (Lock for update)
                $existingTransaction = Transaction::where('reference', $reference)->lockForUpdate()->first();
                if ($existingTransaction) {
                    return response()->json(['message' => 'Transaction already processed'], 400);
                }

                // Verify the amount paid matches the expected amount (converted to kobo)
                $expectedAmountKobo = $amount * 100;
                $paidAmountKobo = $data['data']['amount'];

                // Allow for small discrepancy (less than 1 NGN) due to rounding
                if (abs($paidAmountKobo - $expectedAmountKobo) > 100) {
                    Log::error("Paystack amount mismatch for reference: {$reference}. Expected: {$expectedAmountKobo}, Paid: {$paidAmountKobo}");
                    return response()->json(['message' => 'Payment amount discrepancy detected'], 400);
                }

                $user = $request->user();

                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'deposit',
                    'amount' => $amount,
                    'status' => 'completed',
                    'method' => 'Paystack',
                    'reference' => $reference,
                    'description' => 'Paystack deposit verified: ' . $reference,
                ]);

// Increment user balance
                $user->increment('balance', $amount);

                Log::info("Paystack deposit successful for User ID: {$user->id}, Amount: ₦{$amount}, Ref: {$reference}");

                return response()->json([
                    'message' => 'Payment verified and deposit successful',
                    'transaction' => $transaction,
                    'balance' => $user->fresh()->balance
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Paystack Verification Exception: " . $e->getMessage(), [
                'reference' => $reference,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'An error occurred during payment verification'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5000',
            'method' => 'required|string',
            'receipt' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'description' => 'nullable|string'
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'type' => 'deposit',
            'amount' => $request->amount,
            'status' => 'pending',
            'method' => $request->method,
            'reference' => 'DEP-' . strtoupper(Str::random(10)),
            'description' => $request->description,
            'receipt_path' => $receiptPath,
        ]);

        return response()->json([
            'message' => 'Deposit request submitted successfully. Waiting for admin approval.',
            'transaction' => $transaction
        ]);
    }

    public function handleWebhook(Request $request)
    {
        $secretKey = config('services.paystack.secret_key');
        
        // Validate Paystack Signature
        if (!$request->header('x-paystack-signature') || $request->header('x-paystack-signature') !== hash_hmac('sha512', $request->getContent(), $secretKey)) {
            Log::warning("Invalid Paystack webhook signature.");
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->json()->all();

        if ($event['event'] === 'charge.success') {
            $data = $event['data'];
            $reference = $data['reference'];
            $paidAmountKobo = $data['amount'];
            $email = $data['customer']['email'];
            
            // Extract amount from Paystack (in kobo)
            $amount = $paidAmountKobo / 100;

            return DB::transaction(function () use ($reference, $amount, $email) {
                $existingTransaction = Transaction::where('reference', $reference)->lockForUpdate()->first();
                if ($existingTransaction) {
                    return response()->json(['message' => 'Processed'], 200);
                }

                $user = \App\Models\User::where('email', $email)->first();
                if (!$user) {
                    Log::error("User not found for Paystack webhook: {$email}");
                    return response()->json(['message' => 'User not found'], 404);
                }

                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'deposit',
                    'amount' => $amount,
                    'status' => 'completed',
                    'method' => 'Paystack',
                    'reference' => $reference,
                    'description' => 'Paystack deposit verified via Webhook: ' . $reference,
                ]);

$user->increment('balance', $amount);

                Log::info("Paystack deposit successful via Webhook for User ID: {$user->id}, Amount: ₦{$amount}, Ref: {$reference}");

                return response()->json(['message' => 'Success'], 200);
            });
        }

        return response()->json(['message' => 'Event ignored'], 200);
    }
}
