<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Social\CommentController;
use App\Http\Controllers\Social\CommunityController;
use App\Http\Controllers\Social\DiaryController;
use App\Http\Controllers\Social\FootprintController;
use App\Http\Controllers\Social\FriendController;
use App\Http\Controllers\Social\HomeController;
use App\Http\Controllers\Social\MemberController;
use App\Http\Controllers\Social\ProfileController;
use App\Http\Controllers\Social\TopicController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->name('social.')->group(function (): void {
    Route::get('/home', HomeController::class)->name('home');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{user}', [MemberController::class, 'show'])->name('members.show');
    Route::get('/members/{user}/avatar', [MediaController::class, 'avatarShow'])->name('members.avatar');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:30,1,social-profile')->name('profile.update');
    Route::post('/profile/avatar', [MediaController::class, 'avatarStore'])->middleware('throttle:10,1,social-media')->name('profile.avatar.store');
    Route::delete('/profile/avatar', [MediaController::class, 'avatarDestroy'])->name('profile.avatar.destroy');

    Route::get('/diaries/create', [DiaryController::class, 'create'])->name('diaries.create');
    Route::post('/diaries', [DiaryController::class, 'store'])->middleware('throttle:20,1,social-write')->name('diaries.store');
    Route::get('/diaries/{diary}', [DiaryController::class, 'show'])->name('diaries.show');
    Route::get('/diaries/{diary}/edit', [DiaryController::class, 'edit'])->name('diaries.edit');
    Route::patch('/diaries/{diary}', [DiaryController::class, 'update'])->middleware('throttle:30,1,social-write')->name('diaries.update');
    Route::delete('/diaries/{diary}', [DiaryController::class, 'destroy'])->name('diaries.destroy');
    Route::post('/diaries/{diary}/comments', [CommentController::class, 'store'])->middleware('throttle:20,1,social-comments')->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/diaries/{diary}/images', [MediaController::class, 'store'])->middleware('throttle:10,1,social-media')->name('media.store');
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/friends', [FriendController::class, 'index'])->name('friends');
    Route::post('/members/{user}/friend-request', [FriendController::class, 'store'])->middleware('throttle:10,1,social-friends')->name('friends.store');
    Route::patch('/friendships/{friendship}/accept', [FriendController::class, 'accept'])->middleware('throttle:30,1,social-friends')->name('friends.accept');
    Route::delete('/friendships/{friendship}', [FriendController::class, 'destroy'])->name('friends.destroy');
    Route::post('/members/{user}/block', [FriendController::class, 'block'])->middleware('throttle:30,1,social-blocks')->name('blocks.store');
    Route::delete('/members/{user}/block', [FriendController::class, 'unblock'])->name('blocks.destroy');

    Route::get('/communities', [CommunityController::class, 'index'])->name('communities.index');
    Route::get('/communities/create', [CommunityController::class, 'create'])->name('communities.create');
    Route::post('/communities', [CommunityController::class, 'store'])->middleware('throttle:10,1,social-communities')->name('communities.store');
    Route::get('/communities/{community}', [CommunityController::class, 'show'])->name('communities.show');
    Route::post('/communities/{community}/join', [CommunityController::class, 'join'])->middleware('throttle:20,1,social-communities')->name('communities.join');
    Route::delete('/communities/{community}/membership', [CommunityController::class, 'leave'])->name('communities.leave');
    Route::delete('/communities/{community}', [CommunityController::class, 'destroy'])->name('communities.destroy');
    Route::get('/communities/{community}/topics/create', [TopicController::class, 'create'])->name('topics.create');
    Route::post('/communities/{community}/topics', [TopicController::class, 'store'])->middleware('throttle:20,1,social-topics')->name('topics.store');
    Route::get('/topics/{topic}', [TopicController::class, 'show'])->name('topics.show');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    Route::post('/topics/{topic}/replies', [TopicController::class, 'reply'])->middleware('throttle:20,1,social-replies')->name('replies.store');
    Route::delete('/replies/{reply}', [TopicController::class, 'deleteReply'])->name('replies.destroy');

    Route::post('/members/{user}/footprint', [FootprintController::class, 'store'])->middleware('throttle:60,1,social-footprints')->name('footprints.store');
    Route::get('/footprints', [FootprintController::class, 'index'])->name('footprints.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});
