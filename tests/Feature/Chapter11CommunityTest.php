<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter11CommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_owner_can_start_a_topic(): void
    {
        $this->withoutVite();
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $this->post('/communities', ['name' => '学習会', 'description' => 'Laravelを学ぶ'])->assertRedirect();
        $community = Community::query()->where('name', '学習会')->sole();
        $this->post('/communities/'.$community->id.'/topics', ['title' => '自己紹介', 'body' => 'はじめまして'])->assertRedirect();

        // Assert
        $this->assertDatabaseHas('memberships', ['community_id' => $community->id, 'user_id' => $user->id, 'role' => 'owner']);
        $this->assertDatabaseHas('topics', ['community_id' => $community->id, 'title' => '自己紹介']);
    }
}
