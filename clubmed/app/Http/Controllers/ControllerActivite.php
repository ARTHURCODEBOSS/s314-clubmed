<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\ActiviteAdulte;
use App\Models\ActiviteEnfant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControllerActivite extends Controller
{
    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'titre' => 'required|string|max:1024',
            'description' => 'required|string|max:1024',
            'prixmin' => 'required|numeric|min:0',
            'type_activite' => 'required|in:adulte,enfant',
            
            'numtypeactivite' => 'required_if:type_activite,adulte|integer',
            'duree' => 'required_if:type_activite,adulte|numeric|min:0',
            'ageminimum' => 'required_if:type_activite,adulte|integer|min:0',
            'frequence' => 'required_if:type_activite,adulte|string|max:1024',
            
            'numtranche' => 'required_if:type_activite,enfant|integer',
            'detail' => 'required_if:type_activite,enfant|string|max:1024',
        ]);
    
        try {
            DB::beginTransaction();
            
            $activiteParent = Activite::create([
                'titre' => $validatedData['titre'],
                'description' => $validatedData['description'],
                'prixmin' => $validatedData['prixmin'],
            ]);
            
            $id = $activiteParent->idactivite;
    
            $enfantData = array_merge(['idactivite' => $id], $validatedData);
    
            if ($validatedData['type_activite'] === 'adulte') {
                ActiviteAdulte::create($enfantData);
            } else {
                ActiviteEnfant::create($enfantData);
            }
            
            DB::commit();
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur de création.'], 500); 
        }
    
        return response()->json(['message' => 'Créé avec succès.', 'id' => $id], 201);
    }

    public function GetActivite($idActivite)
    {
        $activite = Activite::with(['adulte', 'enfant'])
                            ->where('idactivite', $idActivite)
                            ->first();

        if (!$activite) {
            return response()->json(['message' => 'Activité non trouvée.'], 404);
        }

        $activiteData = $activite->toArray();

        if ($activite->adulte) {
            $activiteData['profil'] = $activite->adulte->toArray();
        } elseif ($activite->enfant) {
            $activiteData['profil'] = $activite->enfant->toArray();
        }

        return response()->json($activiteData, 200);
    }
    
    public function GetAllActivites()
    {
        $activites = Activite::with(['adulte', 'enfant'])->get();
        return response()->json($activites, 200);
    }
    
    
    public function GetActivitesAdultes()
    {
        $activitesAdultes = ActiviteAdulte::with('activite')->get();
        return response()->json($activitesAdultes, 200);
    }

    public function GetActivitesEnfants()
    {
        $activitesEnfants = ActiviteEnfant::with('activite')->get();
        return response()->json($activitesEnfants, 200);
    }


}