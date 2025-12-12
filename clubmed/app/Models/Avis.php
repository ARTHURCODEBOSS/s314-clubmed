<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avis extends Model
{
    protected $table = 'avis';
    protected $primaryKey = 'numavis';
    public $timestamps = false;

    protected $fillable = [
        'idclub',      
        'numclient',
        'numreservation',
        'titre',
        'commentaire',
        'note'
    ];
    protected static function booted()
    {
        static::creating(function ($avis) {
            if (empty($avis->numavis)) {
                $avis->numavis = Avis::max('numavis') + 1;
            }
        });
    }
}

