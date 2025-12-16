<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Activite;
use Illuminate\Support\Facades\Route;
use App\Models\AutreVoyageur;
use App\Models\Transport;
use App\Models\Transaction;
use App\Models\Se_Lie_A;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Models\ConfirmationMail;
use App\Http\Controllers\ControllerCarte;

class ControllerReservation extends Controller
{
    public function GetAllReservation()
    {
        $reservations = Reservation::with([
                'client', 
                'club.categorie',
                'club.pays',
                'activites',
            ])
            ->orderBy('datedebut', 'desc')
            ->get();
        $reservations->each(function ($reservation) {
            if ($reservation->activites) {
                $reservation->activites->each(function ($activite) {
                    $activite->pivot->disponibilite_confirmee = (bool) $activite->pivot->disponibilite_confirmee;
                });
            }

            
       
            if ($reservation->club && $reservation->club->categorie->isNotEmpty()) {
                $reservation->club->idcategorie = $reservation->club->categorie->first()->numcategory;
            }
        });
    
        return response()->json($reservations, 200);
    }

    public function refuserProposition(Request $request, $numreservation)
{
    
    

    try {
        
        $reservation = Reservation::findOrFail($numreservation);

        
        if ($reservation->statut !== 'PROPOSITION_EN_COURS') {
            return response()->json([
                'error' => 'Action impossible. La réservation n\'est pas en attente de modification.'
            ], 400);
        }

        
        $montantARembourser = Transaction::where('numreservation', $numreservation)
            ->where('statut_paiement', 'SUCCES')
            ->sum('montant');

        
        if ($montantARembourser > 0) {
            Transaction::create([
                'numreservation' => $numreservation,
                'montant'        => -$montantARembourser,
                'date_transaction' => now(),
                'moyen_paiement' => 'VIREMENT', 
                'statut_paiement'=> 'REMBOURSE' 
            ]);
            $reservation->statut = 'REMBOURSE';
            $reservation->save();
        } 
        else
        {
            
            $reservation->statut = 'ANNULEE';
            $reservation->save();
        }

        

        return response()->json([
            'message' => 'Proposition refusée. Commande annulée et client remboursé.',
            'montant_rembourse' => $montantARembourser
        ], 200);

    } catch (\Exception $e) {
        
        return response()->json(['error' => 'Erreur technique : ' . $e->getMessage()], 500);
    }
}
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
            $tokenReservation = Str::random(60);
            $urlFrontEnd = "http://51.83.36.122:8045";
            $lien= $urlFrontEnd . "/validation?token=" .  $tokenReservation;
            Mail::to($email)->queue(new ConfirmationMail($reservation, null, $lien));

            foreach($activites as $act){
                $tokenActivite = Str::random(60);
                $urlFrontEndActivite = "http://51.83.36.122:8045";
                $lienActivite = $urlFrontEndActivite . "/validation?token=" . $tokenActivite;
                if($act->partenaire && $act->partenaire->email){
                    Mail::to($act->partenaire->email)->queue(new ConfirmationMail($reservation,$act,$lienActivite));
                    DB::table('se_lie_a')->where('numreservation', $numreservation)->where('idactivite', $act->idactivite)->update(['token' => $tokenActivite]);
                }
            }
            $reservation->token_partenaire =  $tokenReservation;
            $reservation->mail = true; 
            Reservation::where('numreservation', $numreservation)->update([
            'token_partenaire' => $tokenReservation,
            'mail' => true
        ]);
            return response()->json(['message' => 'Mail Envoyée'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function VerificationToken($token){
        $reservation = Reservation::where('token_partenaire', $token)->with(['client'])->first();
        if ($reservation){
            return response()->json([ 'type' => 'reservation' ,'data' => $reservation], 200);;
        }
        $check = DB::table('se_lie_a')->where('token', $token)->first();
        if($check){
            $reservationE = Reservation::where('numreservation', $check->numreservation)->with(['client'])->first();
            return response()->json(['type' => 'partenaire', 'data' => $reservationE], 200);
        }
        return response()->json(['message' => 'Token Non Valide'], 404);
    }

    public function ReponseReservation(Request $request){
        try{
            $token = $request->token;
            $reservation = Reservation::where('token_partenaire', $token)->first();
            if($reservation){
                Reservation::where('numreservation', $reservation->numreservation)->update([
                    'token_partenaire' => null,
                    'statut' => $request->statut
                ]);
            }
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
    }
    public function ReponsePartenaire(Request $request){
        try{
            $check = DB::table('se_lie_a')->where('token', $request->token)->first();
            if($check){
                if ($request->statut == 'CONFIRME'){
                    $statut = true;
                }
                else{
                    $statut = false;
                }
                DB::table('se_lie_a')->where('token', $request->token)->update([
                    'disponibilite_confirmee' => $statut,
                    'token' => null
                ]);
                }
        }
            
        
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
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



