<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Block extends Model
{
    protected $fillable = ['blocker_id', 'blocked_id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['blocker_id' => 'integer', 'blocked_id' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function blockUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
