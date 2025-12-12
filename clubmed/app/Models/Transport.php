<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    use HasFactory;
    protected $table = 'transport';
    protected $primaryKey = 'idtransport';
    public $timestamps = false; 

    protected $fillable = [
        'lieudepart',
        'prix'
    ];
}
