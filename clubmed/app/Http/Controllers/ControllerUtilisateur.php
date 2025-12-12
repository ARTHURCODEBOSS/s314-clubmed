<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Adresse;
use App\Models\Club;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ControllerUtilisateur extends Controller
{
    public function GetAllUtilisateur()
    {
        $utilisateurs = User::all();
        return response()->json($utilisateurs, 200);
    }

    public function EnregistrerClient(Request $request)
    {
        $request->validate([
            'prenom' => 'required|string',
            'nom' => 'required|string',
            'email' => 'required|email|unique:client,email',
            'password' => 'required|min:6',
            'telephone' => 'nullable|string',
            'genre' => 'nullable|string|max:1',
            'datenaissance' => 'nullable|date',

            'numrue' => 'required|integer',
            'nomrue' => 'required|string',
            'codepostal' => 'required|string',
            'ville' => 'required|string',
            'pays' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            
            $adresse = Adresse::create([
                'numrue' => $request->numrue,
                'nomrue' => $request->nomrue,
                'codepostal' => $request->codepostal,
                'ville' => $request->ville,
                'pays' => $request->pays,
            ]);
            $user = User::create([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'genre' => $request->genre,
                'datenaissance' => $request->datenaissance,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'motdepasse_crypter' => Hash::make($request->password), 

                'numadresse' => $adresse->numadresse, 
            ]);


            $reservations = Reservation::where('numclient', $user->numclient)->get();
            foreach($reservations as $reservation){
                if(!($reservation == null)){
                    $numClub = $reservation->idclub;
                    $club = Club::find($numClub);
                    $reservation->club = $club;
                }
            };
            $token = $user->createToken('MobileApp')->plainTextToken;
            $user->adresse = $adresse;
            $user->reservations = $reservations;
            return response()->json([
                'status' => 'success',
                'message' => 'Compte créé avec succès',
                'user' => $user,
                'token' => $token,
            ], 201);
        });
    }
    
    public function ModifierClient(Request $request)
    {

        $request->validate([
            'idclient' => 'required|integer',
            'prenom' => 'required|string',
            'nom' => 'required|string',
            'email' => 'required|email',
            'telephone' => 'nullable|string',
            'genre' => 'nullable|string|max:1',
            'datenaissance' => 'nullable|date',
            'numadresse' => 'required|integer',

            'numrue' => 'required|integer',
            'nomrue' => 'required|string',
            'codepostal' => 'required|string',
            'ville' => 'required|string',
            'pays' => 'required|string',
        ]);
        return DB::transaction(function () use ($request) {
            $idClientToUpdate = $request->idclient;
            $idAdresseToUpdate = $request ->numadresse;
            $client = User::find($idClientToUpdate);
            $adresse = Adresse::find($idAdresseToUpdate);
            $reservations = Reservation::where('numclient', $client->numclient)->get();
            foreach($reservations as $reservation){
                if(!($reservation == null)){
                    $numClub = $reservation->idclub;
                    $club = Club::find($numClub);
                    $reservation->club = $club;
                }
            };
            $client->update([
                'prenom' => $request->prenom,
                'nom' => $request->nom,
                'genre' => $request->genre,
                'datenaissance' => $request->datenaissance,
                'telephone' => $request->telephone, 
            ]);
            $adresse->update([
                'numrue' => $request->numrue,
                'nomrue' => $request->nomrue,
                'codepostal' => $request->codepostal,
                'ville' => $request->ville,
                'pays' => $request->pays,
            ]);
            $client->adresse = $adresse;
            $client->reservations = $reservations;
            return response()->json([
                'status' => 'success',
                'message' => 'Compte créé avec succès',
                'user' => $client,
            ], 201);
        });
    }
}