<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\RegisteredUserController;
Route::get('/', function () {
    return view('welcome'); // Your home screen with buttons
});
Route::get('/', function () {
    return 'Public Home Page';
});

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/admin-dashboard', function () {
        return 'Welcome Admin!';
    });
});

Route::middleware(['auth', 'role:Manager'])->group(function () {
    Route::get('/manager-dashboard', function () {
        return 'Welcome Manager!';
    });
});
Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});
Route::get('/register/new-tenant', [RegisteredUserController::class, 'createNewTenant'])->name('register.newTenant');
Route::get('/register/existing-tenant', [RegisteredUserController::class, 'createExistingTenant'])->name('register.existingTenant');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
