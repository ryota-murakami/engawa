<?php

namespace App\Policies;

use App\Models\Diary;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DiaryPolicy
{
    public function view(User $user, Diary $diary): Response
    {
        return Diary::query()->visibleTo($user)->whereKey($diary->id)->exists()
            ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Diary $diary): Response
    {
        return (int) $diary->user_id === $user->id
            ? Response::allow() : Response::denyAsNotFound();
    }

    public function delete(User $user, Diary $diary): Response
    {
        return $this->update($user, $diary);
    }
}
