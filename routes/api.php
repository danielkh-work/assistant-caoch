<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\PlayController;
use App\Http\Controllers\Api\SportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();

// });
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('userUpdate',[AuthController::class,'userUpdate']);
    Route::post('change-password',[AuthController::class,'changePassword'])->name('password.change');
    Route::get('/sport',[SportController::class,'sport'])->name('sport');
    Route::get('/leaque',[SportController::class,'league'])->name('leaque');
    Route::get('/leaque-rule',[SportController::class,'leagueRule'])->name('leaque-rule');
    Route::post('/leaque-create',[SportController::class,'store'])->name('league.create');
    Route::post('/add-player',[SportController::class,'addPlayer'])->name('add.player');
    Route::get('/leaque-view/{id}',[SportController::class,'leagueView'])->name('leagueView');
    Route::get('/dashboard',[SportController::class,'dashboard'])->name('dashboard');
    Route::post('/create-formation',[FormationController::class,'store'])->name('create-formation');
    Route::get('/formation-view/{id}',[FormationController::class,'view'])->name('formation-view');
    Route::get('/formation-list',[FormationController::class,'list'])->name('formation-list');

    Route::post('/uplaod-play',[PlayController::class,'store'])->name('uplaod-play');
    Route::get('/upload-play-list',[PlayController::class,'index'])->name('upload-play-list');
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password',[AuthController::class,'forgotPassword'])->name('forget.change');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
