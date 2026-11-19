<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Footprint;
use App\Models\User;
use App\Services\SocialGraph;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FootprintController extends Controller
{
    public function index(Request $request, SocialGraph $graph): Response
    {
        $viewer = $request->user();
        $footprints = Footprint::query()->where('owner_id', $viewer->id)
            ->whereNotIn('visitor_id', $graph->blockedIds($viewer))->with('visitor.avatar')
            ->orderByDesc('last_visited_at')->orderByDesc('id')->paginate(30)
            ->through(fn (Footprint $footprint): array => [
                'id' => $footprint->id,
                'visitor' => ($footprint->visitor ?? abort(404))->publicCard(),
                'last_visited_at' => $footprint->last_visited_at->toIso8601String(),
            ]);

        return Inertia::render('social/footprints', ['footprints' => $footprints]);
    }

    public function store(Request $request, User $user, SocialGraph $graph): RedirectResponse
    {
        $viewer = $request->user();
        abort_unless($user->hasVerifiedEmail(), 404);

        if ($viewer->id !== $user->id) {
            $graph->withLockedPair($viewer, $user, function () use ($viewer, $user, $graph): void {
                abort_if($graph->blocked($viewer, $user), 404);
                Footprint::upsert([
                    'owner_id' => $user->id,
                    'visitor_id' => $viewer->id,
                    'visited_on' => now()->timezone('Asia/Tokyo')->toDateString(),
                    'last_visited_at' => now(),
                ], ['owner_id', 'visitor_id', 'visited_on'], ['last_visited_at']);
            });
        }

        return back(303);
    }
}
