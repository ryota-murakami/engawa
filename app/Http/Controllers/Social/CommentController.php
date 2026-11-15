<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Diary;
use App\Models\DiaryComment;
use App\Services\SocialGraph;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(Request $request, Diary $diary, SocialGraph $graph): RedirectResponse
    {
        Gate::authorize('view', $diary);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $actor = $request->user();

        $graph->withLockedPair($actor, $diary->author ?? abort(404), function () use ($actor, $diary, $data): void {
            $current = Diary::query()->lockForUpdate()->findOrFail($diary->id);
            Gate::authorize('view', $current);
            $current->comments()->create(['user_id' => $actor->id, 'body' => $data['body']]);

        });

        return back(303);
    }

    public function destroy(Request $request, DiaryComment $comment): RedirectResponse
    {
        $actor = $request->user();
        abort_unless((int) $comment->user_id === $actor->id || (int) ($comment->diary ?? abort(404))->user_id === $actor->id, 404);
        $comment->delete();

        return back(303);
    }
}
