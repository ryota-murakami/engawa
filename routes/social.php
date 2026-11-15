<?php

use App\Http\Controllers\Social\DiaryController;
use App\Http\Controllers\Social\FriendController;
use App\Http\Controllers\Social\HomeController;
use App\Http\Controllers\Social\MemberController;
use App\Http\Controllers\Social\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->name('social.')->group(function (): void {
    Route::get('/home', HomeController::class)->name('home');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{user}', [MemberController::class, 'show'])->name('members.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:30,1,social-profile')->name('profile.update');

    Route::get('/diaries/create', [DiaryController::class, 'create'])->name('diaries.create');
    Route::post('/diaries', [DiaryController::class, 'store'])->middleware('throttle:20,1,social-write')->name('diaries.store');
    Route::get('/diaries/{diary}', [DiaryController::class, 'show'])->name('diaries.show');
    Route::get('/diaries/{diary}/edit', [DiaryController::class, 'edit'])->name('diaries.edit');
    Route::patch('/diaries/{diary}', [DiaryController::class, 'update'])->middleware('throttle:30,1,social-write')->name('diaries.update');
    Route::delete('/diaries/{diary}', [DiaryController::class, 'destroy'])->name('diaries.destroy');

    Route::get('/friends', [FriendController::class, 'index'])->name('friends');
    Route::post('/members/{user}/friend-request', [FriendController::class, 'store'])->middleware('throttle:10,1,social-friends')->name('friends.store');
    Route::patch('/friendships/{friendship}/accept', [FriendController::class, 'accept'])->middleware('throttle:30,1,social-friends')->name('friends.accept');
    Route::delete('/friendships/{friendship}', [FriendController::class, 'destroy'])->name('friends.destroy');
    Route::post('/members/{user}/block', [FriendController::class, 'block'])->middleware('throttle:30,1,social-blocks')->name('blocks.store');
    Route::delete('/members/{user}/block', [FriendController::class, 'unblock'])->name('blocks.destroy');


});