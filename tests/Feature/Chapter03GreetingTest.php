<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter03GreetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_member_sees_the_greeting_and_member_count(): void
    {
        $this->withoutVite();

        // Arrange
        $user = User::factory()->create(['name' => 'アリス']);

        // Act
        $response = $this->actingAs($user)->get('/learn');

        // Assert
        $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('course/hello')->where('message', 'こんにちは、アリスさん')->where('memberCount', 1));
    }
}
