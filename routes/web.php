<?php

use App\Http\Controllers\FraccionamientoController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard', [
        'fraccionamientosCount' => \App\Models\Fraccionamiento::count(),
        'ownersCount' => \App\Models\Owner::count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('owners', OwnerController::class);
    Route::resource('fraccionamientos', FraccionamientoController::class);
});

require __DIR__.'/auth.php';
