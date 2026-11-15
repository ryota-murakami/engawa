import { Link, usePage } from '@inertiajs/react';
import DiaryList from '@/components/social/diary-list';
import SocialLayout from '@/components/social/social-layout';
import {
    ActionButton,
    Avatar,
    Section,
    secondaryClass,
} from '@/components/social/ui';
import type {
    DiaryCard,
    Friendship,
    Paginator,
    SocialSharedProps,
    UserCard,
} from '@/types/social';

export default function Member({
    member,
    diaries,
    friendship,
    is_self,
}: {
    member: UserCard;
    diaries: Paginator<DiaryCard>;
    friendship: Friendship | null;
    is_self: boolean;
}) {
    const props = usePage().props as unknown as SocialSharedProps;
    const userId = props.auth?.user?.id;
    return (
        <SocialLayout title={`${member.name} さんのページ`}>
            <Section title="プロフィール">
                <div className="flex flex-wrap items-start gap-5">
                    <Avatar user={member} large />
                    <div className="min-w-0 flex-1 space-y-3">
                        <h2 className="text-xl font-bold">{member.name}</h2>
                        <p className="text-sm leading-7 wrap-break-word whitespace-pre-wrap">
                            {member.bio || '自己紹介はまだありません。'}
                        </p>
                    </div>
                </div>
                {is_self ? (
                    <Link href="/profile/edit" className={secondaryClass}>
                        プロフィールを編集
                    </Link>
                ) : (
                    <div className="space-y-4">
                        <div className="flex flex-wrap gap-3">
                            {!friendship && (
                                <ActionButton
                                    url={`/members/${member.id}/friend-request`}
                                >
                                    マイミク申請を送る
                                </ActionButton>
                            )}
                            {friendship?.status === 'accepted' && (
                                <>
                                    <span className="self-center text-sm text-green-800">
                                        マイミクです
                                    </span>
                                    <ActionButton
                                        url={`/friendships/${friendship.id}`}
                                        method="delete"
                                        confirm="マイミクを解除しますか？"
                                    >
                                        マイミクを解除
                                    </ActionButton>
                                </>
                            )}
                            {friendship?.status === 'pending' &&
                                friendship.requested_by === userId && (
                                    <>
                                        <span className="self-center text-sm text-stone-600">
                                            申請を送っています
                                        </span>
                                        <ActionButton
                                            url={`/friendships/${friendship.id}`}
                                            method="delete"
                                        >
                                            申請を取り消す
                                        </ActionButton>
                                    </>
                                )}
                            {friendship?.status === 'pending' &&
                                friendship.requested_by !== userId && (
                                    <>
                                        <ActionButton
                                            url={`/friendships/${friendship.id}/accept`}
                                            method="patch"
                                        >
                                            マイミク申請を承認
                                        </ActionButton>
                                        <ActionButton
                                            url={`/friendships/${friendship.id}`}
                                            method="delete"
                                        >
                                            申請を辞退
                                        </ActionButton>
                                    </>
                                )}
                        </div>
                        <ActionButton
                            url={`/members/${member.id}/block`}
                            danger
                            confirm="このメンバーをブロックしますか？マイミク関係も解除されます。"
                        >
                            ブロックする
                        </ActionButton>
                    </div>
                )}
            </Section>
            <Section title="日記">
                <DiaryList diaries={diaries} />
            </Section>
        </SocialLayout>
    );
}