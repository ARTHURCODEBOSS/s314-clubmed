<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activite extends Model
{
    protected $table = 'activite';
    protected $primaryKey = 'idactivite';
    public $timestamps = false; 
    protected $fillable = [
        'titre',
        'description',
        'prixmin'
    ];
    
    public function adulte()
    {
        return $this->hasOne(ActiviteAdulte::class, 'idactivite', 'idactivite');
    }
    public function enfant()
    {
        return $this->hasOne(ActiviteEnfant::class, 'idactivite', 'idactivite');
    }
    public function partenaire()
    {
        return $this->hasOne(Partenaire::class, 'idpartenaire', 'idpartenaire');
    }
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'incruste_avec', 'idactivite', 'idclub');
    }
    public function reservations()
    {
        return $this->belongsToMany(
            Reservation::class,
            'se_lie_a',            // 🔥 Même table
            'idactivite',          // 🔥 Clé étrangère de Activite
            'numreservation'       // 🔥 Clé étrangère de Reservation
        )->withPivot([
            'disponibilite_confirmee',
            'nbpersonnes'
        ]);
    } 
}
