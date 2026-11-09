import { Link, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { Paginator, UserCard, Visibility } from '@/types/social';

export const inputClass =
    'w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-slate-900 shadow-sm outline-none focus:border-orange-600 focus:ring-2 focus:ring-orange-100 disabled:opacity-60';
export const buttonClass =
    'inline-flex min-h-10 items-center justify-center rounded-lg bg-orange-700 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-700 disabled:cursor-wait disabled:opacity-60';
export const secondaryClass =
    'inline-flex min-h-10 items-center justify-center rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-800 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-orange-700 disabled:opacity-60';
export const textLinkClass =
    'font-medium text-orange-800 underline decoration-orange-300 underline-offset-4 hover:text-orange-950';

export const visibilityLabels: Record<Visibility, string> = {
    members: '会員全体',
    friends: 'マイミクまで',
    private: '自分だけ',
};

export function Section({
    title,
    children,
    action,
}: {
    title: string;
    children: ReactNode;
    action?: ReactNode;
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 bg-orange-50 px-5 py-3">
                <h2 className="font-bold text-stone-900">{title}</h2>
                {action}
            </div>
            <div className="space-y-5 p-5">{children}</div>
        </section>
    );
}

export function Empty({ children }: { children: ReactNode }) {
    return (
        <p className="rounded-lg bg-stone-50 px-4 py-6 text-sm leading-7 text-stone-600">
            {children}
        </p>
    );
}

export function Field({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-2">
            <label htmlFor={id} className="block text-sm font-semibold">
                {label}
            </label>
            {children}
            {hint && (
                <p
                    id={`${id}-hint`}
                    className="text-xs leading-6 text-stone-600"
                >
                    {hint}
                </p>
            )}
            {error && (
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="text-sm text-red-700"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

export function FormErrors({
    errors,
}: {
    errors: Record<string, string | undefined>;
}) {
    const messages = [
        ...new Set(
            Object.values(errors).filter((value): value is string =>
                Boolean(value),
            ),
        ),
    ];
    if (!messages.length) return null;
    return (
        <div
            role="alert"
            className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
        >
            {messages.map((message) => (
                <p key={message}>{message}</p>
            ))}
        </div>
    );
}

export function ActionButton({
    url,
    method = 'post',
    children,
    confirm,
    danger = false,
}: {
    url: string;
    method?: 'post' | 'patch' | 'delete';
    children: ReactNode;
    confirm?: string;
    danger?: boolean;
}) {
    const form = useForm({});
    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                if (confirm && !window.confirm(confirm)) return;
                const options = { preserveScroll: true };
                if (method === 'delete') form.delete(url, options);
                else if (method === 'patch') form.patch(url, options);
                else form.post(url, options);
            }}
            className="space-y-2"
        >
            <button
                type="submit"
                className={`${secondaryClass} ${danger ? 'border-red-200 text-red-700' : ''}`}
                disabled={form.processing}
            >
                {form.processing ? '処理中…' : children}
            </button>
            <FormErrors errors={form.errors} />
        </form>
    );
}

export function Avatar({
    user,
    large = false,
}: {
    user: Pick<UserCard, 'name' | 'avatar_url'>;
    large?: boolean;
}) {
    const size = large ? 'h-24 w-24 text-3xl' : 'h-10 w-10 text-base';
    return user.avatar_url ? (
        <img
            src={user.avatar_url}
            alt=""
            loading="lazy"
            className={`${size} shrink-0 rounded-xl border border-stone-200 object-cover`}
        />
    ) : (
        <span
            aria-hidden="true"
            className={`${size} flex shrink-0 items-center justify-center rounded-xl border border-orange-200 bg-orange-100 font-bold text-orange-800`}
        >
            {Array.from(user.name)[0] ?? '？'}
        </span>
    );
}

/** Render an author before member profile routes are introduced in chapter 8. */
export function MemberLink({ user }: { user: UserCard }) {
    return (
        <span className="inline-flex items-center gap-3">
            <Avatar user={user} />
            <span>{user.name}</span>
        </span>
    );
}

export function DateTime({ value }: { value: string }) {
    const date = new Date(value);
    const text = Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat('ja-JP', {
              dateStyle: 'medium',
              timeStyle: 'short',
              timeZone: 'Asia/Tokyo',
          }).format(date);
    return (
        <time dateTime={value} className="text-xs text-stone-500">
            {text}
        </time>
    );
}

export function Pagination({
    page,
}: {
    page: Pick<
        Paginator<unknown>,
        | 'prev_page_url'
        | 'next_page_url'
        | 'current_page'
        | 'last_page'
        | 'total'
    >;
}) {
    return (
        <nav
            aria-label="ページ移動"
            className="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 pt-4"
        >
            {page.prev_page_url ? (
                <Link href={page.prev_page_url} className={secondaryClass}>
                    前のページ
                </Link>
            ) : (
                <span />
            )}
            <span className="text-xs text-stone-600">
                {page.current_page} / {page.last_page} ページ · {page.total} 件
            </span>
            {page.next_page_url ? (
                <Link href={page.next_page_url} className={secondaryClass}>
                    次のページ
                </Link>
            ) : (
                <span />
            )}
        </nav>
    );
}

export function SearchForm({
    initial = '',
    action,
    label,
}: {
    initial?: string;
    action: string;
    label: string;
}) {
    const form = useForm({ q: initial });
    return (
        <form
            className="space-y-2"
            onSubmit={(event) => {
                event.preventDefault();
                form.get(action, { preserveState: true, replace: true });
            }}
        >
            <label htmlFor="social-search" className="text-sm font-semibold">
                {label}
            </label>
            <div className="flex gap-2">
                <input
                    id="social-search"
                    type="search"
                    value={form.data.q}
                    onChange={(event) => form.setData('q', event.target.value)}
                    className={inputClass}
                    maxLength={100}
                />
                <button
                    type="submit"
                    disabled={form.processing}
                    className={buttonClass}
                >
                    {form.processing ? '検索中…' : '検索'}
                </button>
            </div>
            <FormErrors errors={form.errors} />
        </form>
    );
}
