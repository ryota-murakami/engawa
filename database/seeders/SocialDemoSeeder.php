<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\Diary;
use App\Models\DiaryComment;
use App\Models\Friendship;
use App\Models\Membership;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SocialDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('デモ会員はlocal/testingでだけ作成できます。');
        }

        $users = [];
        foreach (['alice' => 'アリス', 'bob' => 'ボブ', 'carol' => 'キャロル'] as $slug => $name) {
            $user = User::query()->firstOrNew(['email' => $slug.'@example.test']);
            $user->name = $name;
            $user->password = Hash::make('LocalDemo-ChangeMe-2026!');
            $user->email_verified_at = now();
            $user->bio = 'これは開発用のデモプロフィールです。';
            $user->save();
            $users[$slug] = $user;
        }

        [$alice, $bob, $carol] = [$users['alice'], $users['bob'], $users['carol']];
        Friendship::query()->updateOrCreate([
            'user_low_id' => min($alice->id, $bob->id),
            'user_high_id' => max($alice->id, $bob->id),
        ], ['requested_by' => $alice->id, 'status' => 'accepted']);

        foreach (['members' => '会員のみなさんへ', 'friends' => 'マイミクへの日記', 'private' => '自分だけのメモ'] as $visibility => $title) {
            Diary::query()->firstOrCreate(['user_id' => $alice->id, 'title' => $title], [
                'body' => "公開範囲は {$visibility} です。\nアリス・ボブ・キャロルで見え方を比較してください。",
                'visibility' => $visibility,
            ]);
        }

        $diary = Diary::query()->where('user_id', $alice->id)->where('visibility', 'friends')->firstOrFail();
        DiaryComment::query()->firstOrCreate([
            'diary_id' => $diary->id, 'user_id' => $bob->id, 'body' => 'マイミクとして読むことができました。',
        ]);

        $community = Community::query()->firstOrCreate(['owner_id' => $alice->id, 'name' => 'はじめてのLaravel'], [
            'description' => '作ったものや学んだことを共有する、開発用のコミュニティです。',
        ]);
        foreach ([$alice, $bob] as $user) {
            Membership::query()->firstOrCreate(['community_id' => $community->id, 'user_id' => $user->id], [
                'role' => $user->id === $alice->id ? 'owner' : 'member',
            ]);
        }
        Topic::query()->firstOrCreate(['community_id' => $community->id, 'title' => '自己紹介はこちら'], [
            'user_id' => $alice->id, 'body' => '参加したら、好きな技術をひとつ教えてください。',
        ]);
    }
}
