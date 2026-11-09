import { Link } from '@inertiajs/react';
import DiaryList from '@/components/social/diary-list';
import SocialLayout from '@/components/social/social-layout';
import { Section, secondaryClass } from '@/components/social/ui';
import type { DiaryCard, Paginator } from '@/types/social';

export default function Home({
    diaries,
    filter,
}: {
    diaries: Paginator<DiaryCard>;
    filter: 'friends' | 'all';
}) {
    return (
        <SocialLayout
            title="ホーム"
            description="自分とマイミクの日記を、ゆっくり読んでみましょう。"
        >
            <nav aria-label="日記の表示範囲" className="flex gap-2">
                <Link
                    href="/home?filter=friends"
                    className={secondaryClass}
                    aria-current={filter === 'friends' ? 'page' : undefined}
                >
                    マイミクと自分
                </Link>
                <Link
                    href="/home?filter=all"
                    className={secondaryClass}
                    aria-current={filter === 'all' ? 'page' : undefined}
                >
                    会員の日記
                </Link>
            </nav>
            <Section
                title={
                    filter === 'friends'
                        ? 'マイミクと自分の新着日記'
                        : '読める日記の一覧'
                }
            >
                <DiaryList diaries={diaries} />
            </Section>
        </SocialLayout>
    );
}
