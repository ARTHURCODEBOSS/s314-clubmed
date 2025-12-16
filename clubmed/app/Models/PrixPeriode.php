<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrixPeriode extends Model
{
    protected $table = 'prix_periode';
    public $timestamps = false;
    
    // INDISPENSABLE : Empêche Laravel de chercher un 'id' auto-incrémenté qui n'existe pas
    public $incrementing = false;
    protected $primaryKey = null;

    // C'EST LA LIGNE QUI MANQUE : Elle autorise l'écriture de ces colonnes
    protected $fillable = [
        'numperiode', 
        'idtypechambre', 
        'prixperiode'
    ];
}
