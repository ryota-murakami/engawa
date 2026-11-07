<?php

namespace App\Models;

use App\Services\SocialGraph;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read int $replies_count */
class Topic extends Model
{
    protected $fillable = ['community_id', 'user_id', 'title', 'body'];

    /** @return BelongsTo<Community, $this> */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<TopicReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(TopicReply::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        return $query->whereNotIn('topics.user_id', app(SocialGraph::class)->blockedIds($viewer))
            ->whereIn('community_id', Community::query()->visibleTo($viewer)->select('id'));
    }
}
