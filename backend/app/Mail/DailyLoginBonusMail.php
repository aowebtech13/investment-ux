<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyLoginBonusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $amount;
    public $balance;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $amount = 0.50, $balance = 0)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->balance = $balance;
    }

    
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 You earned a $' . number_format($this->amount, 2) . ' Daily Login Bonus!',
        );
    }

    
public function content(): Content
    {
        return new Content(
            view: 'emails.daily_login_bonus',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
