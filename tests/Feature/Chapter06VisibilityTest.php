<?php

namespace Tests\Feature;

use App\Models\Diary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter06VisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_members_cannot_query_a_private_diary(): void
    {
        $this->withoutVite();
        // Arrange
        /** @var User $author */
        $author = User::factory()->createOne();
        /** @var User $viewer */
        $viewer = User::factory()->createOne();
        Diary::create(['user_id' => $author->id, 'title' => '秘密', 'body' => '非公開', 'visibility' => 'private']);

        // Act
        $visibleToViewer = Diary::query()->visibleTo($viewer)->count();
        $visibleToAuthor = Diary::query()->visibleTo($author)->count();

        // Assert
        $this->assertSame(0, $visibleToViewer);
        $this->assertSame(1, $visibleToAuthor);
    }
}
