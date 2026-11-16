import { Link } from '@inertiajs/react';
import CommentThread from '@/components/social/comment-thread';
import SocialLayout from '@/components/social/social-layout';
import {
    ActionButton,
    DateTime,
    MemberLink,
    Section,
    secondaryClass,
    visibilityLabels,
} from '@/components/social/ui';
import type { CommentCard, DiaryCard, Paginator } from '@/types/social';

export default function Diary({
    diary,
    comments,
    can_comment,
}: {
    diary: DiaryCard;
    comments: Paginator<CommentCard>;
    can_comment: boolean;
}) {
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
                <p className="leading-8 wrap-break-word whitespace-pre-wrap">
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
            <Section title={`コメント (${comments.total})`}>
                <CommentThread
                    key={diary.id}
                    comments={comments}
                    postUrl={`/diaries/${diary.id}/comments`}
                    deletePrefix="/comments"
                    canPost={can_comment}
                />
            </Section>
        </SocialLayout>
    );
}