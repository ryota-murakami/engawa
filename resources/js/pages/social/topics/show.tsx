import { Link } from '@inertiajs/react';
import CommentThread from '@/components/social/comment-thread';
import SocialLayout from '@/components/social/social-layout';
import {
    ActionButton,
    MemberLink,
    Section,
    textLinkClass,
} from '@/components/social/ui';
import type { CommentCard, Paginator, UserCard } from '@/types/social';

type Topic = {
    id: number;
    title: string;
    body: string;
    author: UserCard;
    community: { id: number; name: string };
    can_delete: boolean;
};

export default function Topic({
    topic,
    replies,
    can_reply,
}: {
    topic: Topic;
    replies: Paginator<CommentCard>;
    can_reply: boolean;
}) {
    return (
        <SocialLayout title={topic.title}>
            <Link
                href={`/communities/${topic.community.id}`}
                className={textLinkClass}
            >
                ← {topic.community.name}
            </Link>
            <Section title="トピック本文">
                <MemberLink user={topic.author} />
                <p className="leading-8 break-words whitespace-pre-wrap">
                    {topic.body}
                </p>
                {topic.can_delete && (
                    <ActionButton
                        url={`/topics/${topic.id}`}
                        method="delete"
                        danger
                        confirm="このトピックとすべての返信を削除しますか？"
                    >
                        トピックを削除
                    </ActionButton>
                )}
            </Section>
            <Section title={`返信 (${replies.total})`}>
                <CommentThread
                    key={topic.id}
                    comments={replies}
                    postUrl={`/topics/${topic.id}/replies`}
                    deletePrefix="/replies"
                    canPost={can_reply}
                    noun="返信"
                />
            </Section>
        </SocialLayout>
    );
}
