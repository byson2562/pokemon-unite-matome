<?php //一覧（子テーマ上書き）
if ( !defined( 'ABSPATH' ) ) exit;

if (is_single_breadcrumbs_position_main_top()) {
  cocoon_template_part('tmp/breadcrumbs');
}

if ( is_category() ){
  if (!is_paged()) {
    cocoon_template_part('tmp/category-content');
  } else {
    cocoon_template_part('tmp/list-title');
  }
} elseif ( (is_tag() || is_tax()) && !is_paged() ) {
  cocoon_template_part('tmp/tag-content');
} elseif (!is_home()) {
  cocoon_template_part('tmp/list-title');
}

if (is_ad_pos_index_top_visible() && is_all_adsenses_visible()){
  get_template_part_with_ad_format(get_ad_pos_index_top_format(), 'ad-index-top', is_ad_pos_index_top_label_visible());
}

if ( is_active_sidebar( 'index-top' ) ){
  dynamic_sidebar( 'index-top' );
}

if (is_sns_top_share_buttons_visible() &&
  (is_front_page() && !is_paged() && is_sns_front_page_top_share_buttons_visible())
){
  get_template_part_with_option('tmp/sns-share-buttons', SS_TOP);
}

if (is_front_page() && !is_paged() && function_exists('um_render_ranking_list')): ?>
  <section class="um-home-block um-ranking-block">
    <h2>ランキング</h2>
    <?php echo um_render_ranking_list(5); ?>
  </section>
<?php endif; ?>

<?php
$template_map = [
  'index'     => 'tmp/list-index',
  'tab_index' => 'tmp/list-tab-index',
  'category'  => 'tmp/list-category',
  'category_2_columns' => 'tmp/list-category-columns',
  'category_3_columns' => 'tmp/list-category-columns',
];

$template_map = apply_filters('front_page_type_map', $template_map);
$page_type = get_front_page_type();

if (isset($template_map[$page_type]) && is_front_top_page()) {
  cocoon_template_part($template_map[$page_type]);
} else {
  cocoon_template_part('tmp/list-index');
}

if (is_ad_pos_index_bottom_visible() && is_all_adsenses_visible()){
  get_template_part_with_ad_format(get_ad_pos_index_bottom_format(), 'ad-index-bottom', is_ad_pos_index_bottom_label_visible());
}

if ( is_active_sidebar( 'index-bottom' ) ){
  dynamic_sidebar( 'index-bottom' );
}

if (is_sns_bottom_share_buttons_visible() && !is_paged() &&
  (
  (is_front_page() && is_sns_front_page_bottom_share_buttons_visible()) ||
  (is_category() && is_sns_category_bottom_share_buttons_visible()) ||
  (is_tag() && is_sns_tag_bottom_share_buttons_visible())
  )
){
  get_template_part_with_option('tmp/sns-share-buttons', SS_BOTTOM);
}

if (is_sns_follow_buttons_visible() && !is_paged() &&
  (
    (is_front_page() && is_sns_front_page_follow_buttons_visible()) ||
    (is_category() && is_sns_category_follow_buttons_visible()) ||
    (is_tag() && is_sns_tag_follow_buttons_visible())
  )
){
  get_template_part_with_option('tmp/sns-follow-buttons', SF_BOTTOM);
}

if (is_front_page_type_index() || !is_front_top_page()) {
  cocoon_template_part('tmp/pagination');
}

if (is_single_breadcrumbs_position_main_bottom()){
  cocoon_template_part('tmp/breadcrumbs');
}

cocoon_template_part('tmp/main-scroll');
