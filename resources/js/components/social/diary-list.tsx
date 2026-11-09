import { Link } from '@inertiajs/react';
import type { DiaryCard, Paginator } from '@/types/social';
import {
    DateTime,
    Empty,
    MemberLink,
    Pagination,
    textLinkClass,
    visibilityLabels,
} from './ui';

export default function DiaryList({
    diaries,
}: {
    diaries: Paginator<DiaryCard>;
}) {
    return (
        <div className="space-y-5">
            {diaries.data.length === 0 && (
                <Empty>表示できる日記はまだありません。</Empty>
            )}
            {diaries.data.map((diary) => (
                <article
                    key={diary.id}
                    className="space-y-3 border-b border-stone-100 pb-5 last:border-b-0"
                >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <MemberLink user={diary.author} />
                        <DateTime value={diary.created_at} />
                    </div>
                    <h3 className="text-lg font-bold">
                        <Link
                            href={`/diaries/${diary.id}`}
                            className={textLinkClass}
                        >
                            {diary.title}
                        </Link>
                    </h3>
                    <p className="line-clamp-3 text-sm leading-7 break-words whitespace-pre-wrap text-stone-700">
                        {diary.body}
                    </p>
                    <p className="text-xs text-stone-500">
                        {visibilityLabels[diary.visibility]} · コメント{' '}
                        {diary.comments_count} 件
                    </p>
                </article>
            ))}
            <Pagination page={diaries} />
        </div>
    );
}
