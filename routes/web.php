<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

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
