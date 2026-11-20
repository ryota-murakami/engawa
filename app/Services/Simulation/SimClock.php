<?php

namespace App\Services\Simulation;

use App\Models\SimState;
use Carbon\CarbonImmutable;

/**
 * Maps real time onto the replayed year 2006.
 *
 * The clock is anchored at the first tick: [real_start .. real_end] compresses
 * linearly onto [sim_start .. sim_end], so generated activity is always stamped
 * inside calendar-2006 (JST) and the campaign ends exactly at sim year-end.
 */
class SimClock
{
    public const JST = 'Asia/Tokyo';

    private CarbonImmutable $realStart;

    private CarbonImmutable $realEnd;

    private CarbonImmutable $simStart;

    private CarbonImmutable $simEnd;

    public function __construct(
        private string $stateKey = 'clock',
    ) {
        $epoch = $this->ensureEpoch();

        $this->realStart = CarbonImmutable::parse($epoch['real_start']);
        $this->realEnd = CarbonImmutable::parse($epoch['real_end']);
        $this->simStart = CarbonImmutable::parse($epoch['sim_start']);
        $this->simEnd = CarbonImmutable::parse($epoch['sim_end']);
    }

    /**
     * Lazily persist the clock epoch so every process shares one timeline.
     *
     * @return array<string, string>
     */
    private function ensureEpoch(): array
    {
        $epoch = SimState::get($this->stateKey);
        if ($epoch !== null) {
            return $epoch;
        }

        $epoch = [
            'real_start' => CarbonImmutable::now()->toIso8601String(),
            'real_end' => CarbonImmutable::parse(config('engawa.simulation.real_end'), self::JST)->toIso8601String(),
            'sim_start' => CarbonImmutable::parse(config('engawa.simulation.sim_start'), self::JST)->toIso8601String(),
            'sim_end' => CarbonImmutable::parse(config('engawa.simulation.sim_end'), self::JST)->toIso8601String(),
        ];
        SimState::put($this->stateKey, $epoch);

        return $epoch;
    }

    public function realEnd(): CarbonImmutable
    {
        return $this->realEnd;
    }

    /** True once the real-time campaign window has passed. */
    public function ended(?CarbonImmutable $realNow = null): bool
    {
        return ($realNow ?? CarbonImmutable::now())->gte($this->realEnd);
    }

    /** The current simulated instant (JST), clamped into the 2006 window. */
    public function now(?CarbonImmutable $realNow = null): CarbonImmutable
    {
        return $this->at($realNow ?? CarbonImmutable::now());
    }

    /** Convert a real instant into the simulated 2006 timeline (JST). */
    public function at(CarbonImmutable $real): CarbonImmutable
    {
        $realSeconds = max(1, $this->realStart->diffInSeconds($this->realEnd, false));
        $simSeconds = $this->simStart->diffInSeconds($this->simEnd, false);

        $elapsed = $this->realStart->diffInSeconds($real, false);
        $elapsed = max(0, min($elapsed, $realSeconds));

        return $this->simStart->addSeconds((int) round($simSeconds * $elapsed / $realSeconds));
    }

    /** A timestamp slightly behind sim-now for organic-looking posting times. */
    public function recentAt(int $maxBackMinutes = 40): CarbonImmutable
    {
        return $this->now()->subMinutes(random_int(0, $maxBackMinutes));
    }
}
