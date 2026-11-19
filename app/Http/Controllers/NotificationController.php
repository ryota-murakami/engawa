<?php

namespace App\Http\Controllers;

use App\Notifications\SocialActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $rows = $request->user()->notifications()
            ->where('type', SocialActivity::class)
            ->latest()->limit((int) config('social.notification_window', 500))->get();
        $entries = $rows->mapWithKeys(fn (DatabaseNotification $row): array => [$row->id => $row->data]);
        $resolved = SocialActivity::resolveBatch($request->user(), $entries);
        $visible = $rows->filter(fn (DatabaseNotification $row): bool => isset($resolved[$row->id]))
            ->map(fn (DatabaseNotification $row): array => [
                'id' => $row->id,
                'text' => $resolved[$row->id]['text'],
                'url' => $resolved[$row->id]['url'],
                'read_at' => $row->read_at?->toISOString(),
                'created_at' => $row->created_at->toISOString(),
            ])->values();
        $page = max(1, $request->integer('page', 1));

        return Inertia::render('social/notifications', [
            'notifications' => new LengthAwarePaginator(
                $visible->forPage($page, 20)->values(),
                $visible->count(),
                20,
                $page,
                ['path' => $request->url(), 'query' => $request->except('page')],
            ),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }
}
