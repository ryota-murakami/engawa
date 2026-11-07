<?php

use App\Http\Controllers\GreetingController;
use Illuminate\Support\Facades\Route;

Route::get('/learn', GreetingController::class)->middleware(['auth', 'verified'])->name('learing');
