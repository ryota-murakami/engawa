import { Head } from '@inertiajs/react';

interface Props {
    message: string;
    memberCount: number;
}

export default function Hello({ message, memberCount }: Props) {
    return (
        <main className="space-x-4">
            <Head title="Laravelの最初の実装" />
            <h1 className="text-2xl font-bold">{message}</h1>
            <p>登録済みの会員は {memberCount} 人です。</p>
        </main>
    );
}
