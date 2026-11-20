import { Form, Head, Link } from '@inertiajs/react';
import { secondaryClass, textLinkClass } from '@/components/social/ui';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="メールアドレスの確認" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    確認用のリンクを再送しました。メールをご確認ください。
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <button
                            type="submit"
                            className={secondaryClass}
                            disabled={processing}
                        >
                            {processing ? '送信中…' : '確認メールを再送する'}
                        </button>

                        <Link
                            href={logout()}
                            className={`${textLinkClass} mx-auto block text-sm`}
                        >
                            ログアウト
                        </Link>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'メールアドレスの確認',
    description:
        'お送りしたメール内のリンクをクリックして、メールアドレスの確認を完了してください。',
};
