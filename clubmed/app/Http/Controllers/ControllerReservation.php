<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Route;
use App\Models\AutreVoyageur;
use App\Models\Transport;
use App\Models\Se_Lie_A;
use Illuminate\Support\Facades\Mail;
use App\Models\ConfirmationMail;
use App\Http\Controllers\ControllerCarte;

class ControllerReservation extends Controller
{
    
    public function PostReservation(Request $request)
    {

        $reservation = Reservation::create([
            'numreservation' => $request->numreservation,
            'idclub' => $request->idclub,
            'idtransport' => $request->idtransport,
            'numclient' => $request->numclient,
            'datedebut' => $request->datedebut,
            'datefin' => $request->datefin,
            'nbpersonnes' => $request->nbpersonnes,
            'lieudepart' => $request-> lieudepart,
            'prix' => $request->prix,
            'statut' => $request->statut,
            'etat_calcule' => $request->etat_calcule
        ]);
        foreach ($request->autrevoyageurs as $key => $autrevoyageur) {
            $autrevoyageurtt = AutreVoyageur::create([
                'numreservation' => $reservation->numreservation,
                
                'genre'          => $autrevoyageur['genre'], 
                'prenom'         => $autrevoyageur['prenom'],
                'nom'            => $autrevoyageur['nom'],
                'datenaissance'  => $autrevoyageur['dateNaissance']
            ]);
        }
        // $this->EnvoiMail($reservation);
        return response()->json($reservation, 201);
    }

    public function EnvoiMail(Request $request){
        $email = $request->mail;
        $numreservation = $request->numreservation;
        $activites = ControllerReservation::GetPartenaireReservation($numreservation);
        $reservation = Reservation::create([
            'numreservation' => $request->numreservation,
            'idclub' => $request->idclub,
            'idtransport' => $request->idtransport,
            'numclient' => $request->numclient,
            'datedebut' => $request->datedebut,
            'datefin' => $request->datefin,
            'nbpersonnes' => $request->nbpersonnes,
            'lieudepart' => $request-> lieudepart,
            'prix' => $request->prix,
            'statut' => $request->statut,
            'etat_calcule' => $request->etat_calcule
        ]);
       
        try {
            $token = Str::random(60);
            $urlFrontEnd = "http://51.83.36.122:8045";
            $lien= $urlFrontEnd . "/partenaire/validation?token=" . $token;
            Mail::to($email)->queue(new ConfirmationMail($reservation,null, $lien));
            foreach($activites as $act){
                Mail::to($act->partenaire->email)->queue(new ConfirmationMail($reservation,$act));
            }
            $reservation->token_partenaire = $token;
            $reservation->mail = true;
            $reservation->save();
            return response()->json(['message' => 'Mail Envoyée'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function VerificationToken($token){
        $reservation = Reservation::where('token_partenaire', $token)
                                  ->with(['client', 'club']) 
                                  ->first();
        if (!$reservation){
           return responce()->json(['message' => 'Token Non Valide'], 404);
        }
         return responce()->json(['message' => 'Token Valide'], 200);
    }

    

    public function GetAllReservation()
    {
        \Log::info('🚀 DÉBUT GetAllReservation');
        
        $reservations = Reservation::with([
                'client', 
                'club', 
                'club.categorie', 
                'club.pays',
                'activites' => function ($query) {
                    $query->withPivot(['disponibilite_confirmee', 'nbpersonnes']); 
                }      
            ])
            ->orderBy('datedebut', 'desc')
            ->get();
            
        \Log::info('📊 Nombre de réservations: ' . $reservations->count());
            
        // 🔥 TRANSFORMATION EXPLICITE pour PostgreSQL
        $reservations->each(function ($reservation) {
            \Log::info("📦 Réservation #{$reservation->numreservation} - Nb activités: " . $reservation->activites->count());
            
            if ($reservation->activites && $reservation->activites->count() > 0) {
                $reservation->activites->each(function ($activite) {
                    $valeurBrute = $activite->pivot->disponibilite_confirmee;
                    \Log::info("🔍 Activité #{$activite->idactivite} - Valeur brute: " . var_export($valeurBrute, true) . " (type: " . gettype($valeurBrute) . ")");
                    
                    // Force la conversion en vrai booléen PHP
                    $activite->pivot->disponibilite_confirmee = (bool) $valeurBrute;
                    
                    \Log::info("✅ Activité #{$activite->idactivite} - Après conversion: " . var_export($activite->pivot->disponibilite_confirmee, true));
                });
            }
        });
    
        \Log::info('🏁 FIN GetAllReservation');
    
        return response()->json($reservations, 200);
    }

    public function GetAllReservationById()
    {
        $userId = auth()->id();
        $reservations = Reservation::with(['club.photo', 'club.pays', 'type_chambre'])
            ->where('numclient', $userId)
            ->orderBy('datedebut', 'desc')
            ->get();

        return response()->json($reservations, 200);
    }



    public function getTransport()
    {
        $transports = Transport::all();
        return response()->json($transports, 200);
    }
    public function PostReservationActivite(Request $request)
    {
        
        if (!$request->has('activites') || !is_array($request->activites)) {
            return response()->json(['error' => 'Aucune activité reçue ou format incorrect'], 400);
        }
        $reservation = Reservation::find($request->numreservation);
        $reservation->prix = $reservation->prix+$request->prixTotal;
        $reservation->save();
        $tab = [];
        foreach ($request->activites as $item) 
        {
            if (isset($item['activite']['idactivite'])) {
                
                $idActivite = $item['activite']['idactivite'];
                $nbpersonnes = $item['nbpersonnes'];

                $seliea = Se_Lie_A::create([
                    'numreservation' => $request->numreservation,
                    'idactivite' => $idActivite,
                    'nbpersonnes' => $nbpersonnes
                ]);
                array_push($tab, $seliea);
            }
        }
        
        return response()->json($reservation, 200);
    }
    public function GetPartenaireReservation($numreservation){
        $activite = [];
        $reservation =Reservation::with('activites.partenaire')->find($numreservation);
        foreach($reservation->activites as $act){
            $activite[] = $act;
        }
        return $activite;
    }

    public function updateDisponibilite(Request $request, $numreservation)
    {
        $reservation = Reservation::where('numreservation', $numreservation)->firstOrFail();
        $reservation->disponibilite_confirmee = $request->disponibilite_confirmee;
        $reservation->save();
        
        return response()->json(['success' => true, 'reservation' => $reservation]);
    }

    public function updateDisponibiliteActivite(Request $request, $numreservation, $idactivite)
    {
        $etat = $request->disponibilite_confirmee ? 1 : 0;
        $updated = Se_Lie_A::where('numreservation', $numreservation)
                           ->where('idactivite', $idactivite)
                           ->update(['disponibilite_confirmee' => $etat]);
    
        if ($updated) {
            return response()->json([
                'success' => true, 
                'message' => 'Mise à jour réussie'
            ]);
        } else {
            return response()->json([
                'success' => false, 
                'message' => 'Ligne introuvable'
            ], 404);
        }
    }
    
}



