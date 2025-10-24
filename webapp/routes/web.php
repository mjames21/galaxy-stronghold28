<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', \App\Livewire\Dashboard\Index::class)->name('dashboard');

    Route::get('/forecast', \App\Livewire\ForecastEngine\Index::class)->name('forecast');
    Route::get('/seats', \App\Livewire\SeatProjection\Index::class)->name('seats');
    Route::get('/gotv', \App\Livewire\GOTVLab\Index::class)->name('gotv');
    Route::get('/scenarios', \App\Livewire\ScenarioLab\Index::class)->name('scenarios');
    Route::get('/pvt', \App\Livewire\PVTVerifier\Index::class)->name('pvt');
});
