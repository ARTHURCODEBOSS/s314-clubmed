<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Se_Lie_A extends Model
{
    use HasFactory;

    protected $table = 'se_lie_a'; 
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    
    protected $fillable = [
        'numreservation',
        'idactivite',
        'nbpersonnes',
        'disponibilite_confirmee'
    ];

    protected $casts = [
        'disponibilite_confirmee' => 'boolean', // ✅ Déjà là
        'nbpersonnes' => 'integer',
        'numreservation' => 'integer',
        'idactivite' => 'integer',
    ];

    // 🔥 AJOUTE CECI pour forcer le cast même dans les pivots
    protected function asJson($value)
    {
        return json_encode($value, JSON_PRESERVE_ZERO_FRACTION);
    }
}