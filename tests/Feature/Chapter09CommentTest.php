<?php

namespace Tests\Feature;

use App\Models\Diary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter09CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_comment_on_a_visible_diary(): void
    {
        $this->withoutVite();
        // Arrange
        $author = User::factory()->create();
        $reader = User::factory()->create();
        $diary = Diary::create(['user_id' => $author->id, 'title' => '日記', 'body' => '本文', 'visibility' => 'members']);

        // Act
        $response = $this->actingAs($reader)->post('/diaries/'.$diary->id.'/comments', ['body' => '読みました']);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('diary_comments', ['diary_id' => $diary->id, 'body' => '読みました', 'user_id' => $reader->id]);
        $this->get('/diaries/'.$diary->id)->assertInertia(fn (Assert $page) => $page->where('comments.total', 1));
    }
}
