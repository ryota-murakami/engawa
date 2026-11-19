<?php

namespace Tests\Feature;

use App\Models\Diary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter14SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_excludes_other_members_private_diaries(): void
    {
        $this->withoutVite();
        // Arrange
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        Diary::create(['user_id' => $author->id, 'title' => '学習日記', 'body' => '本文', 'visibility' => 'members']);
        Diary::create(['user_id' => $author->id, 'title' => '学習秘密', 'body' => '本文', 'visibility' => 'private']);

        // Act
        $response = $this->actingAs($viewer)->get('/search?'.http_build_query(['q' => '学習']));

        // Assert
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('diaries.total', 1)->where('diaries.data.0.title', '学習日記'));
    }
}
