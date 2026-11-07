<?php

namespace App\Models;

use App\Services\SocialGraph;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    protected $fillable = ['owner_id', 'name', 'description'];

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** @return HasMany<Topic, $this> */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    /**
     * @param  Builder<Community>  $query
     * @return Builder<Community>
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        return $query->whereNotIn('owner_id', app(SocialGraph::class)->blockedIds($viewer));
    }

    public function hasMember(User $user): bool
    {
        return $this->memberships()->where('user_id', $user->id)->exists();
    }
}
