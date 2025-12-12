<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partenaire extends Model
{
    protected $table = 'partenaires';
    protected $primaryKey = 'idpartenaire';


    public $timestamps = false; 
    protected $fillable = [
        'nom',
        'email',
        'telephone'
    ];
}