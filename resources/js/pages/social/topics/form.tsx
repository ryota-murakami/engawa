import { Link, useForm } from '@inertiajs/react';
import SocialLayout from '@/components/social/social-layout';
import {
    buttonClass,
    Field,
    FormErrors,
    inputClass,
    Section,
    secondaryClass,
} from '@/components/social/ui';

export default function TopicForm({
    community,
}: {
    community: { id: number; name: string };
}) {
    const form = useForm({ title: '', body: '' });
    return (
        <SocialLayout title="トピックを作る" description={community.name}>
            <Section title="新しい話題">
                <form
                    className="space-y-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(`/communities/${community.id}/topics`);
                    }}
                >
                    <Field
                        id="topic-title"
                        label="タイトル"
                        error={form.errors.title}
                    >
                        <input
                            id="topic-title"
                            className={inputClass}
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            required
                            maxLength={160}
                            aria-invalid={Boolean(form.errors.title)}
                        />
                    </Field>
                    <Field
                        id="topic-body"
                        label="本文"
                        error={form.errors.body}
                        hint="10,000 文字まで。"
                    >
                        <textarea
                            id="topic-body"
                            className={inputClass}
                            value={form.data.body}
                            onChange={(event) =>
                                form.setData('body', event.target.value)
                            }
                            required
                            maxLength={10000}
                            rows={10}
                            aria-invalid={Boolean(form.errors.body)}
                        />
                    </Field>
                    <FormErrors errors={form.errors} />
                    <div className="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className={buttonClass}
                        >
                            {form.processing ? '投稿中…' : 'トピックを作る'}
                        </button>
                        <Link
                            href={`/communities/${community.id}`}
                            className={secondaryClass}
                        >
                            キャンセル
                        </Link>
                    </div>
                </form>
            </Section>
        </SocialLayout>
    );
}
