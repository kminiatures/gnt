# CLAUDE.md

このファイルは、このリポジトリでコードを扱う際のClaude Code (claude.ai/code) へのガイダンスを提供します。

* いつも日本語で回答してください。
* ガントチャートライブラリのドキュメントは  https://ej2.syncfusion.com/vue/documentation/gantt で、参照してOKです。


## プロジェクト概要

プロジェクト名は gnt です。
ガントチャート画面をメインとして以下のような機能を盛り込む予定です。

- ガントチャート画面
    - タスクの追加・更新・削除・移動
- アサイン状況画面
    - ユーザーのアサイン状況をプロジェクトを横断して表示可能
- プロジェクトとユーザー画面
    - 複数のプロジェクトが追加可能
    - ユーザーは複数のプロジェクトに参加できる

PHP 8.2+を使用したLaravel 12アプリケーションで、以下の主要技術を使用しています：

$page.props.auth- フロントエンド: Vite + TailwindCSS 4.0
- データベース: SQLite（デフォルト）、Docker経由でMariaDBサポート
- テスト: FeatureテストとUnitテストスイートを持つPHPUnit
- 開発ツール: Laravel Pint（コードフォーマット）、Laravel Pail（ログ監視）
- Laravel Sail経由でのDockerサポート
- breeze + Vuejs を使っています。
- @syncfusion/ej2-vue-gantt でガントチャートを描画します。

## 代表的な URL は以下のようなもの

- /projects : プロジェクト一覧
- /projects/{project_key} : プロジェクト詳細
- /projects/{project_key}/gantt : プロジェクトのガントチャート
- /users : ユーザー一覧
- /users/{user_id} : ユーザー詳細
- /groups : ユーザーグループ一覧
- /groups/{group_key} : ユーザーグループ詳細

## 初回セットアップ

新しい開発環境での初回セットアップ手順：
1. `composer install` - PHP依存関係をインストール
2. `npm install` - フロントエンド依存関係をインストール
3. `.env.example`を`.env`にコピーし、必要に応じて設定を調整
4. `php artisan key:generate` - アプリケーションキーを生成
5. `php artisan migrate` - データベーステーブルを作成
6. `php artisan db:seed` - 初期データを投入（オプション）

## 開発コマンド

### 基本的なLaravelコマンド
- `sail php artisan serve` - 開発サーバーを起動
- `sail php artisan migrate` - データベースマイグレーションを実行
- `sail php artisan migrate:fresh --seed` - シーダー付きで新しいデータベースを作成
- `sail php artisan queue:work` - キューされたジョブを処理
- `sail php artisan tinker` - インタラクティブなPHPシェル

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

### デバッグとトラブルシューティング
- `php artisan route:list` - 全ルートを表示
- `php artisan config:cache` - 設定をキャッシュ（本番環境向け）
- `php artisan view:clear` - ビューキャッシュをクリア
- `php artisan cache:clear` - アプリケーションキャッシュをクリア
- `php artisan optimize:clear` - 全キャッシュをクリア
- `php artisan pail` - リアルタイムログ監視
- `php artisan about` - アプリケーション情報を表示

### パフォーマンス最適化
- `php artisan optimize` - 本番環境向け最適化
- `php artisan route:cache` - ルートをキャッシュ
- `php artisan event:cache` - イベントをキャッシュ
- `composer dump-autoload --optimize` - オートローダーを最適化

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

### 環境設定
- `.env` - 環境変数設定（ローカル開発用、Gitで管理されない）
- `.env.example` - 環境変数のテンプレート
- デフォルトでSQLiteを使用、MariaDBへの切り替えはDocker Composeで可能
- `APP_KEY`は`php artisan key:generate`で生成必須
- ログレベルはLOG_LEVELで調整可能（debug, info, notice, warning, error）

### 重要な開発ワークフロー
1. 新機能開発時：マイグレーション作成 → モデル/コントローラー作成 → ルート定義 → テスト作成
2. フロントエンド変更時：`npm run dev`でホットリロード開発
3. 本番デプロイ前：`composer test`でテスト実行 → `vendor/bin/pint`でコード整形
4. Docker使用時：`./vendor/bin/sail`プレフィックスを全コマンドに追加
