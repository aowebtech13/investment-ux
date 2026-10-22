<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Mail\AdminLoginNotification;
use Illuminate\Support\Facades\Mail;

class AdminLoginListener
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user->role === 'admin') {
            Mail::to($user->email)->send(new AdminLoginNotification($user, request()->ip()));
        }
    }
}
