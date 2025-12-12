<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeChambre extends Model
{
    protected $table = "typechambre";
    protected $primaryKey = "idtypechambre";
    public $timestamps = false;

    public function photo()
    {
        return $this->belongsTo(Photo::class, 'numphoto', 'numphoto');
    }
     
    public function prixPeriodes()
    {
        return $this->hasMany(PrixPeriode::class, 'idtypechambre', 'idtypechambre');
    }
}

