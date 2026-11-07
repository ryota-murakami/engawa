<?php

namespace App\Models;

use App\Services\SocialGraph;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read int $comments_count */
class Diary extends Model
{
    protected $fillable = ['user_id', 'title', 'body', 'visibility'];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<DiaryComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(DiaryComment::class);
    }

    /** @return HasMany<Media, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(Media::class)->where('kind', 'diary');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        $graph = app(SocialGraph::class);
        $blocked = $graph->blockedIds($viewer);
        $friends = $graph->friendIds($viewer);

        return $query->where(function (Builder $visible) use ($viewer, $blocked, $friends): void {
            $visible->where('diaries.user_id', $viewer->id)
                ->orWhere(function (Builder $others) use ($blocked, $friends): void {
                    $others->whereNotIn('diaries.user_id', $blocked)
                        ->where(function (Builder $audience) use ($friends): void {
                            $audience->where('visibility', 'members')
                                ->orWhere(function (Builder $restricted) use ($friends): void {
                                    $restricted->where('visibility', 'friends')
                                        ->whereIn('diaries.user_id', $friends);
                                });
                        });
                });
        });
    }
}
