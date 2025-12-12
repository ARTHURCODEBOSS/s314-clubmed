<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regroupement extends Model
{
    use HasFactory;

    protected $table = 'regroupement';
    protected $primaryKey = 'numregroupement'; 
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['numregroupement', 'libelleregroupement'];

    public function clubs()
    {
        return $this->belongsToMany(
            Club::class, 
            'converge_vers', 
            'numregroupement', 
            'idclub'
        );
    }
}
