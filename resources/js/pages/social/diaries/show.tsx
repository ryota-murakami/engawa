import { Link } from '@inertiajs/react';
import CommentThread from '@/components/social/comment-thread';
import ImageUpload from '@/components/social/image-upload';
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
    const images = diary.images ?? [];
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
                {images.length > 0 && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {images.map((image, index) => (
                            <figure key={image.id} className="space-y-2">
                                <a
                                    href={image.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="block rounded-lg focus-visible:outline-2 focus-visible:outline-orange-700"
                                >
                                    <img
                                        src={image.url}
                                        alt={`${diary.title} の添付画像 ${index + 1}`}
                                        className="max-h-96 w-full rounded-lg border border-stone-200 object-contain"
                                        loading="lazy"
                                    />
                                </a>
                                {diary.can_update && (
                                    <ActionButton
                                        url={`/media/${image.id}`}
                                        method="delete"
                                        danger
                                        confirm="この画像を削除しますか？"
                                    >
                                        画像を削除
                                    </ActionButton>
                                )}
                            </figure>
                        ))}
                    </div>
                )}
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
            {diary.can_update && (
                <Section title={`写真を追加 (${images.length} / 3 枚)`}>
                    {images.length < 3 ? (
                        <ImageUpload
                            url={`/diaries/${diary.id}/images`}
                            field="image"
                            label="日記に添付する画像"
                        />
                    ) : (
                        <p className="text-sm text-stone-600">
                            画像は 3 枚までです。入れ替えるときは、先に 1
                            枚削除してください。
                        </p>
                    )}
                </Section>
            )}
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
