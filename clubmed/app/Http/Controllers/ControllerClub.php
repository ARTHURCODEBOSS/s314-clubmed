<?php

namespace App\Http\Controllers;
use App\Models\Club;
use App\Models\Localisation;
use App\Models\Souslocalisation;
use App\Models\Categorie;
use App\Models\Periode;
use App\Models\TypeChambre;
use App\Models\Station;
use App\Models\ClubStation;
use Illuminate\Http\Request;
use App\Models\ActiviteEnfant;
use App\Models\Trancheage;
use App\Models\PrixPeriode;

class ControllerClub extends Controller
{
    public function GetAllActivite($idClub)
    {
        $activite = Club::where('statut_mise_en_ligne', 'PUBLIE')
                    ->with('activites')
                    ->find($idClub);
        return response()->json($activite, 200);
    }
    
    public function getClubsEnAttente()
    {
        
        $clubs = Club::where('statut_mise_en_ligne', 'EN_CREATION')
                     ->with('typeChambres')
                     ->get();

        $data = $clubs->map(function ($club) {
                        $clubArray = $club->toArray();
            
                        
        $clubArray['types_chambres_uniques'] = $club->typeChambres->toArray();
                        
            return $clubArray;
        });
            
        return response()->json($data);
    }
    public function getPeriodes()
    {
        return response()->json(Periode::all());
    }
    public function validerEtTarifer(Request $request, $idclub)
    {
        
        $tarifs = $request->input('tarifs');

        
        $club = Club::findOrFail($idclub);

        try {
            foreach ($tarifs as $tarif) {
                PrixPeriode::updateOrCreate(
                    [
                        'numperiode' => $tarif['numperiode'],
                        'idtypechambre' => $tarif['idtypechambre']
                    ],
                    [
                        'prixperiode' => $tarif['prix']
                    ]
                );
            }

            
            $club->statut_mise_en_ligne = 'PUBLIE';
            $club->save();

            return response()->json(['message' => 'Tarifs enregistrés et Club publié !']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur technique : ' . $e->getMessage()], 500);
        }
    }
    public function getClubsByCategorie($idcategorie)
    {
        
        $clubs = Club::with([
            'activites.adulte.typeactivite',
            'activites.enfant.trancheage',
            'photo',
            'photosGalerie',
            'chambres.typeChambre.prixPeriodes',
            'categorie',
            'pays'
        ])
        ->whereHas('categorie', function ($query) use ($idcategorie) {
            $query->where('categorie.numcategory', $idcategorie);
        })
        ->where('statut_mise_en_ligne', 'PUBLIE')
        ->get();
    
        if ($clubs->isEmpty()) {
            return response()->json([], 200);
        }
                
        $periodes = Periode::all();
        $aujourdhui = date('Y-m-d');
        $periodeActuel = null;
                
        foreach ($periodes as $periode) {
            if ($periode->datedeb <= $aujourdhui && $periode->datefin >= $aujourdhui) {
                $periodeActuel = $periode;
                break;
            }
        }
                
        if (!$periodeActuel) {
            foreach ($clubs as $c) $c->prix = "Fermé";
            return response()->json($clubs, 200);
        }
    
        foreach ($clubs as $club) {
            $prixMinTrouve = null;
            
            foreach ($club->chambres as $chambre) {
                $type = $chambre->typeChambre ?? null;
                
                if ($type) {
                    $les_prix = $type->prixPeriodes ?? [];
                    
                    foreach ($les_prix as $prix_periode) {
                        if ($prix_periode->numperiode == $periodeActuel->numperiode) {
                            if ($prixMinTrouve === null || $prix_periode->prixperiode < $prixMinTrouve) {
                                $prixMinTrouve = $prix_periode->prixperiode;
                            }
                        }
                    }
                }
            }
            
            $club->prix = ($prixMinTrouve === null) ? "Indisp." : floatval($prixMinTrouve) . " €";
        } 
            
        return response()->json($clubs, 200);
    }
    
    
    
    public function GetClub($idClub)
    {
        $club = Club::with([
            'activites.adulte.typeactivite',
            'activites.enfant.trancheage',
            'photo',
            'photosGalerie',
            'avis',
            'chambres.typeChambre',
            'chambres.typeChambre.photo',
            'pays', 
            'lieurestauration.photo', 
            'stations', 
            'clubStations.station', 
            'activites.enfant.trancheage',
            'activites.adulte.typeactivite.photo', 
            
        ])
        ->where('statut_mise_en_ligne', 'PUBLIE')
        ->find($idClub);

        
        
        return response()->json($club, 200);
    }

    public function show($id)
    {
        $club = Club::with([
            'activites.adulte.typeactivite',
            'activites.enfant.trancheage',
            'photo',
            'pays',
            'chambres.type_chambre',
            'lieurestauration',
            'avis',
            'clubstation.station'
        ])
        ->where('statut_mise_en_ligne', 'PUBLIE')
        ->findOrFail($id);
        
        return response()->json($club);
    }
    
    public function GetAllClub()
    {
        $clubs = Club::where('statut_mise_en_ligne', 'PUBLIE')
        ->with([
            'activites.adulte.typeactivite', 
            'activites.enfant.trancheage',
            'photo',
            'photosGalerie',
            'chambres.typeChambre.prixPeriodes',
            'categorie',
            'pays',
            'stations'
        ])
        ->get();
        
        if ($clubs->isEmpty()) {
            return response()->json([], 200);
        }
                
        $periodes = Periode::all();
        $aujourdhui = date('Y-m-d');
        $periodeActuel = null;
                
        foreach ($periodes as $periode)
        {
            if ($periode->datedeb <= $aujourdhui && $periode->datefin >= $aujourdhui)
            {
                $periodeActuel = $periode;
                break;
            }
        }

                
        if (!$periodeActuel) {
            
            foreach ($clubs as $c) $c->prix = "Fermé";
            return response()->json($clubs, 200);
        }

        foreach ($clubs as $club)
        {
          
            $prixMinTrouve = null;

            foreach ($club->chambres as $chambre)
            {
                
                $type = $chambre->typeChambre ?? $chambre->type_chambre;
                
                if ($type)
                {
                    $les_prix = $type->prixPeriodes ?? $type->prix_periodes ?? [];
                    
                    foreach ($les_prix as $prix_periode)
                    {
                        if ($prix_periode->numperiode == $periodeActuel->numperiode)
                        {
                            if ($prixMinTrouve === null || $prix_periode->prixperiode < $prixMinTrouve)
                            {
                                $prixMinTrouve = $prix_periode->prixperiode;
                            }
                        }
                    }
                }
            }

            

            
            if ($prixMinTrouve === null) {
                $club->prix = "Indisp.";
            } else {
                $club->prix = floatval($prixMinTrouve) . " €";
            }
        } 
            
        return response()->json($clubs, 200);


    }
    
    public function getLocalisationsWithSousLocalisation()
    {
        $localisations = Localisation::with('souslocalisations')->get();
        return response()->json($localisations, 200);
    }

    public function getClubStation()
    {
        $clubstations = ClubStation::all();
        return response()->json($clubstations, 200);
    }


    public function GetAllCategorie()
    {
        $categorie = Categorie::all();
        return response()->json($categorie, 200);
    }

    
    public function GetChambreByClub()
    {
        
    }

    /**
     * Récupère les clubs filtrés par pays et calcule leur prix minimum actuel.
     * @param int $idPays
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClubsByPays($idPays)
    {
        
        // AJOUT CLÉ : 'photosGalerie'
        $clubs = Club::with([
            'activites.adulte.typeactivite',
            'activites.enfant.trancheage','photo', 'photosGalerie', 'chambres.typeChambre.prixPeriodes','pays'])
                     ->where('numpays', $idPays)
                     ->where('statut_mise_en_ligne', 'PUBLIE')
                     ->get();

        if ($clubs->isEmpty()) {
            return response()->json([], 200);
        }
                
        $periodes = Periode::all();
        $aujourdhui = date('Y-m-d');
        $periodeActuel = null;
                
        foreach ($periodes as $periode)
        {
            if ($periode->datedeb <= $aujourdhui && $periode->datefin >= $aujourdhui)
            {
                $periodeActuel = $periode;
                break;
            }
        }
                
        if (!$periodeActuel) {
            
            foreach ($clubs as $c) $c->prix = "Fermé";
            return response()->json($clubs, 200);
        }

        foreach ($clubs as $club)
        {
            
            $prixMinTrouve = null;

            foreach ($club->chambres as $chambre)
            {
                
                $type = $chambre->typeChambre;
                
                if ($type)
                {
                    $les_prix = $type->prixPeriodes;
                    
                    foreach ($les_prix as $prix_periode)
                    {
                        if ($prix_periode->numperiode == $periodeActuel->numperiode)
                        {
                            if ($prixMinTrouve === null || $prix_periode->prixperiode < $prixMinTrouve)
                            {
                                $prixMinTrouve = $prix_periode->prixperiode;
                            }
                        }
                    }
                }
            }

            
            if ($prixMinTrouve === null) {
                $club->prix = "Indisp.";
            } else {
                $club->prix = floatval($prixMinTrouve) . " €";
            }
        } 
            
        return response()->json($clubs, 200);
        
    }

    /**
     * Récupère les clubs filtrés par localisation (groupe de pays) et calcule leur prix minimum actuel.
     * @param int $idLocalisation
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClubsByLocalisation($idLocalisation)
    {
        
        $localisation = Localisation::with('souslocalisations')->find($idLocalisation);

        if (!$localisation) {
            return response()->json([], 200);
        }

        $idsPays = $localisation->souslocalisations->pluck('numpays');

        
        // AJOUT CLÉ : 'photosGalerie'
        $clubs = Club::with([
            'activites.adulte.typeactivite',
            'activites.enfant.trancheage','photo','photosGalerie','chambres.typeChambre.prixPeriodes','pays'])
                     ->whereIn('numpays', $idsPays) 
                     ->where('statut_mise_en_ligne', 'PUBLIE')
                     ->get();
        

        if ($clubs->isEmpty()) {
            return response()->json([], 200);
        }
            
        $periodes = Periode::all();
        $aujourdhui = date('Y-m-d');
        $periodeActuel = null;
                
        foreach ($periodes as $periode)
        {
            if ($periode->datedeb <= $aujourdhui && $periode->datefin >= $aujourdhui)
            {
                $periodeActuel = $periode;
                break;
            }
        }
                
        if (!$periodeActuel) {
            
            foreach ($clubs as $c) $c->prix = "Fermé";
            return response()->json($clubs, 200);
        }
        
        foreach ($clubs as $club)
        {
            
            $prixMinTrouve = null;
            
            foreach ($club->chambres as $chambre)
            {
                
                $type = $chambre->typeChambre;
                
                if ($type)
                {
                    $les_prix = $type->prixPeriodes;
                    
                    foreach ($les_prix as $prix_periode)
                    {
                        if ($prix_periode->numperiode == $periodeActuel->numperiode)
                        {
                            if ($prixMinTrouve === null || $prix_periode->prixperiode < $prixMinTrouve)
                            {
                                $prixMinTrouve = $prix_periode->prixperiode;
                            }
                        }
                    }
                }
            }
            
            
            if ($prixMinTrouve === null) {
                $club->prix = "Indisp.";
            } else {
                $club->prix = floatval($prixMinTrouve) . " €";
            }
        } 
            
        return response()->json($clubs, 200);
        
    }

    public function GetAllRegroupement()
    {
        $regroupements = \App\Models\Regroupement::all();
        
        return response()->json($regroupements, 200);
    }

    public function getClubsByRegroupement($numRegroupement)
    {
        $clubs = Club::with([
            'activites.adulte.typeactivite',
            'activites.enfant.trancheage',
            'photo', 
            'photosGalerie', 
            'chambres.typeChambre.prixPeriodes', 
            'pays',
            'categorie'
        ])
        ->whereHas('regroupements', function ($query) use ($numRegroupement) {
            $query->where('regroupement.numregroupement', $numRegroupement);
        })
        ->where('statut_mise_en_ligne', 'PUBLIE')
        ->get();

        if ($clubs->isEmpty()) {
            return response()->json([], 200);
        }
                
        $periodes = Periode::all();
        $aujourdhui = date('Y-m-d');
        $periodeActuel = null;
                
        foreach ($periodes as $periode) {
            if ($periode->datedeb <= $aujourdhui && $periode->datefin >= $aujourdhui) {
                $periodeActuel = $periode;
                break;
            }
        }
                
        if (!$periodeActuel) {
            foreach ($clubs as $c) $c->prix = "Fermé";
            return response()->json($clubs, 200);
        }

        foreach ($clubs as $club) {
            $prixMinTrouve = null;
            foreach ($club->chambres as $chambre) {
                $type = $chambre->typeChambre ?? null;
                if ($type) {
                    $les_prix = $type->prixPeriodes ?? [];
                    foreach ($les_prix as $prix_periode) {
                        if ($prix_periode->numperiode == $periodeActuel->numperiode) {
                            if ($prixMinTrouve === null || $prix_periode->prixperiode < $prixMinTrouve) {
                                $prixMinTrouve = $prix_periode->prixperiode;
                            }
                        }
                    }
                }
            }
            
            $club->prix = ($prixMinTrouve === null) ? "Indisp." : floatval($prixMinTrouve) . " €";
        } 
            
        return response()->json($clubs, 200);
    }
    
    /**
     * Récupère le prix minimum actuel pour un club donné.
     * @param int $idclub
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrixMinByIdClub($idclub)
    {
        $club = Club::with('chambres.typeChambre.prixPeriodes')->where('statut_mise_en_ligne', 'PUBLIE')->find($idclub);
        
        if (!$club) {
            return response()->json("Indisp.", 200);
        }

        $periodes = Periode::all();
        $aujourdhui = date('Y-m-d');
        $periodeActuel = null;
        $prix = null;

        foreach ($periodes as $periode)
        {
            if ($periode->datedeb <= $aujourdhui && $periode->datefin >= $aujourdhui)
            {
                $periodeActuel = $periode;
                break;
            }
        }

        if (!$periodeActuel) {
            return response()->json("Fermé", 200);
        }

        foreach ($club->chambres as $chambre)
        {
            $type = $chambre->typeChambre;

            if ($type)
            {
                $les_prix = $type->prixPeriodes;

                foreach ($les_prix as $prix_periode)
                {
                    if ($prix_periode->numperiode == $periodeActuel->numperiode)
                    {
                        if ($prix === null || $prix_periode->prixperiode < $prix)
                        {
                            $prix = $prix_periode->prixperiode;
                        }
                    }
                }
            }
        }

        if ($prix === null) {
            return response()->json("Indisp.", 200);
        }

        return response()->json(floatval($prix) . " €", 200);
    }

    public function getPrixByIdTypeChambre($idTypeChambre)
    {
        $typeChambre = TypeChambre::with('prixPeriodes')->find($idTypeChambre);
        
        if (!$typeChambre) {
            return response()->json("Indisp.", 200);
        }

        $periodes = Periode::all();
        $aujourdhui = date('Y-m-d');
        $periodeActuel = null;
        $prix = null;

        foreach ($periodes as $periode)
        {
            if ($periode->datedeb <= $aujourdhui && $periode->datefin >= $aujourdhui)
            {
                $periodeActuel = $periode;
                break;
            }
        }

        if (!$periodeActuel) {
            return response()->json("Fermé", 200);
        }

        $les_prix = $typeChambre->prixPeriodes;
            foreach ($les_prix as $prix_periode)
            {
                if ($prix_periode->numperiode == $periodeActuel->numperiode)
                {
                    
                    $prix = $prix_periode->prixperiode;
                    
                }
            }
                    

        if ($prix === null) 
        {
            return response()->json("Indisp.", 200);
        }

        return response()->json(floatval($prix) . " €", 200);
    }

    
}