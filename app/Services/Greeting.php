<?php

namespace App\Services;

use App\Models\User;

class Greeting
{
    /**
     * Build the greeting shown by the learning page.
     * GreetingController calls this after the verified member opens /learn.
     */
    public function forMember(User $user): string
    {
        return 'こんにちは、' . $user->name . 'さん';
    }
}
