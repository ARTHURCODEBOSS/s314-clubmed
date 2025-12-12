<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\BotManController;
Route::match(['get', 'post'], '/botman',
'App\Http\Controllers\BotManController@handle');

Route::get('/', function () {
    return view('welcome');
});
Route::get('/clubs', [ControllerClub::class, 'GetAllClub'])->name('clubs.index');
Route::get('/users', [ControllerUtilisateur::class, 'GetAllUtilisateur'])->name('users.index');
