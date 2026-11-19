<?php

namespace Tests\Feature;

use App\Models\Diary;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Chapter10ImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_and_read_a_private_diary_image(): void
    {
        $this->withoutVite();
        // Arrange
        Storage::fake('local');
        $author = User::factory()->create();
        $diary = Diary::create(['user_id' => $author->id, 'title' => '写真', 'body' => '本文', 'visibility' => 'private']);

        // Act
        $response = $this->actingAs($author)->post('/diaries/'.$diary->id.'/images', ['image' => UploadedFile::fake()->image('photo.jpg', 100, 100)]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseCount('media', 1);
        $this->get('/media/'.Media::query()->sole()->id)->assertOk()->assertHeader('content-type', 'image/webp');
    }
}
