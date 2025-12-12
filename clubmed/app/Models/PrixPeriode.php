<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrixPeriode extends Model
{
    protected $table = "prix_periode";
    protected $primaryKey = "numperiode";
    public $timestamps = false;
}
