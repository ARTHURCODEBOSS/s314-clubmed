<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiviteAdulte extends Model
{
    protected $table = 'activiteadulte';
    protected $primaryKey = 'idactivite';
    public $incrementing = false; 
    public $timestamps = false;
    protected $fillable = [
        'idactivite',         
        'numtypeactivite', 
        'duree',
        'ageminimum',
        'frequence',
        'titre',
        'description',
        'prixmin',
    ]; 

    public function typeactivite()
    {
        return $this->belongsTo(TypeActivite::class, 'numtypeactivite', 'numtypeactivite');
    }
}
