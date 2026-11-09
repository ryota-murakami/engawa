<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Diary;
use App\Services\SocialGraph;
use App\Support\SocialData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, SocialGraph $graph): Response
    {
        $request->validate(['filter' => ['nullable', 'in:friends,all']]);
        $viewer = $request->user();
        $filter = $request->string('filter')->toString() ?: 'friends';
        $query = SocialData::diaryQuery($viewer);

        if ($filter === 'friends') {
            $query->whereIn('user_id', [$viewer->id, ...$graph->friendIds($viewer)]);
        }

        return Inertia::render('social/home', [
            'filter' => $filter,
            'diaries' => $query->latest('id')->paginate(15)->withQueryString()
                ->through(fn (Diary $diary): array => SocialData::diary($diary, $viewer)),
        ]);
    }
}
