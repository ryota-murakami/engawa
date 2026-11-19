<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('avatar');

        return Inertia::render('social/profile/edit', [
            'profile' => [
                'name' => $user->name,
                'bio' => $user->bio ?? '',
                'avatar_url' => $user->publicCard()['avatar_url'],
                'email_social_notifications' => (bool) $user->email_social_notifications,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'email_social_notifications' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
            'email_social_notifications' => (bool) $data['email_social_notifications'],
        ])->save();

        return back(303);
    }
}
