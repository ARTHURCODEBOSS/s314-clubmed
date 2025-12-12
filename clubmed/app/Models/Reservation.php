<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Club;

class Reservation extends Model
{
    use HasFactory;

    protected $table = "reservation";
    protected $primaryKey = "numreservation";
    public $timestamps = false;
    
    protected $fillable = [
        'idclub',
        'idtransport',
        'numclient',
        'datedebut',
        'datefin',
        'nbpersonnes',
        'lieudepart',
        'prix',
        'statut',
        'etat_calcule',
        'mail',
        'disponibilite_confirmee',
    ];
    protected $hidden = [
        'token_partenaire',
    ];

    protected $casts = [
        'mail' => 'boolean',
        'disponibilite_confirmee' => 'boolean',

    ];

    protected $appends = ['disponibiliteConfirmee'];

    public function getDisponibiliteConfirmeeAttribute()
    {
        return $this->attributes['disponibilite_confirmee'] ?? false;
    }

    public function club()
    {
        return $this->belongsTo(Club::class, 'idclub', 'idclub');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'numclient', 'numclient'); 
    }

    protected static function booted()
    {
        static::creating(function ($reservation) {
            if (empty($reservation->numreservation)) {
                $reservation->numreservation = Reservation::max('numreservation') + 1;
            }
        });
    }
    public function activites()
    {
        return $this->belongsToMany(
            Activite::class,           // Le modèle lié
            'se_lie_a',                // 🔥 TON VRAI NOM DE TABLE (en minuscules)
            'numreservation',          // 🔥 Clé étrangère de Reservation
            'idactivite'               // 🔥 Clé étrangère de Activite
        )->withPivot([
            'disponibilite_confirmee', // 🔥 Le champ qu'on modifie
            'nbpersonnes'
        ]);
    
    }
}