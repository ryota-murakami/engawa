<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Friendship;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SocialGraph
{
    /** @return list<int> */
    public function blockedIds(User $viewer): array
    {
        return array_values(Block::query()
            ->where('blocker_id', $viewer->id)
            ->orWhere('blocked_id', $viewer->id)
            ->get(['blocker_id', 'blocked_id'])
            ->map(fn (Block $block): int => $block->blocker_id === $viewer->id
                ? $block->blocked_id : $block->blocker_id)
            ->unique()->all());
    }

    /** @return list<int> */
    public function friendIds(User $viewer): array
    {
        $blocked = $this->blockedIds($viewer);

        return array_values(Friendship::query()->where('status', 'accepted')
            ->where(fn ($query) => $query->where('user_low_id', $viewer->id)->orWhere('user_high_id', $viewer->id))
            ->get(['user_low_id', 'user_high_id'])
            ->map(fn (Friendship $friendship): int => $friendship->peerId($viewer))
            ->reject(fn (int $id): bool => in_array($id, $blocked, true))
            ->all());
    }

    public function blocked(User $a, User $b): bool
    {
        return Block::query()
            ->where(fn ($query) => $query->where('blocker_id', $a->id)->where('blocked_id', $b->id))
            ->orWhere(fn ($query) => $query->where('blocker_id', $b->id)->where('blocked_id', $a->id))
            ->exists();
    }

    public function areFriends(User $a, User $b): bool
    {
        if ($a->id === $b->id || $this->blocked($a, $b)) {
            return false;
        }

        return Friendship::query()->where('user_low_id', min($a->id, $b->id))
            ->where('user_high_id', max($a->id, $b->id))->where('status', 'accepted')->exists();
    }

    /**
     * Existing users are the lock anchors: a missing friendship cannot be locked.
     * Use this same ordering for relationship changes and direct interactions.
     *
     * @template TResult
     *
     * @param  Closure(int, int): TResult  $operation
     * @return TResult
     */
    public function withLockedPair(User $a, User $b, Closure $operation): mixed
    {
        $ids = array_values(array_unique([$a->id, $b->id]));
        sort($ids);

        return DB::transaction(function () use ($ids, $operation): mixed {
            $locked = User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
            abort_unless($locked->count() === count($ids), 404);

            return $operation(min($ids), max($ids));
        }, 3);
    }

    public function request(User $actor, User $target): Friendship
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages(['friendship' => '自分自身には申請できません。']);
        }

        return $this->withLockedPair($actor, $target, function (int $low, int $high) use ($actor, $target): Friendship {
            abort_if($this->blocked($actor, $target), 404);
            $existing = Friendship::query()->where('user_low_id', $low)->where('user_high_id', $high)->first();

            if ($existing !== null) {
                // A crossing request must not silently accept an incoming request.
                return $existing;
            }

            $friendship = Friendship::create([
                'user_low_id' => $low,
                'user_high_id' => $high,
                'requested_by' => $actor->id,
                'status' => 'pending',
            ]);

            return $friendship;
        });
    }

    public function accept(User $actor, Friendship $friendship): void
    {
        abort_unless(in_array($actor->id, [$friendship->user_low_id, $friendship->user_high_id], true), 404);
        $target = User::findOrFail($friendship->peerId($actor));

        $this->withLockedPair($actor, $target, function () use ($actor, $target, $friendship): void {
            $current = Friendship::findOrFail($friendship->id);
            abort_if($this->blocked($actor, $target), 404);
            abort_if($current->requested_by === $actor->id, 403);

            if ($current->status === 'accepted') {
                return;
            }

            $current->update(['status' => 'accepted']);
        });
    }

    public function remove(User $actor, Friendship $friendship): void
    {
        abort_unless(in_array($actor->id, [$friendship->user_low_id, $friendship->user_high_id], true), 404);
        $target = User::findOrFail($friendship->peerId($actor));

        $this->withLockedPair($actor, $target, function () use ($friendship): void {
            Friendship::query()->whereKey($friendship->id)->delete();
        });
    }

    public function block(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages(['block' => '自分自身はブロックできません。']);
        }

        $this->withLockedPair($actor, $target, function (int $low, int $high) use ($actor, $target): void {
            Block::firstOrCreate(['blocker_id' => $actor->id, 'blocked_id' => $target->id]);
            Friendship::query()->where('user_low_id', $low)->where('user_high_id', $high)->delete();
        });
    }

    public function unblock(User $actor, User $target): void
    {
        $this->withLockedPair($actor, $target, function () use ($actor, $target): void {
            Block::query()->where('blocker_id', $actor->id)->where('blocked_id', $target->id)->delete();
        });
    }
}
