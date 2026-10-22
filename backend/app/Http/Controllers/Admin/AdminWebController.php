<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Investment;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Mail\AdminResetToken;
use App\Mail\WithdrawalStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use Illuminate\Support\Str;

class AdminWebController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_investments' => Investment::count(),
            'total_invested_amount' => Investment::sum('amount'),
            'pending_withdrawals' => Withdrawal::where('status', 'pending')->count(),
        ];
        return view('admin.dashboard', compact('stats'));
    }

    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            if (Auth::user()->role !== 'admin') {
                Auth::logout();
                return back()->with('error', 'Unauthorized access.');
            }
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->with('error', 'The provided credentials do not match our records.');
    }

    public function showForgotForm()
    {
        return view('admin.auth.forgot');
    }

    public function sendResetToken(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        
        $user = User::where('email', $request->email)->where('role', 'admin')->first();
        if (!$user) {
            return back()->with('error', 'Admin user not found.');
        }

        $token = sprintf("%06d", mt_rand(1, 999999));
        
        DB::table('admin_password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'expires_at' => now()->addMinutes(15),
                'created_at' => now()
            ]
        );

        Mail::to($request->email)->send(new AdminResetToken($token));

        return redirect()->route('admin.password.reset')->with('success', 'A 6-digit reset token has been sent to your email.');
    }

    public function showResetForm()
    {
        return view('admin.auth.reset');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = DB::table('admin_password_resets')->where('email', $request->email)->first();

        if (!$reset || now()->greaterThan($reset->expires_at) || !Hash::check($request->token, $reset->token)) {
            return back()->with('error', 'Invalid or expired token.');
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('admin_password_resets')->where('email', $request->email)->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Password reset successful.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function users(Request $request)
    {
        $search = $request->input('search');
        
        $users = User::where('role', '!=', 'admin')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('Nex_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(50);

        return view('admin.users', compact('users', 'search'));
    }

public function showUser($id)
    {
        $user = User::with(['transactions' => function($query) {
            $query->latest();
        }, 'investments.plan', 'referrals' => function($query) {
            $query->latest();
        }])->findOrFail($id);
        
        return view('admin.user_show', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'balance' => 'required|numeric|min:0',
            'level' => 'nullable|string|in:' . implode(',', array_keys(User::LEVELS)),
            'withdrawal_date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $oldBalance = (float) $user->balance;
        $newBalance = (float) $request->balance;

        if ($oldBalance != $newBalance && !$request->description) {
            return back()->with('error', 'A description is required when manually adjusting the account balance.');
        }

        DB::transaction(function () use ($user, $request, $oldBalance, $newBalance) {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'balance' => $request->balance,
                'withdrawal_date' => $request->withdrawal_date,
            ];

            // Update account level if provided and valid
            if ($request->filled('level') && array_key_exists($request->level, User::LEVELS)) {
                $updateData['level'] = $request->level;
            }

            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
                }

                $path = $request->file('avatar')->store('avatars', 'public');
                $updateData['avatar'] = $path;
            }

            $user->update($updateData);

            if ($oldBalance != $newBalance) {
                $diff = $newBalance - $oldBalance;
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => $diff > 0 ? 'admin_credit' : 'admin_debit',
                    'amount' => $diff,
                    'status' => 'completed',
                    'description' => $request->description ?? "Balance manually adjusted by administrator. (" . ($diff > 0 ? "+" : "") . "$diff)",
                ]);
            }
        });

        return back()->with('success', 'User details updated successfully.');
    }

    public function transactions()
    {
        $transactions = Transaction::with('user')->latest()->paginate(50);
        return view('admin.transactions', compact('transactions'));
    }

    public function fundUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit,debit',
            'description' => 'required|string|max:255',
        ]);

        $amount = $request->type === 'credit' ? $request->amount : -$request->amount;

        DB::transaction(function () use ($user, $amount, $request) {
            $user->increment('balance', $amount);
            
            Transaction::create([
                'user_id' => $user->id,
                'type' => $request->type === 'credit' ? 'admin_credit' : 'admin_debit',
                'amount' => $amount,
                'status' => 'completed',
                'description' => $request->description,
            ]);
        });

        return back()->with('success', 'User wallet updated successfully.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot delete admin user.');
        }
        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    /**
     * Switch a user's account level.
     */
    public function updateUserLevel(Request $request, $id)
    {
        $request->validate([
            'level' => 'required|string|in:' . implode(',', array_keys(User::LEVELS)),
        ]);

        $user = User::findOrFail($id);

        $oldLevel = $user->level;
        $user->level = $request->level;
        $user->save();

        return back()->with('success', "{$user->name}'s account level changed from " . ($oldLevel ? ucfirst($oldLevel) : 'N/A') . " to " . User::LEVELS[$request->level] . ".");
    }

    public function investments()
    {
        $investments = Investment::with(['user', 'plan'])->latest()->get();
        return view('admin.investments', compact('investments'));
    }

    public function cancelInvestment($id)
    {
        $investment = Investment::findOrFail($id);
        if ($investment->status !== 'active') {
            return back()->with('error', 'Only active investments can be cancelled.');
        }

        DB::transaction(function () use ($investment) {
            $investment->status = 'cancelled';
            $investment->profit = 0; // No profit share for leaving before maturity
            $investment->save();

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
        });

        return back()->with('success', 'Investment cancelled. 90% of principal refunded to user.');
    }

    public function extendInvestment(Request $request, $id)
    {
        $request->validate(['end_date' => 'required|date|after:today']);
        $investment = Investment::findOrFail($id);
        $investment->end_date = $request->end_date;
        $investment->save();

        return back()->with('success', 'Investment end date extended.');
    }

    public function withdrawals(Request $request)
    {
        $status = $request->query('status', 'pending');
        $query = Withdrawal::with('user')->latest();
        
        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }
        
        $withdrawals = $query->paginate(50);
        return view('admin.withdrawals', compact('withdrawals', 'status'));
    }

    public function deposits(Request $request)
    {
        $status = $request->query('status', 'pending');
        $query = Transaction::where('type', 'deposit')->with('user')->latest();
        
        if (in_array($status, ['pending', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }
        
        $deposits = $query->paginate(50);
        return view('admin.deposits', compact('deposits', 'status'));
    }

    public function updateDeposit(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:completed,cancelled',
        ]);

        $transaction = Transaction::findOrFail($id);
        if ($transaction->type !== 'deposit' || $transaction->status !== 'pending') {
            return back()->with('error', 'Only pending deposits can be updated.');
        }

        DB::transaction(function () use ($transaction, $request) {
            $transaction->status = $request->status;
            if ($request->status === 'completed') {
                $transaction->user->increment('balance', $transaction->amount);
            }
            $transaction->save();
        });

        return back()->with('success', "Deposit {$request->status} successfully.");
    }

    public function updateWithdrawal(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be updated.');
        }

        DB::transaction(function () use ($withdrawal, $request) {
            $withdrawal->status = $request->status;
            if ($request->status === 'rejected') {
                $withdrawal->rejection_reason = $request->rejection_reason;
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

            // Notify the user by email about the withdrawal status change.
            try {
                Mail::to($withdrawal->user->email)->send(
                    new WithdrawalStatusMail(
                        $withdrawal->user,
                        $withdrawal,
                        $request->status,
                        $request->rejection_reason
                    )
                );
            } catch (\Exception $e) {
                Log::warning('Withdrawal status email failed to send', [
                    'withdrawal_id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return back()->with('success', "Withdrawal {$request->status} successfully.");
    }

    public function plans()
    {
        $plans = \App\Models\InvestmentPlan::latest()->get();
        return view('admin.plans', compact('plans'));
    }

    public function createPlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:Salary,Earnings,Investment Tier',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'return_type' => 'required|string|in:daily,weekly,monthly,end_of_term',
        ]);

        \App\Models\InvestmentPlan::create($request->all());

        return back()->with('success', 'Investment plan created successfully.');
    }

    public function updatePlan(Request $request, $id)
    {
        $plan = \App\Models\InvestmentPlan::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:Salary,Earnings,Investment Tier',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'return_type' => 'required|string|in:daily,weekly,monthly,end_of_term',
            'is_active' => 'required|boolean',
        ]);

        $plan->update($request->all());

        return back()->with('success', 'Investment plan updated successfully.');
    }

    public function deletePlan($id)
    {
        $plan = \App\Models\InvestmentPlan::findOrFail($id);
        
        // Check if there are any investments in this plan
        if ($plan->investments()->exists()) {
            return back()->with('error', 'Cannot delete plan because it has active investments. Deactivate it instead.');
        }

        $plan->delete();

        return back()->with('success', 'Investment plan deleted successfully.');
    }

    /**
     * Display all products in the admin panel.
     */
    public function products()
    {
        $products = Product::latest()->get();
        return view('admin.products', compact('products'));
    }

    /**
     * Store a newly created product.
     */
    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subtitle' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $data = $request->only([
            'name',
            'category',
            'subtitle',
            'description',
            'price',
            'old_price',
            'brand',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return back()->with('success', 'Product created successfully.');
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subtitle' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $data = $request->only([
            'name',
            'category',
            'subtitle',
            'description',
            'price',
            'old_price',
            'brand',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return back()->with('success', 'Product updated successfully.');
    }

    /**
     * Delete a product.
     */
    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        // Delete associated image if it exists
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }
}
