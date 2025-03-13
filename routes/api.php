<?php

use App\Http\Controllers\Api\AuthController;
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
    Route::get('/leaque',[SportController::class,'leaque'])->name('leaque');
    Route::post('/leaque-create',[SportController::class,'store'])->name('leaque.create');
    Route::post('/add-player',[SportController::class,'addPlayer'])->name('add.player');
    Route::get('/leaque-view/{id}',[SportController::class,'leaqueView'])->name('leaqueView');
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password',[AuthController::class,'forgotPassword'])->name('forget.change');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
