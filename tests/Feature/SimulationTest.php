<?php

namespace Tests\Feature;

use App\Models\Diary;
use App\Models\Footprint;
use App\Models\Persona;
use App\Models\SimState;
use App\Models\User;
use App\Services\Simulation\PersonaMaker;
use App\Services\Simulation\SimClock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SimulationTest extends TestCase
{
    use RefreshDatabase;

    private function clock(): SimClock
    {
        SimState::put('clock', [
            'real_start' => '2026-11-20T00:00:00+00:00',
            'real_end' => '2027-11-11T00:00:00+00:00',
            'sim_start' => '2006-01-01T00:00:00+09:00',
            'sim_end' => '2006-12-31T23:59:59+09:00',
        ]);

        return new SimClock;
    }

    public function test_clock_maps_real_time_into_the_2006_simulation_year(): void
    {
        // Arrange
        $clock = $this->clock();
        $span = CarbonImmutable::parse('2026-11-20T00:00:00+00:00')
            ->diffInSeconds(CarbonImmutable::parse('2027-11-11T00:00:00+00:00'), false);
        $midpoint = CarbonImmutable::parse('2026-11-20T00:00:00+00:00')->addSeconds((int) ($span / 2));

        // Act
        $simNow = $clock->at($midpoint);

        // Assert
        $this->assertSame(2006, $simNow->year);
        $this->assertGreaterThan(CarbonImmutable::parse('2006-04-01', 'Asia/Tokyo'), $simNow);
        $this->assertLessThan(CarbonImmutable::parse('2006-10-01', 'Asia/Tokyo'), $simNow);
        $this->assertFalse($clock->ended(CarbonImmutable::parse('2026-12-01')));
        $this->assertTrue($clock->ended(CarbonImmutable::parse('2027-11-12')));
    }

    public function test_persona_definitions_are_deterministic_and_plausible(): void
    {
        // Act
        $a = PersonaMaker::definition(0);
        $b = PersonaMaker::definition(0);
        $c = PersonaMaker::definition(7);

        // Assert
        $this->assertSame($a, $b);
        $this->assertNotSame($a['name'], $c['name']);
        $this->assertStringEndsWith('@persona.engawa.invalid', $a['email']);
        $this->assertGreaterThanOrEqual(18, $a['age']);
        $this->assertContains($a['voice'], ['genki', 'cool', 'otaku', 'gyaru', 'kansai', 'soft', 'teinei', 'adult']);
    }

    public function test_tick_bootstrap_creates_personas_and_2006_dated_activity(): void
    {
        // Arrange
        Queue::fake();
        config()->set('engawa.simulation.persona_target', 30);
        $this->clock();

        // Act
        $this->artisan('engawa:tick')->assertSuccessful();
        $this->artisan('engawa:tick')->assertSuccessful();

        // Assert: personas exist, wrapped Users have no email notifications.
        $personaCount = Persona::count();
        $this->assertGreaterThanOrEqual(3, $personaCount);
        $this->assertSame(0, User::where('email_social_notifications', true)
            ->whereIn('id', Persona::select('user_id'))->count());

        // Assert: any generated content is stamped inside calendar 2006.
        $diary = Diary::latest('created_at')->first();
        if ($diary !== null) {
            $this->assertSame(2006, $diary->created_at->year);
            $this->assertContains($diary->visibility, ['members', 'friends', 'private']);
        }
        $this->assertDatabaseHas('sim_state', ['key' => 'last_tick']);
    }

    public function test_tick_does_nothing_when_disabled_or_after_campaign_end(): void
    {
        // Arrange
        Queue::fake();
        config()->set('engawa.simulation.enabled', false);

        // Act
        $this->artisan('engawa:tick')->assertSuccessful();

        // Assert
        $this->assertSame(0, Persona::count());

        // Arrange: re-enable but move the campaign end into the past.
        config()->set('engawa.simulation.enabled', true);
        SimState::put('clock', [
            'real_start' => '2026-01-01T00:00:00+00:00',
            'real_end' => '2026-01-02T00:00:00+00:00',
            'sim_start' => '2006-01-01T00:00:00+09:00',
            'sim_end' => '2006-12-31T23:59:59+09:00',
        ]);

        // Act
        $this->artisan('engawa:tick')->assertSuccessful();

        // Assert
        $this->assertSame(0, Persona::count());
    }

    public function test_footprints_from_personas_survive_the_30_day_prune(): void
    {
        // Arrange: a persona footprint stamped in 2006 and a stale real footprint.
        $this->clock();
        config()->set('engawa.simulation.persona_target', 10);
        $this->artisan('engawa:tick')->assertSuccessful();
        $persona = Persona::firstOrFail();
        $realUser = User::factory()->create();

        Footprint::create([
            'owner_id' => $realUser->id,
            'visitor_id' => $persona->user_id,
            'visited_on' => '2006-06-15',
            'last_visited_at' => '2006-06-15 12:00:00',
        ]);
        $staleVisitor = User::factory()->create();
        Footprint::create([
            'owner_id' => $persona->user_id,
            'visitor_id' => $staleVisitor->id,
            'visited_on' => '2006-01-01',
            'last_visited_at' => now()->subDays(40),
        ]);

        // Act: run the same prune closure the scheduler registers.
        Footprint::query()->where('last_visited_at', '<', now()->subDays(30))
            ->whereNotIn('visitor_id', Persona::query()->select('user_id'))->delete();

        // Assert: the persona's 2006 footprint stays; the real stale one is gone.
        $this->assertDatabaseHas('footprints', [
            'visitor_id' => $persona->user_id,
            'visited_on' => '2006-06-15',
        ]);
        $this->assertDatabaseMissing('footprints', ['visitor_id' => $staleVisitor->id]);
    }
}
