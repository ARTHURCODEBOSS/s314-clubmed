<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeActivite extends Model
{
    use HasFactory;
    protected $table = 'typeactivite';
    protected $primaryKey = 'numtypeactivite';
    public $timestamps = false; 

    protected $fillable = [
        'numphoto',
        'titre',
        'description',
        'nbactivitecarte',
        'nbactiviteincluse',
    ];
    public function photo()
    {
        
        return $this->belongsTo(Photo::class, 'numphoto', 'numphoto');
    }
}
