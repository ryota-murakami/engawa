<?php

namespace App\Services\Simulation;

use App\Jobs\NotifySocialActivity;
use App\Models\Community;
use App\Models\Diary;
use App\Models\DiaryComment;
use App\Models\Footprint;
use App\Models\Friendship;
use App\Models\Membership;
use App\Models\Persona;
use App\Models\SimState;
use App\Models\Topic;
use App\Models\TopicReply;
use App\Models\User;
use App\Services\Simulation\Content\CommunityBank;
use App\Services\Simulation\Content\DiaryBank;
use App\Services\Simulation\Content\EraEvents;
use App\Services\Simulation\Content\Fragments;
use App\Services\SocialGraph;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * The simulation's heartbeat: every call of tick() grows the persona
 * population toward 1,000 and then emits a bounded batch of organic-looking
 * activity (diaries, comments, topics, replies, footprints, friendships,
 * memberships) stamped onto the simulated 2006 timeline.
 */
class ActivityEngine
{
    /** Per-tick action budgets at full population; scaled by persona count and hour. */
    private const BUDGET = [
        'diary' => 6,
        'comment' => 10,
        'topic' => 2,
        'reply' => 6,
        'footprint' => 14,
        'friendship' => 5,
        'membership' => 5,
    ];

    public function __construct(
        private PersonaMaker $maker,
        private TextComposer $composer,
        private SimClock $clock,
        private SocialGraph $graph,
    ) {}

    /**
     * Run one simulation step. Returns a stat summary for the artisan output.
     *
     * @return array<string, int|string|bool>
     */
    public function tick(?CarbonImmutable $realNow = null): array
    {
        if (! config('engawa.simulation.enabled')) {
            return ['skipped' => 'disabled'];
        }
        if ($this->clock->ended($realNow)) {
            return ['skipped' => 'campaign-ended'];
        }

        $simNow = $this->clock->now($realNow);
        $eventKeys = EraEvents::keysFor($simNow);
        SimState::put('last_tick', ['sim_at' => $simNow->toIso8601String(), 'events' => EraEvents::labelsFor($simNow)]);

        /** @var array<string, mixed> $stats */
        $stats = [
            'sim_at' => $simNow->toDateTimeString(),
            'events' => implode(',', EraEvents::labelsFor($simNow)) ?: '-',
        ];

        $stats['personas'] = $this->bootstrapPersonas($simNow);
        $stats['communities'] = $this->ensureCommunities($simNow);

        $population = Persona::query()->count();
        if ($population === 0) {
            return $stats + ['actions' => 0];
        }

        // Scale the budget with population growth and the sim-hour curve.
        $scale = max(0.15, min(1.0, $population / max(1, (int) config('engawa.simulation.persona_target'))))
            * $this->hourFactor($simNow->hour);

        $actions = [
            'diary' => fn (Persona $p) => $this->actDiary($p, $simNow, $eventKeys),
            'comment' => fn (Persona $p) => $this->actComment($p, $simNow),
            'topic' => fn (Persona $p) => $this->actTopic($p, $simNow, $eventKeys),
            'reply' => fn (Persona $p) => $this->actReply($p, $simNow),
            'footprint' => fn (Persona $p) => $this->actFootprint($p, $simNow),
            'friendship' => fn (Persona $p) => $this->actFriendship($p, $simNow),
            'membership' => fn (Persona $p) => $this->actMembership($p, $simNow),
        ];

        $done = 0;
        foreach (self::BUDGET as $action => $base) {
            $n = (int) max(0, round($base * $scale * (random_int(70, 130) / 100)));
            // Even at 3 a.m. mixi had its night owls — keep a floor of visible activity.
            $n = match ($action) {
                'diary' => max($n, random_int(1, 2)),
                'comment' => max($n, random_int(1, 3)),
                'footprint' => max($n, random_int(2, 4)),
                'friendship', 'membership' => max($n, random_int(0, 1)),
                default => $n,
            };
            $stats[$action] = 0;
            for ($i = 0; $i < $n; $i++) {
                $persona = $this->pickActivePersona();
                if ($persona === null) {
                    break;
                }
                if ($actions[$action]($persona)) {
                    $stats[$action]++;
                    $done++;
                }
            }
        }
        $stats['actions'] = $done;

        return $stats;
    }

    /** Introduce personas gradually (~1/120 of target per tick, min 3). */
    private function bootstrapPersonas(CarbonImmutable $simNow): int
    {
        $target = (int) config('engawa.simulation.persona_target');
        $have = Persona::query()->count();
        $remaining = $target - $have;
        if ($remaining <= 0) {
            return 0;
        }

        $toCreate = min($remaining, max(3, intdiv($target, 120)));
        $created = 0;
        // Definitions are indexed deterministically; probe past `have` may collide
        // with materialized rows, so scan a modest window ahead.
        for ($i = $have; $i < $have + $toCreate; $i++) {
            if ($this->maker->materialize($i % $target, $simNow) !== null) {
                $created++;
            }
        }

        return $created;
    }

    /** Materialize any communities from the bank that do not exist yet (1-2 per tick). */
    private function ensureCommunities(CarbonImmutable $simNow): int
    {
        $created = 0;
        foreach (CommunityBank::communities() as $c) {
            // {pref} communities legitimately spawn one per prefecture.
            $exists = Community::query()->where('name', 'like', str_replace('{pref}', '%', $c['name']))->exists();
            if ($exists) {
                continue;
            }
            if ($created >= 2) {
                break;
            }
            $owner = Persona::query()->with('user')->inRandomOrder()->first();
            if ($owner === null) {
                break;
            }
            $community = new Community;
            $community->owner_id = $owner->user_id;
            $community->name = mb_substr($this->composer->compose($c['name'], $owner), 0, 80);
            $community->description = $this->composer->compose($c['desc'], $owner);
            // Communities predate the tick to feel established.
            $community->created_at = $community->updated_at = $simNow->subDays(random_int(3, 120));
            $community->save();

            $membership = new Membership;
            $membership->community_id = $community->id;
            $membership->user_id = $owner->user_id;
            $membership->role = 'owner';
            $membership->created_at = $membership->updated_at = $community->created_at;
            $membership->save();
            $created++;
        }

        return $created;
    }

    /** Weighted pick of a persona; higher `activity` means higher chance. */
    private function pickActivePersona(): ?Persona
    {
        return Persona::query()
            ->with('user')
            ->inRandomOrder()
            ->limit(40)
            ->get()
            ->first(fn (Persona $p) => random_int(0, 100) < $p->activity * 100);
    }

    /** mixi was busiest at night; scale the budget by simulated JST hour. */
    private function hourFactor(int $hour): float
    {
        return match (true) {
            $hour < 5 => 0.2,
            $hour < 8 => 0.5,
            $hour < 12 => 0.8,
            $hour < 18 => 0.9,
            $hour < 23 => 1.3,
            default => 1.0,
        };
    }

    /**
     * Pick a weighted diary template, respecting archetype/hour/weekday filters.
     *
     * @param  list<string>  $eventKeys
     * @return array{t:string,b:string,w?:int,k?:list<string>,a?:list<string>,h?:list<int>,wd?:list<int>}|null
     */
    private function pickTemplate(CarbonImmutable $simNow, array $eventKeys, Persona $persona): ?array
    {
        $candidates = [];
        foreach (DiaryBank::bank() as $theme => $entries) {
            foreach ($entries as $e) {
                if (isset($e['a']) && ! in_array($persona->archetype, $e['a'])) {
                    continue;
                }
                if (isset($e['h']) && ($simNow->hour < $e['h'][0] || $simNow->hour > $e['h'][1])) {
                    continue;
                }
                if (isset($e['wd']) && ! in_array($simNow->dayOfWeekIso, $e['wd'])) {
                    continue;
                }
                if ($theme === 'love' && $persona->age < 18) {
                    continue;
                }
                // Event-keyed templates (季節・ニュース) only appear while that event is live.
                if (isset($e['k']) && array_intersect($e['k'], $eventKeys) === []) {
                    continue;
                }
                $weight = ($e['w'] ?? 5) * (isset($e['k']) ? 8 : 1);
                // First diary for a fresh persona should be an intro.
                if ($theme === 'intro' && $this->hasDiaries($persona)) {
                    continue;
                }
                $candidates[] = [$weight, $e];
            }
        }
        if ($candidates === []) {
            return null;
        }

        $total = array_sum(array_column($candidates, 0));
        $roll = random_int(1, $total);
        foreach ($candidates as [$w, $e]) {
            $roll -= $w;
            if ($roll <= 0) {
                return $e;
            }
        }

        return $candidates[0][1];
    }

    private function hasDiaries(Persona $persona): bool
    {
        return Diary::query()->where('user_id', $persona->user_id)->exists();
    }

    /** @param list<string> $eventKeys */
    private function actDiary(Persona $persona, CarbonImmutable $simNow, array $eventKeys): bool
    {
        $entry = $this->pickTemplate($simNow, $eventKeys, $persona);
        if ($entry === null) {
            return false;
        }

        $at = $this->clock->recentAt(120);
        // Visibility mix: mostly members-public, a quarter friends-only, a few private.
        $roll = random_int(0, 99);
        $visibility = $roll < 75 ? 'members' : ($roll < 95 ? 'friends' : 'private');

        $diary = new Diary;
        $diary->user_id = $persona->user_id;
        $diary->title = mb_substr($this->composer->compose($entry['t'], $persona), 0, 80);
        $diary->body = $this->composer->compose($entry['b'], $persona);
        $diary->visibility = $visibility;
        $diary->created_at = $diary->updated_at = $at;
        $diary->save();

        $persona->update(['last_sim_active_at' => $at]);

        return true;
    }

    private function actComment(Persona $persona, CarbonImmutable $simNow): bool
    {
        // Any members-visible diary is fair game — including real users' posts,
        // whose comments get stamped in sim-time like everything else.
        $diary = Diary::query()
            ->where('user_id', '<>', $persona->user_id)
            ->where('visibility', 'members')
            ->inRandomOrder()
            ->first();

        if ($diary === null) {
            return false;
        }

        $frag = Fragments::commentFragments()[array_rand(Fragments::commentFragments())];
        $at = $this->clock->recentAt(60);
        if ($at->lt($diary->created_at)) {
            $at = $simNow;
        }

        $comment = new DiaryComment;
        $comment->diary_id = $diary->id;
        $comment->user_id = $persona->user_id;
        $comment->body = $this->composer->compose($frag, $persona);
        $comment->created_at = $comment->updated_at = $at;
        $comment->save();

        // Notify only real users; personas don't read their inbox.
        if (Persona::query()->where('user_id', $diary->user_id)->doesntExist()) {
            NotifySocialActivity::dispatch((int) $diary->user_id, $persona->user_id, 'diary-comment', $comment->id)->afterCommit();
        }

        return true;
    }

    /** @param list<string> $eventKeys */
    private function actTopic(Persona $persona, CarbonImmutable $simNow, array $eventKeys): bool
    {
        $community = $this->communityFor($persona, $eventKeys);
        if ($community === null) {
            return false;
        }
        $def = collect(CommunityBank::communities())->first(function ($d) use ($community) {
            if ($d['name'] === $community->name) {
                return true;
            }
            // Parameterized names like "{pref}の人集まれ!" match by their static tail.
            $tail = preg_replace('/^.*\{pref\}/', '', $d['name']);

            return $tail !== '' && str_ends_with($community->name, $tail);
        });
        if ($def === null) {
            return false;
        }
        $tpl = $def['topics'][array_rand($def['topics'])] ?? null;
        if ($tpl === null) {
            return false;
        }

        $at = $this->clock->recentAt(240);
        $topic = new Topic;
        $topic->community_id = $community->id;
        $topic->user_id = $persona->user_id;
        $topic->title = mb_substr($this->composer->compose($tpl['t'], $persona), 0, 80);
        $topic->body = $this->composer->compose($tpl['b'], $persona);
        $topic->created_at = $topic->updated_at = $at;
        $topic->save();

        return true;
    }

    private function actReply(Persona $persona, CarbonImmutable $simNow): bool
    {
        $topic = Topic::query()
            ->where('user_id', '<>', $persona->user_id)
            ->where('created_at', '>=', $simNow->copy()->subDays(30))
            ->inRandomOrder()
            ->first();
        if ($topic === null) {
            return false;
        }

        $frag = Fragments::replyFragments()[array_rand(Fragments::replyFragments())];
        $at = $this->clock->recentAt(90);
        if ($at->lt($topic->created_at)) {
            $at = $simNow;
        }

        $reply = new TopicReply;
        $reply->topic_id = $topic->id;
        $reply->user_id = $persona->user_id;
        $reply->body = $this->composer->compose($frag, $persona);
        $reply->created_at = $reply->updated_at = $at;
        $reply->save();

        return true;
    }

    private function actFootprint(Persona $persona, CarbonImmutable $simNow): bool
    {
        // 15% chance: visit a real (non-persona) user's page — mixi's signature feature.
        $target = random_int(0, 99) < 15
            ? User::query()->whereNotIn('id', Persona::query()->select('user_id'))->where('id', '<>', $persona->user_id)->inRandomOrder()->first()
            : Persona::query()->where('user_id', '<>', $persona->user_id)->inRandomOrder()->first()?->user;

        if ($target === null) {
            return false;
        }

        Footprint::upsert(
            [['owner_id' => $target->id, 'visitor_id' => $persona->user_id, 'visited_on' => $simNow->toDateString(), 'last_visited_at' => $simNow]],
            ['owner_id', 'visitor_id', 'visited_on'],
            ['last_visited_at'],
        );

        return true;
    }

    private function actFriendship(Persona $persona, CarbonImmutable $simNow): bool
    {
        // 10%: request a real user (pending — they decide). Otherwise another persona.
        $target = random_int(0, 99) < 10
            ? User::query()->whereNotIn('id', Persona::query()->select('user_id'))->where('id', '<>', $persona->user_id)->inRandomOrder()->first()
            : Persona::query()->where('user_id', '<>', $persona->user_id)->inRandomOrder()->first()?->user;

        if ($target === null) {
            return false;
        }

        $isReal = Persona::query()->where('user_id', $target->id)->doesntExist();
        if ($isReal) {
            try {
                $this->graph->request($persona->user, $target); // notifies the real user
            } catch (Throwable) {
                return false;
            }

            return true;
        }

        // Persona↔persona: direct insert keeps the queue quiet; mixi pairs mostly accepted.
        $low = max(1, min($persona->user_id, $target->id));
        $high = max($persona->user_id, $target->id);
        $exists = Friendship::query()->where('user_low_id', $low)->where('user_high_id', $high)->exists();
        if ($exists) {
            return false;
        }

        $friendship = new Friendship;
        $friendship->user_low_id = $low;
        $friendship->user_high_id = $high;
        $friendship->requested_by = random_int(0, 1) ? $low : $high;
        $friendship->status = random_int(0, 99) < 70 ? 'accepted' : 'pending';
        $friendship->created_at = $friendship->updated_at = $this->clock->recentAt(4320); // up to 3 sim-days back
        $friendship->save();

        return true;
    }

    private function actMembership(Persona $persona, CarbonImmutable $simNow): bool
    {
        $community = $this->communityFor($persona, EraEvents::keysFor($simNow));
        if ($community === null) {
            return false;
        }

        $membership = Membership::firstOrNew(
            ['community_id' => $community->id, 'user_id' => $persona->user_id],
        );
        if ($membership->exists) {
            return false;
        }
        $membership->role = 'member';
        $membership->created_at = $membership->updated_at = $this->clock->recentAt(2880);
        $membership->save();

        return true;
    }

    /**
     * A community this persona would join: prefer event-matched or interest-matched.
     *
     * @param  list<string>  $eventKeys
     */
    private function communityFor(Persona $persona, array $eventKeys): ?Community
    {
        $defs = collect(CommunityBank::communities());
        // Event-keyed communities rise while their event is live.
        if ($eventKeys !== [] && random_int(0, 99) < 40) {
            $keyed = $defs->filter(fn ($d) => array_intersect($d['keys'] ?? [], $eventKeys) !== [])->pluck('name');
            if ($keyed->isNotEmpty()) {
                $community = Community::query()->whereIn('name', $keyed)->inRandomOrder()->first();
                if ($community !== null) {
                    return $community;
                }
            }
        }

        return Community::query()->inRandomOrder()->first();
    }
}
