<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AstronautController;

Route::post('/astronauts', [AstronautController::class, 'getAstronautsByNationality']);





