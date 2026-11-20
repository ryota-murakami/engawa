import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

// The auth screens share the site's えんがわ look (cream bg, orange accent,
// white card). AuthLayoutProps.title/description render inside the card band.
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col bg-[#f7f4ed] text-slate-900">
            <header className="border-b border-orange-200 bg-white">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-5 sm:px-6">
                    <Link
                        href={home()}
                        className="text-2xl font-black tracking-widest text-orange-800"
                    >
                        えんがわ
                    </Link>
                    <p className="hidden text-sm text-stone-500 sm:block">
                        日記から、つながる毎日。
                    </p>
                </div>
            </header>
            <main className="flex flex-1 items-center justify-center p-6">
                {/* Re-pin the --color-* tokens Tailwind v4 utilities consume so
                    shadcn parts inside the card stay on-brand even in dark mode. */}
                <div
                    className="w-full max-w-md overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm"
                    style={
                        {
                            '--color-background': 'oklch(1 0 0)',
                            '--color-foreground': 'oklch(0.145 0 0)',
                            '--color-card': 'oklch(1 0 0)',
                            '--color-card-foreground': 'oklch(0.145 0 0)',
                            '--color-popover': 'oklch(1 0 0)',
                            '--color-popover-foreground': 'oklch(0.145 0 0)',
                            '--color-primary': '#c2410c',
                            '--color-primary-foreground': 'oklch(0.985 0 0)',
                            '--color-secondary': '#f5f5f4',
                            '--color-secondary-foreground': '#292524',
                            '--color-muted': '#f5f5f4',
                            '--color-muted-foreground': '#78716c',
                            '--color-accent': '#f5f5f4',
                            '--color-accent-foreground': '#1c1917',
                            '--color-destructive': 'oklch(0.577 0.245 27.325)',
                            '--color-destructive-foreground':
                                'oklch(0.577 0.245 27.325)',
                            '--color-border': '#e7e5e4',
                            '--color-input': '#d6d3d1',
                            '--color-ring': '#c2410c',
                        } as React.CSSProperties
                    }
                >
                    <div className="border-b border-stone-200 bg-orange-50 px-6 py-4">
                        <h1 className="text-lg font-bold text-stone-900">
                            {title}
                        </h1>
                        {description && (
                            <p className="mt-1 text-sm leading-6 text-stone-600">
                                {description}
                            </p>
                        )}
                    </div>
                    <div className="p-6">{children}</div>
                </div>
            </main>
            <footer className="px-6 py-6 text-center text-xs text-stone-500">
                えんがわ · メンバー同士で楽しむ小さな SNS
            </footer>
        </div>
    );
}
