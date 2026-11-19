<?php

namespace Tests\Feature;

use App\Jobs\NotifySocialActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter13NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_friend_request_enqueues_a_notification_for_the_recipient(): void
    {
        $this->withoutVite();
        // Arrange
        Queue::fake();
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        // Act
        $this->actingAs($alice)->post('/members/'.$bob->id.'/friend-request')->assertRedirect();

        // Assert
        Queue::assertPushed(NotifySocialActivity::class, 1);
        $this->actingAs($bob)->get('/notifications')->assertOk();
    }
}
