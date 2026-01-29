<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('dashboard'));

// 💣 SIMULACIÓN DE FALLO DE SINTAXIS
Route::get('/fallo', function () {
    return "Esto falla" // <--- FALTA PUNTO Y COMA
})
