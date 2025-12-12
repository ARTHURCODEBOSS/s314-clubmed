<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    protected $table = "adresse";
    protected $primaryKey = "numadresse";
    public $timestamps = false;
    protected $fillable = [
        'numrue',
        'nomrue',
        'codepostal',
        'ville',
        'pays'
    ];
    protected static function booted()
    {
        static::creating(function ($adresse) {
            if (empty($adresse->numadresse)) {
                $adresse->numadresse = User::max('numclient') + 1;
            }
        });
    }
}
