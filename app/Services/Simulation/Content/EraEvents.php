<?php

namespace App\Services\Simulation\Content;

use Carbon\CarbonInterface;

/**
 * The 2006 calendar the simulation replays. Dated events (festivals,
 * releases, news) steer what personas talk about on the matching sim days.
 */
class EraEvents
{
    /**
     * Entries: [start MM-DD, end MM-DD|null, label, [content keys]].
     * Content keys match DiaryBank themes and boost related vocab/topics.
     *
     * @return list<array{0: string, 1: string|null, 2: string, 3: list<string>}>
     */
    public static function calendar(): array
    {
        return [
            ['01-01', '01-03', 'お正月', ['newyear']],
            ['01-02', '01-03', '箱根駅伝', ['hakone']],
            ['01-09', '01-09', '成人の日', ['seijin']],
            ['01-14', '01-15', 'センター試験', ['center']],
            ['01-16', '01-24', 'ライブドアショック・堀江逮捕', ['livedoor']],
            ['01-26', '02-09', 'インフルエンザ・寒波', ['winter']],
            ['02-03', '02-03', '節分', ['setsubun']],
            ['02-10', '02-26', 'トリノ五輪', ['olympic']],
            ['02-11', '02-11', '建国記念日', ['holiday']],
            ['02-14', '02-14', 'バレンタイン', ['valentine']],
            ['02-23', '02-25', '荒川静香 金メダル(イナバウアー)', ['inabauer']],
            ['03-01', '03-25', '卒業式シーズン', ['graduation']],
            ['03-02', '03-02', 'mixi会員数100万人突破', ['mixi_news']],
            ['03-03', '03-03', 'ひな祭り', ['hina']],
            ['03-03', '03-21', 'WBC(日本優勝)', ['wbc']],
            ['03-14', '03-14', 'ホワイトデー', ['whiteday']],
            ['03-16', '03-19', 'FF12発売', ['ff12']],
            ['03-22', '03-24', 'KAT-TUNデビュー', ['kattun']],
            ['03-27', '04-09', '桜・花見シーズン', ['hanami']],
            ['04-01', '04-01', 'エイプリルフール', ['aprilfool']],
            ['04-01', '04-10', '新生活・入社式', ['newlife']],
            ['04-02', '07-02', '涼宮ハルヒの憂鬱 放映', ['haruhi']],
            ['04-10', '05-20', '新歓・歓迎会シーズン', ['party']],
            ['04-29', '05-07', 'ゴールデンウィーク', ['gw']],
            ['05-14', '05-14', '母の日', ['mother']],
            ['05-15', '05-31', '五月病', ['may_blues']],
            ['06-01', '06-30', '梅雨', ['rainy']],
            ['06-09', '07-09', 'FIFAワールドカップ ドイツ大会', ['worldcup']],
            ['06-12', '06-13', '日本 vs オーストラリア', ['worldcup']],
            ['06-18', '06-18', '父の日 / 日本 vs クロアチア', ['worldcup']],
            ['06-22', '06-23', '日本 vs ブラジル(敗退)', ['worldcup']],
            ['07-05', '07-06', '北朝鮮ミサイル発射', ['news']],
            ['07-07', '07-07', '七夕', ['tanabata']],
            ['07-17', '07-17', '海の日', ['umi']],
            ['07-20', '08-31', '夏休み', ['summer']],
            ['07-22', '07-25', '中田英寿 引退表明', ['worldcup']],
            ['08-06', '08-21', '夏の甲子園(早実・ハンカチ王子)', ['koshien']],
            ['08-11', '08-13', '夏コミ(C70)', ['comiket']],
            ['08-13', '08-16', 'お盆・帰省', ['obon']],
            ['08-01', '08-31', '花火大会シーズン', ['hanabi']],
            ['09-01', '09-10', '新学期・台風', ['autumn_start']],
            ['09-14', '09-16', 'mixi上場', ['mixi_news']],
            ['09-18', '09-18', '敬老の日', ['holiday']],
            ['09-26', '09-28', '安倍内閣発足', ['news']],
            ['09-28', '10-02', 'ポケモン ダイヤモンド・パール発売', ['pokemon']],
            ['10-06', '10-06', '十五夜', ['tsukimi']],
            ['10-09', '10-11', '体育の日・北朝鮮核実験', ['news']],
            ['10-01', '10-31', '学園祭・体育祭シーズン', ['festival']],
            ['10-11', '12-20', '「14才の母」放送', ['dorama']],
            ['10-16', '12-25', '「のだめカンタービレ」ドラマ', ['nodame']],
            ['10-31', '10-31', 'ハロウィン(まだ地味)', ['halloween']],
            ['11-03', '11-05', '文化の日・デスノート後篇公開', ['deathnote']],
            ['11-11', '11-13', 'PS3発売', ['ps3']],
            ['11-16', '11-16', 'ボジョレー・ヌーボー解禁', ['beaujolais']],
            ['11-17', '12-01', 'Wii予約・発売', ['wii']],
            ['11-22', '11-23', 'いい夫婦の日・勤労感謝の日', ['holiday']],
            ['11-01', '11-30', '紅葉シーズン', ['autumn']],
            ['12-02', '12-03', 'Wii発売', ['wii']],
            ['12-01', '12-23', '忘年会シーズン', ['bounenkai']],
            ['12-12', '12-14', 'ニコニコ動画(仮)公開', ['nico']],
            ['12-20', '12-25', 'クリスマス', ['christmas']],
            ['12-24', '12-25', 'クリスマスイブ・当日', ['christmas']],
            ['12-26', '12-30', '年末・年賀状・大掃除', ['yearend']],
            ['12-29', '12-31', '冬コミ(C71)', ['comiket']],
            ['12-31', '12-31', '大晦日・紅白・年越し', ['omisoka']],
        ];
    }

    /**
     * Keys of events covering the given sim date (most specific first).
     *
     * @return list<string>
     */
    public static function keysFor(CarbonInterface $simDate): array
    {
        $md = $simDate->format('m-d');
        $keys = [];
        foreach (self::calendar() as [$start, $end, $label, $eventKeys]) {
            $end = $end ?? $start;
            if ($md >= $start && $md <= $end) {
                foreach ($eventKeys as $k) {
                    $keys[$k] = true;
                }
            }
        }

        return array_keys($keys);
    }

    /**
     * Labels of events covering the given sim date, for display/debug.
     *
     * @return list<string>
     */
    public static function labelsFor(CarbonInterface $simDate): array
    {
        $md = $simDate->format('m-d');
        $labels = [];
        foreach (self::calendar() as [$start, $end, $label]) {
            if ($md >= $start && $md <= ($end ?? $start)) {
                $labels[] = $label;
            }
        }

        return $labels;
    }
}
