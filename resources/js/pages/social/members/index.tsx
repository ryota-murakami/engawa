import SocialLayout from '@/components/social/social-layout'
import {
    Empty,
    MemberLink,
    Pagination,
    SearchForm,
    Section
} from '@/components/social/ui'
import type { Paginator, UserCard } from '@/types/social'

export default function Members({
                                    members,
                                    q
                                }: {
    members: Paginator<UserCard>;
    q: string;
}) {
    return (
        <SocialLayout
            title="メンバー"
            description="プロフィールを見て、気になる人にマイミク申請を送れます。"
        >
            <Section title="名前から探す">
                <SearchForm
                    key={q}
                    initial={q}
                    action="/members"
                    label="表示名"
                />
            </Section>
            <Section title="メンバー一覧">
                {!members.data.length && (
                    <Empty>条件に合うメンバーはいません。</Empty>
                )}
                <ul className="grid gap-5 sm:grid-cols-2">
                    {members.data.map((member) => (
                        <li key={member.id}>
                            <MemberLink user={member}/>
                        </li>
                    ))}
                </ul>
                <Pagination page={members}/>
            </Section>
        </SocialLayout>
    )
}
