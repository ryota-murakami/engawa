<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\SocialActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Str;

class NotifySocialActivity implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public string $notificationId;

    public function __construct(
        public int $recipientId,
        public int $actorId,
        public string $kind,
        public int $subjectId,
    ) {
        $this->notificationId = (string) Str::uuid();
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('social-notification:'.$this->notificationId))->releaseAfter(5)->expireAfter(90)];
    }

    public function handle(): void
    {
        $recipient = User::query()->find($this->recipientId);
        if (! $recipient || ! $recipient->hasVerifiedEmail()) {
            return;
        }

        $notification = new SocialActivity($this->kind, $this->subjectId, $this->actorId);
        if ($notification->resolve($recipient) === null) {
            return;
        }

        $notification->id = $this->notificationId;
        $recipient->notify($notification);
    }
}
