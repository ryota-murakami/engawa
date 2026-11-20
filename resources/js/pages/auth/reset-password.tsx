import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { buttonClass, inputClass } from '@/components/social/ui';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    return (
        <>
            <Head title="パスワードの再設定" />

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-5">
                        <div className="grid gap-2">
                            <label
                                htmlFor="email"
                                className="text-sm font-semibold"
                            >
                                メールアドレス
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={email}
                                className={`${inputClass} mt-1`}
                                readOnly
                            />
                            <InputError
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-2">
                            <label
                                htmlFor="password"
                                className="text-sm font-semibold"
                            >
                                新しいパスワード
                            </label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                className={`${inputClass} mt-1`}
                                autoFocus
                                placeholder="新しいパスワード"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <label
                                htmlFor="password_confirmation"
                                className="text-sm font-semibold"
                            >
                                新しいパスワード（確認）
                            </label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className={`${inputClass} mt-1`}
                                placeholder="新しいパスワード（確認）"
                                passwordrules={passwordRules}
                            />
                            <InputError
                                message={errors.password_confirmation}
                                className="mt-2"
                            />
                        </div>

                        <button
                            type="submit"
                            className={`${buttonClass} mt-2 w-full`}
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing ? '再設定中…' : '再設定する'}
                        </button>
                    </div>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'パスワードの再設定',
    description: '新しいパスワードを入力してください。',
};
