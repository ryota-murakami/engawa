import DiaryList from '@/components/social/diary-list'
import SocialLayout from '@/components/social/social-layout'
import { SearchForm, Section } from '@/components/social/ui'
import type { DiaryCard, Paginator } from '@/types/social'

export default function Search({ q, diaries }: { q: string; diaries: Paginator<DiaryCard> }) {
    return (
        <SocialLayout
            title="日記を検索"
            description="自分が読める日記のタイトルと本文から検索します。"
        >
            <Section title="キーワード検索">
                <SearchForm key={q} initial={q} action="/search" label="日記のキーワード" />
            </Section>
            <Section title={q ? `「${q}」の検索結果` : '読める日記'}>
                <DiaryList diaries={diaries} />
            </Section>
        </SocialLayout>
    )
}
