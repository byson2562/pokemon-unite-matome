# Unite Matome (WordPress + Docker)

ポケモンユナイト速報型まとめサイトのローカルMVPです。

## MVPでできること

- DockerでWordPressローカル環境を起動
- Cocoon親テーマ + Cocoon子テーマを自動導入して有効化
- 速報向けの投稿テンプレートを自動適用
- ホームで「新着」と「カテゴリ別最新」を表示
- 固定ページ（運営者情報、引用・転載ポリシー、問い合わせ、プライバシーポリシー）を自動作成
- サンプル投稿を投入して表示確認

## 1. 起動

```bash
cp .env.example .env
docker compose up -d
```

- WordPress: http://localhost:8080
- phpMyAdmin: http://localhost:8081

## 2. 初期セットアップ

```bash
./scripts/first_setup.sh
```

引数で上書き可能:

```bash
./scripts/first_setup.sh "http://localhost:8080" "ユナイト速報まとめ" "admin" "StrongPassword!234" "you@example.com"
```

## 3. 推奨プラグイン導入

```bash
./scripts/install_plugins.sh
```

導入対象（取得不可なものは skipped として継続）:
- Yoast SEO
- LiteSpeed Cache
- Wordfence（ローカルでは無効化）
- UpdraftPlus
- EWWW Image Optimizer
- Advanced Custom Fields
- Table of Contents Plus（ローカルでは無効化）

## 4. 一括MVP構築

```bash
./scripts/mvp_bootstrap.sh
```

## 5. MVPデータ投入

```bash
./scripts/seed_mvp.sh
```

## 6. 動作確認

1. `http://localhost:8080` を開く
2. ホームに「新着速報」「カテゴリ別最新」が表示される
3. 投稿本文に「関連記事」が表示される
4. 新規投稿画面で `引用 -> 管理人コメント` の初期テンプレが入る

## 7. 主要ファイル

- `docker-compose.yml`: ローカル実行基盤
- `scripts/first_setup.sh`: 初期設定（固定ページ、ホーム設定含む）
- `scripts/install_cocoon_theme.sh`: Cocoon親子テーマ導入
- `scripts/install_plugins.sh`: 推奨プラグイン導入
- `scripts/seed_mvp.sh`: MVP用サンプル投稿投入
- `wp-content/mu-plugins/unite-matome-core.php`: MVP機能本体
- `wp-content/mu-plugins/assets/unite-matome.css`: テーマ非依存MVPスタイル

## 8. 速報運用フロー

1. 監視対象スレを1日4-8回巡回
2. 見出し候補を3本作成
3. 引用選定と整形（主従関係を守る）
4. カテゴリ/タグ設定して公開
5. SNS投稿
6. 30-120分後、反応の良い記事を追記更新

## 9. 注意

5ch転載・引用は規約/ガイドラインの最新確認が前提です。自動収集は規約違反リスクが高いため、手動または半自動編集運用を推奨します。
