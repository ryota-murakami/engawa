<?php

namespace App\Console\Commands;

use App\Services\Simulation\ActivityEngine;
use Illuminate\Console\Command;

/**
 * `engawa:tick` — one heartbeat of the 2006 mixi reenactment.
 *
 * Run by the Laravel scheduler every 5 minutes; each call grows the persona
 * population and emits a small batch of sim-time-stamped social activity.
 * Safe to run manually (`php artisan engawa:tick`) for inspection.
 */
class EngawaTick extends Command
{
    protected $signature = 'engawa:tick';

    protected $description = 'Advance the 2006 mixi simulation by one tick';

    public function handle(ActivityEngine $engine): int
    {
        $stats = $engine->tick();

        $this->components->info('engawa tick: '.collect($stats)->map(fn ($v, $k) => "$k=$v")->implode(' '));

        return self::SUCCESS;
    }
}
