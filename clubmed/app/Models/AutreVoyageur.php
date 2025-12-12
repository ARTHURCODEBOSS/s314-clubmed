<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutreVoyageur extends Model
{
    protected $table = 'autrevoyageur';
    protected $primaryKey = 'numvoyageur';
    public $timestamps = false; 
    protected $fillable = [
        'numreservation',
        'genre',
        'prenom',
        'nom',
        'datenaissance'
    ];
    protected static function booted()
    {
        static::creating(function ($autrevoyageur) {
            if (empty($autrevoyageur->numvoyageur)) {
                $autrevoyageur->numvoyageur = AutreVoyageur::max('numvoyageur') + 1;
            }
        });
    }
}
