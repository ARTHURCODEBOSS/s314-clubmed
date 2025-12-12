<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Souslocalisation;

class Localisation extends Model
{
    protected $table = "localisation";
    protected $primaryKey = "numlocalisation"; 
    public $timestamps = false;
    protected $fillable = [
        'nomlocalisation'
    ];

    public function souslocalisations()
    {
        return $this->belongsToMany(
            Souslocalisation::class, 
            's_articule_autour_de', 
            'numlocalisation',      
            'numpays'         
        );
    }
}