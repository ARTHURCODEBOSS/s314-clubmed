<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerReservation;
use App\Http\Controllers\ControllerChambre;
use App\Http\Controllers\ControllerUtilisateur;
use App\Http\Controllers\ControllerAdresse;
use App\Http\Controllers\ControllerClub;
use App\Http\Controllers\ControllerAuth;
use App\Http\Controllers\ControllerCreation;
use App\Http\Controllers\ControllerActivite;
use App\Http\Controllers\ControllerAvis;
use App\Http\Controllers\ControllerCarte;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/check-token', [ControllerAuth::class, 'CheckToken']);
    Route::post('/envoyer-mail', [ControllerReservation::class,'EnvoiMail']);
    Route::post('/reservationsById', [ControllerReservation::class, 'GetAllReservationById']);
});
Route::get('/verif-token/{token}', [ControllerReservation::class, 'VerificationToken']);
Route::Get('/reservations', [ControllerReservation::class, 'GetAllReservation']);
Route::Get("/chambres ",[ControllerChambre::class, "GetAllChambre" ]);
Route::Get("/utilisateurs ",[ControllerUtilisateur::class, "GetAllUtilisateur" ]);
Route::Get("/adresses ",[ControllerAdresse::class, "GetAllAdresse" ]);
Route::Get('/clubs', [ControllerClub::class, 'GetAllClub']);
Route::Get('/localisations', [ControllerClub::class, 'GetAllLocalisation']);
Route::Get("/getLocalisationsWithSousLocalisation",[ControllerClub::class, "getLocalisationsWithSousLocalisation"]);
Route::Post("/login",[ControllerAuth::class, "login"]);
Route::Post("/inscription",[ControllerUtilisateur::class, "EnregistrerClient"]);
Route::Post("/modification",[ControllerUtilisateur::class, "ModifierClient"]);
Route::Post('/creation', [ControllerCreation::class, 'creation']);
Route::Post('/enregistrerAvis', [ControllerAvis::class, 'EnregistrerAvis']);
Route::Get("/GetAllCategorie ",[ControllerClub::class, "GetAllCategorie" ]);
Route::Get("/clubs/pays/{id}",[ControllerClub::class, "getClubsByPays" ]);
Route::Get("/clubs/localisation/{id}", [ControllerClub::class, "getClubsByLocalisation"]);
Route::Get("/club/{id}",[ControllerClub::class, "GetClub" ]);
Route::Get('/activites', [ControllerActivite::class, 'GetAllActivites']);
Route::Get('/activites/{idActivite}', [ControllerActivite::class, 'GetActivite']);
Route::Get('/activites/adultes', [ControllerActivite::class, 'GetActivitesAdultes']);
Route::Get('/activites/enfants', [ControllerActivite::class, 'GetActivitesEnfants']);
Route::Post('/activites', [ControllerActivite::class, 'store']);
Route::Get('/clubs/prix/{idclub}', [ControllerClub::class, 'getPrixMinByIdClub']);
Route::Get('/prixbyidtypechambre/{idtypechambre}', [ControllerClub::class, 'getPrixByIdTypeChambre']);
Route::get('/GetAllRegroupement', [ControllerClub::class, 'GetAllRegroupement']);
Route::get('/clubs/regroupement/{numRegroupement}', [ControllerClub::class, 'getClubsByRegroupement']);
Route::get('/GetAllClub', [ControllerClub::class, 'GetAllClub']);
Route::Post('/postReservation', [ControllerReservation::class, 'PostReservation']);
Route::get('/GetAllActivite/{idclub}', [ControllerClub::class, 'GetAllActivite']);
Route::Get('/prixbyidtransport/{idtransport}', [ControllerClub::class, 'getPrixByIdTransport']);
Route::Post('/enregistrerCarte', [ControllerCarte::class, 'EnregistrerCarte']);
Route::Get('/GetAllCarte/{numclient}', [ControllerCarte::class, 'GetAllCarte']);
Route::Get('/transports', [ControllerReservation::class, 'getTransport']);
Route::Post('/PostReservationActivite', [ControllerReservation::class, 'PostReservationActivite']);
Route::put('/reservations/{numreservation}/disponibilite', [ControllerReservation::class, 'updateDisponibilite']);
Route::put('/reservations/{numreservation}/activites/{idactivite}/disponibilite', [ControllerReservation::class, 'updateDisponibiliteActivite']);


