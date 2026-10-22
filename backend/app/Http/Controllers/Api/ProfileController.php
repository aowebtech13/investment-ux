<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Transaction;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        
        // Ensure user has an LXP ID if missing (e.g. old accounts)
        if (!$user->Nex_id) {
            do {
                $id = 'LXP' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (\App\Models\User::where('Nex_id', $id)->exists());
            
            $user->Nex_id = $id;
            $user->save();
        }

        $user->load('investments');
        return response()->json([
            'user' => $user
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|alpha_dash|unique:users,username,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'phone']);

        // Only update username if provided
        if ($request->filled('username')) {
            $data['username'] = $request->username;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    public function updateWithdrawalDetails(Request $request)
    {
        $user = Auth::user();

$request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_holder' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:50',
            'account_type' => 'required|string|in:checking,savings',
        ]);

        $user->update($request->only([
            'bank_name',
            'bank_account_holder',
            'bank_account_number',
            'account_type',
        ]));

        return response()->json([
            'message' => 'Withdrawal details updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Get the daily login bonus status for the authenticated user.
     */
public function getDailyBonusStatus()
    {
        $user = Auth::user();

        return response()->json([
            'amount' => $user->dailyLoginBonusAmount(),
            'claimed_today' => $user->hasClaimedDailyLoginBonusToday(),
            'balance' => $user->balance,
            'has_plan' => $user->hasPlan(),
            'level' => $user->level,
            'level_label' => $user->level_label,
        ]);
    }

    /**
     * Claim the daily login bonus for the authenticated user (once per day).
     */
    public function claimDailyBonus()
    {
        $user = Auth::user();

        // Free-plan users (or users with no plan) cannot earn free points.
        if (!$user->hasPlan()) {
            return response()->json([
                'success' => false,
                'message' => 'Free plan users cannot earn free points. Please upgrade your plan to start earning daily login bonuses.',
                'has_plan' => false,
                'level_label' => $user->level_label,
            ], 403);
        }

        $credited = $user->creditDailyLoginBonus();

        return response()->json([
            'success' => $credited,
            'already_claimed' => !$credited && $user->hasClaimedDailyLoginBonusToday(),
            'claimed_today' => $user->hasClaimedDailyLoginBonusToday(),
            'amount' => $user->dailyLoginBonusAmount(),
            'message' => $credited
                ? 'Daily login bonus claimed successfully!'
                : 'Daily login bonus already claimed today.',
            'balance' => $user->fresh()->balance,
            'has_plan' => $user->hasPlan(),
            'level_label' => $user->level_label,
        ]);
    }

    public function getReferrals()
    {
        $user = Auth::user();
        
        $bonuses = Transaction::where('user_id', $user->id)
            ->where('type', 'referral_bonus')
            ->latest()
            ->get();
            
        $referredUsers = $user->referrals()
            ->select('id', 'name', 'email', 'created_at', 'Nex_id', 'total_invested', 'total_profit')
            ->latest()
            ->get();
            
        $totalReferralEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'referral_bonus')
            ->where('status', 'completed')
            ->sum('amount');

        return response()->json([
            'bonuses' => $bonuses,
            'referred_users' => $referredUsers,
            'total_referral_earnings' => $totalReferralEarnings,
        ]);
    }
}

