import SocialLayout from '@/components/social/social-layout'
import { ActionButton, Empty, MemberLink, Section } from '@/components/social/ui'
import type { FriendRequest, UserCard } from '@/types/social'

export default function Friends({
    friends,
    incoming,
    outgoing,
    blocked,
}: {
    friends: UserCard[]
    incoming: FriendRequest[]
    outgoing: FriendRequest[]
    blocked: UserCard[]
}) {
    return (
        <SocialLayout
            title="マイミク"
            description="申請を承認すると、お互いの「マイミクまで」の日記を読めるようになります。"
        >
            <Section title={`届いた申請 (${incoming.length})`}>
                {!incoming.length && <Empty>届いている申請はありません。</Empty>}
                {incoming.map((request) => (
                    <div
                        key={request.id}
                        className="flex flex-wrap items-center justify-between gap-3"
                    >
                        <MemberLink user={request.member} />
                        <div className="flex flex-wrap gap-2">
                            <ActionButton url={`/friendships/${request.id}/accept`} method="patch">
                                承認する
                            </ActionButton>
                            <ActionButton url={`/friendships/${request.id}`} method="delete">
                                辞退する
                            </ActionButton>
                        </div>
                    </div>
                ))}
            </Section>
            <Section title={`マイミク一覧 (${friends.length})`}>
                {!friends.length && (
                    <Empty>まだマイミクがいません。メンバー一覧から申請してみましょう。</Empty>
                )}
                <ul className="grid gap-5 sm:grid-cols-2">
                    {friends.map((friend) => (
                        <li key={friend.id}>
                            <MemberLink user={friend} />
                        </li>
                    ))}
                </ul>
                {friends.length > 0 && (
                    <p className="text-xs text-stone-500">
                        マイミクの解除は、その人のプロフィールページから行えます。
                    </p>
                )}
            </Section>
            <Section title={`送った申請 (${outgoing.length})`}>
                {!outgoing.length && <Empty>承認待ちの申請はありません。</Empty>}
                {outgoing.map((request) => (
                    <div
                        key={request.id}
                        className="flex flex-wrap items-center justify-between gap-3"
                    >
                        <MemberLink user={request.member} />
                        <ActionButton url={`/friendships/${request.id}`} method="delete">
                            申請を取り消す
                        </ActionButton>
                    </div>
                ))}
            </Section>
            <Section title={`ブロック中 (${blocked.length})`}>
                {!blocked.length && <Empty>ブロックしているメンバーはいません。</Empty>}
                {blocked.map((member) => (
                    <div
                        key={member.id}
                        className="flex flex-wrap items-center justify-between gap-3"
                    >
                        <span className="font-medium">{member.name}</span>
                        <ActionButton url={`/members/${member.id}/block`} method="delete">
                            ブロックを解除
                        </ActionButton>
                    </div>
                ))}
                {blocked.length > 0 && (
                    <p className="text-xs text-stone-500">
                        解除しても、以前のマイミク関係は自動では戻りません。
                    </p>
                )}
            </Section>
        </SocialLayout>
    )
}
