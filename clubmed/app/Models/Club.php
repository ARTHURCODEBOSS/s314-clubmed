<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    protected $table = "club";
    protected $primaryKey = "idclub";
    public $timestamps = false;
    protected $fillable = [
        'titre',
        'description',
        'notemoyenne',
        'lienpdf',
        'numphoto'
    ];

    public function photo()
    {
        return $this->belongsTo(Photo::class, 'numphoto', 'numphoto');
    }

    public function photosGalerie()
    {
        return $this->belongsToMany(
            Photo::class,
            'photo_club',   
            'idclub',      
            'numphoto'      
        )
        ->withPivot('ordre') 
        ->orderBy('pivot_ordre', 'asc');
    }

    public function lieurestauration()
    {
        return $this->belongsToMany(
            LieuRestauration::class, 
            'fusionne',
            'idclub',
            'numrestauration'
        );
    }

    public function chambres()
    {
        return $this->belongsToMany(
            Chambre::class, 
            's_unit_a',    
            'idclub',   
            'numchambre'   
        );
    }
    public function categorie()
    {
        return $this->belongsToMany(
            Categorie::class, 
            'collabore',   
            'idclub',       
            'numcategory'
        );
    }

    public function regroupements()
    {
        return $this->belongsToMany(Regroupement::class, 'converge_vers', 'idclub', 'numregroupement');
    }

    public function type_chambre()
    {

    }
    public function pays()
    {
        return $this->belongsTo(Souslocalisation::class, 'numpays', 'numpays');
    }

    public function avis()
    {
        return $this->hasMany(Avis::class, 'idclub', 'idclub');
    }

    public function clubStations()
    {
        return $this->hasMany(ClubStation::class, 'idclub', 'idclub');
    }

    public function stations()
{
    return $this->belongsToMany(Station::class, 'clubstation', 'idclub', 'numstation')
        ->withPivot('altitudeclub', 'titre', 'description', 'notemoyenne', 'lienpdf');
}
    public function activites()
    {
        return $this->belongsToMany(
            Activite::class, 
            'incruste_avec',    
            'idclub',   
            'idactivite'   
        );
    }
}
