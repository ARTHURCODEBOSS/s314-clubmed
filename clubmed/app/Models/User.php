<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'client';

    protected $primaryKey = 'numclient';

    public $timestamps = false;

    protected $fillable = [
        'numadresse',
        'genre',
        'prenom', 
        'nom',
        'datenaissance',
        'email',
        'telephone',
        'motdepasse_crypter',
        'role',
    ];

    protected $hidden = [
        'motdepasse_crypter', 
        'numcartebancaire_crypter',
        'cvv_crypter',
        'remember_token',
    ];

    protected $casts = [
        'motdepasse_crypter' => 'hashed', 
    ];
    public function getAuthPassword()
    {
        return $this->motdepasse_crypter;
        
    }
    public function adresse()
    {
        return $this->belongsTo(Adresse::class, 'numadresse', 'numadresse');
    }
    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->numclient)) {
                $user->numclient = User::max('numclient') + 1;
            }
        });
    }
    public function Verifi_Role(Request $request, string $roles)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, $roles)) {
            return false;
            // return response()->json(['message' => 'Accès Interdit : Réservé au staff.'], 403);
        }
        return true;
    }
}
