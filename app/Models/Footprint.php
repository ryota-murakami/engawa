<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable $visited_on
 * @property CarbonImmutable $last_visited_at
 */
class Footprint extends Model
{
    public $timestamps = false;

    protected $fillable = ['owner_id', 'visitor_id', 'visited_on', 'last_visited_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['visited_on' => 'date', 'last_visited_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visitor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
