<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOTP;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function sendOTP(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'No user found with this email address.',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

        $user->update([
            'password_reset_otp' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        try {
            Mail::to($user->email)->send(new PasswordResetOTP($otp, $user->email));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'OTP sent successfully to your email',
        ]);
    }

    public function verifyOTP(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:7'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user->password_reset_otp) {
            throw ValidationException::withMessages([
                'otp' => ['OTP not found. Please request a new OTP.'],
            ]);
        }

        if (now()->isAfter($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired. Please request a new OTP.'],
            ]);
        }

        if (!Hash::check($request->otp, $user->password_reset_otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP. Please try again.'],
            ]);
        }

        return response()->json([
            'message' => 'OTP verified successfully',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:7'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user->password_reset_otp) {
            throw ValidationException::withMessages([
                'otp' => ['OTP not found. Please request a new OTP.'],
            ]);
        }

        if (now()->isAfter($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired. Please request a new OTP.'],
            ]);
        }

        if (!Hash::check($request->otp, $user->password_reset_otp)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP. Please try again.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_otp' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }
}
