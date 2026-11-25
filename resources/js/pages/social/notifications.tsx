import { Link } from '@inertiajs/react'
import SocialLayout from '@/components/social/social-layout'
import {
    ActionButton,
    DateTime,
    Empty,
    Pagination,
    Section,
    textLinkClass,
} from '@/components/social/ui'
import type { NotificationItem, Paginator } from '@/types/social'

export default function Notifications({
    notifications,
}: {
    notifications: Paginator<NotificationItem>
}) {
    return (
        <SocialLayout
            title="お知らせ"
            description="現在も閲覧できる申請やコメントのお知らせを表示します。"
        >
            <Section title="届いたお知らせ">
                {!notifications.data.length && <Empty>表示できるお知らせはありません。</Empty>}
                {notifications.data.map((notification) => (
                    <article
                        key={notification.id}
                        className={`space-y-3 rounded-lg border p-4 ${notification.read_at ? 'border-stone-200' : 'border-orange-200 bg-orange-50'}`}
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span className="text-xs font-semibold text-stone-600">
                                {notification.read_at ? '既読' : '未読'}
                            </span>
                            <DateTime value={notification.created_at} />
                        </div>
                        <p className="text-sm leading-7">{notification.text}</p>
                        <div className="flex flex-wrap items-center gap-4">
                            <Link href={notification.url} className={textLinkClass}>
                                内容を見る
                            </Link>
                            {!notification.read_at && (
                                <ActionButton
                                    url={`/notifications/${notification.id}/read`}
                                    method="patch"
                                >
                                    既読にする
                                </ActionButton>
                            )}
                        </div>
                    </article>
                ))}
                <Pagination page={notifications} />
            </Section>
        </SocialLayout>
    )
}
