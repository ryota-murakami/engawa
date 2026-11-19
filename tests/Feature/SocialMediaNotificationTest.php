<?php

namespace Tests\Feature;

use App\Jobs\DeleteMediaObject;
use App\Jobs\NotifySocialActivity;
use App\Models\Block;
use App\Models\Diary;
use App\Models\DiaryComment;
use App\Models\Friendship;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SocialMediaNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'social.media_disk' => 'local',
            'images.default' => 'gd',
            'mail.default' => 'array',
            'mail.from.address' => 'no-reply@example.test',
            'mail.from.name' => 'Engawa',
        ]);
        Storage::fake('local');
        Queue::fake();
    }

    public function test_upload_normalizes_orientation_and_size_and_removes_exif(): void
    {
        $owner = User::factory()->create();
        $diary = $this->diary($owner);
        $marker = 'PRIVATE-GPS-AND-CAMERA-METADATA';
        $file = $this->orientedJpeg($marker);

        // The fixture really contains an orientation and private metadata.
        $inputExif = exif_read_data($file->getRealPath());
        $this->assertSame(6, $inputExif['Orientation']);
        $this->assertSame($marker, $inputExif['ImageDescription']);

        $this->actingAs($owner)->from('/diaries/'.$diary->id)
            ->post('/diaries/'.$diary->id.'/images', ['image' => $file])
            ->assertRedirect('/diaries/'.$diary->id);

        $media = Media::query()->sole();
        $bytes = Storage::disk('local')->get($media->path);
        $size = getimagesizefromstring($bytes);

        $this->assertSame('image/webp', $media->mime);
        $this->assertSame(strlen($bytes), $media->size);
        $this->assertSame('image/webp', $size['mime']);
        $this->assertSame([800, 1600], [$size[0], $size[1]]);
        $this->assertStringNotContainsString($marker, $bytes);
        $this->assertNotContains('EXIF', $this->webpChunks($bytes));
        $this->assertNotContains('XMP ', $this->webpChunks($bytes));
        $this->assertNotSame('camera.jpg', basename($media->path));
    }

    public static function invalidImages(): array
    {
        return [
            'SVG' => ['svg'],
            'SVG renamed as JPEG' => ['renamed-svg'],
            'code renamed as JPEG' => ['not-an-image'],
            'over 2 MiB' => ['too-large'],
            'over 4096 pixels on one axis' => ['too-wide'],
            'over eight million total pixels' => ['too-many-pixels'],
        ];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_uploads_do_not_create_files_or_media_rows(string $case): void
    {
        $owner = User::factory()->create();
        $diary = $this->diary($owner);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><script>alert(1)</script></svg>';
        $file = match ($case) {
            'svg' => UploadedFile::fake()->createWithContent('payload.svg', $svg),
            'renamed-svg' => UploadedFile::fake()->createWithContent('payload.jpg', $svg),
            'not-an-image' => UploadedFile::fake()->createWithContent('payload.jpg', '<?php echo "unsafe";'),
            'too-large' => UploadedFile::fake()->image('photo.jpg', 32, 32)->size(2049),
            'too-wide' => UploadedFile::fake()->image('photo.jpg', 4097, 8),
            'too-many-pixels' => UploadedFile::fake()->image('photo.jpg', 3000, 3000),
        };

        $this->actingAs($owner)->post('/diaries/'.$diary->id.'/images', ['image' => $file], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_fourth_image_is_rejected_without_leaving_an_extra_object(): void
    {
        $owner = User::factory()->create();
        $diary = $this->diary($owner);
        $this->actingAs($owner);

        for ($i = 0; $i < 3; $i++) {
            $this->from('/diaries/'.$diary->id)->post('/diaries/'.$diary->id.'/images', [
                'image' => UploadedFile::fake()->image('photo-'.$i.'.png', 32, 32),
            ])->assertRedirect('/diaries/'.$diary->id);
        }

        $this->post('/diaries/'.$diary->id.'/images', [
            'image' => UploadedFile::fake()->image('fourth.webp', 32, 32),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('media', 3);
        $this->assertCount(3, Storage::disk('local')->allFiles());
    }

    public function test_another_member_cannot_upload_to_or_delete_someones_diary_media(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $diary = $this->diary($owner);
        $media = $this->uploadImage($owner, $diary);

        $this->actingAs($attacker)->post('/diaries/'.$diary->id.'/images', [
            'image' => UploadedFile::fake()->image('forged.jpg', 32, 32),
        ])->assertNotFound();
        $this->delete('/media/'.$media->id)->assertNotFound();

        $this->assertDatabaseCount('media', 1);
        Storage::disk('local')->assertExists($media->path);
        Queue::assertNotPushed(DeleteMediaObject::class);
    }

    public function test_media_stream_rechecks_visibility_and_block_on_every_request(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();
        $this->friendship($owner, $friend, 'accepted');
        $diary = $this->diary($owner, 'friends');
        $media = $this->uploadImage($owner, $diary);
        $url = '/media/'.$media->id;

        $response = $this->actingAs($friend)->get($url)->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertStreamedContent(Storage::disk('local')->get($media->path));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->actingAs($stranger)->get($url)->assertNotFound();

        $diary->update(['visibility' => 'private']);
        $this->actingAs($friend)->get($url)->assertNotFound();
        $this->actingAs($owner)->get($url)->assertOk();

        $diary->update(['visibility' => 'members']);
        $this->actingAs($friend)->get($url)->assertOk();
        Block::create(['blocker_id' => $friend->id, 'blocked_id' => $owner->id]);
        $this->get($url)->assertNotFound();
    }

    public function test_avatar_and_its_direct_media_url_are_hidden_after_a_block(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $this->actingAs($owner)->from('/profile/edit')->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.png', 64, 64),
        ])->assertRedirect('/profile/edit');
        $avatar = Media::query()->sole();

        $this->actingAs($viewer)->get('/members/'.$owner->id.'/avatar')->assertOk();
        $this->get('/media/'.$avatar->id)->assertOk();
        $this->delete('/media/'.$avatar->id)->assertNotFound();

        Block::create(['blocker_id' => $owner->id, 'blocked_id' => $viewer->id]);
        $this->get('/members/'.$owner->id.'/avatar')->assertNotFound();
        $this->get('/media/'.$avatar->id)->assertNotFound();
    }

    public function test_object_cleanup_is_queued_and_repeatable_and_preserves_referenced_files(): void
    {
        $owner = User::factory()->create();
        $diary = $this->diary($owner);
        $media = $this->uploadImage($owner, $diary);
        $cleanup = new DeleteMediaObject($media->disk, $media->path);

        $cleanup->handle();
        Storage::disk('local')->assertExists($media->path);

        $this->actingAs($owner)->from('/diaries/'.$diary->id)
            ->delete('/media/'.$media->id)->assertRedirect('/diaries/'.$diary->id);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('local')->assertExists($media->path);
        Queue::assertPushed(DeleteMediaObject::class, fn (DeleteMediaObject $job): bool => $job->disk === $media->disk && $job->path === $media->path && $job->afterCommit === true
        );

        $cleanup->handle();
        $cleanup->handle();
        Storage::disk('local')->assertMissing($media->path);
    }

    public static function invalidatedNotifications(): array
    {
        return [
            'blocked before worker runs' => ['block'],
            'request cancelled before worker runs' => ['cancel'],
            'recipient is no longer verified' => ['unverified'],
        ];
    }

    #[DataProvider('invalidatedNotifications')]
    public function test_notification_job_rechecks_current_state_before_delivery(string $change): void
    {
        $actor = User::factory()->create();
        $recipient = User::factory()->create();
        $friendship = $this->friendship($actor, $recipient);
        $job = new NotifySocialActivity($recipient->id, $actor->id, 'friend-request', $friendship->id);

        match ($change) {
            'block' => Block::create(['blocker_id' => $recipient->id, 'blocked_id' => $actor->id]),
            'cancel' => $friendship->delete(),
            'unverified' => $recipient->forceFill(['email_verified_at' => null])->save(),
        };
        $job->handle();

        $this->assertDatabaseCount('notifications', 0);
        $this->assertCount(0, $this->mailMessages());
        Queue::assertNothingPushed();
    }

    public static function mailPreferences(): array
    {
        return ['opted in' => [true, 1], 'opted out' => [false, 0]];
    }

    #[DataProvider('mailPreferences')]
    public function test_mail_preference_changes_mail_but_keeps_the_in_app_notification(bool $enabled, int $mailCount): void
    {
        $actor = User::factory()->create();
        $recipient = User::factory()->create(['email_social_notifications' => $enabled]);
        $friendship = $this->friendship($actor, $recipient);
        $job = new NotifySocialActivity($recipient->id, $actor->id, 'friend-request', $friendship->id);

        $job->handle();

        $this->assertDatabaseHas('notifications', ['id' => $job->notificationId, 'notifiable_id' => $recipient->id]);
        $this->assertCount($mailCount, $this->mailMessages());
        // The outer job owns asynchronous delivery; the notification does not queue again.
        Queue::assertNothingPushed();
    }

    public function test_retry_of_the_same_serialized_job_does_not_duplicate_the_database_notification(): void
    {
        $actor = User::factory()->create();
        $recipient = User::factory()->create(['email_social_notifications' => false]);
        $friendship = $this->friendship($actor, $recipient);
        $job = new NotifySocialActivity($recipient->id, $actor->id, 'friend-request', $friendship->id);

        $job->handle();
        unserialize(serialize($job))->handle();

        $this->assertDatabaseCount('notifications', 1);
        $notification = $recipient->notifications()->sole();
        $this->assertSame($job->notificationId, $notification->id);
        $this->assertSame([
            'kind' => 'friend-request', 'subject_id' => $friendship->id, 'actor_id' => $actor->id,
        ], $notification->data);
        $this->assertCount(0, $this->mailMessages());
    }

    public function test_comment_notification_does_not_put_private_content_or_actor_name_in_email(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Secret Actor Name']);
        $diary = $this->diary($owner);
        $diary->update(['title' => 'Secret diary title', 'body' => 'Secret diary body']);
        $comment = DiaryComment::create([
            'diary_id' => $diary->id, 'user_id' => $actor->id, 'body' => 'Secret comment body',
        ]);
        $job = new NotifySocialActivity($owner->id, $actor->id, 'diary-comment', $comment->id);

        $job->handle();

        $this->assertCount(1, $this->mailMessages());
        $email = $this->mailMessages()->sole()->getOriginalMessage();
        $body = $email->getTextBody().' '.$email->getHtmlBody();
        $this->assertStringContainsString('/notifications', $body);
        foreach ([$diary->title, $diary->body, $comment->body, $actor->name] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
        $this->assertSame([
            'kind' => 'diary-comment', 'subject_id' => $comment->id, 'actor_id' => $actor->id,
        ], $owner->notifications()->sole()->data);
    }

    public function test_comment_job_does_not_deliver_to_a_forged_recipient_or_for_a_deleted_comment(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $outsider = User::factory()->create();
        $diary = $this->diary($owner);
        $comment = DiaryComment::create(['diary_id' => $diary->id, 'user_id' => $actor->id, 'body' => 'Comment']);

        (new NotifySocialActivity($outsider->id, $actor->id, 'diary-comment', $comment->id))->handle();
        $this->assertDatabaseCount('notifications', 0);
        $comment->delete();
        (new NotifySocialActivity($owner->id, $actor->id, 'diary-comment', $comment->id))->handle();

        $this->assertDatabaseCount('notifications', 0);
        $this->assertCount(0, $this->mailMessages());
    }

    public function test_notification_uuid_cannot_be_marked_read_by_someone_else(): void
    {
        $actor = User::factory()->create();
        $recipient = User::factory()->create(['email_social_notifications' => false]);
        $attacker = User::factory()->create();
        $friendship = $this->friendship($actor, $recipient);
        (new NotifySocialActivity($recipient->id, $actor->id, 'friend-request', $friendship->id))->handle();
        $notification = $recipient->notifications()->sole();

        $this->actingAs($attacker)->patch('/notifications/'.$notification->id.'/read')->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
        $this->actingAs($recipient)->from('/notifications')
            ->patch('/notifications/'.$notification->id.'/read')->assertRedirect('/notifications');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_list_hides_old_items_after_relationship_changes_and_only_exposes_display_fields(): void
    {
        $actor = User::factory()->create();
        $recipient = User::factory()->create(['email_social_notifications' => false]);
        $friendship = $this->friendship($actor, $recipient);
        (new NotifySocialActivity($recipient->id, $actor->id, 'friend-request', $friendship->id))->handle();

        $this->actingAs($recipient)->get('/notifications')->assertInertia(fn (Assert $page) => $page
            ->component('social/notifications', false)
            ->where('notifications.total', 1)
            ->where('notifications.data.0.url', '/friends')
            ->missing('notifications.data.0.data')
            ->missing('notifications.data.0.actor_id')
            ->missing('notifications.data.0.email'));

        Block::create(['blocker_id' => $actor->id, 'blocked_id' => $recipient->id]);
        $this->get('/notifications')->assertInertia(fn (Assert $page) => $page
            ->where('notifications.total', 0)->has('notifications.data', 0));
        $this->assertDatabaseCount('notifications', 1);
    }

    private function diary(User $owner, string $visibility = 'members'): Diary
    {
        return Diary::create([
            'user_id' => $owner->id, 'title' => 'Diary', 'body' => 'Diary body', 'visibility' => $visibility,
        ]);
    }

    private function friendship(User $actor, User $recipient, string $status = 'pending'): Friendship
    {
        return Friendship::create([
            'user_low_id' => min($actor->id, $recipient->id),
            'user_high_id' => max($actor->id, $recipient->id),
            'requested_by' => $actor->id,
            'status' => $status,
        ]);
    }

    private function uploadImage(User $owner, Diary $diary): Media
    {
        $this->actingAs($owner)->from('/diaries/'.$diary->id)->post('/diaries/'.$diary->id.'/images', [
            'image' => UploadedFile::fake()->image('photo.webp', 64, 32),
        ])->assertRedirect('/diaries/'.$diary->id);

        return Media::query()->where('diary_id', $diary->id)->latest('id')->firstOrFail();
    }

    private function mailMessages(): Collection
    {
        // Array transport renders real notification email without contacting a provider.
        return Mail::mailer('array')->getSymfonyTransport()->messages();
    }

    private function orientedJpeg(string $marker): UploadedFile
    {
        $jpeg = UploadedFile::fake()->image('camera.jpg', 2000, 1000)->get();
        $description = $marker."\0";
        // TIFF little-endian header, two IFD entries, then the description bytes.
        $tiff = 'II'.pack('vV', 42, 8)
            .pack('v', 2)
            .pack('vvVv', 0x0112, 3, 1, 6)."\0\0"
            .pack('vvVV', 0x010E, 2, strlen($description), 38)
            .pack('V', 0).$description;
        $exif = "Exif\0\0".$tiff;
        $withExif = substr($jpeg, 0, 2)."\xff\xe1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);

        return UploadedFile::fake()->createWithContent('camera.jpg', $withExif);
    }

    /** @return list<string> */
    private function webpChunks(string $bytes): array
    {
        $chunks = [];
        for ($offset = 12; $offset + 8 <= strlen($bytes);) {
            $chunks[] = substr($bytes, $offset, 4);
            $length = unpack('Vlength', substr($bytes, $offset + 4, 4))['length'];
            $offset += 8 + $length + ($length % 2);
        }

        return $chunks;
    }
}
