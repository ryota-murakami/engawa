<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Community;
use App\Models\Diary;
use App\Models\DiaryComment;
use App\Models\Friendship;
use App\Models\Media;
use App\Models\Topic;
use App\Models\User;
use App\Services\SocialGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SocialPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Queue::fake();
    }

    public static function visibilityCases(): array
    {
        return [
            'owner sees members' => ['members', 'owner', 200],
            'owner sees friends' => ['friends', 'owner', 200],
            'owner sees private' => ['private', 'owner', 200],
            'friend sees members' => ['members', 'friend', 200],
            'friend sees friends' => ['friends', 'friend', 200],
            'friend cannot see private' => ['private', 'friend', 404],
            'pending sees members' => ['members', 'pending', 200],
            'pending cannot see friends' => ['friends', 'pending', 404],
            'pending cannot see private' => ['private', 'pending', 404],
            'stranger sees members' => ['members', 'stranger', 200],
            'stranger cannot see friends' => ['friends', 'stranger', 404],
            'stranger cannot see private' => ['private', 'stranger', 404],
        ];
    }

    #[DataProvider('visibilityCases')]
    public function test_diary_visibility_matrix(string $visibility, string $relationship, int $status): void
    {
        $author = User::factory()->create();
        $viewer = $relationship === 'owner' ? $author : User::factory()->create();
        $diary = $this->makeDiary($author, $visibility);

        if (in_array($relationship, ['friend', 'pending'], true)) {
            $this->connect($author, $viewer, $relationship === 'friend' ? 'accepted' : 'pending');
        }

        $this->actingAs($viewer)->get('/diaries/'.$diary->id)->assertStatus($status);
    }

    public function test_guest_and_unverified_user_cannot_read_social_pages_or_media(): void
    {
        $author = User::factory()->create();
        $diary = $this->makeDiary($author, 'members');
        $media = $this->makeMedia($author, $diary);
        $paths = ['/home', '/diaries/'.$diary->id, '/media/'.$media->id, '/search?q=needle', '/footprints'];

        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified);

        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/email/verify');
        }
    }

    public function test_block_in_either_direction_overrides_members_and_stale_friendship(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $this->connect($author, $viewer, 'accepted');
        $diary = $this->makeDiary($author, 'members');
        $media = $this->makeMedia($author, $diary);

        foreach ([[$author, $viewer], [$viewer, $author]] as [$blocker, $blocked]) {
            Block::query()->delete();
            Block::create(['blocker_id' => $blocker->id, 'blocked_id' => $blocked->id]);

            $this->actingAs($viewer)->get('/diaries/'.$diary->id)->assertNotFound();
            $this->get('/members/'.$author->id)->assertNotFound();
            $this->get('/media/'.$media->id)->assertNotFound();
            $this->get('/home?filter=all')->assertInertia(fn (Assert $page) => $page
                ->component('social/home', false)->where('diaries.total', 0));
        }
    }

    public function test_private_rows_are_excluded_before_pagination_and_search(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->makeDiary($author, 'private', 'needle secret '.$i);
        }

        $allowed = $this->makeDiary($author, 'members', 'needle allowed');
        $this->actingAs($viewer)->get('/home?filter=all')->assertInertia(fn (Assert $page) => $page
            ->where('diaries.total', 1)
            ->where('diaries.last_page', 1)
            ->has('diaries.data', 1)
            ->where('diaries.data.0.id', $allowed->id)
            ->missing('diaries.data.0.author.email'));

        $this->get('/search?q=needle')->assertInertia(fn (Assert $page) => $page
            ->where('diaries.total', 1)
            ->has('diaries.data', 1)
            ->where('diaries.data.0.id', $allowed->id));
    }

    public function test_search_treats_percent_and_underscore_as_literal_characters(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $exact = $this->makeDiary($author, 'members', 'needle_%');
        $this->makeDiary($author, 'members', 'needleABC');

        $this->actingAs($viewer)->get('/search?'.http_build_query(['q' => 'needle_%']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('diaries.total', 1)
                ->where('diaries.data.0.id', $exact->id));
    }

    public function test_blocked_comments_are_excluded_from_rows_and_count(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $blocked = User::factory()->create();
        $diary = $this->makeDiary($author, 'members');
        DiaryComment::create(['diary_id' => $diary->id, 'user_id' => $viewer->id, 'body' => 'visible']);
        DiaryComment::create(['diary_id' => $diary->id, 'user_id' => $blocked->id, 'body' => 'hidden']);
        Block::create(['blocker_id' => $viewer->id, 'blocked_id' => $blocked->id]);

        $this->actingAs($viewer)->get('/diaries/'.$diary->id)->assertInertia(fn (Assert $page) => $page
            ->where('diary.comments_count', 1)
            ->where('comments.total', 1)
            ->where('comments.data.0.body', 'visible')
            ->missing('comments.data.0.author.email'));
    }

    public function test_forged_author_id_is_not_used_when_creating_diary(): void
    {
        $actor = User::factory()->create();
        $victim = User::factory()->create();

        $this->actingAs($actor)->post('/diaries', [
            'title' => 'My diary',
            'body' => 'Only the authenticated user is the author.',
            'visibility' => 'members',
            'user_id' => $victim->id,
        ])->assertStatus(303);

        $this->assertDatabaseHas('diaries', ['title' => 'My diary', 'user_id' => $actor->id]);
        $this->assertDatabaseMissing('diaries', ['title' => 'My diary', 'user_id' => $victim->id]);
    }

    public function test_other_members_cannot_modify_diary_comment_or_media_by_id(): void
    {
        $author = User::factory()->create();
        $attacker = User::factory()->create();
        $diary = $this->makeDiary($author, 'members');
        $comment = DiaryComment::create(['diary_id' => $diary->id, 'user_id' => $author->id, 'body' => 'original']);
        $media = $this->makeMedia($author, $diary);

        $this->actingAs($attacker)->patch('/diaries/'.$diary->id, [
            'title' => 'forged', 'body' => 'forged', 'visibility' => 'members',
        ])->assertNotFound();
        $this->delete('/diaries/'.$diary->id)->assertNotFound();
        $this->delete('/comments/'.$comment->id)->assertNotFound();
        $this->delete('/media/'.$media->id)->assertNotFound();

        $this->assertDatabaseHas('diaries', ['id' => $diary->id, 'title' => 'needle diary']);
        $this->assertDatabaseHas('diary_comments', ['id' => $comment->id, 'body' => 'original']);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_unreadable_diary_cannot_receive_comment(): void
    {
        $author = User::factory()->create();
        $attacker = User::factory()->create();
        $diary = $this->makeDiary($author, 'private');

        $this->actingAs($attacker)->post('/diaries/'.$diary->id.'/comments', ['body' => 'injected'])
            ->assertNotFound();

        $this->assertDatabaseCount('diary_comments', 0);
    }

    public function test_only_recipient_can_accept_and_third_party_cannot_remove_friendship(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();
        $friendship = $this->connect($sender, $recipient, 'pending');

        $this->actingAs($sender)->patch('/friendships/'.$friendship->id.'/accept')->assertForbidden();
        $this->actingAs($outsider)->patch('/friendships/'.$friendship->id.'/accept')->assertNotFound();
        $this->delete('/friendships/'.$friendship->id)->assertNotFound();
        $this->assertSame('pending', $friendship->fresh()->status);

        $this->actingAs($recipient)->from('/friends')->patch('/friendships/'.$friendship->id.'/accept')->assertStatus(303);
        $this->assertSame('accepted', $friendship->fresh()->status);
    }

    public function test_crossing_request_stays_pending_and_block_removes_friendship(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $graph = app(SocialGraph::class);

        $first = $graph->request($a, $b);
        $second = $graph->request($b, $a);
        $this->assertSame($first->id, $second->id);
        $this->assertSame('pending', $second->status);
        $this->assertDatabaseCount('friendships', 1);

        $graph->accept($b, $first);
        $graph->block($a, $b);
        $this->assertDatabaseCount('friendships', 0);
        $this->assertTrue($graph->blocked($a, $b));
        $graph->unblock($a, $b);
        $this->assertFalse($graph->areFriends($a, $b));
    }

    public function test_profile_get_and_prefetch_do_not_create_footprints(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();

        $this->actingAs($visitor)->get('/members/'.$owner->id)->assertOk();
        $this->get('/members/'.$owner->id, ['Purpose' => 'prefetch', 'X-Inertia-Prefetch' => 'true'])->assertOk();
        $this->assertDatabaseCount('footprints', 0);

        $this->from('/members/'.$owner->id)->post('/members/'.$owner->id.'/footprint')->assertStatus(303);
        $this->post('/members/'.$owner->id.'/footprint')->assertStatus(303);
        $this->assertDatabaseCount('footprints', 1);
        $this->assertDatabaseHas('footprints', ['owner_id' => $owner->id, 'visitor_id' => $visitor->id]);

        $this->actingAs($owner)->post('/members/'.$owner->id.'/footprint')->assertStatus(303);
        $this->assertDatabaseCount('footprints', 1);
    }

    public function test_footprints_use_tokyo_dates_and_only_owner_can_list_them(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $this->travelTo(Carbon::parse('2026-10-20T14:30:00Z'));

        $this->actingAs($visitor)->post('/members/'.$owner->id.'/footprint');
        $this->travel(2)->hours();
        $this->post('/members/'.$owner->id.'/footprint');
        $this->assertDatabaseCount('footprints', 2);
        $this->assertDatabaseHas('footprints', ['visited_on' => '2026-10-20']);
        $this->assertDatabaseHas('footprints', ['visited_on' => '2026-10-21']);

        $this->get('/footprints')->assertInertia(fn (Assert $page) => $page->where('footprints.total', 0));
        $this->actingAs($owner)->get('/footprints')->assertInertia(fn (Assert $page) => $page
            ->where('footprints.total', 2)
            ->where('footprints.data.0.visitor.id', $visitor->id));
        $this->travelBack();
    }

    public function test_non_member_cannot_post_topic_and_client_role_is_ignored(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $community = Community::create(['owner_id' => $owner->id, 'name' => 'PHP', 'description' => 'PHP discussion']);
        $community->memberships()->create(['user_id' => $owner->id, 'role' => 'owner']);
        $payload = ['title' => 'Topic', 'body' => 'Discussion', 'user_id' => $owner->id];

        $this->actingAs($visitor)->post('/communities/'.$community->id.'/topics', $payload)->assertForbidden();
        $this->post('/communities/'.$community->id.'/join', ['role' => 'owner'])->assertStatus(303);
        $this->assertDatabaseHas('memberships', ['community_id' => $community->id, 'user_id' => $visitor->id, 'role' => 'member']);
        $this->post('/communities/'.$community->id.'/topics', $payload)->assertStatus(303);
        $this->assertDatabaseHas('topics', ['community_id' => $community->id, 'user_id' => $visitor->id, 'title' => 'Topic']);
        $this->delete('/communities/'.$community->id)->assertNotFound();
    }

    public function test_blocked_community_owner_hides_community_and_topics(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $community = Community::create(['owner_id' => $owner->id, 'name' => 'PHP', 'description' => 'Discussion']);
        $topic = Topic::create(['community_id' => $community->id, 'user_id' => $owner->id, 'title' => 'Private boundary', 'body' => 'Body']);
        Block::create(['blocker_id' => $owner->id, 'blocked_id' => $viewer->id]);

        $this->actingAs($viewer)->get('/communities/'.$community->id)->assertNotFound();
        $this->get('/topics/'.$topic->id)->assertNotFound();
        $this->get('/communities')->assertInertia(fn (Assert $page) => $page->where('communities.total', 0));
    }

    private function makeDiary(User $author, string $visibility, string $title = 'needle diary'): Diary
    {
        return Diary::create(['user_id' => $author->id, 'title' => $title, 'body' => 'Diary body', 'visibility' => $visibility]);
    }

    private function connect(User $sender, User $recipient, string $status): Friendship
    {
        return Friendship::create([
            'user_low_id' => min($sender->id, $recipient->id),
            'user_high_id' => max($sender->id, $recipient->id),
            'requested_by' => $sender->id,
            'status' => $status,
        ]);
    }

    private function makeMedia(User $author, Diary $diary): Media
    {
        return Media::create([
            'user_id' => $author->id,
            'diary_id' => $diary->id,
            'kind' => 'diary',
            'disk' => 'local',
            'path' => 'testing/'.$diary->id.'.webp',
            'mime' => 'image/webp',
            'size' => 100,
        ]);
    }
}
