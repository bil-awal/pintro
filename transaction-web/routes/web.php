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

// Redirect root to user panel (Filament will handle authentication)
Route::get('/', function () {
    return redirect('/admin/login'); // Keep admin as default for now
});

// Redirect /login to user panel login
Route::get('/login', function () {
    return redirect('/user/login');
})->name('login');

// Admin panel redirect
Route::get('/admin', function () {
    return redirect('/admin/login');
});
