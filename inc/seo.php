<?php
/**
 * Canonical SEO metadata for both WordPress templates and imported snapshots.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// This theme owns the complete metadata set. Prevent Yoast from outputting a
// second canonical/OG/schema set beside it.
add_filter( 'wpseo_frontend_presenters', '__return_empty_array', 1000 );

function digilens_seo_data(): array {
    $site_name   = 'DigiLens Việt Nam';
    $canonical   = function_exists( 'digilens_canonical_url' ) ? digilens_canonical_url( get_queried_object_id() ?: null ) : home_url( '/' );
    $title       = wp_get_document_title();
    $description = '';
    $image       = DIGILENS_THEME_URI . '/assets/images/digilens-share.svg';
    $type        = 'website';

    if ( is_front_page() ) {
        $title       = 'DigiLens Việt Nam - Kính AR và công nghệ quang học';
        $description = 'Khám phá kính AR, ống dẫn sóng Crystal và các giải pháp quang học DigiLens được cung cấp tại Việt Nam.';
    } elseif ( is_page( 'store' ) || ( function_exists( 'is_shop' ) && is_shop() ) ) {
        $title       = 'Sản phẩm và công nghệ quang học DigiLens | ' . $site_name;
        $description = 'Xem kính AR, thiết bị và các giải pháp công nghệ quang học DigiLens được cung cấp tại Việt Nam.';
    } elseif ( is_singular() ) {
        $post_id     = get_queried_object_id();
        $custom_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
        $custom_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
        $excerpt     = get_the_excerpt( $post_id );
        $title       = $custom_title ?: get_the_title( $post_id ) . ' | ' . $site_name;
        $description = $custom_desc ?: wp_trim_words( wp_strip_all_tags( $excerpt ?: get_post_field( 'post_content', $post_id ) ), 28, '…' );
        $thumb       = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( ! $thumb ) {
            $thumb = get_post_meta( $post_id, '_digilens_image_url', true ) ?: get_post_meta( $post_id, '_digilens_featured_image_url', true );
        }
        if ( $thumb ) { $image = $thumb; }
        if ( is_singular( 'product' ) ) { $type = 'product'; }
        elseif ( is_singular( 'post' ) ) { $type = 'article'; }
    }

    if ( ! $description ) {
        $description = 'Thông tin sản phẩm, công nghệ quang học và giải pháp thực tế tăng cường DigiLens tại Việt Nam.';
    }

    return compact( 'site_name', 'canonical', 'title', 'description', 'image', 'type' );
}

add_action( 'wp_head', function (): void {
    $seo = digilens_seo_data();
    echo '<title>' . esc_html( $seo['title'] ) . '</title>' . "\n";
    echo '<meta name="description" content="' . esc_attr( $seo['description'] ) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $seo['canonical'] ) . '">' . "\n";
    echo '<meta property="og:locale" content="vi_VN">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr( $seo['type'] ) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( $seo['site_name'] ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $seo['title'] ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $seo['description'] ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $seo['canonical'] ) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $seo['image'] ) . '">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr( $seo['title'] ) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}, 2 );

add_filter( 'wp_robots', function ( array $robots ): array {
    if ( is_404() || is_search() ) { return $robots; }
    return [
        'index'                   => true,
        'follow'                  => true,
        'max-snippet'             => -1,
        'max-image-preview'       => 'large',
        'max-video-preview'       => -1,
    ];
}, 100 );

/** Remove stale SEO nodes captured from digilens.com before wp_head() is injected. */
function digilens_clean_snapshot_seo( string $html ): string {
    $html = preg_replace( '#<title\b[^>]*>[\s\S]*?</title>#i', '', $html );
    $html = preg_replace( '#<meta\b[^>]*(?:name|property)=["\'](?:description|robots|googlebot|bingbot|twitter:[^"\']+|og:[^"\']+)["\'][^>]*>#i', '', $html );
    $html = preg_replace( '#<link\b[^>]*rel=["\'][^"\']*canonical[^"\']*["\'][^>]*>#i', '', $html );
    $html = preg_replace( '#<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>[\s\S]*?</script>#i', '', $html );

    // Snapshots often duplicate responsive H1 nodes or omit H1 entirely.
    $seen = false;
    $html = preg_replace_callback( '#<h1\b([^>]*)>([\s\S]*?)</h1>#i', function ( $match ) use ( &$seen ) {
        if ( ! $seen ) { $seen = true; return $match[0]; }
        return '<h2' . $match[1] . '>' . $match[2] . '</h2>';
    }, $html );
    if ( ! $seen ) {
        // Reuse the first visible Elementor page heading instead of inserting a
        // second, oversized title before the site header.
        $page_heading = get_the_title( get_queried_object_id() );
        $html = preg_replace_callback( '#<h([2-6])\b([^>]*)>([\s\S]*?)</h\1>#i', function ( $match ) use ( &$seen, $page_heading ) {
            if ( $seen || stripos( $match[2], 'elementor-heading-title' ) === false ) { return $match[0]; }
            $candidate = trim( wp_strip_all_tags( html_entity_decode( $match[3], ENT_QUOTES, 'UTF-8' ) ) );
            if ( ! $candidate || stripos( $page_heading, $candidate ) === false ) { return $match[0]; }
            $seen = true;
            return '<h1' . $match[2] . '>' . $match[3] . '</h1>';
        }, $html );

        if ( ! $seen ) {
            $html = preg_replace_callback( '#<h([2-6])\b([^>]*)>([\s\S]*?)</h\1>#i', function ( $match ) use ( &$seen ) {
                if ( $seen || stripos( $match[2], 'elementor-heading-title' ) === false ) { return $match[0]; }
                $seen = true;
                return '<h1' . $match[2] . '>' . $match[3] . '</h1>';
            }, $html );
        }

        if ( ! $seen ) {
            $heading = $page_heading;
            if ( ! $heading ) { $heading = digilens_seo_data()['title']; }
            $node = '<h1 class="dl-seo-page-title screen-reader-text">' . esc_html( $heading ) . '</h1>';
            $html = preg_replace( '#(<main\b[^>]*>)#i', '$1' . $node, $html, 1 );
        }
    }

    return $html;
}

add_filter( 'document_title_parts', function ( array $parts ): array {
    $parts['site'] = 'DigiLens Việt Nam';
    if ( is_front_page() ) { $parts['title'] = 'Kính AR và công nghệ quang học'; }
    return $parts;
} );

/** Add intrinsic dimensions to theme-hosted images in every HTML template. */
function digilens_add_image_dimensions( string $html ): string {
    if ( stripos( $html, '<html' ) === false ) { return $html; }
    $html = preg_replace_callback( '#<img\b[^>]*>#i', function ( $match ) {
        $tag = $match[0];
        if ( preg_match( '#\bwidth\s*=#i', $tag ) && preg_match( '#\bheight\s*=#i', $tag ) ) { return $tag; }
        if ( ! preg_match( '#\bsrc\s*=["\']([^"\']+)["\']#i', $tag, $src_match ) ) { return $tag; }
        $src_path  = (string) wp_parse_url( html_entity_decode( $src_match[1] ), PHP_URL_PATH );
        $theme_uri = (string) wp_parse_url( DIGILENS_THEME_URI, PHP_URL_PATH );
        if ( strpos( $src_path, $theme_uri . '/' ) !== 0 ) { return $tag; }
        $file = DIGILENS_THEME_DIR . substr( $src_path, strlen( $theme_uri ) );
        if ( ! is_file( $file ) ) { return $tag; }
        $size = @getimagesize( $file );
        if ( ! $size || empty( $size[0] ) || empty( $size[1] ) ) { return $tag; }
        $attrs = '';
        if ( ! preg_match( '#\bwidth\s*=#i', $tag ) ) { $attrs .= ' width="' . (int) $size[0] . '"'; }
        if ( ! preg_match( '#\bheight\s*=#i', $tag ) ) { $attrs .= ' height="' . (int) $size[1] . '"'; }
        return preg_replace( '#\s*/?>$#', $attrs . ' />', $tag );
    }, $html );

    // Some legacy responsive templates render the same heading twice.
    $seen_h1 = false;
    return preg_replace_callback( '#<h1\b([^>]*)>([\s\S]*?)</h1>#i', function ( $match ) use ( &$seen_h1 ) {
        if ( ! $seen_h1 ) { $seen_h1 = true; return $match[0]; }
        return '<h2' . $match[1] . '>' . $match[2] . '</h2>';
    }, $html );
}

add_action( 'template_redirect', function (): void {
    if ( is_admin() || wp_doing_ajax() || is_feed() ) { return; }
    ob_start( 'digilens_add_image_dimensions' );
}, -1000 );
