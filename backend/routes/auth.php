<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyEmailCodeController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->name('register');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('api.login');
    
    Route::post('/verify-email-code', VerifyEmailCodeController::class)
        ->name('verification.verify-code');

    Route::post('/resend-verification-code', [VerifyEmailCodeController::class, 'resend'])
        ->name('verification.resend-code');
});

Route::middleware(['throttle:auth'])->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
        ->name('social.redirect');

    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
        ->name('social.callback');

    Route::post('/auth/{provider}/token', [SocialAuthController::class, 'loginWithToken'])
        ->name('social.token');
});

Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('guest')
        ->name('password.email');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('guest')
        ->name('password.store');

    Route::post('/forgot-password-otp', [PasswordResetController::class, 'sendOTP'])
        ->middleware(['guest', 'throttle:3,1'])
        ->name('password.send-otp');

    Route::post('/verify-otp', [PasswordResetController::class, 'verifyOTP'])
        ->middleware(['guest', 'throttle:5,1'])
        ->name('password.verify-otp');

    Route::post('/reset-password-with-otp', [PasswordResetController::class, 'resetPassword'])
        ->middleware(['guest', 'throttle:3,1'])
        ->name('password.reset-with-otp');
});

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');
