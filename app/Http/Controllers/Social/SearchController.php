<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Diary;
use App\Support\SocialData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->validate(['q' => ['nullable', 'string', 'min:2', 'max:100']]);
        $q = trim($request->string('q')->toString());
        $viewer = $request->user();
        $query = SocialData::diaryQuery($viewer);

        if ($q === '') {
            $query->whereRaw('1 = 0');
        } else {
            $pattern = SocialData::like($q);
            $query->where(function (Builder $search) use ($pattern): void {
                $search->where('title', 'ilike', $pattern)->orWhere('body', 'ilike', $pattern);
            });
        }

        return Inertia::render('social/search', [
            'q' => $q,
            'diaries' => $query->latest('id')->paginate(15)->withQueryString()
                ->through(fn (Diary $diary): array => SocialData::diary($diary, $viewer)),
        ]);
    }
}
