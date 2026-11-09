import { Link, useForm } from '@inertiajs/react';
import SocialLayout from '@/components/social/social-layout';
import {
    buttonClass,
    Field,
    FormErrors,
    inputClass,
    Section,
    secondaryClass,
    visibilityLabels,
} from '@/components/social/ui';
import type { DiaryCard, Visibility } from '@/types/social';

function Editor({ diary }: { diary: DiaryCard | null }) {
    const form = useForm<{
        title: string;
        body: string;
        visibility: Visibility;
    }>({
        title: diary?.title ?? '',
        body: diary?.body ?? '',
        visibility: diary?.visibility ?? 'friends',
    });
    return (
        <form
            className="space-y-5"
            onSubmit={(event) => {
                event.preventDefault();
                if (diary) form.patch(`/diaries/${diary.id}`);
                else form.post('/diaries');
            }}
        >
            <Field id="diary-title" label="タイトル" error={form.errors.title}>
                <input
                    id="diary-title"
                    value={form.data.title}
                    onChange={(event) =>
                        form.setData('title', event.target.value)
                    }
                    required
                    maxLength={160}
                    className={inputClass}
                    aria-invalid={Boolean(form.errors.title)}
                />
            </Field>
            <Field
                id="diary-body"
                label="本文"
                error={form.errors.body}
                hint="10,000 文字まで。"
            >
                <textarea
                    id="diary-body"
                    value={form.data.body}
                    onChange={(event) =>
                        form.setData('body', event.target.value)
                    }
                    rows={14}
                    required
                    maxLength={10000}
                    className={inputClass}
                    aria-invalid={Boolean(form.errors.body)}
                />
            </Field>
            <Field
                id="diary-visibility"
                label="公開範囲"
                error={form.errors.visibility}
                hint="「会員全体」もログインしてメール認証を終えた会員だけが対象です。"
            >
                <select
                    id="diary-visibility"
                    className={inputClass}
                    value={form.data.visibility}
                    onChange={(event) =>
                        form.setData(
                            'visibility',
                            event.target.value as Visibility,
                        )
                    }
                >
                    {Object.entries(visibilityLabels).map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>
            </Field>
            <FormErrors errors={form.errors} />
            <div className="flex flex-wrap gap-3">
                <button
                    type="submit"
                    disabled={form.processing}
                    className={buttonClass}
                >
                    {form.processing ? '保存中…' : '日記を保存'}
                </button>
                <Link
                    href={diary ? `/diaries/${diary.id}` : '/home'}
                    className={secondaryClass}
                >
                    キャンセル
                </Link>
            </div>
        </form>
    );
}

export default function DiaryForm({ diary }: { diary: DiaryCard | null }) {
    return (
        <SocialLayout title={diary ? '日記を編集' : '日記を書く'}>
            <Section title="今日のできごと">
                <Editor key={diary?.id ?? 'new'} diary={diary} />
            </Section>
        </SocialLayout>
    );
}
