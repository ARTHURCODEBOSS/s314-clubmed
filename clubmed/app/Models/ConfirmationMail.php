<?php

namespace App\Models;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Reservation;
use App\Models\Activite;

class ConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $activitePartenaire;
    public $lienValidation;

    public function __construct(Reservation $reservation, ?Activite $activite = null, ?string $lien = null)
    {
        $this->reservation = $reservation;
        $this->activitePartenaire =$activite ;
        $this->lienValidation = $lien;
    }

    public function build()
    {
        $mail = '';
        if ($this->activitePartenaire == null){
            $emailClub = $this->reservation->email;
            $mail = 'emails.confirmation';
        }
        else{
            $emailClub = $this->activitePartenaire->partenaire->mail;
            $mail = 'emails.partenaire';
        }
        $idclient = $this->reservation->idclient;
        return $this->subject('Confirmation de votre séjour à ' . $idclient)
                    ->from($emailClub, $idclient)
                    ->replyTo($emailClub)
                    ->view($mail);
    }
}
