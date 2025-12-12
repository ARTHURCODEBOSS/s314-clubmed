<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trancheage extends Model
{
    use HasFactory;
    protected $table = 'trancheage'; 
    protected $primaryKey = 'numtranche'; 
    public $timestamps = false;

    protected $fillable = ['numtranche', 'agemin', 'agemax'];
}
