<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaryComment extends Model
{
    protected $fillable = ['diary_id', 'user_id', 'body'];

    /** @return BelongsTo<Diary, $this> */
    public function diary(): BelongsTo
    {
        return $this->belongsTo(Diary::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
