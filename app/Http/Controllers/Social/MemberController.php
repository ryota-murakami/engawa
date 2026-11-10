<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
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
            $query->where('name', 'like', SocialData::like($q));
        }

        return Inertia::Render('social/members/index', [
            'q' => $q,
            'members' => $query->orderBy('name')->orderBy('id')->paginate(24)->withQueryString()->through(fn (User $user): array => $user->publicCard()),

        ]);
    }
}
