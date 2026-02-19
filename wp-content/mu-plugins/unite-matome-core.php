<?php
/**
 * Plugin Name: Unite Matome Core
 * Description: まとめ運用向けの軽量機能。
 * Version: 0.4.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * テーマに依存しないMVPスタイル。
 */
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'unite-matome-core-style',
        content_url('mu-plugins/assets/unite-matome.css'),
        [],
        '0.3.0'
    );
});

/**
 * フロント表示の不要アセットを削減。
 */
add_action('init', static function (): void {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
    remove_action('wp_head', 'rel_canonical');
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
    remove_action('wp_head', 'wp_oembed_add_host_js');
});

add_action('wp_enqueue_scripts', static function (): void {
    if (is_admin()) {
        return;
    }

    // Cocoonが読み込むjQuery Migrateを外し、ローカルのjQuery Coreのみを使う。
    wp_deregister_script('jquery');
    wp_deregister_script('jquery-core');
    wp_deregister_script('jquery-migrate');
    wp_register_script('jquery-core', includes_url('/js/jquery/jquery.min.js'), [], null, true);
    wp_register_script('jquery', false, ['jquery-core'], null, true);
    wp_enqueue_script('jquery');

    wp_dequeue_script('wp-embed');
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 100);

add_action('wp_default_scripts', static function (WP_Scripts $scripts): void {
    if (is_admin() || empty($scripts->registered['jquery'])) {
        return;
    }

    $scripts->registered['jquery']->deps = array_values(
        array_filter(
            $scripts->registered['jquery']->deps,
            static fn (string $dep): bool => $dep !== 'jquery-migrate'
        )
    );
});

add_filter('wp_resource_hints', static function (array $urls, string $relation_type): array {
    if (!in_array($relation_type, ['dns-prefetch', 'preconnect'], true)) {
        return $urls;
    }

    $homeHost = (string) parse_url(home_url(), PHP_URL_HOST);
    if ($homeHost === '') {
        return $urls;
    }

    return array_values(
        array_filter(
            $urls,
            static function ($url) use ($homeHost): bool {
                if (!is_string($url)) {
                    return false;
                }

                $host = (string) parse_url($url, PHP_URL_HOST);
                return $host === '' || $host === $homeHost;
            }
        )
    );
}, 10, 2);

add_action('wp_enqueue_scripts', static function (): void {
    if (is_user_logged_in()) {
        return;
    }

    wp_deregister_style('dashicons');
}, 101);

add_filter('script_loader_src', static function (string $src): string {
    if (is_admin()) {
        return $src;
    }

    return um_remove_asset_version_param($src);
}, 9999);

add_filter('style_loader_src', static function (string $src): string {
    if (is_admin()) {
        return $src;
    }

    return um_remove_asset_version_param($src);
}, 9999);

add_filter('wp_get_attachment_image_attributes', static function (array $attr): array {
    if (empty($attr['loading'])) {
        $attr['loading'] = 'lazy';
    }
    if (empty($attr['decoding'])) {
        $attr['decoding'] = 'async';
    }

    return $attr;
}, 10);

add_action('send_headers', static function (): void {
    if (is_admin() || is_user_logged_in()) {
        return;
    }

    if (!is_singular() && !is_home() && !is_front_page() && !is_archive()) {
        return;
    }

    header('Cache-Control: public, max-age=300, s-maxage=300');
}, 20);

/**
 * Cocoonの重い既定値を抑える。
 */
add_action('after_setup_theme', static function (): void {
    if ((string) get_theme_mod('pre_acquisition_list', '') !== '') {
        set_theme_mod('pre_acquisition_list', '');
    }
});

add_filter('is_sns_share_buttons_visible', static function (): bool {
    return false;
}, 99, 2);

/**
 * SEO: タイトル、description、canonicalを統一。
 */
add_filter('document_title_parts', static function (array $parts): array {
    if (is_front_page() || is_home()) {
        return $parts;
    }

    $prefix = '【ポケユナ】';
    if (!empty($parts['title']) && strpos($parts['title'], $prefix) !== 0) {
        $parts['title'] = $prefix . $parts['title'];
    }

    return $parts;
}, 20);

add_filter('wpseo_title', static function (string $title): string {
    if (is_front_page() || is_home() || strpos($title, '【ポケユナ】') === 0) {
        return $title;
    }

    return '【ポケユナ】' . $title;
}, 20);

add_filter('wpseo_metadesc', static function (string $desc): string {
    $generated = um_generate_meta_description();
    if ($generated === '') {
        return $desc;
    }

    return $generated;
}, 20);

add_filter('get_meta_description_text', static function (string $description): string {
    $generated = um_generate_meta_description();
    if ($generated === '') {
        return $description;
    }

    return $generated;
}, 20);

add_filter('get_ogp_description_text', static function (string $description): string {
    $generated = um_generate_meta_description();
    if ($generated === '') {
        return $description;
    }

    return $generated;
}, 20);

add_filter('robots_txt', static function (string $output, bool $public): string {
    if (!$public) {
        return $output;
    }

    $lines = array_filter(array_map('trim', explode("\n", $output)));
    $hasSitemap = false;

    foreach ($lines as $line) {
        if (stripos($line, 'Sitemap:') === 0) {
            $hasSitemap = true;
            break;
        }
    }

    if (!$hasSitemap) {
        $lines[] = 'Sitemap: ' . home_url('/wp-sitemap.xml');
    }

    if (!in_array('Allow: /wp-admin/admin-ajax.php', $lines, true)) {
        $lines[] = 'Allow: /wp-admin/admin-ajax.php';
    }

    return implode("\n", $lines) . "\n";
}, 10, 2);

add_filter('wp_robots', static function (array $robots): array {
    if (is_search()) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }

    return $robots;
});

/**
 * GA4 / Search Console タグ出力（値が設定されている場合のみ）。
 */
add_action('wp_head', static function (): void {
    if (is_admin()) {
        return;
    }

    $token = um_get_search_console_token();
    if ($token !== '') {
        echo '<meta name="google-site-verification" content="' . esc_attr($token) . '">' . "\n";
    }

    $measurementId = um_get_ga4_measurement_id();
    if ($measurementId === '') {
        return;
    }

    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr($measurementId) . '"></script>' . "\n";
    echo "<script>\n";
    echo "window.dataLayer = window.dataLayer || [];\n";
    echo "function gtag(){dataLayer.push(arguments);}\n";
    echo "gtag('js', new Date());\n";
    echo "gtag('config', '" . esc_js($measurementId) . "', { 'anonymize_ip': true });\n";
    echo "</script>\n";
}, 3);

/**
 * 新規投稿の初期本文を固定化。
 */
add_filter('default_content', static function (string $content, WP_Post $post): string {
    if ($post->post_type !== 'post') {
        return $content;
    }

    return <<<'HTML'
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>ここに引用本文（出典URL・日時を併記）</p><cite>出典: https://example.com/ (YYYY-MM-DD HH:MM)</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph {"className":"um-admin-comment"} -->
<p class="um-admin-comment"><strong>管理人コメント:</strong> ここに短評（3-6行）</p>
<!-- /wp:paragraph -->
HTML;
}, 10, 2);

/**
 * 速報投稿用ブロックパターン。
 */
add_action('init', static function (): void {
    if (!function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern_category('unite-matome', ['label' => 'Unite Matome']);

    register_block_pattern(
        'unite-matome/quick-matome-post',
        [
            'title'       => '速報まとめテンプレート',
            'description' => '引用 -> 管理人コメント -> 区切りの最小テンプレート',
            'categories'  => ['unite-matome'],
            'content'     => <<<'HTML'
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>ここに引用本文（出典URL・日時を併記）</p><cite>出典: https://example.com/ (YYYY-MM-DD HH:MM)</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph {"className":"um-admin-comment"} -->
<p class="um-admin-comment"><strong>管理人コメント:</strong> ここに短評（3-6行）</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->
HTML,
        ]
    );
});

/**
 * 関連記事を本文下に自動出力（同タグ優先）。
 */
add_filter('the_content', static function (string $content): string {
    if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $post_id = get_the_ID();
    if (!$post_id) {
        return $content;
    }

    $related = um_get_related_posts((int) $post_id);
    if (!$related) {
        return $content;
    }

    $html = '<section class="um-related-posts"><h2>関連記事</h2><ul>';
    foreach ($related as $item) {
        $title = esc_html(get_the_title($item));
        $url = esc_url(get_permalink($item));
        $html .= "<li><a href=\"{$url}\">{$title}</a></li>";
    }
    $html .= '</ul></section>';

    return $content . $html;
}, 20);

/**
 * トップページ向けショートコード。
 */
add_shortcode('um_home_sections', static function (): string {
    ob_start();

    echo '<section class="um-home-block">';
    echo '<h2>新着速報</h2>';
    echo um_render_post_list([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 6,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);
    echo '</section>';

    echo '<section class="um-home-block um-ranking-block">';
    echo '<h2>ランキング</h2>';
    echo um_render_ranking_list(5);
    echo '</section>';

    echo '<section class="um-home-block um-home-list-block">';
    echo '<h2>一覧</h2>';
    echo um_render_post_list([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 20,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);
    echo '</section>';

    return (string) ob_get_clean();
});

/**
 * 記事詳細のPVをカウント。
 */
add_action('wp', static function (): void {
    if (!is_singular('post') || is_preview() || is_admin()) {
        return;
    }

    $postId = get_queried_object_id();
    if (!$postId) {
        return;
    }

    $metaKey = '_um_views';
    $views = (int) get_post_meta($postId, $metaKey, true);
    update_post_meta($postId, $metaKey, $views + 1);
});

function um_get_related_posts(int $post_id): array
{
    $tag_ids = wp_get_post_tags($post_id, ['fields' => 'ids']);

    $args = [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'post__not_in'        => [$post_id],
        'posts_per_page'      => 6,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ];

    if (!empty($tag_ids)) {
        $args['tag__in'] = $tag_ids;
    }

    $query = new WP_Query($args);
    if (!$query->have_posts()) {
        return [];
    }

    return wp_list_pluck($query->posts, 'ID');
}

function um_render_post_list(array $args): string
{
    $query = new WP_Query($args);
    if (!$query->have_posts()) {
        return '<p>記事はまだありません。</p>';
    }

    $html = '<ul class="um-post-list">';
    foreach ($query->posts as $post) {
        $title = esc_html(get_the_title($post));
        $url = esc_url(get_permalink($post));
        $date = esc_html(get_the_date('Y-m-d H:i', $post));
        $html .= "<li><a href=\"{$url}\">{$title}</a> <span>{$date}</span></li>";
    }
    $html .= '</ul>';

    return $html;
}

function um_render_ranking_list(int $limit = 5): string
{
    $query = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'meta_key'            => '_um_views',
        'orderby'             => ['meta_value_num' => 'DESC', 'date' => 'DESC'],
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);

    if (!$query->have_posts()) {
        $query = new WP_Query([
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ]);
    }

    if (!$query->have_posts()) {
        return '<p>記事はまだありません。</p>';
    }

    $html = '<ol class="um-ranking-list">';
    foreach ($query->posts as $post) {
        $title = esc_html(get_the_title($post));
        $url = esc_url(get_permalink($post));
        $views = (int) get_post_meta((int) $post->ID, '_um_views', true);
        $html .= "<li><a href=\"{$url}\">{$title}</a> <span>{$views} views</span></li>";
    }
    $html .= '</ol>';

    return $html;
}

function um_remove_asset_version_param(string $src): string
{
    if ($src === '') {
        return $src;
    }

    return (string) remove_query_arg('ver', $src);
}

function um_generate_meta_description(): string
{
    if (is_singular('post')) {
        $post_id = get_queried_object_id();
        if (!$post_id) {
            return '';
        }

        $manual = trim((string) get_post_meta($post_id, '_yoast_wpseo_metadesc', true));
        if ($manual !== '') {
            return um_truncate_text($manual, 120);
        }

        $excerpt = trim((string) get_the_excerpt($post_id));
        if ($excerpt !== '') {
            return um_truncate_text(wp_strip_all_tags($excerpt), 120);
        }

        $content = trim((string) get_post_field('post_content', $post_id));
        return um_truncate_text(wp_strip_all_tags($content), 120);
    }

    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $desc = trim(wp_strip_all_tags((string) term_description($term)));
            if ($desc !== '') {
                return um_truncate_text($desc, 120);
            }
            return um_truncate_text($term->name . 'に関する最新の速報・反応まとめ記事一覧。', 120);
        }
    }

    if (is_home() || is_front_page()) {
        return 'ポケモンユナイトの最新速報、環境考察、ランクマ・大会の反応を最速でまとめています。';
    }

    return um_truncate_text((string) get_bloginfo('description'), 120);
}

function um_truncate_text(string $text, int $length): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $length);
    }

    return substr($text, 0, $length);
}

function um_get_ga4_measurement_id(): string
{
    $option = trim((string) get_option('um_ga4_measurement_id', ''));
    if ($option !== '') {
        return (string) preg_replace('/[^A-Z0-9\-]/', '', strtoupper($option));
    }

    $env = trim((string) getenv('GA4_MEASUREMENT_ID'));
    if ($env === '') {
        return '';
    }

    return (string) preg_replace('/[^A-Z0-9\-]/', '', strtoupper($env));
}

function um_get_search_console_token(): string
{
    $option = trim((string) get_option('um_gsc_verification_token', ''));
    if ($option !== '') {
        return (string) preg_replace('/[^a-zA-Z0-9_\-]/', '', $option);
    }

    $env = trim((string) getenv('GSC_VERIFICATION_TOKEN'));
    if ($env === '') {
        return '';
    }

    return (string) preg_replace('/[^a-zA-Z0-9_\-]/', '', $env);
}
