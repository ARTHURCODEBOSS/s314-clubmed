<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carte extends Model
{
    protected $table = "carte_bancaire";
    protected $primaryKey = "idcb";
    public $timestamps = false;
    protected $fillable = [
        'numclient',
        'numcartebancaire_crypter',
        'dateexpiration_carte_bancaire',
        'cvv_crypter',
        'est_active'
    ];
    
    public function client()
{
    return $this->belongsTo(User::class, 'numclient', 'numclient');
}
    
}