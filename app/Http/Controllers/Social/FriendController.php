<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Friendship;
use App\Models\User;
use App\Services\SocialGraph;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FriendController extends Controller
{
    public function index(Request $request, SocialGraph $graph): Response
    {
        $viewer = $request->user();
        $blockedIds = $graph->blockedIds($viewer);
        $pending = Friendship::query()->where('status', 'pending')
            ->where(fn ($query) => $query->where('user_low_id', $viewer->id)->orWhere('user_high_id', $viewer->id))
            ->whereNotIn('user_low_id', $blockedIds)->whereNotIn('user_high_id', $blockedIds)
            ->with(['lowUser.avatar', 'highUser.avatar'])->latest('id')->get();
        $row = fn (Friendship $friendship): array => [
            'id' => $friendship->id,
            'status' => $friendship->status,
            'requested_by' => $friendship->requested_by,
            'member' => (($friendship->user_low_id === $viewer->id ? $friendship->highUser : $friendship->lowUser) ?? abort(404))->publicCard(),
        ];

        return Inertia::render('social/friends', [
            'friends' => User::query()->whereIn('id', $graph->friendIds($viewer))->with('avatar')->orderBy('name')->get()
                ->map(fn (User $user): array => $user->publicCard())->values()->all(),
            'incoming' => $pending->where('requested_by', '!=', $viewer->id)->map($row)->values()->all(),
            'outgoing' => $pending->where('requested_by', $viewer->id)->map($row)->values()->all(),
            'blocked' => Block::query()->where('blocker_id', $viewer->id)->with('blockedUser')->latest('id')->get()
                ->map(fn (Block $block): array => ['id' => ($block->blockedUser ?? abort(404))->id, 'name' => ($block->blockedUser ?? abort(404))->name, 'avatar_url' => null])
                ->values()->all(),
        ]);
    }

    public function store(Request $request, User $user, SocialGraph $graph): RedirectResponse
    {
        abort_unless($user->hasVerifiedEmail(), 404);
        $graph->request($request->user(), $user);

        return back(303);
    }

    public function accept(Request $request, Friendship $friendship, SocialGraph $graph): RedirectResponse
    {
        $graph->accept($request->user(), $friendship);

        return back(303);
    }

    public function destroy(Request $request, Friendship $friendship, SocialGraph $graph): RedirectResponse
    {
        $graph->remove($request->user(), $friendship);

        return back(303);
    }

    public function block(Request $request, User $user, SocialGraph $graph): RedirectResponse
    {
        $graph->block($request->user(), $user);

        return to_route('social.friends', status: 303);
    }

    public function unblock(Request $request, User $user, SocialGraph $graph): RedirectResponse
    {
        $graph->unblock($request->user(), $user);

        return back(303);
    }
}
