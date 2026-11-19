import SocialLayout from '@/components/social/social-layout';
import {
    DateTime,
    Empty,
    MemberLink,
    Pagination,
    Section,
} from '@/components/social/ui';
import type { Footprint, Paginator } from '@/types/social';

export default function Footprints({
    footprints,
}: {
    footprints: Paginator<Footprint>;
}) {
    return (
        <SocialLayout
            title="足あと"
            description="過去 30 日間に、あなたのプロフィールを見たメンバーです。同じ人の訪問は 1 日に 1 件にまとめます。"
        >
            <Section title="最近の訪問">
                {!footprints.data.length && (
                    <Empty>足あとはまだありません。</Empty>
                )}
                {footprints.data.map((footprint) => (
                    <div
                        key={footprint.id}
                        className="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 pb-4"
                    >
                        <MemberLink user={footprint.visitor} />
                        <DateTime value={footprint.last_visited_at} />
                    </div>
                ))}
                <Pagination page={footprints} />
            </Section>
        </SocialLayout>
    );
}
