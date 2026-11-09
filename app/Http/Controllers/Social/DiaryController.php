<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Diary;
use App\Models\DiaryComment;
use App\Services\SocialGraph;
use App\Support\SocialData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiaryController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Diary::class);

        return Inertia::render('social/diaries/form', ['diary' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Diary::class);
        $data = $this->validated($request);
        $diary = $request->user()->diaries()->create($data);

        return to_route('social.diaries.show', $diary, 303);
    }

    public function show(Request $request, Diary $diary, SocialGraph $graph): Response
    {
        Gate::authorize('view', $diary);
        $viewer = $request->user();
        $card = SocialData::diaryQuery($viewer)->findOrFail($diary->id);
        $comments = $diary->comments()->whereNotIn('user_id', $graph->blockedIds($viewer))
            ->with('author.avatar')->oldest('id')->paginate(30)->withQueryString()
            ->through(fn (DiaryComment $comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at->toIso8601String(),
                'author' => ($comment->author ?? abort(404))->publicCard(),
                'can_delete' => (int) $comment->user_id === $viewer->id || (int) $diary->user_id === $viewer->id,
            ]);

        return Inertia::render('social/diaries/show', [
            'diary' => SocialData::diary($card, $viewer),
            'comments' => $comments,
            'can_comment' => true,
        ]);
    }

    public function edit(Request $request, Diary $diary): Response
    {
        Gate::authorize('update', $diary);
        $viewer = $request->user();

        return Inertia::render('social/diaries/form', [
            'diary' => SocialData::diary(SocialData::diaryQuery($viewer)->findOrFail($diary->id), $viewer),
        ]);
    }

    public function update(Request $request, Diary $diary): RedirectResponse
    {
        Gate::authorize('update', $diary);
        $data = $this->validated($request);

        DB::transaction(function () use ($diary, $data): void {
            $current = Diary::query()->lockForUpdate()->findOrFail($diary->id);
            Gate::authorize('update', $current);
            $current->update($data);
        }, 3);

        return to_route('social.diaries.show', $diary, 303);
    }

    public function destroy(Diary $diary): RedirectResponse
    {
        Gate::authorize('delete', $diary);

        DB::transaction(function () use ($diary): void {
            $current = Diary::query()->lockForUpdate()->findOrFail($diary->id);
            Gate::authorize('delete', $current);
            $current->delete();
        }, 3);

        return to_route('social.home', status: 303);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'visibility' => ['required', Rule::in(['members', 'friends', 'private'])],
        ]);
    }
}
