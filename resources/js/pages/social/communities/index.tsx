import { Link } from '@inertiajs/react'
import SocialLayout from '@/components/social/social-layout'
import {
    buttonClass,
    Empty,
    MemberLink,
    Pagination,
    Section,
    textLinkClass,
} from '@/components/social/ui'
import type { CommunityCard, Paginator } from '@/types/social'

export default function Communities({ communities }: { communities: Paginator<CommunityCard> }) {
    return (
        <SocialLayout
            title="コミュニティ"
            description="同じ好きなことを、トピックで語り合いましょう。"
            action={
                <Link href="/communities/create" className={buttonClass}>
                    コミュニティを作る
                </Link>
            }
        >
            <Section title="コミュニティ一覧">
                {!communities.data.length && <Empty>コミュニティはまだありません。</Empty>}
                {communities.data.map((community) => (
                    <article
                        key={community.id}
                        className="space-y-3 border-b border-stone-100 pb-5"
                    >
                        <h2 className="text-lg font-bold">
                            <Link href={`/communities/${community.id}`} className={textLinkClass}>
                                {community.name}
                            </Link>
                        </h2>
                        <p className="line-clamp-3 text-sm leading-7 break-words whitespace-pre-wrap">
                            {community.description}
                        </p>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <MemberLink user={community.owner} />
                            <span className="text-xs text-stone-600">
                                {community.members_count} 人{community.is_member ? ' · 参加中' : ''}
                            </span>
                        </div>
                    </article>
                ))}
                <Pagination page={communities} />
            </Section>
        </SocialLayout>
    )
}
