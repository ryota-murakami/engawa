import { Form, Head, setLayoutProps } from '@inertiajs/react'
import { REGEXP_ONLY_DIGITS } from 'input-otp'
import { useMemo, useState } from 'react'
import InputError from '@/components/input-error'
import { buttonClass, inputClass } from '@/components/social/ui'
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp'
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth'
import { store } from '@/routes/two-factor/login'

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false)
    const [code, setCode] = useState<string>('')

    const authConfigContent = useMemo<{
        title: string
        description: string
        toggleText: string
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'リカバリーコード',
                description: '緊急用のリカバリーコードを入力してログインしてください。',
                toggleText: '認証コードでログインする',
            }
        }

        return {
            title: '認証コード',
            description: '認証アプリに表示されたコードを入力してください。',
            toggleText: 'リカバリーコードでログインする',
        }
    }, [showRecoveryInput])

    setLayoutProps({
        title: authConfigContent.title,
        description: authConfigContent.description,
    })

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput)
        clearErrors()
        setCode('')
    }

    return (
        <>
            <Head title="二段階認証" />

            <div className="space-y-6">
                <Form
                    {...store.form()}
                    className="space-y-4"
                    resetOnError
                    resetOnSuccess={!showRecoveryInput}
                >
                    {({ errors, processing, clearErrors }) => (
                        <>
                            {showRecoveryInput ? (
                                <>
                                    <input
                                        name="recovery_code"
                                        type="text"
                                        placeholder="リカバリーコードを入力"
                                        autoFocus={showRecoveryInput}
                                        required
                                        className={inputClass}
                                    />
                                    <InputError message={errors.recovery_code} />
                                </>
                            ) : (
                                <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            name="code"
                                            maxLength={OTP_MAX_LENGTH}
                                            value={code}
                                            onChange={(value) => setCode(value)}
                                            disabled={processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                            autoFocus
                                        >
                                            <InputOTPGroup>
                                                {Array.from(
                                                    { length: OTP_MAX_LENGTH },
                                                    (_, index) => (
                                                        <InputOTPSlot key={index} index={index} />
                                                    ),
                                                )}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError message={errors.code} />
                                </div>
                            )}

                            <button
                                type="submit"
                                className={`${buttonClass} w-full`}
                                disabled={processing}
                            >
                                {processing ? '確認中…' : '次へ'}
                            </button>

                            <div className="text-center text-sm text-stone-600">
                                <span>または </span>
                                <button
                                    type="button"
                                    className="cursor-pointer font-medium text-orange-800 underline decoration-orange-300 underline-offset-4 hover:text-orange-950"
                                    onClick={() => toggleRecoveryMode(clearErrors)}
                                >
                                    {authConfigContent.toggleText}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    )
}
