<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TaskController;

Route::get('/',  [HomeController::class, 'i']);
Route::get('/{is_vue_pade}',  [HomeController::class, 'index'])->name('home')->where( 'is_vue_pade', '.*');
