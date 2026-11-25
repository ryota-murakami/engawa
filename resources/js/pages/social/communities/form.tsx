import { Link, useForm } from '@inertiajs/react'
import SocialLayout from '@/components/social/social-layout'
import {
    buttonClass,
    Field,
    FormErrors,
    inputClass,
    Section,
    secondaryClass,
} from '@/components/social/ui'

export default function CommunityForm() {
    const form = useForm({ name: '', description: '' })
    return (
        <SocialLayout
            title="コミュニティを作る"
            description="コミュニティは会員全体が閲覧でき、参加した会員がトピックや返信を投稿できます。"
        >
            <Section title="コミュニティの情報">
                <form
                    className="space-y-5"
                    onSubmit={(event) => {
                        event.preventDefault()
                        form.post('/communities')
                    }}
                >
                    <Field id="community-name" label="コミュニティ名" error={form.errors.name}>
                        <input
                            id="community-name"
                            className={inputClass}
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            required
                            maxLength={80}
                            aria-invalid={Boolean(form.errors.name)}
                        />
                    </Field>
                    <Field
                        id="community-description"
                        label="紹介文"
                        error={form.errors.description}
                        hint="5,000 文字まで。話題や参加時のルールを書いておきましょう。"
                    >
                        <textarea
                            id="community-description"
                            className={inputClass}
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                            required
                            rows={8}
                            maxLength={5000}
                            aria-invalid={Boolean(form.errors.description)}
                        />
                    </Field>
                    <FormErrors errors={form.errors} />
                    <div className="flex flex-wrap gap-3">
                        <button type="submit" disabled={form.processing} className={buttonClass}>
                            {form.processing ? '作成中…' : 'コミュニティを作る'}
                        </button>
                        <Link href="/communities" className={secondaryClass}>
                            キャンセル
                        </Link>
                    </div>
                </form>
            </Section>
        </SocialLayout>
    )
}
