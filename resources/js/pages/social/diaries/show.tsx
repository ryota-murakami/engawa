import { Link } from '@inertiajs/react';
import SocialLayout from '@/components/social/social-layout';
import {
    ActionButton,
    DateTime,
    MemberLink,
    Section,
    secondaryClass,
    visibilityLabels,
} from '@/components/social/ui';
import type { DiaryCard } from '@/types/social';

export default function Diary({ diary }: { diary: DiaryCard }) {
    return (
        <SocialLayout title={diary.title}>
            <Section title="日記">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <MemberLink user={diary.author} />
                    <DateTime value={diary.created_at} />
                </div>
                <p className="text-xs text-stone-600">
                    公開範囲：{visibilityLabels[diary.visibility]}
                </p>
                <p className="leading-8 break-words whitespace-pre-wrap">
                    {diary.body}
                </p>
                {diary.can_update && (
                    <div className="flex flex-wrap gap-3">
                        <Link
                            href={`/diaries/${diary.id}/edit`}
                            className={secondaryClass}
                        >
                            日記を編集
                        </Link>
                        <ActionButton
                            url={`/diaries/${diary.id}`}
                            method="delete"
                            danger
                            confirm="日記・コメント・添付画像を削除しますか？"
                        >
                            日記を削除
                        </ActionButton>
                    </div>
                )}
            </Section>
        </SocialLayout>
    );
}
