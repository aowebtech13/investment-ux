<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\VerificationMail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->authenticate();

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $user = Auth::user();

            // If the user is not yet verified, ensure they have a valid
            // verification code (legacy users registered before email
            // verification was added may have null). Generate and mail a
            // fresh code automatically so the verification flow is smooth.
            if (!$user->hasVerifiedEmail()) {
                $needsNewCode = false;

                if (!$user->email_verification_code) {
                    $needsNewCode = true;
                } elseif (
                    $user->email_verification_expires_at &&
                    now()->isAfter($user->email_verification_expires_at)
                ) {
                    $needsNewCode = true;
                }

                if ($needsNewCode) {
                    $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $user->email_verification_code = $verificationCode;
                    $user->email_verification_expires_at = now()->addMinutes(10);
                    $user->save();

                    try {
                        Mail::to($user->email)->send(new VerificationMail($verificationCode));
                    } catch (\Exception $e) {
                        Log::error('Verification code mail failed on login: ' . $e->getMessage());
                    }
                }
            }

// NOTE: The daily login bonus is no longer auto-credited on login.
            // It is now claimed manually by the user via the "Claim" button
            // on the /chat page (see POST /api/daily-login-bonus/claim).

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user->fresh(),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Login failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->tokens()->delete();

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
