#!/usr/bin/env bash
set -euo pipefail

SITE_URL="${1:-http://localhost:8080}"
SITE_TITLE="${2:-ポケモンユナイト速報まとめ}"
ADMIN_USER="${3:-admin}"
ADMIN_PASSWORD="${4:-adminpass123!}"
ADMIN_EMAIL="${5:-admin@example.com}"
GA4_MEASUREMENT_ID_INPUT="${6:-${GA4_MEASUREMENT_ID:-}}"
GSC_VERIFICATION_TOKEN_INPUT="${7:-${GSC_VERIFICATION_TOKEN:-}}"

WP="docker compose --profile tools run --rm wpcli --allow-root"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
"${SCRIPT_DIR}/install_cocoon_theme.sh"

if ! $WP core is-installed >/dev/null 2>&1; then
  $WP core install \
    --url="$SITE_URL" \
    --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" \
    --admin_password="$ADMIN_PASSWORD" \
    --admin_email="$ADMIN_EMAIL"
fi

$WP theme activate cocoon-child-master

$WP option update timezone_string Asia/Tokyo
$WP option update blogname "$SITE_TITLE"
$WP option update blogdescription "ポケモンユナイトの速報・反応まとめ"
$WP option update posts_per_page 20
$WP option update posts_per_rss 20
$WP rewrite structure '/%postname%/' --hard
$WP rewrite flush --hard

# 速報運用向け: コメントを初期OFF
$WP option update default_comment_status closed
$WP option update default_ping_status closed
$WP option update default_pingback_flag 0

if [ -n "$GA4_MEASUREMENT_ID_INPUT" ]; then
  $WP option update um_ga4_measurement_id "$GA4_MEASUREMENT_ID_INPUT"
fi

if [ -n "$GSC_VERIFICATION_TOKEN_INPUT" ]; then
  $WP option update um_gsc_verification_token "$GSC_VERIFICATION_TOKEN_INPUT"
fi

create_page () {
  local title="$1"
  local content="$2"
  local page_id

  page_id=$($WP post list --post_type=page --title="$title" --field=ID --posts_per_page=1 || true)

  if [ -z "$page_id" ]; then
    $WP post create --post_type=page --post_status=publish --post_title="$title" --post_content="$content" >/dev/null
  else
    $WP post update "$page_id" --post_content="$content" >/dev/null
  fi
}

# カテゴリ
for cat in キャラ ランクマ 大会 雑談; do
  $WP term create category "$cat" >/dev/null 2>&1 || true
done

set_category_meta_description () {
  local cat_name="$1"
  local description="$2"
  local term_id

  $WP term update category "$cat_name" --description "$description" >/dev/null 2>&1 || true
  term_id=$($WP term list category --name="$cat_name" --field=term_id --format=ids | awk 'NR==1 {print $1}')
  if [ -n "$term_id" ]; then
    $WP term meta update "$term_id" the_category_meta_description "$description" >/dev/null 2>&1 || true
  fi
}

set_category_meta_description "キャラ" "ポケモンユナイトのキャラ性能、ビルド、環境評価の速報まとめ。"
set_category_meta_description "ランクマ" "ランクマッチ環境、構成、勝率傾向の反応まとめ。"
set_category_meta_description "大会" "大会結果、ドラフト、注目構成の速報と反応まとめ。"
set_category_meta_description "雑談" "ポケモンユナイトに関する雑談、予想、コミュニティ反応まとめ。"

# 旧カテゴリを削除
$WP term delete category 持ち物 >/dev/null 2>&1 || true

create_page "運営者情報" "このサイトの運営者情報を記載してください。"
create_page "引用・転載ポリシー" "引用条件、出典明記方法、削除依頼窓口を記載してください。"
create_page "問い合わせ" "問い合わせフォームを設置してください。"
create_page "プライバシーポリシー" "広告配信、アクセス解析、Cookie利用方針を記載してください。"

$WP option update show_on_front posts
$WP option update page_on_front 0
$WP option update page_for_posts 0

# Cocoon高速化向け初期値
$WP eval "set_theme_mod('pre_acquisition_list', '');"
$WP eval "set_theme_mod('sns_top_share_buttons_visible', 0);"
$WP eval "set_theme_mod('sns_bottom_share_buttons_visible', 0);"
$WP eval "set_theme_mod('access_count_enable', 0);"
$WP eval "set_theme_mod('toc_visible', 0); set_theme_mod('single_toc_visible', 0); set_theme_mod('page_toc_visible', 0); set_theme_mod('category_toc_visible', 0); set_theme_mod('tag_toc_visible', 0);"
$WP plugin deactivate wordfence table-of-contents-plus updraftplus ewww-image-optimizer >/dev/null 2>&1 || true

MENU_NAME="速報カテゴリ"
MENU_ID=$($WP menu list --fields=term_id,name --format=csv | awk -F, -v name="$MENU_NAME" '$2 == name {print $1}' | head -n1)
if [ -z "$MENU_ID" ]; then
  MENU_ID=$($WP menu create "$MENU_NAME" --porcelain)
fi

# 既存メニュー項目をクリアして毎回同じ構成に揃える
EXISTING_ITEMS=$($WP menu item list "$MENU_ID" --format=ids || true)
if [ -n "$EXISTING_ITEMS" ]; then
  $WP post delete $EXISTING_ITEMS --force >/dev/null
fi

for cat in キャラ ランクマ 大会 雑談; do
  TERM_ID=$($WP term list category --name="$cat" --field=term_id --format=ids | awk 'NR==1 {print $1}')
  if [ -n "$TERM_ID" ]; then
    $WP menu item add-term "$MENU_ID" category "$TERM_ID" --title="$cat" >/dev/null
  fi
done

if [ -n "$MENU_ID" ]; then
  $WP menu location assign "$MENU_ID" navi-header
  $WP menu location assign "$MENU_ID" navi-mobile
fi

echo "Initial setup completed."
