<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    protected $fillable = ['user_low_id', 'user_high_id', 'requested_by', 'status'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['user_low_id' => 'integer', 'user_high_id' => 'integer', 'requested_by' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function lowUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_low_id');
    }

    /** @return BelongsTo<User, $this> */
    public function highUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_high_id');
    }

    public function peerId(User $viewer): int
    {
        return $this->user_low_id === $viewer->id ? $this->user_high_id : $this->user_low_id;
    }
}
