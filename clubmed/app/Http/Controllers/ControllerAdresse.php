<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Adresse;
class ControllerAdresse extends Controller
{
    public function GetAllAdresse()
    {
        $adresses = Adresse::all();
        return response()->json($adresses, 200);
    }
}
