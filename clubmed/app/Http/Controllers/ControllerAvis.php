<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Avis;

class ControllerAvis extends Controller
{
    public function EnregistrerAvis(Request $request)
    {
        $request->validate([
            'idclub' => 'required|integer',
            'numclient' => 'required|integer',
            'titre' => 'required|string',
            'numreservation' => 'required|integer',
            'commentaire' => 'required|string',
            'note' => 'required|integer',
        ]);
        return DB::transaction(function () use ($request){
            $avis = Avis::create([
                'idclub' => $request->idclub,
                'numclient' => $request->numclient,
                'titre' => $request->titre,
                'numreservation' => $request->numreservation,
                'commentaire' => $request->commentaire,
                'note' => $request->note,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Avis Créer',
            ], 201);
        });
    }
}
