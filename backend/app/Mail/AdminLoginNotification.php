<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminLoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $time;
    public $ip;

    public function __construct($user, $ip)
    {
        $this->user = $user;
        $this->time = now()->format('Y-m-d H:i:s');
        $this->ip = $ip;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Security Alert: Admin Login Notification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.login_notification',
        );
    }
}
