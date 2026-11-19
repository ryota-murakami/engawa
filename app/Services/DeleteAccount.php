<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteAccount
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $current = User::query()->lockForUpdate()->findOrFail($user->id);

            // 親のcascadeがmedia行を消す前にobjectの削除を予約する。
            $current->media()->get()->each(function ($media): void {
                app(MediaStorage::class)->delete($media);
            });

            // morph関係は通常の外部キーでは削除されない。
            $current->notifications()->delete();

            // 標準のreset tokensとdatabase sessionsにはusersへのFKがない。
            DB::table('password_reset_tokens')->where('email', $current->email)->delete();
            DB::table('sessions')->where('user_id', $current->id)->delete();

            // 所有コミュニティは、会員に事前説明した仕様として閉鎖する。
            // テーブルのFK cascadeによりtopics/replies/membershipsも削除。
            $current->delete();
        });
    }
}
