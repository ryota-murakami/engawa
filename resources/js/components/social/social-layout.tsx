import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SocialSharedProps } from '@/types/social';
import { ActionButton, buttonClass } from './ui';
import { socialNavigation } from './social-navigation';

export default function SocialLayout({
    title,
    description,
    children,
    action,
}: {
    title: string;
    description?: string;
    children: ReactNode;
    action?: ReactNode;
}) {
    const page = usePage();
    const shared = page.props as unknown as SocialSharedProps;
    const user = shared.auth?.user;
    const currentPath = page.url.split('?')[0];
    const links = socialNavigation;
    return (
        <div className="min-h-screen bg-[#f7f4ed] text-slate-900">
            <Head title={`${title} | えんがわ`} />
            <a
                href="#social-main"
                className="sr-only rounded-lg bg-white p-3 focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50"
            >
                本文へ移動
            </a>
            <header className="border-b border-orange-200 bg-white">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-5 sm:px-6">
                    <Link
                        href="/home"
                        className="text-2xl font-black tracking-widest text-orange-800"
                    >
                        えんがわ
                    </Link>
                    <p className="hidden text-sm text-stone-500 sm:block">
                        日記から、つながる毎日。
                    </p>
                    <span className="text-sm text-stone-600">
                        {user?.name ?? 'メンバー'} さん
                    </span>
                </div>
            </header>
            <div className="mx-auto grid max-w-6xl gap-6 px-4 py-6 sm:px-6 md:grid-cols-[220px_minmax(0,1fr)]">
                <aside className="space-y-4">
                    <Link
                        href="/diaries/create"
                        className={`${buttonClass} w-full`}
                    >
                        日記を書く
                    </Link>
                    <nav
                        aria-label="メインメニュー"
                        className="grid grid-cols-2 gap-1 rounded-xl border border-stone-200 bg-white p-2 shadow-sm md:grid-cols-1"
                    >
                        {links.map(([href, label]) => (
                            <Link
                                key={href}
                                href={href}
                                aria-current={
                                    currentPath === href ? 'page' : undefined
                                }
                                className={`rounded-lg px-3 py-2.5 text-sm font-medium focus-visible:outline-2 focus-visible:outline-orange-700 ${currentPath === href ? 'bg-orange-100 text-orange-900' : 'text-stone-700 hover:bg-stone-100'}`}
                            >
                                {label}
                            </Link>
                        ))}
                    </nav>
                    <div className="rounded-xl border border-stone-200 bg-white p-4 text-xs leading-6 text-stone-600">
                        <p>
                            気になる日記にひとこと。
                            <br />
                            ゆっくり、自分のペースで。
                        </p>
                    </div>
                    <ActionButton url="/logout">ログアウト</ActionButton>
                </aside>
                <main
                    id="social-main"
                    tabIndex={-1}
                    className="min-w-0 space-y-5"
                >
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                {title}
                            </h1>
                            {description && (
                                <p className="mt-2 text-sm leading-7 text-stone-600">
                                    {description}
                                </p>
                            )}
                        </div>
                        {action}
                    </div>
                    {(shared.flash?.success || shared.flash?.status) && (
                        <p
                            role="status"
                            className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800"
                        >
                            {shared.flash.success ?? shared.flash.status}
                        </p>
                    )}
                    {children}
                </main>
            </div>
            <footer className="mx-auto max-w-6xl px-6 py-8 text-xs text-stone-500">
                えんがわ · メンバー同士で楽しむ小さな SNS
            </footer>
        </div>
    );
}
