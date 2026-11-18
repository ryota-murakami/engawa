import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { buttonClass, Field, FormErrors, inputClass } from './ui';

export default function ImageUpload({
                                        url,
                                        field,
                                        label,
                                    }: {
    url: string;
    field: 'image' | 'avatar';
    label: string;
}) {
    const form = useForm<{ image: File | null; avatar: File | null }>({
        image: null,
        avatar: null,
    });
    const fileInput = useRef<HTMLInputElement>(null);
    const selected = form.data[field];
    return (
        <form
            className="space-y-4"
            onSubmit={(event) => {
                event.preventDefault();
                form.post(url, {
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset();
                        if (fileInput.current) fileInput.current.value = '';
                    },
                });
            }}
        >
            <Field
                id={`upload-${field}`}
                label={label}
                error={form.errors[field]}
                hint="JPEG / PNG / WebP、2 MB まで。縦横 4,096 px 以下・800 万画素以下の画像を選んでください。大きい写真は先に縮小します。"
            >
                <input
                    ref={fileInput}
                    id={`upload-${field}`}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    disabled={form.processing}
                    onChange={(event) =>
                        form.setData(field, event.target.files?.[0] ?? null)
                    }
                    className={inputClass}
                    aria-invalid={Boolean(form.errors[field])}
                    aria-describedby={`upload-${field}-hint upload-${field}-error`}
                />
            </Field>
            {form.progress && (
                <progress
                    className="w-full"
                    value={form.progress.percentage ?? 0}
                    max={100}
                    aria-label="画像のアップロード進捗"
                />
            )}
            <FormErrors errors={form.errors} />
            <button
                type="submit"
                disabled={form.processing || !selected}
                className={buttonClass}
            >
                {form.processing ? 'アップロード中…' : '画像をアップロード'}
            </button>
            {form.recentlySuccessful && (
                <p role="status" className="text-sm text-green-800">
                    画像を保存しました。
                </p>
            )}
        </form>
    );
}
