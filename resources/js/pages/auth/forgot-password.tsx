import { Form, Head, Link } from '@inertiajs/react'
import InputError from '@/components/input-error'
import { buttonClass, inputClass, textLinkClass } from '@/components/social/ui'
import { login } from '@/routes'
import { email } from '@/routes/password'

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="パスワードをお忘れの方" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>
            )}

            <div className="space-y-6">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <label htmlFor="email" className="text-sm font-semibold">
                                    メールアドレス
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="email@example.com"
                                    className={inputClass}
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <button
                                    type="submit"
                                    className={`${buttonClass} w-full`}
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing ? '送信中…' : '再設定メールを送信する'}
                                </button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="text-center text-sm text-stone-600">
                    <Link href={login()} className={textLinkClass}>
                        ログインへ戻る
                    </Link>
                </div>
            </div>
        </>
    )
}

ForgotPassword.layout = {
    title: 'パスワードをお忘れの方',
    description: '登録したメールアドレスを入力してください。再設定用のリンクをお送りします。',
}
