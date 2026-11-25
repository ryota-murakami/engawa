import { Form, Head, Link } from '@inertiajs/react'
import InputError from '@/components/input-error'
import PasswordInput from '@/components/password-input'
import { buttonClass, inputClass, textLinkClass } from '@/components/social/ui'
import { login } from '@/routes'
import { store } from '@/routes/register'

type Props = {
    passwordRules: string
}

export default function Register({ passwordRules }: Props) {
    return (
        <>
            <Head title="新規登録" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <label htmlFor="name" className="text-sm font-semibold">
                                    ニックネーム
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="えんがわ 太郎"
                                    className={inputClass}
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div className="grid gap-2">
                                <label htmlFor="email" className="text-sm font-semibold">
                                    メールアドレス
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                    className={inputClass}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <label htmlFor="password" className="text-sm font-semibold">
                                    パスワード
                                </label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="パスワード"
                                    passwordrules={passwordRules}
                                    className={inputClass}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <label
                                    htmlFor="password_confirmation"
                                    className="text-sm font-semibold"
                                >
                                    パスワード（確認）
                                </label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="パスワード（確認）"
                                    passwordrules={passwordRules}
                                    className={inputClass}
                                />
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <button
                                type="submit"
                                className={`${buttonClass} mt-2 w-full`}
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing ? '登録中…' : '登録する'}
                            </button>
                        </div>

                        <div className="text-center text-sm text-stone-600">
                            すでにアカウントをお持ちの方は{' '}
                            <Link href={login()} className={textLinkClass} tabIndex={6}>
                                ログイン
                            </Link>
                        </div>
                    </>
                )}
            </Form>
        </>
    )
}

Register.layout = {
    title: '新規登録',
    description: '必要事項を入力してアカウントを作成してください。',
}
