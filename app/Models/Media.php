<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['user_id', 'diary_id', 'kind', 'disk', 'path', 'mime', 'size'];

    protected $hidden = ['disk', 'path'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Diary, $this> */
    public function diary(): BelongsTo
    {
        return $this->belongsTo(Diary::class);
    }
}
