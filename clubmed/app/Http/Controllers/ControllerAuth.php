<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Adresse;
use App\Models\Reservation;
use App\Models\Club;


class ControllerAuth extends Controller
{
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $user = User::with('adresse')->where('email', $email)->first();

        $idAdresse = $user->numadresse;
        $numClient = $user->numclient;
        $adresseTrouvee = Adresse::find($idAdresse);

        $reservations = Reservation::where('numclient', $user->numclient)->get();
        foreach($reservations as $reservation){
            if(!($reservation == null)){
                $numClub = $reservation->idclub;
                $club = Club::find($numClub);
                $reservation->club = $club;
            }
            
        }
        $user->adresse = $adresseTrouvee;
        $user->reservations = $reservations;
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ];
        $token = $user->createToken('MonTokenClient')->plainTextToken;
        if (Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie',
                'user' => $user,
                'role' => $user->role,
                'token' => $token,
            ], 200);
        };
        return response()->json([
            'status' => 'error',
            'message' => 'Identifiants incorrects'
        ], 401);
    }
    public function CheckToken(Request $request)
    {
        $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Non autorisé'], 401);
    }

    $adresseTrouvee = Adresse::find($user->numadresse);

    $reservations = Reservation::with(['club', 'activites'])
    ->where('numclient', $user->numclient)
    ->get();

    foreach($reservations as $reservation){
        if($reservation){
            $numClub = $reservation->idclub; // ou numclub selon ta BDD
            if($numClub) {
                $club = Club::find($numClub);
                $reservation->club = $club;
            }
        }
    }

    $user->adresse = $adresseTrouvee;
    $user->reservations = $reservations;

    return response()->json([
        'status' => 'success',
        'message' => 'Session valide',
        'user' => $user,
    ], 200);
    }
}

