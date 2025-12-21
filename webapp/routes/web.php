<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Manage\Districts;
use App\Livewire\Manage\Populations;
use App\Livewire\Manage\Elections;
use App\Livewire\Manage\ResultsImport;
use App\Livewire\Manage\Results;

use App\Livewire\VoterRegistry\Index as VoterRegistryIndex;

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
    

    Route::get('/manage/districts', Districts::class)->name('manage.districts');
    Route::get('/manage/populations', Populations::class)->name('manage.populations');
    Route::get('/manage/elections', Elections::class)->name('manage.elections');

    // 🔹 New: NEC results import screen
    Route::get('/manage/results/import', ResultsImport::class)->name('manage.results.import');
    Route::get('/manage/results', Results::class)->name('manage.results');

    Route::get('manage/voter-registry', VoterRegistryIndex::class)->name('manage.voter-registry');
});
