<?php

use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
  
  Route::controller(PlayerController::class)->group(function () {
    Route::get('players', 'index')->name('players.index');
    Route::get('players/create', 'create')->name('players.create'); // <- Move this above
    Route::get('players/{id}', 'show')->name('players.show');
   Route::post('players', 'store')->name('players.store');
});
});
