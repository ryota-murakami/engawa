import { Form, Head, Link } from '@inertiajs/react'
import InputError from '@/components/input-error'
import PasswordInput from '@/components/password-input'
import PasskeyVerify from '@/components/passkey-verify'
import { buttonClass, inputClass, textLinkClass } from '@/components/social/ui'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

type Props = {
    status?: string
    canResetPassword: boolean
}

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="ログイン" />

            <PasskeyVerify
                label="パスキーでログイン"
                loadingLabel="認証中…"
                separator="またはメールアドレスでログイン"
            />

            <Form {...store.form()} resetOnSuccess={['password']} className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <label htmlFor="email" className="text-sm font-semibold">
                                    メールアドレス
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                    className={inputClass}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <label htmlFor="password" className="text-sm font-semibold">
                                        パスワード
                                    </label>
                                    {canResetPassword && (
                                        <Link
                                            href={request()}
                                            className={`${textLinkClass} ml-auto text-sm`}
                                            tabIndex={5}
                                        >
                                            パスワードを忘れた方はこちら
                                        </Link>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="パスワード"
                                    className={inputClass}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-2">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    name="remember"
                                    tabIndex={3}
                                    className="h-4 w-4 rounded border-stone-300 accent-orange-700"
                                />
                                <label htmlFor="remember" className="text-sm">
                                    ログイン状態を保持する
                                </label>
                            </div>

                            <button
                                type="submit"
                                className={`${buttonClass} mt-2 w-full`}
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing ? 'ログイン中…' : 'ログイン'}
                            </button>
                        </div>

                        <div className="text-center text-sm text-stone-600">
                            アカウントをお持ちでない方は{' '}
                            <Link href={register()} className={textLinkClass} tabIndex={5}>
                                新規登録
                            </Link>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-green-600">{status}</div>
            )}
        </>
    )
}

Login.layout = {
    title: 'ログイン',
    description: 'メールアドレスとパスワードを入力してログインしてください。',
}
