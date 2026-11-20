<?php

use App\Models\Footprint;
use App\Models\Persona;
use Illuminate\Support\Facades\Schedule;

// Simulation footprints are stamped in 2006 — keep them out of the 30-day prune.
Schedule::call(fn () => Footprint::query()
    ->where('last_visited_at', '<', now()->subDays(30))
    ->whereNotIn('visitor_id', Persona::query()->select('user_id'))->delete())
    ->daily()->name('social:prune-footprints')->withoutOverlapping();

Schedule::command('engawa:tick')
    ->everyFiveMinutes()
    ->name('engawa:tick')
    ->withoutOverlapping()
    ->onOneServer();
