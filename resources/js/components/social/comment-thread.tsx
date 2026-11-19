import { useForm } from '@inertiajs/react';
import type { CommentCard, Paginator } from '@/types/social';
import {
    ActionButton,
    buttonClass,
    DateTime,
    Empty,
    Field,
    FormErrors,
    inputClass,
    MemberLink,
    Pagination,
} from './ui';

export default function CommentThread({
    comments,
    postUrl,
    deletePrefix,
    canPost,
    noun = 'コメント',
}: {
    comments: Paginator<CommentCard>;
    postUrl: string;
    deletePrefix: string;
    canPost: boolean;
    noun?: string;
}) {
    const form = useForm({ body: '' });
    return (
        <div className="space-y-6">
            {!comments.data.length && <Empty>まだ{noun}はありません。</Empty>}
            {comments.data.map((comment) => (
                <article
                    key={comment.id}
                    className="space-y-3 border-b border-stone-100 pb-4"
                >
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <MemberLink user={comment.author} />
                        <DateTime value={comment.created_at} />
                    </div>
                    <p className="text-sm leading-7 wrap-break-word whitespace-pre-wrap">
                        {comment.body}
                    </p>
                    {comment.can_delete && (
                        <ActionButton
                            url={`${deletePrefix}/${comment.id}`}
                            method="delete"
                            danger
                            confirm={`この${noun}を削除しますか？`}
                        >
                            削除
                        </ActionButton>
                    )}
                </article>
            ))}
            <Pagination page={comments} />
            {canPost ? (
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(postUrl, {
                            preserveScroll: true,
                            onSuccess: () => form.reset('body'),
                        });
                    }}
                >
                    <Field
                        id="comment-body"
                        label={`${noun}を書く`}
                        error={form.errors.body}
                        hint="2,000 文字まで。HTML は使えません。"
                    >
                        <textarea
                            id="comment-body"
                            value={form.data.body}
                            onChange={(event) =>
                                form.setData('body', event.target.value)
                            }
                            className={inputClass}
                            rows={4}
                            required
                            maxLength={2000}
                            aria-invalid={Boolean(form.errors.body)}
                            aria-describedby="comment-body-hint comment-body-error"
                        />
                    </Field>
                    <FormErrors errors={form.errors} />
                    <button
                        type="submit"
                        disabled={form.processing}
                        className={buttonClass}
                    >
                        {form.processing ? '送信中…' : `${noun}を送信`}
                    </button>
                    {form.recentlySuccessful && (
                        <p role="status" className="text-sm text-green-800">
                            送信しました。
                        </p>
                    )}
                </form>
            ) : (
                <p className="text-sm text-stone-600">
                    現在、このページには投稿できません。
                </p>
            )}
        </div>
    );
}
