<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Diary;
use App\Models\Friendship;
use App\Models\User;
use App\Services\SocialGraph;
use App\Support\SocialData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request, SocialGraph $graph): Response
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = trim($request->string('q')->toString());
        $query = User::query()->whereNotNull('email_verified_at')
            ->whereNotIn('id', $graph->blockedIds($request->user()))->with('avatar');

        if ($q !== '') {
            $query->where('name', 'ilike', SocialData::like($q));
        }

        return Inertia::render('social/members/index', [
            'q' => $q,
            'members' => $query->orderBy('name')->orderBy('id')->paginate(24)->withQueryString()
                ->through(fn (User $user): array => $user->publicCard()),
        ]);
    }

    public function show(Request $request, User $user, SocialGraph $graph): Response
    {
        $viewer = $request->user();
        abort_if($graph->blocked($viewer, $user), 404);
        abort_unless($user->hasVerifiedEmail(), 404);
        $user->load('avatar');
        $friendship = Friendship::query()->where('user_low_id', min($viewer->id, $user->id))
            ->where('user_high_id', max($viewer->id, $user->id))->first();

        return Inertia::render('social/members/show', [
            'member' => $user->publicProfile(),
            'is_self' => $viewer->id === $user->id,
            'friendship' => $friendship?->only(['id', 'status', 'requested_by']),
            'diaries' => SocialData::diaryQuery($viewer)->where('user_id', $user->id)
                ->latest('id')->paginate(15)->withQueryString()
                ->through(fn (Diary $diary): array => SocialData::diary($diary, $viewer)),
        ]);
    }
}
