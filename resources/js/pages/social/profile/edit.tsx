import { useForm } from '@inertiajs/react'
import ImageUpload from '@/components/social/image-upload'
import SocialLayout from '@/components/social/social-layout'
import { Avatar, buttonClass, Field, FormErrors, inputClass, Section } from '@/components/social/ui'

type Profile = {
    name: string
    bio: string | null
    avatar_url: string | null
    email_social_notifications: boolean
}

export default function EditProfile({ profile }: { profile: Profile }) {
    const form = useForm({
        name: profile.name,
        bio: profile.bio ?? '',
        email_social_notifications: profile.email_social_notifications,
    })
    return (
        <SocialLayout
            title="プロフィール設定"
            description="表示名と自己紹介は、プロフィールを見られる会員に表示されます。"
        >
            <Section title="基本情報">
                <form
                    className="space-y-5"
                    onSubmit={(event) => {
                        event.preventDefault()
                        form.patch('/profile', { preserveScroll: true })
                    }}
                >
                    <Field id="profile-name" label="表示名" error={form.errors.name}>
                        <input
                            id="profile-name"
                            autoComplete="nickname"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            maxLength={255}
                            required
                            className={inputClass}
                            aria-invalid={Boolean(form.errors.name)}
                        />
                    </Field>
                    <Field
                        id="profile-bio"
                        label="自己紹介"
                        error={form.errors.bio}
                        hint="2,000 文字まで。"
                    >
                        <textarea
                            id="profile-bio"
                            value={form.data.bio}
                            onChange={(event) => form.setData('bio', event.target.value)}
                            maxLength={2000}
                            rows={6}
                            className={inputClass}
                            aria-invalid={Boolean(form.errors.bio)}
                        />
                    </Field>
                    <label className="flex items-start gap-3 text-sm leading-6">
                        <input
                            type="checkbox"
                            className="mt-1 h-4 w-4 accent-orange-700"
                            checked={form.data.email_social_notifications}
                            onChange={(event) =>
                                form.setData('email_social_notifications', event.target.checked)
                            }
                        />
                        マイミク申請やコメントのお知らせをメールでも受け取る
                    </label>
                    <p className="text-xs leading-6 text-stone-600">
                        会員登録の認証とパスワード再設定のメールは、この設定にかかわらず送信されます。
                    </p>
                    <FormErrors errors={form.errors} />
                    <button className={buttonClass} disabled={form.processing} type="submit">
                        {form.processing ? '保存中…' : '変更を保存'}
                    </button>
                    {form.recentlySuccessful && (
                        <p role="status" className="text-sm text-green-800">
                            プロフィールを保存しました。
                        </p>
                    )}
                </form>
            </Section>
            <Section title="プロフィール画像">
                <Avatar user={profile} large />
                <p className="text-sm text-stone-600">
                    新しい画像をアップロードすると、現在の画像を置き換えます。
                </p>
                <ImageUpload url="/profile/avatar" field="avatar" label="新しいプロフィール画像" />
            </Section>
        </SocialLayout>
    )
}
