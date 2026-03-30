<?php

namespace App\Mail;

use App\Models\Paiement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CotisationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $paiement;
    public $cotisation;

    public function __construct(Paiement $paiement)
    {
        $this->paiement = $paiement;
        $this->cotisation = $paiement->cotisation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de paiement - ' . $this->cotisation->nom,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cotisation-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}