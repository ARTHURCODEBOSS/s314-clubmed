<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chambre extends Model
{
    protected $table = "chambre";
    protected $primaryKey = "numchambre";
    public $timestamps = false;
    protected $fillable = [
        'titre',
        'description',
        'prixmin',
        'photochambre'
    ];
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 's_unit_a', 'numchambre', 'idclub');
    }

    public function typeChambre()
{
    
    return $this->belongsTo(TypeChambre::class, 'idtypechambre', 'idtypechambre');
}
}

