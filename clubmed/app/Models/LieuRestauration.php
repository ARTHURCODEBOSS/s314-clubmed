<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LieuRestauration extends Model
{
    use HasFactory;

    protected $table = 'lieurestauration'; 
    protected $primaryKey = 'numrestauration';
    public $incrementing = false;
    public $timestamps = false; 

    protected $fillable = [
        'numrestauration', 'numphoto', 'presentation', 'nom', 'description', 'estbar'
    ];

    protected $casts = [
        'estbar' => 'boolean', 
    ];

    public function photo()
    {
        return $this->belongsTo(Photo::class, 'numphoto', 'numphoto');
    }
}