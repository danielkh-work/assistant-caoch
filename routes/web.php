<?php

use App\Http\Controllers\LeagueController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-ethereal', function () {
    try {
        Mail::raw('This is a plain text test email from Laravel via Ethereal.', function ($message) {
            $message->to('test@example.com')
                ->subject('Test Email from Laravel');
        });

        return 'Email sent!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

Auth::routes();
Route::get('/send-message', function () {
    
    broadcast(new MessageSent(['msg' => 'broadcast is working']));
    return response()->json(['status' => 'Message broadcasted']);
});
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::controller(PlayerController::class)->group(function () {
        Route::get('players', 'index')->name('players.index');
        Route::get('players/create', 'create')->name('players.create'); // <- Move this above
        Route::get('players/{id}', 'show')->name('players.show');
        Route::post('players', 'store')->name('players.store');

         Route::get('players/{id}/edit', 'edit')->name('players.edit');
         Route::put('players/{id}', 'update')->name('players.update');
         Route::delete('players/{id}', 'destroy')->name('players.destroy');
    });
    Route::controller(PlayController::class)->group(function () {
        Route::get('play', 'index')->name('play.index');
        Route::get('play/create', 'create')->name('play.create'); // <- Move this above
        Route::get('play/{id}', 'show')->name('play.show');
        Route::post('play', 'store')->name('play.store');

        Route::get('play/{id}/edit', 'edit')->name('play.edit');
        Route::put('play/{id}', 'update')->name('play.update');
        Route::delete('play/{id}', 'destroy')->name('play.destroy');
    });
    Route::controller(LeagueController::class)->group(function () {
        Route::get('league', 'index')->name('league.index');
        Route::get('league/create', 'create')->name('league.create'); // <- Move this above
        Route::get('league/{id}/edit', 'edit')->name('league.edit'); 
        Route::put('league/{id}', 'update')->name('league.update');
        Route::get('league/{id}', 'show')->name('league.show');
        Route::post('league', 'store')->name('league.store');
    });

});
