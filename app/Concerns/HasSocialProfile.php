<?php

namespace App\Concerns;

use App\Models\Diary;
use App\Models\Media;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasSocialProfile
{
    public function initializeHasSocialProfile(): void
    {
        $this->mergeCasts(['email_social_notifications' => 'boolean']);
    }

    /** @return HasMany<Diary, $this> */
    public function diaries(): HasMany
    {
        return $this->hasMany(Diary::class);
    }

    /** @return HasMany<Media, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /** @return HasOne<Media, $this> */
    public function avatar(): HasOne
    {
        return $this->hasOne(Media::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $query) => $query->where('kind', 'avatar'),
        );
    }

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Only explicitly public fields are returned to another member.
     *
     * @return array{id: int, name: string, avatar_url: string|null}
     */
    public function publicCard(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar !== null ? '/members/'.$this->id.'/avatar' : null,
        ];
    }

    /** @return array{id: int, name: string, avatar_url: string|null, bio: string} */
    public function publicProfile(): array
    {
        return [...$this->publicCard(), 'bio' => $this->bio ?? ''];
    }
}
