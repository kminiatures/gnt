# CLAUDE.md

このファイルは、このリポジトリでコードを扱う際のClaude Code (claude.ai/code) へのガイダンスを提供します。

## プロジェクト概要

PHP 8.2+を使用したLaravel 12アプリケーションで、以下の主要技術を使用しています：
- Laravel Framework (^12.0) とArtisan CLI
- フロントエンド: Vite + TailwindCSS 4.0
- データベース: SQLite（デフォルト）、Docker経由でMariaDBサポート
- テスト: FeatureテストとUnitテストスイートを持つPHPUnit
- 開発ツール: Laravel Pint（コードフォーマット）、Laravel Pail（ログ監視）
- Laravel Sail経由でのDockerサポート

## 開発コマンド

### 基本的なLaravelコマンド
- `php artisan serve` - 開発サーバーを起動
- `php artisan migrate` - データベースマイグレーションを実行
- `php artisan migrate:fresh --seed` - シーダー付きで新しいデータベースを作成
- `php artisan queue:work` - キューされたジョブを処理
- `php artisan tinker` - インタラクティブなPHPシェル

### テスト
- `composer test` または `php artisan test` - すべてのテストを実行
- `php artisan test --filter=ExampleTest` - 特定のテストを実行
- `vendor/bin/phpunit tests/Unit` - ユニットテストのみ実行
- `vendor/bin/phpunit tests/Feature` - フィーチャーテストのみ実行

### フロントエンド開発
- `npm run dev` - Vite開発サーバーを起動
- `npm run build` - プロダクション用アセットをビルド

### 統合開発
- `composer dev` - すべてのサービスを同時に開始（サーバー、キュー、ログ、vite）

### コード品質
- `vendor/bin/pint` - Laravel Pintでコードをフォーマット
- `php artisan config:clear` - 設定キャッシュをクリア

### Docker (Laravel Sail)
- `./vendor/bin/sail up` - Docker環境を開始
- `./vendor/bin/sail down` - Docker環境を停止
- `./vendor/bin/sail artisan [command]` - コンテナ内でartisanコマンドを実行

## アーキテクチャ

### ディレクトリ構造
- `app/` - アプリケーションロジック（Models、Controllers、Providers）
- `config/` - 設定ファイル
- `database/` - マイグレーション、ファクトリー、シーダー、SQLiteデータベース
- `resources/` - ビュー（Bladeテンプレート）、CSS、JSアセット
- `routes/` - ルート定義（web.php、console.php）
- `tests/` - PHPUnitテスト（Feature/、Unit/）
- `public/` - Webサーバーのドキュメントルート

### データベース
デフォルトでSQLite（`database/database.sqlite`）を使用。プロダクション環境に近い開発のためにDocker Compose経由でMariaDBが利用可能。

### フロントエンドアセット
ViteがTailwindCSSとLaravel統合で`resources/css/app.css`と`resources/js/app.js`を処理。

### テスト環境
PHPUnitは、テスト時に分離されたテストデータベースとキャッシュ/セッション/メール用の配列ベースドライバーで設定されています。