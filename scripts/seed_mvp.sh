#!/usr/bin/env bash
set -euo pipefail

WP="docker compose --profile tools run --rm wpcli --allow-root"

ensure_tag () {
  local tag_name="$1"
  $WP term create post_tag "$tag_name" >/dev/null 2>&1 || true
}

ensure_category () {
  local category_name="$1"
  $WP term create category "$category_name" >/dev/null 2>&1 || true
}

post_exists_by_slug () {
  local slug="$1"
  local id
  id=$($WP post list --post_type=post --name="$slug" --field=ID --posts_per_page=1)
  [ -n "$id" ]
}

create_post () {
  local slug="$1"
  local title="$2"
  local category="$3"
  local tags_csv="$4"
  local source_url="$5"
  local source_dt="$6"
  local comment="$7"

  if post_exists_by_slug "$slug"; then
    echo "skipped: $slug"
    return
  fi

  ensure_category "$category"

  IFS=',' read -r -a tags <<< "$tags_csv"
  for t in "${tags[@]}"; do
    ensure_tag "$t"
  done

  local content
  content="<!-- wp:quote -->
<blockquote class=\"wp-block-quote\"><p>サンプル引用: スレの反応をここにまとめます。</p><cite>出典: ${source_url} (${source_dt})</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph {\"className\":\"um-admin-comment\"} -->
<p class=\"um-admin-comment\"><strong>管理人コメント:</strong> ${comment}</p>
<!-- /wp:paragraph -->"

  local post_id
  post_id=$($WP post create --post_type=post --post_status=publish --post_name="$slug" --post_title="$title" --post_content="$content" --porcelain)

  $WP post term set "$post_id" category "$category" --by=name >/dev/null
  $WP post term set "$post_id" post_tag "${tags[@]}" --by=name >/dev/null

  echo "created: $slug"
}

create_post "news-pikachu-usage" "[速報] ピカチュウ採用率が上昇、わざ構成の議論が活発化" "キャラ" "ピカチュウ,ver1.15.1,環境" "https://example.com/thread1" "2026-02-19 09:00" "レンジ構成の評価が上がっており、序盤の主導権重視の編成で採用が増えています。"
create_post "news-bangle-meta" "[速報] ちからのハチマキ再評価、持ち物優先度に変化" "ランクマ" "ちからのハチマキ,ver1.15.1,ビルド" "https://example.com/thread2" "2026-02-19 10:20" "通常攻撃主体の構成で期待値が高く、レーン選択次第で優先度が変わるという意見が多いです。"
create_post "news-ranked-tank2" "[速報] ランクマ上位帯でタンク2構成が増加" "ランクマ" "ランクマ,環境,構成" "https://example.com/thread3" "2026-02-19 11:45" "終盤のオブジェクト戦を安定させる意図が強く、火力枠は1-2枚に絞る流れです。"
create_post "news-tournament-jungle" "[速報] 大会で注目された中央ルートの新ピック" "大会" "大会,ドラフト,環境" "https://example.com/thread4" "2026-02-19 13:10" "BAN誘導と相性補完の両面で評価され、ランクマでも試すプレイヤーが増えています。"
create_post "news-patch-prediction" "[速報] 雑談スレで次パッチ予想が加速" "雑談" "雑談,次パッチ,予想" "https://example.com/thread5" "2026-02-19 14:30" "調整候補として複数の近接キャラ名が挙がっており、ナーフ対象予想が割れています。"

echo "MVP sample posts phase completed."
