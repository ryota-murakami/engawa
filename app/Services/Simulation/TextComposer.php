<?php

namespace App\Services\Simulation;

use App\Models\Persona;
use App\Services\Simulation\Content\Fragments;

/**
 * Fills {slot} placeholders in templates with era-2006 vocabulary and the
 * persona's traits, then applies the persona's writing voice (語尾・顔文字).
 */
class TextComposer
{
    /** @var array<string, list<string>> */
    private array $vocab;

    /** @var array<string, array{openers:list<string>,closers:list<string>,kaomoji:list<string>,endings:array<string, string>}> */
    private array $voices;

    public function __construct()
    {
        $this->vocab = Fragments::vocab();
        $this->voices = Fragments::voices();
    }

    /**
     * Compose final text for a persona from a template containing {slots}.
     *
     * @param  array<string, mixed>  $extra  extra slot values (e.g. persona fields)
     */
    public function compose(string $template, Persona $persona, array $extra = []): string
    {
        $text = preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($persona, $extra) {
            $slot = $m[1];

            return $extra[$slot] ?? $this->slot($slot, $persona);
        }, $template) ?? $template;

        return $this->applyVoice($text, $persona);
    }

    /** Default filler for a slot name, preferring persona traits when relevant. */
    private function slot(string $slot, Persona $persona): string
    {
        return match ($slot) {
            'name' => $persona->user->name,
            'age' => (string) $persona->age,
            'pref' => $persona->prefecture,
            'job' => self::jobLabel($persona),
            'work_school' => self::workSchool($persona),
            'hobby' => ($persona->interests ?? [])[0] ?? $this->pick('hobby'),
            'friend' => $this->friendName($persona),
            'time' => random_int(5, 9).'時',
            'hours' => (string) random_int(2, 8),
            'count' => (string) random_int(8, 60),
            'number' => (string) random_int(15, 60),
            'weight' => (string) random_int(45, 70),
            'overtime' => (string) random_int(10, 60),
            'choco' => ['手作りチョコ', 'ガトーショコラ', 'トリュフ', 'クッキー'][random_int(0, 3)],
            'direction' => ['北北西', '南南東', '西南西'][random_int(0, 2)],
            'fortune' => ['大吉', '中吉', '小吉', '吉', '末吉'][random_int(0, 4)],
            'goal' => ['貯金', 'ダイエット', '資格取得', '早起き', '料理上達'][random_int(0, 4)],
            'wish' => ['恋人ができますように', '仕事がうまくいきますように', '健康第一', '宝くじ当たれ'][random_int(0, 3)],
            'snack' => ['ポテチ', 'じゃがりこ', 'プリッツ', 'チョコ'][random_int(0, 3)],
            'book' => ['東野圭吾の新刊', '村上春樹', '乙一', '重松清'][random_int(0, 3)],
            'player' => ['中田英寿', '中村俊輔', '高原', '柳沢', '大黒'][random_int(0, 4)],
            'starter' => ['ポッチャマ', 'ヒコザル', 'ナエトル'][random_int(0, 2)],
            'keitai' => ['W51S', 'N902i', 'D905i', 'SH903i', 'W44S', 'P903i'][random_int(0, 5)],
            'env' => ['WinXP + Eclipse', 'Vim + gcc', 'Windows + Visual Studio', 'Mac + テキストエディタ'][random_int(0, 3)],
            'work_fail' => ['資料の誤字', 'メール送信忘れ', '会議で居眠り', '電話取り次ぎミス'][random_int(0, 3)],
            'big_company' => ['トヨタ', 'ソニー', '松下', '富士通', 'NEC', 'リクルート', '博報堂'][random_int(0, 6)],
            'opinion' => ['賛成', '反対', '状況による', 'どちらかといえば賛成'][random_int(0, 3)],
            'hearsay' => ['結構前から話題らしい', 'またニュースになるらしい', '意外と有名らしい'][random_int(0, 2)],
            'year' => (string) random_int(2000, 2005),
            'worry' => ['将来のこと', 'お金のこと', '人間関係', '仕事のこと'][random_int(0, 3)],
            'train', 'station' => $this->pick('station'),
            'band', 'song', 'drama', 'movie', 'manga', 'game', 'talent', 'tvshow', 'shop',
            'place', 'food', 'drink', 'item', 'subject', 'club', 'hobby', 'weather' => $this->pick($slot),
            default => $this->pick('hobby'),
        };
    }

    private function pick(string $slot): string
    {
        $list = $this->vocab[$slot] ?? $this->vocab['hobby'];

        return $list[array_rand($list)];
    }

    private static function jobLabel(Persona $persona): string
    {
        return match ($persona->archetype) {
            'college' => '大学生', 'senmon' => '専門学生', 'salaryman' => '会社員', 'ol' => 'OL',
            'engineer' => 'SE', 'civil' => '公務員', 'nurse' => '看護師', 'hairdresser' => '美容師',
            'freeter' => 'フリーター', 'neet' => 'ニート', 'otaku' => '一般人', 'mama' => '主婦',
            default => '社会人',
        };
    }

    private static function workSchool(Persona $persona): string
    {
        return in_array($persona->archetype, ['college', 'senmon']) ? '学校' : '仕事';
    }

    /** A plausible friend name for this persona's gender milieu. */
    private function friendName(Persona $persona): string
    {
        $names = $persona->gender === 'f'
            ? ['ゆうこ', 'あや', 'みき', 'なな', 'りな', 'さき', 'えり', 'まな']
            : ['たかし', 'けん', 'ゆうき', 'こうじ', 'まさし', 'たいち', 'しん', 'だい'];

        return $names[array_rand($names)];
    }

    /**
     * Apply the persona's writing voice: opener/closer lines, kaomoji
     * decoration and conservative sentence-ending substitutions.
     */
    private function applyVoice(string $text, Persona $persona): string
    {
        $v = $this->voices[$persona->voice] ?? $this->voices['soft'];

        // Ending substitutions: only exact safe suffixes, low probability so text stays readable.
        foreach ($v['endings'] as $from => $to) {
            if ($from === 'ます。' || $from === 'です。') {
                continue; // 丁寧語→崩す変換は危険なのでスキップ
            }
            if (random_int(0, 100) < 45) {
                $text = str_replace($from, $to, $text);
            }
        }

        // Gyaru flavor: occasional light substitutions.
        if ($persona->voice === 'gyaru' && random_int(0, 100) < 40) {
            foreach (Fragments::gyaruQuirks() as $from => $to) {
                if (random_int(0, 100) < 25) {
                    $text = str_replace($from, $to, $text);
                }
            }
        }

        // Kaomoji after sentence-final punctuation (~1 in 3 sentences).
        if ($v['kaomoji'] !== [] && random_int(0, 100) < 60) {
            $k = $v['kaomoji'][array_rand($v['kaomoji'])];
            $pos = mb_strrpos($text, '。');
            if ($pos !== false) {
                $text = mb_substr($text, 0, $pos).$k.'。'.mb_substr($text, $pos + 1);
            } else {
                $text .= $k;
            }
        }

        // Opener / closer lines with modest probability.
        if ($v['openers'] !== [] && random_int(0, 100) < 25) {
            $text = $v['openers'][array_rand($v['openers'])]."\n".$text;
        }
        if ($v['closers'] !== [] && random_int(0, 100) < 30) {
            $text = $text."\n".$v['closers'][array_rand($v['closers'])];
        }

        return $text;
    }
}
