import { Link } from '@inertiajs/react';
import SocialLayout from '@/components/social/social-layout';
import {
    ActionButton,
    buttonClass,
    DateTime,
    Empty,
    MemberLink,
    Pagination,
    Section,
    textLinkClass,
} from '@/components/social/ui';
import type { CommunityCard, Paginator, TopicCard } from '@/types/social';

export default function Community({
    community,
    topics,
    is_member,
    is_owner,
}: {
    community: CommunityCard;
    topics: Paginator<TopicCard>;
    is_member: boolean;
    is_owner: boolean;
}) {
    return (
        <SocialLayout title={community.name}>
            <Section title="コミュニティについて">
                <p className="text-sm leading-7 break-words whitespace-pre-wrap">
                    {community.description}
                </p>
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <MemberLink user={community.owner} />
                    <span className="text-sm text-stone-600">
                        参加メンバー {community.members_count} 人
                    </span>
                </div>
                {!is_member && (
                    <ActionButton url={`/communities/${community.id}/join`}>
                        このコミュニティに参加する
                    </ActionButton>
                )}
                {is_member && !is_owner && (
                    <ActionButton
                        url={`/communities/${community.id}/membership`}
                        method="delete"
                        confirm="コミュニティから退会しますか？これまでの投稿は残ります。"
                    >
                        コミュニティから退会
                    </ActionButton>
                )}
                {is_owner && (
                    <>
                        <p className="text-sm text-stone-600">
                            あなたはこのコミュニティの管理者です。
                        </p>
                        <ActionButton
                            url={`/communities/${community.id}`}
                            method="delete"
                            danger
                            confirm="コミュニティとすべてのトピック・返信を削除しますか？"
                        >
                            コミュニティを削除
                        </ActionButton>
                    </>
                )}
            </Section>
            <Section
                title="トピック"
                action={
                    is_member && (
                        <Link
                            href={`/communities/${community.id}/topics/create`}
                            className={buttonClass}
                        >
                            トピックを作る
                        </Link>
                    )
                }
            >
                {!topics.data.length && (
                    <Empty>トピックはまだありません。</Empty>
                )}
                {topics.data.map((topic) => (
                    <article
                        key={topic.id}
                        className="space-y-3 border-b border-stone-100 pb-4"
                    >
                        <h2 className="font-bold">
                            <Link
                                href={`/topics/${topic.id}`}
                                className={textLinkClass}
                            >
                                {topic.title}
                            </Link>
                        </h2>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <MemberLink user={topic.author} />
                            <span className="text-xs text-stone-600">
                                返信 {topic.replies_count} 件
                            </span>
                            <DateTime value={topic.created_at} />
                        </div>
                    </article>
                ))}
                <Pagination page={topics} />
            </Section>
        </SocialLayout>
    );
}
