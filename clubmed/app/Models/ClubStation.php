<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Station;


class ClubStation extends Model
{
    protected $table = "clubstation";
    public $timestamps = false;
    protected $fillable = [
        'idclub',
        'numstation',
        'titre',
        'description',
        'notemoyenne',
        'lienpdf',
        'altitudeclub'
    ];

    public function club()
    {
        return $this->belongsTo(Club::class, 'idclub', 'idclub');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'numstation', 'numstation');
    }
}
