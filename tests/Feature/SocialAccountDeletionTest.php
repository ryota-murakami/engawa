<?php

namespace Tests\Feature;

use App\Jobs\DeleteMediaObject;
use App\Models\Community;
use App\Models\Diary;
use App\Models\Media;
use App\Models\Membership;
use App\Models\User;
use App\Services\DeleteAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SocialAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_cleans_owned_data_and_schedules_objects_without_touching_other_users(): void
    {
        Queue::fake();
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $diary = Diary::create(['user_id' => $owner->id, 'title' => '私の日記', 'body' => '本文', 'visibility' => 'private']);
        $otherDiary = Diary::create(['user_id' => $other->id, 'title' => '別の会員の日記', 'body' => '本文', 'visibility' => 'members']);
        $image = Media::create([
            'user_id' => $owner->id, 'diary_id' => $diary->id, 'kind' => 'diary',
            'disk' => 'local', 'path' => 'media/account-delete.webp', 'mime' => 'image/webp', 'size' => 1,
        ]);
        Storage::disk('local')->put($image->path, 'placeholder');
        $community = Community::create(['owner_id' => $owner->id, 'name' => '閉鎖する会', 'description' => '説明']);
        Membership::create(['community_id' => $community->id, 'user_id' => $owner->id, 'role' => 'owner']);
        Membership::create(['community_id' => $community->id, 'user_id' => $other->id, 'role' => 'member']);
        $owner->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['kind' => 'test'],
        ]);

        app(DeleteAccount::class)->handle($owner);

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('diaries', ['id' => $diary->id]);
        $this->assertDatabaseMissing('media', ['id' => $image->id]);
        $this->assertDatabaseMissing('communities', ['id' => $community->id]);
        $this->assertDatabaseMissing('memberships', ['community_id' => $community->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $owner->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
        $this->assertDatabaseHas('diaries', ['id' => $otherDiary->id]);
        Queue::assertPushed(DeleteMediaObject::class);

        // Queue::fakeでは外部削除を実行しない。ジョブ本体は再試行も安全。
        $job = new DeleteMediaObject('local', $image->path);
        $job->handle();
        $job->handle();
        Storage::disk('local')->assertMissing($image->path);
    }

    public function test_all_social_pages_render_with_database_relations(): void
    {
        Queue::fake();
        $this->withoutVite();
        $owner = User::factory()->create();
        $diary = Diary::create(['user_id' => $owner->id, 'title' => '日記', 'body' => '本文', 'visibility' => 'members']);
        $community = Community::create(['owner_id' => $owner->id, 'name' => '会', 'description' => '説明']);
        Membership::create(['community_id' => $community->id, 'user_id' => $owner->id, 'role' => 'owner']);
        $topic = $community->topics()->create(['user_id' => $owner->id, 'title' => '話題', 'body' => '本文']);
        $this->actingAs($owner);
        foreach ([
            '/home', '/members', '/members/'.$owner->id, '/profile/edit', '/diaries/create',
            '/diaries/'.$diary->id, '/diaries/'.$diary->id.'/edit', '/friends',
            '/communities', '/communities/create', '/communities/'.$community->id,
            '/communities/'.$community->id.'/topics/create', '/topics/'.$topic->id,
            '/footprints', '/notifications', '/search?'.http_build_query(['q' => '日記']),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
