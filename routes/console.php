<?php

use App\Models\Footprint;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => Footprint::query()
    ->where('last_visited_at', '<', now()->subDays(30))->delete())
    ->daily()->name('social:prune-footprints')->withoutOverlapping();
