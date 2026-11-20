<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value bookkeeping for the 2006 simulation (clock epoch, counters).
 *
 * @property string $key
 * @property array<string, mixed>|null $value
 */
class SimState extends Model
{
    protected $table = 'sim_state';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $key): ?array
    {
        $value = static::query()->find($key)?->value;

        return is_array($value) ? $value : null;
    }

    /** @param array<string, mixed> $value */
    public static function put(string $key, array $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
