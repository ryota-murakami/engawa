<?php

use App\Http\Controllers\Social\DiaryController;
use App\Http\Controllers\Social\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->name('social.')->group(function (): void {
    Route::get('/home', HomeController::class)->name('home');

    Route::get('/diaries/create', [DiaryController::class, 'create'])->name('diaries.create');
    Route::post('/diaries', [DiaryController::class, 'store'])->middleware('throttle:20,1,social-write')->name('diaries.store');
    Route::get('/diaries/{diary}', [DiaryController::class, 'show'])->name('diaries.show');
    Route::get('/diaries/{diary}/edit', [DiaryController::class, 'edit'])->name('diaries.edit');
    Route::patch('/diaries/{diary}', [DiaryController::class, 'update'])->middleware('throttle:30,1,social-write')->name('diaries.update');
    Route::delete('/diaries/{diary}', [DiaryController::class, 'destroy'])->name('diaries.destroy');

});
