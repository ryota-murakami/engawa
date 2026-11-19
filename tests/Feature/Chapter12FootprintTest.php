<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter12FootprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_get_does_not_record_a_visit_but_explicit_post_does(): void
    {
        $this->withoutVite();
        // Arrange
        $visitor = User::factory()->create();
        $owner = User::factory()->create();
        $this->actingAs($visitor);

        // Act / Assert: prefetch and normal GET must not write a visit.
        $this->get('/members/'.$owner->id)->assertOk();
        $this->assertDatabaseCount('footprints', 0);
        $this->post('/members/'.$owner->id.'/footprint')->assertRedirect();
        $this->assertDatabaseCount('footprints', 1);
    }
}
