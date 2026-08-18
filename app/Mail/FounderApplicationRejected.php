<?php

namespace App\Mail;

use App\Models\Startup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FounderApplicationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Startup $startup)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Update - PUP TBIDO',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-rejected',
            with: ['startup' => $this->startup],
        );
    }
}
