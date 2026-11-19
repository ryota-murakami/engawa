<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Topic;
use App\Models\TopicReply;
use App\Services\SocialGraph;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function create(Community $community): Response
    {
        Gate::authorize('participate', $community);

        return Inertia::render('social/topics/form', ['community' => $community->only(['id', 'name'])]);
    }

    public function store(Request $request, Community $community, SocialGraph $graph): RedirectResponse
    {
        Gate::authorize('participate', $community);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
        ]);
        $actor = $request->user();

        $topic = $graph->withLockedPair($actor, $community->owner ?? abort(404), function () use ($actor, $community, $data): Topic {
            $current = Community::query()->lockForUpdate()->findOrFail($community->id);
            Gate::authorize('participate', $current);

            return $current->topics()->create(['user_id' => $actor->id, ...$data]);
        });

        return to_route('social.topics.show', $topic, 303);
    }

    public function show(Request $request, Topic $topic, SocialGraph $graph): Response
    {
        $viewer = $request->user();
        abort_unless(Topic::query()->visibleTo($viewer)->whereKey($topic->id)->exists(), 404);
        $topic->load(['community', 'author.avatar']);
        $isOwner = (int) ($topic->community ?? abort(404))->owner_id === $viewer->id;
        $replies = $topic->replies()->whereNotIn('user_id', $graph->blockedIds($viewer))
            ->with('author.avatar')->oldest('id')->paginate(30)->through(fn (TopicReply $reply): array => [
                'id' => $reply->id,
                'body' => $reply->body,
                'created_at' => $reply->created_at->toIso8601String(),
                'author' => ($reply->author ?? abort(404))->publicCard(),
                'can_delete' => (int) $reply->user_id === $viewer->id || $isOwner,
            ]);

        return Inertia::render('social/topics/show', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'body' => $topic->body,
                'author' => ($topic->author ?? abort(404))->publicCard(),
                'community' => ($topic->community ?? abort(404))->only(['id', 'name']),
                'can_delete' => (int) $topic->user_id === $viewer->id || $isOwner,
            ],
            'replies' => $replies,
            'can_reply' => ($topic->community ?? abort(404))->hasMember($viewer),
        ]);
    }

    public function destroy(Request $request, Topic $topic): RedirectResponse
    {
        abort_unless((int) $topic->user_id === $request->user()->id || (int) ($topic->community ?? abort(404))->owner_id === $request->user()->id, 404);
        $communityId = $topic->community_id;
        $topic->delete();

        return to_route('social.communities.show', $communityId, 303);
    }

    public function reply(Request $request, Topic $topic, SocialGraph $graph): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(Topic::query()->visibleTo($actor)->whereKey($topic->id)->exists(), 404);
        Gate::authorize('participate', $topic->community ?? abort(404));
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $graph->withLockedPair($actor, $topic->author ?? abort(404), function () use ($actor, $topic, $data): void {
            // Membership changes also lock this community row.
            $community = Community::query()->lockForUpdate()->findOrFail($topic->community_id);
            Gate::authorize('participate', $community);
            $current = Topic::query()->lockForUpdate()->findOrFail($topic->id);
            abort_unless(Topic::query()->visibleTo($actor)->whereKey($current->id)->exists(), 404);
            $current->replies()->create(['user_id' => $actor->id, 'body' => $data['body']]);
        });

        return back(303);
    }

    public function deleteReply(Request $request, TopicReply $reply): RedirectResponse
    {
        abort_unless((int) $reply->user_id === $request->user()->id || (int) (($reply->topic ?? abort(404))->community ?? abort(404))->owner_id === $request->user()->id, 404);
        $reply->delete();

        return back(303);
    }
}
