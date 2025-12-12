<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Carte;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
class ControllerCarte extends Controller
{
    public function EnregistrerCarte(Request $request)
{
    $request->validate([
        'numclient' => 'required|integer',
        'numero_carte' => 'required|string',
        'date_expiration' => 'required|string|max:5',
        'cvv' => 'required|string',
        'est_active' => 'boolean',
    ]);

    return DB::transaction(function () use ($request) {
        $carte = Carte::create([
            'numclient' => $request->numclient,
            'numcartebancaire_crypter' => Crypt::encryptString($request->numero_carte),           
            'dateexpiration_carte_bancaire' => $request->date_expiration,
            'cvv_crypter' => Hash::make($request->cvv),
            'est_active' => $request->has('est_active') ? $request->est_active : true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Carte Bancaire Enregistrée',
            'data' => $carte 
        ], 201);
    });

    
}
public function GetAllCarte($numclient)
{
    $cartes = Carte::where('numclient', $numclient)->get();
    $cartesDecryptees = $cartes->map(function ($carte) {
        try {
            $numeroReel = \Illuminate\Support\Facades\Crypt::decryptString($carte->numcartebancaire_crypter);
            $carte->numero_clair = $numeroReel;
            $carte->num_visible = "**** **** **** " . substr($numeroReel, -4);
            
        } catch (\Exception $e) {
            $carte->numero_clair = ""; 
            $carte->num_visible = "Carte indisponible";
        }
        return $carte;
    });

    return response()->json($cartesDecryptees, 200);
}

}
