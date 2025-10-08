<?php

use Illuminate\Support\Facades\Route;

//pantalla de bienvenida
Route::get('/', function () {
    return view('welcome');
});

//pantalla principal de la aplicacion (despues de iniciar sesion)
Route::get('/dashboard', function(){
    return view('dashboard');
});
