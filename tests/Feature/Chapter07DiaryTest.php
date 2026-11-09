<?php

namespace Tests\Feature;

use App\Models\Diary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter07DiaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_write_edit_and_delete_their_diary(): void
    {
        $this->withoutVite();
        // Arrange
        /** @var User $user */
        $user = User::factory()->createOne();
        $this->actingAs($user);

        // Act
        $created = $this->post('/diaries', ['title' => '最初の日記', 'body' => 'こんにちは', 'visibility' => 'members']);

        // Assert
        $created->assertRedirect();
        $this->assertDatabaseHas('diaries', ['title' => '最初の日記', 'user_id' => $user->id]);
        $diary = Diary::query()->sole();
        $this->get('/diaries/'.$diary->id)->assertOk();
        $this->patch('/diaries/'.$diary->id, ['title' => '更新した日記', 'body' => '更新', 'visibility' => 'private'])->assertRedirect();
        $this->assertDatabaseHas('diaries', ['title' => '更新した日記', 'visibility' => 'private']);
        $this->delete('/diaries/'.$diary->id)->assertRedirect('/home');
        $this->assertDatabaseCount('diaries', 0);
    }
}
