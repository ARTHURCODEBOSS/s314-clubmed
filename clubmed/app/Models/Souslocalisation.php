<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Souslocalisation extends Model
{
    protected $table = "souslocalisation";
    protected $primaryKey = "numpays"; 
    public $timestamps = false;
    protected $fillable = [
        'nompays'
    ];
    
}