<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommunityPolicy
{
    public function view(User $user, Community $community): Response
    {
        return Community::query()->visibleTo($user)->whereKey($community->id)->exists()
            ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function participate(User $user, Community $community): Response
    {
        if ($this->view($user, $community)->denied()) {
            return Response::denyAsNotFound();
        }

        return $community->hasMember($user)
            ? Response::allow() : Response::deny('コミュニティへの参加が必要です。');
    }

    public function delete(User $user, Community $community): Response
    {
        return (int) $community->owner_id === $user->id
            ? Response::allow() : Response::denyAsNotFound();
    }
}
