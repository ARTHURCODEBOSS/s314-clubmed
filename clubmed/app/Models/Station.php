<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $table = "station";
    protected $primaryKey = "numstation"; 
    public $timestamps = false;
    protected $fillable = [
        'nomstation',
        'altitudestation',
        'longueurpistes',
        'nbpistes',
        'infoski'
    ];

    public function clubs()
    {
        return $this->belongsToMany(
            Club::class,
            'clubstation',
            'numstation',
            'idclub'
        );
    }
}
