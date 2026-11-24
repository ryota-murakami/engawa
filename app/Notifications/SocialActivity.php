<?php

namespace App\Notifications;

use App\Models\Diary;
use App\Models\DiaryComment;
use App\Models\Friendship;
use App\Models\User;
use App\Services\SocialGraph;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

// 外側のNotifySocialActivity Jobだけをqueue化する。このNotificationは同期。
class SocialActivity extends Notification
{
    /**
     * 1ユーザーあたりの通知メールの1日上限。
     * シミュレーションでは大量のソーシャルイベントが実ユーザー宛にも飛ぶため、
     * メールプロバイダの日次無料枠を食い尽くさないようキャップする。
     * （in-app の database 通知は制限しない）
     */
    private const DAILY_MAIL_LIMIT = 10;

    public function __construct(public string $kind, public int $subjectId, public int $actorId) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        $channels = [];

        // Jobの再試行でも同じdatabase通知を増やさない。
        if (! $notifiable->notifications()->whereKey($this->id)->exists()) {
            $channels[] = 'database';
        }

        if ($notifiable->email_social_notifications
            && $notifiable->hasVerifiedEmail()
            && ! self::mailQuotaExceeded($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * その日のメール送信カウントをインクリメントし、上限到達時は true。
     * via() が呼ばれる＝これから送信する、として先に消費しておく。
     */
    private static function mailQuotaExceeded(User $notifiable): bool
    {
        $key = 'social-mail:'.$notifiable->id.':'.now()->format('Ymd');
        $count = (int) Cache::get($key, 0);
        if ($count >= self::DAILY_MAIL_LIMIT) {
            return true;
        }
        Cache::put($key, $count + 1, now()->endOfDay());

        return false;
    }

    /** @return array{kind: string, subject_id: int, actor_id: int} */
    public function toDatabase(User $notifiable): array
    {
        return ['kind' => $this->kind, 'subject_id' => $this->subjectId, 'actor_id' => $this->actorId];
    }

    public function toMail(User $notifiable): MailMessage
    {
        // 直前に公開範囲が変わっても、本文・タイトル・投稿者名をメールへ漏らさない。
        return (new MailMessage)
            ->subject('えんがわに新しいお知らせがあります')
            ->greeting('こんにちは')
            ->line('えんがわに新しいお知らせが届いています。')
            ->action('ログインして確認する', url('/notifications'));
    }

    /** @return array{text: string, url: string}|null */
    public function resolve(User $recipient): ?array
    {
        return self::resolveBatch($recipient, collect([$this->toDatabase($recipient)]))[0] ?? null;
    }

    /**
     * 対象をまとめて取得し、現在の権限を使って表示用データだけを返す。
     *
     * @template TKey of array-key
     * @template TData of array<array-key, mixed>
     *
     * @param  Collection<TKey, TData>  $entries
     * @return array<TKey, array{text: string, url: string}>
     */
    public static function resolveBatch(User $recipient, Collection $entries): array
    {
        $supported = ['friend-request', 'friend-accepted', 'diary-comment'];
        $entries = $entries->filter(fn (array $data): bool => in_array($data['kind'] ?? null, $supported, true)
            && is_numeric($data['actor_id'] ?? null)
            && is_numeric($data['subject_id'] ?? null)
        );

        if ($entries->isEmpty()) {
            return [];
        }

        $actors = User::query()->whereIn('id', $entries->pluck('actor_id')->unique())->get(['id', 'name'])->keyBy('id');
        $blocked = array_fill_keys(app(SocialGraph::class)->blockedIds($recipient), true);
        $friendships = Friendship::query()
            ->whereIn('id', $entries->whereIn('kind', ['friend-request', 'friend-accepted'])->pluck('subject_id'))
            ->get()->keyBy('id');
        $comments = DiaryComment::query()
            ->whereIn('id', $entries->where('kind', 'diary-comment')->pluck('subject_id'))
            ->get(['id', 'diary_id', 'user_id'])->keyBy('id');
        $diaries = Diary::query()->visibleTo($recipient)
            ->whereIn('id', $comments->pluck('diary_id')->unique())
            ->get(['id', 'user_id'])->keyBy('id');

        $resolved = [];
        foreach ($entries as $key => $data) {
            $actorId = (int) $data['actor_id'];
            $subjectId = (int) $data['subject_id'];
            $actor = $actors->get($actorId);

            if (! $actor || $actorId === (int) $recipient->id || isset($blocked[$actorId])) {
                continue;
            }

            if ($data['kind'] === 'diary-comment') {
                $comment = $comments->get($subjectId);
                $diary = $comment ? $diaries->get($comment->diary_id) : null;
                if (! $comment || ! $diary || (int) $comment->user_id !== $actorId || (int) $diary->user_id !== (int) $recipient->id) {
                    continue;
                }
                $resolved[$key] = ['text' => $actor->name.'さんが日記にコメントしました。', 'url' => '/diaries/'.$diary->id];

                continue;
            }

            $friendship = $friendships->get($subjectId);
            if (! $friendship) {
                continue;
            }
            $pair = [(int) $friendship->user_low_id, (int) $friendship->user_high_id];
            if (! in_array((int) $recipient->id, $pair, true) || ! in_array($actorId, $pair, true)) {
                continue;
            }

            if ($data['kind'] === 'friend-request' && $friendship->status === 'pending' && (int) $friendship->requested_by === $actorId) {
                $resolved[$key] = ['text' => $actor->name.'さんからマイミク申請が届きました。', 'url' => '/friends'];
            } elseif ($data['kind'] === 'friend-accepted' && $friendship->status === 'accepted' && (int) $friendship->requested_by === (int) $recipient->id) {
                $resolved[$key] = ['text' => $actor->name.'さんがマイミク申請を承認しました。', 'url' => '/members/'.$actorId];
            }
        }

        return $resolved;
    }
}
