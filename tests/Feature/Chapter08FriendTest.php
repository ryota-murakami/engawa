<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter08FriendTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_can_accept_a_friend_request(): void
    {
        $this->withoutVite();
        // Arrange
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        // Act
        $this->actingAs($alice)->post('/members/'.$bob->id.'/friend-request')->assertRedirect();
        $request = Friendship::query()->sole();
        $this->actingAs($bob)->patch('/friendships/'.$request->id.'/accept')->assertRedirect();

        // Assert
        $this->assertDatabaseHas('friendships', ['id' => $request->id, 'status' => 'accepted']);
        $this->get('/friends')->assertOk();
    }
}
