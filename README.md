# えんがわ (engawa)

Laravel 13 + Inertia.js (React) で作る会員制 SNS「えんがわ」の学習プロジェクト。

チュートリアル: [Laravelで作るクラシックSNS「えんがわ」](https://ryota-murakami.github.io/tech-archive/articles/2026-10-20-learn-laravel-classic-sns-engawa.html)

日記・マイミク（フレンド）・コメント・コミュニティ・足あと・通知・検索・退会を、チュートリアルの章に沿って実装していく。

## 技術スタック

- PHP 8.5 / Laravel 13 / Inertia.js 3
- React 19 + TypeScript + Tailwind CSS 4（Vite+）
- PostgreSQL 18（Docker）/ Mailpit（開発用メール捕捉）

## 前提

- PHP 8.5+, Composer 2, Node.js, pnpm
- Docker Desktop（`docker compose` が使えること）

## 初回セットアップ

```bash
# 1. PostgreSQL と Mailpit を起動（ポート: DB=55432, SMTP=1025, Web=8025）
docker compose up -d

# 2. 依存関係と .env
composer install
cp .env.example .env   # 既に .env がある場合はスキップ
php artisan key:generate

# 3. .env を PostgreSQL / Mailpit 向けに設定（チュートリアル第2章 2.6 参照）
#    DB_CONNECTION=pgsql / DB_PORT=55432 / MAIL_MAILER=smtp / MAIL_PORT=1025 など

# 4. DB マイグレーションとフロントのビルド
php artisan migrate
pnpm install
pnpm run build
```

`composer setup` で install → .env 生成 → key:generate → migrate → pnpm install → build を一括実行できるが、**先に `docker compose up -d` と .env の DB 設定が必要**。

## 起動

```bash
docker compose up -d   # DB / Mailpit（起動したままにする）
composer dev           # = php artisan dev：serve + queue + pail + vite を一括起動
```

個別に起動したい場合は Terminal を分けて:

```bash
php artisan serve --host=127.0.0.1 --port=8000   # Laravel HTTP サーバー
pnpm run dev                                     # Vite 開発サーバー
php artisan queue:work --tries=3 --timeout=60    # キューワーカー
```

| URL | 用途 |
| --- | --- |
| http://localhost:8000 | アプリ本体 |
| http://localhost:8025 | Mailpit（送信メールの確認） |
| http://localhost:5173 | Vite 開発サーバー（直接は開かない） |

## 停止

- `composer dev` / 個別プロセスは `Ctrl+C`
- Docker: `docker compose stop`
- ⚠️ `docker compose down -v` は **DB ボリュームごと削除** される。通常の終了時には使わない

## 主要コマンド

### 開発

```bash
composer dev            # 全プロセス一括起動
pnpm run dev            # Vite のみ
php artisan serve       # HTTP サーバーのみ
php artisan queue:work  # キューワーカーのみ
php artisan tinker      # REPL
php artisan route:list  # ルート一覧（--path=diaries などで絞り込み可）
```

### DB / Seeder

```bash
php artisan migrate                  # 未適用の migration を実行
php artisan migrate:status           # 適用状況の確認
php artisan migrate:fresh            # ⚠️ 全テーブル作り直し（データ消去）
php artisan db:seed                  # DatabaseSeeder（test@example.com を作成）
php artisan db:seed --class=SocialDemoSeeder   # SNS デモ会員を作成
```

### 検証・品質

```bash
php artisan test              # PHPUnit（テストは engawa_test DB を使用）
php artisan test tests/Feature/Chapter06VisibilityTest.php  # ファイル指定
composer lint                 # Pint で整形
composer lint:check           # Pint チェックのみ
composer types:check          # PHPStan
pnpm run check                # フォーマット + Lint（Vite+）
pnpm run check:fix            # 自動修正
pnpm run types:check          # tsc --noEmit
pnpm run build                # 本番ビルド
```

## テストアカウント

`SocialDemoSeeder` で作成されるデモ会員（local / testing 環境のみ実行可）:

| メール | パスワード | 役割 |
| --- | --- | --- |
| alice@example.test | `LocalDemo-ChangeMe-2026!` | アリス（コミュニティ owner、ボブとフレンド承認済み） |
| bob@example.test | `LocalDemo-ChangeMe-2026!` | ボブ（アリスとフレンド承認済み） |
| carol@example.test | `LocalDemo-ChangeMe-2026!` | キャロル（未接続） |

```bash
php artisan db:seed --class=SocialDemoSeeder
```

また `DatabaseSeeder`（`php artisan db:seed`）は `test@example.com` / `password` のユーザーを1件作成する。

## DB について

- 開発用: `engawa`（Postgres, 127.0.0.1:55432, user/pass: `engawa` / `engawa-local-only`）
- テスト用: `engawa_test`（`phpunit.xml` で自動的にこちらへ向く。初回は `docker/postgres/init-test-db.sql` が作成。既存ボリュームの場合は `docker compose exec postgres createdb -U engawa engawa_test`）
