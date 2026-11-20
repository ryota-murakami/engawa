<?php

namespace App\Services\Simulation;

use App\Models\Persona;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Generates the ~1,000 simulated users (ペルソナ). Each persona wraps a User
 * row with a 2006-plausible nickname, bio and region, plus a Persona row
 * carrying archetype, voice and activity rate used by the engine.
 */
class PersonaMaker
{
    private const SURNAMES = [
        '佐藤', '鈴木', '高橋', '田中', '渡辺', '伊藤', '山本', '中村', '小林', '加藤', '吉田', '山田', '佐々木', '山口',
        '松本', '井上', '木村', '林', '斎藤', '清水', '山崎', '森', '池田', '橋本', '阿部', '石川', '山下', '中島', '前田',
        '藤田', '小川', '岡田', '後藤', '長谷川', '石井', '村上', '近藤', '坂本', '遠藤', '青木', '藤井', '西村', '福田',
        '太田', '三浦', '藤原', '岡本', '松田', '中川', '中野', '原田', '小野', '田村', '竹内', '金子', '和田', '中山',
        '石田', '上田', '森田', '原', '柴田', '酒井', '工藤', '横山', '宮崎', '宮本', '内田', '高木', '安藤', '島田',
        '谷口', '大野', '高田', '丸山', '今井', '河野', '藤本', '村田', '武田', '上野', '杉山', '増田', '小山', '大塚',
        '平野', '菅原', '久保', '松井', '千葉', '岩崎', '桜井', '木下', '野口', '松尾', '菊地', '野村', '新井', '渡部',
    ];

    private const MALE = [
        '翔太', '健太', '大輔', '拓也', '達也', '和也', '浩二', '直樹', '智也', '祐介', '亮', '翔', '健',
        '誠', '剛', '修', '学', '豊', '隆', '進', '悟', '浩', '毅', '雄一', '俊介', '圭介', '悠介', '康介',
        '大地', '颯太', '悠斗', '蓮', '大輝', '駿', '海斗', '悠人', '翼', '太一', '光', '瞬', '湊', '朝陽',
    ];

    private const FEMALE = [
        '美咲', '愛', '舞', '優', '彩', '瞳', '結衣', '遥', '七海', '美穂', '由美', '真理子', '恵', '京子',
        '明日香', '奈緒', '沙織', '麻衣', '里奈', '友美', '千尋', '理恵', '香織', '絵里', '美香', '由紀', '久美子',
        'さくら', '茜', '美月', '楓', '琴音', '凛', '陽菜', '葵', '澪', '結', '杏', '真由', 'ゆかり', 'あかり',
    ];

    private const NICK_SUFFIX = ['', '', '', 'ちゃん', 'くん', 'っち', 'たん', 'ぴょん', '☆', '♪', '★', '君'];

    /** Archetype => [weight, age_min, age_max, genders(m|f|u), jobs, interests pools] */
    private const ARCHETYPES = [
        'college' => [18, 18, 24, 'u', ['大学生', '短大生', '大学院生'], ['hobby', 'music', 'school']],
        'senmon' => [10, 18, 24, 'u', ['専門学生', '美容学生', '調理師学校生'], ['hobby', 'game', 'music']],
        'salaryman' => [18, 23, 42, 'm', ['会社員', '営業', 'SE', '経理', '総務'], ['work', 'hobby', 'food']],
        'ol' => [14, 22, 34, 'f', ['OL', '事務職', '受付嬢', '秘書'], ['work', 'life', 'food']],
        'engineer' => [8, 23, 38, 'u', ['SE', 'プログラマー', 'インフラエンジニア'], ['game', 'anime', 'work']],
        'civil' => [5, 24, 40, 'u', ['公務員', '市役所職員', '学校職員'], ['life', 'hobby']],
        'nurse' => [5, 21, 34, 'f', ['看護師', '介護士', '保育士'], ['work', 'life', 'food']],
        'hairdresser' => [4, 19, 30, 'u', ['美容師', '理容師', 'アパレル店員'], ['music', 'life']],
        'freeter' => [10, 18, 28, 'u', ['フリーター', 'コンビニ店員', '居酒屋バイト', 'カラオケ店員'], ['music', 'game', 'life']],
        'neet' => [4, 20, 30, 'u', ['無職', 'ニート', '求職中'], ['game', 'anime', 'net']],
        'otaku' => [6, 18, 32, 'u', ['オタク', '同人作家', 'フィギュア職人見習い'], ['anime', 'game', 'net']],
        'mama' => [6, 26, 42, 'f', ['主婦', 'パート主婦', '専業主婦'], ['life', 'food', 'tv']],
    ];

    private const PREFS = [
        '北海道', '青森', '岩手', '宮城', '秋田', '山形', '福島', '茨城', '栃木', '群馬', '埼玉', '千葉',
        '東京', '東京', '東京', '神奈川', '神奈川', '新潟', '富山', '石川', '福井', '山梨', '長野', '岐阜',
        '静岡', '愛知', '愛知', '三重', '滋賀', '京都', '大阪', '大阪', '兵庫', '奈良', '和歌山',
        '鳥取', '島根', '岡山', '広島', '山口', '徳島', '香川', '愛媛', '高知', '福岡', '福岡', '佐賀',
        '長崎', '熊本', '大分', '宮崎', '鹿児島', '沖縄',
    ];

    private const KANSAI_PREFS = ['大阪', '京都', '兵庫', '奈良', '和歌山', '滋賀'];

    /**
     * Deterministically generate the Nth persona definition (0-indexed).
     * Same index always yields the same attributes so bootstrap is idempotent.
     *
     * @return array{name:string,email:string,archetype:string,gender:string,age:int,prefecture:string,dialect:string,voice:string,interests:list<string>,activity:float,job:string}
     */
    public static function definition(int $index): array
    {
        $rng = fn (int $mod) => self::hash($index, 'n') % $mod;

        [$w, $min, $max, $g, $jobs, $pools] = self::pickArchetype($index);
        $gender = $g === 'u' ? ($rng(10) < 5 ? 'f' : 'm') : $g;
        $age = $min + $rng($max - $min + 1);
        $pref = self::PREFS[$rng(count(self::PREFS))];
        $dialect = in_array($pref, self::KANSAI_PREFS) && $rng(10) < 6 ? 'kansai' : 'std';
        $voice = self::pickVoice($index, $dialect, $gender, $age);
        $name = self::makeName($index, $gender);
        $job = $jobs[$rng(count($jobs))];
        $interests = self::pickInterests($index, $pools);
        $activity = 0.15 + ($rng(100) / 100) * 0.85; // 0.15..1.0 — some lurkers, some addicts

        return [
            'name' => $name,
            'email' => sprintf('p%05d@%s', $index + 1, config('engawa.simulation.persona_email_domain')),
            'archetype' => self::archetypeName($index),
            'gender' => $gender,
            'age' => $age,
            'prefecture' => $pref,
            'dialect' => $dialect,
            'voice' => $voice,
            'interests' => $interests,
            'activity' => round($activity, 3),
            'job' => $job,
        ];
    }

    private static function hash(int $index, string $salt): int
    {
        return (int) sprintf('%u', crc32("persona:$salt:$index"));
    }

    private static function archetypeName(int $index): string
    {
        $total = array_sum(array_column(self::ARCHETYPES, 0));
        $roll = self::hash($index, 'arch') % $total;
        foreach (self::ARCHETYPES as $name => $def) {
            $roll -= $def[0];
            if ($roll < 0) {
                return $name;
            }
        }

        return 'college';
    }

    /** @return array{0:int,1:int,2:int,3:string,4:list<string>,5:list<string>} */
    private static function pickArchetype(int $index): array
    {
        return self::ARCHETYPES[self::archetypeName($index)];
    }

    private static function pickVoice(int $index, string $dialect, string $gender, int $age): string
    {
        $roll = self::hash($index, 'voice') % 100;
        if ($dialect === 'kansai') {
            return 'kansai';
        }
        $archetype = self::archetypeName($index);
        if ($archetype === 'otaku' && $roll < 55) {
            return 'otaku';
        }
        if ($archetype === 'neet' && $roll < 40) {
            return 'otaku';
        }
        if ($age >= 30) {
            return $roll < 30 ? 'adult' : 'teinei';
        }
        if ($gender === 'f') {
            return $roll < 35 ? 'genki' : ($roll < 55 ? 'gyaru' : 'soft');
        }

        return $roll < 40 ? 'cool' : ($roll < 70 ? 'genki' : 'soft');
    }

    private static function makeName(int $index, string $gender): string
    {
        $pool = $gender === 'f' ? self::FEMALE : self::MALE;
        $given = $pool[self::hash($index, 'given') % count($pool)];
        $surname = self::SURNAMES[self::hash($index, 'sur') % count(self::SURNAMES)];
        $style = self::hash($index, 'style') % 10;

        // mixi names were often nickname-y: given name only, or surname+given, rarely romaji.
        return match ($style) {
            0 => $surname.$given,
            1, 2 => $given,
            3 => $given.self::NICK_SUFFIX[self::hash($index, 'suf') % count(self::NICK_SUFFIX)],
            4 => mb_substr($surname, 0, 1).$given, // e.g. 佐藤美咲 -> 佐美咲? no: 佐+美咲
            5 => 'の'.$given,
            6 => $given.($gender === 'f' ? 'ちゃん' : 'くん'),
            default => $given,
        };
    }

    /**
     * @param  list<string>  $pools
     * @return list<string>
     */
    private static function pickInterests(int $index, array $pools): array
    {
        $vocab = Content\Fragments::vocab();
        $interests = [];
        foreach ($pools as $pool) {
            $key = match ($pool) {
                'music' => 'band', 'game' => 'game', 'anime' => 'manga', 'food' => 'food',
                'tv' => 'drama', 'net', 'life' => 'hobby', 'work' => 'hobby', 'school' => 'club',
                default => 'hobby',
            };
            $list = $vocab[$key] ?? $vocab['hobby'];
            $interests[] = $list[self::hash($index, "int:$pool") % count($list)];
        }
        // Pad to 3 interests from hobby pool.
        while (count($interests) < 3) {
            $candidate = $vocab['hobby'][self::hash($index, 'pad'.count($interests)) % count($vocab['hobby'])];
            if (! in_array($candidate, $interests)) {
                $interests[] = $candidate;
            }
        }

        return array_values(array_unique($interests));
    }

    /**
     * Create the User + Persona rows for one definition if not present.
     * Idempotent by email; safe to re-run across ticks.
     */
    public function materialize(int $index, CarbonImmutable $simNow): ?Persona
    {
        $def = self::definition($index);
        $user = User::query()->where('email', $def['email'])->first();
        if ($user === null) {
            $user = new User;
            $user->name = $def['name'];
            $user->email = $def['email'];
            $user->password = Hash::make(Str::random(40));
            $user->bio = $this->bioFor($def);
            $user->email_social_notifications = false; // personas never receive mail
            $user->email_verified_at = $simNow;
            $user->created_at = $simNow;
            $user->save();
        }

        $persona = Persona::query()->where('user_id', $user->id)->first();
        if ($persona === null) {
            $persona = Persona::create([
                'user_id' => $user->id,
                'archetype' => $def['archetype'],
                'gender' => $def['gender'],
                'age' => $def['age'],
                'prefecture' => $def['prefecture'],
                'dialect' => $def['dialect'],
                'voice' => $def['voice'],
                'interests' => $def['interests'],
                'activity' => $def['activity'],
                'seed' => self::hash($index, 'seed'),
                'last_sim_active_at' => $simNow,
            ]);
        }

        return $persona;
    }

    /**
     * Short 2006-style profile bio composed from the persona's traits.
     *
     * @param  array<string, mixed>  $def
     */
    private function bioFor(array $def): string
    {
        $interests = implode('、', array_slice($def['interests'], 0, 2));
        $tail = match ($def['voice']) {
            'genki' => 'よろしくお願いします♪',
            'gyaru' => 'よろぴく☆',
            'otaku' => 'よろしくお願いします。',
            'kansai' => 'よろしくね!',
            'cool' => 'よろ。',
            'adult' => 'よろしくお願いいたします。',
            default => 'よろしくお願いします。',
        };

        return "{$def['prefecture']}の{$def['job']}、{$def['age']}です。{$interests}が好き。{$tail}";
    }
}
