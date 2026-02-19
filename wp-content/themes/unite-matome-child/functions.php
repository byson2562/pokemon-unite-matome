<?php

declare(strict_types=1);

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'unite-matome-child-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
});

add_filter('excerpt_length', static fn (): int => 70);

add_filter('excerpt_more', static fn (): string => ' ...');
