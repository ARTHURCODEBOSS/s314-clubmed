<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ReservationActivite extends Pivot
{
    protected $table = 'se_lie_a'; // Ton nom de table
    public $timestamps = false;    // Important si tu n'as pas created_at/updated_at
    
    // Si tes clés étrangères ne sont pas des entiers standards, précise-le ici
    protected $casts = [
        'idactivite' => 'integer',
        'numreservation' => 'integer',
        'disponibilite_confirmee' => 'boolean' // On règle ton souci de booléen au passage
    ];
}