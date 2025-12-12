<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiviteEnfant extends Model
{
    protected $table = 'activiteenfant';
    protected $primaryKey = 'idactivite';
    public $incrementing = false; 
    public $timestamps = false;
    protected $fillable = [
        'idactivite',         
        'numtranche',
        'detail',
        'titre',
        'description',
        'prixmin',
    ]; 
    public function trancheage()
    {
        return $this->belongsTo(Trancheage::class, 'numtranche', 'numtranche');
    }

}
