<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nom;
    public $email;
    public $password;
    public $entityType; // "Région", "District", etc.

    public function __construct($nom, $email, $password, $entityType = "compte")
    {
        $this->nom = $nom;
        $this->email = $email;
        $this->password = $password;
        $this->entityType = $entityType;
    }

    public function build()
    {
        $subject = "Vos identifiants de connexion - ScoutTrack";
        
        return $this->subject($subject)
                    ->view('emails.credentials');
    }
}