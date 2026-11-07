<?php

namespace App\Support;

use App\Models\Diary;
use App\Models\Media;
use App\Models\User;
use App\Services\SocialGraph;
use Illuminate\Database\Eloquent\Builder;

class SocialData
{
    /**
     * Eager-load only the relationships needed by the public card.
     *
     * @return Builder<Diary>
     */
    public static function diaryQuery(User $viewer): Builder
    {
        $blocked = app(SocialGraph::class)->blockedIds($viewer);

        return Diary::query()->visibleTo($viewer)
            ->with(['author.avatar', 'images'])
            ->withCount(['comments' => fn (Builder $comments) => $comments->whereNotIn('user_id', $blocked)]);
    }

    /**
     * @return array{id: int, title: string, body: string, visibility: string, created_at: string, author: array{id: int, name: string, avatar_url: string|null}, comments_count: int, can_update: bool, images: list<array{id: int, url: string}>}
     */
    public static function diary(Diary $diary, User $viewer): array
    {
        return [
            'id' => $diary->id,
            'title' => $diary->title,
            'body' => $diary->body,
            'visibility' => $diary->visibility,
            'created_at' => $diary->created_at->toIso8601String(),
            'author' => ($diary->author ?? abort(404))->publicCard(),
            'comments_count' => (int) $diary->comments_count,
            'can_update' => (int) $diary->user_id === $viewer->id,
            'images' => array_values($diary->images->map(fn (Media $media): array => [
                'id' => $media->id,
                'url' => '/media/'.$media->id,
            ])->all()),
        ];
    }

    /** Escape PostgreSQL LIKE wildcards so the search is a literal substring. */
    public static function like(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }
}
