<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Topic;
use App\Services\SocialGraph;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function index(Request $request, SocialGraph $graph): Response
    {
        $viewer = $request->user();
        $blocked = $graph->blockedIds($viewer);
        $joined = $viewer->memberships()->pluck('community_id')->map(fn ($id): int => (int) $id)->all();
        $communities = Community::query()->visibleTo($viewer)->with('owner.avatar')
            ->withCount(['memberships as members_count' => fn (Builder $query) => $query->whereNotIn('user_id', $blocked)])
            ->latest('id')->paginate(20)->through(fn (Community $community): array => [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'owner' => ($community->owner ?? abort(404))->publicCard(),
                'members_count' => (int) $community->members_count,
                'is_member' => in_array($community->id, $joined, true),
            ]);

        return Inertia::render('social/communities/index', ['communities' => $communities]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Community::class);

        return Inertia::render('social/communities/form');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Community::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $community = DB::transaction(function () use ($request, $data): Community {
            $community = Community::create(['owner_id' => $request->user()->id, ...$data]);
            $community->memberships()->create(['user_id' => $request->user()->id, 'role' => 'owner']);

            return $community;
        }, 3);

        return to_route('social.communities.show', $community, 303);
    }

    public function show(Request $request, Community $community, SocialGraph $graph): Response
    {
        Gate::authorize('view', $community);
        $viewer = $request->user();
        $blocked = $graph->blockedIds($viewer);
        $community->load('owner.avatar');
        $topics = $community->topics()->visibleTo($viewer)->with('author.avatar')
            ->withCount(['replies' => fn (Builder $query) => $query->whereNotIn('user_id', $blocked)])
            ->latest('id')->paginate(20)->through(fn (Topic $topic): array => [
                'id' => $topic->id,
                'title' => $topic->title,
                'created_at' => $topic->created_at->toIso8601String(),
                'author' => ($topic->author ?? abort(404))->publicCard(),
                'replies_count' => (int) $topic->replies_count,
            ]);

        return Inertia::render('social/communities/show', [
            'community' => [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'owner' => ($community->owner ?? abort(404))->publicCard(),
                'members_count' => $community->memberships()->whereNotIn('user_id', $blocked)->count(),
            ],
            'topics' => $topics,
            'is_member' => $community->hasMember($viewer),
            'is_owner' => (int) $community->owner_id === $viewer->id,
        ]);
    }

    public function join(Request $request, Community $community, SocialGraph $graph): RedirectResponse
    {
        Gate::authorize('view', $community);
        $viewer = $request->user();

        $graph->withLockedPair($viewer, $community->owner ?? abort(404), function () use ($viewer, $community): void {
            $current = Community::query()->lockForUpdate()->findOrFail($community->id);
            Gate::authorize('view', $current);
            $current->memberships()->firstOrCreate(
                ['user_id' => $viewer->id],
                ['role' => (int) $current->owner_id === $viewer->id ? 'owner' : 'member'],
            );
        });

        return back(303);
    }

    public function leave(Request $request, Community $community): RedirectResponse
    {
        // A member may leave even after the owner blocks them.
        DB::transaction(function () use ($request, $community): void {
            $current = Community::query()->lockForUpdate()->findOrFail($community->id);

            if ((int) $current->owner_id === $request->user()->id) {
                throw ValidationException::withMessages(['membership' => '管理者は退出できません。コミュニティを削除してください。']);
            }

            $current->memberships()->where('user_id', $request->user()->id)->delete();
        }, 3);

        return to_route('social.communities.index', status: 303);
    }

    public function destroy(Community $community): RedirectResponse
    {
        Gate::authorize('delete', $community);

        DB::transaction(function () use ($community): void {
            $current = Community::query()->lockForUpdate()->findOrFail($community->id);
            Gate::authorize('delete', $current);
            $current->delete();
        }, 3);

        return to_route('social.communities.index', status: 303);
    }
}
