<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(auth()->check() ? '/home' : '/login'))->name('home');
Route::get('/dashboard', fn () => redirect('/home'))
    ->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/social.php';

require __DIR__.'/learning.php';
