<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'DIGILENS_THEME_VERSION', '1.3.1' );
define( 'DIGILENS_THEME_DIR', get_template_directory() );
define( 'DIGILENS_THEME_URI', get_template_directory_uri() );
define( 'DIGILENS_SNAPSHOT_DIR', DIGILENS_THEME_DIR . '/snapshot' );
define( 'DIGILENS_SNAPSHOT_URI', DIGILENS_THEME_URI . '/snapshot' );

require_once DIGILENS_THEME_DIR . '/inc/snapshot-renderer.php';
require_once DIGILENS_THEME_DIR . '/inc/snapshot-importer.php';
require_once DIGILENS_THEME_DIR . '/inc/forms.php';
require_once DIGILENS_THEME_DIR . '/inc/header-nav.php';
require_once DIGILENS_THEME_DIR . '/inc/post-metaboxes.php';
require_once DIGILENS_THEME_DIR . '/inc/products.php';
require_once DIGILENS_THEME_DIR . '/inc/sitemap-robots.php';
require_once DIGILENS_THEME_DIR . '/inc/schema.php';
require_once DIGILENS_THEME_DIR . '/inc/seo.php';
require_once DIGILENS_THEME_DIR . '/inc/yoast-meta.php';
require_once DIGILENS_THEME_DIR . '/inc/floating-contact.php';

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'script', 'style' ) );
    register_nav_menus( array( 'primary' => __( 'Menu chính', 'digilens-snapshot' ) ) );
} );

// Force Classic Editor for posts & pages to match user layout
add_filter( 'use_block_editor_for_post', '__return_false', 10 );
add_filter( 'use_block_editor_for_post_type', '__return_false', 10 );

// Standardize all content and excerpt emails to contact@digilens.vn
add_filter( 'the_content', function( $content ) {
    if ( function_exists( 'digilens_standardize_all_emails' ) ) {
        return digilens_standardize_all_emails( $content );
    }
    return $content;
}, 99 );

add_filter( 'the_excerpt', function( $excerpt ) {
    if ( function_exists( 'digilens_standardize_all_emails' ) ) {
        return digilens_standardize_all_emails( $excerpt );
    }
    return $excerpt;
}, 99 );

// Disable WordPress emojis completely
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

// 3. Enqueue styles for Classic Editor (TinyMCE)
add_filter( 'mce_css', function ( $mce_css ) {
    $editor_style = get_template_directory_uri() . '/assets/css/editor-style.css';
    return ! empty( $mce_css ) ? $mce_css . ',' . $editor_style : $editor_style;
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'digilens-theme', get_stylesheet_uri(), array(), DIGILENS_THEME_VERSION );
}, 100 );

// Route all category and tag links directly to /media/ (Trung tâm truyền thông)
add_filter( 'category_link', function () {
    return home_url( '/media/' );
} );

add_filter( 'term_link', function ( $termlink, $term, $taxonomy ) {
    if ( $taxonomy === 'category' || $taxonomy === 'post_tag' ) {
        return home_url( '/media/' );
    }
    return $termlink;
}, 10, 3 );

add_action( 'template_redirect', function () {
    if ( is_admin() ) { return; }

    $req_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

    // Handle missing Elementor webpack chunk bundle JS requests (resolves ChunkLoadError & handler constructors)
    if ( preg_match( '#\.(?:bundle\.min|bundle)\.js#i', $req_uri ) ) {
        header( 'Content-Type: application/javascript; charset=UTF-8' );
        status_header( 200 );
        echo '"use strict";(function(){var B=function(e){this.$element=(e&&e.$element)?e.$element:(window.jQuery?window.jQuery(e):null);this.options=(e&&e.options)?e.options:{};};B.prototype={getDefaultSettings:function(){return{};},getDefaultElements:function(){return{};},findElement:function(){return window.jQuery?window.jQuery():null;},bindEvents:function(){},unbindEvents:function(){},onInit:function(){},onDestroy:function(){},onElementChange:function(){},onEditSettingsChange:function(){},onPageSettingsChange:function(){},run:function(){}};if(window.elementorModules&&window.elementorModules.frontend&&window.elementorModules.frontend.handlers&&window.elementorModules.frontend.handlers.Base){B.prototype=Object.create(window.elementorModules.frontend.handlers.Base.prototype);B.prototype.constructor=B;}var M=function(){var H=function(e){B.call(this,e);};H.prototype=Object.create(B.prototype);H.prototype.constructor=H;return function(m,x){m.exports=H;x.default=H;};};var mods={};for(var i=0;i<=1000;i++){mods[i]=M();}var chunks=[77,557,535,396,120,324,800,900];(self["webpackChunkelementor"]=self["webpackChunkelementor"]||[]).push([chunks,mods]);(self["webpackChunkelementor_pro"]=self["webpackChunkelementor_pro"]||[]).push([chunks,mods]);})();';
        exit;
    }

    // Handle wp-emoji-release.min.js request
    if ( strpos( $req_uri, 'wp-emoji-release' ) !== false ) {
        header( 'Content-Type: application/javascript; charset=UTF-8' );
        status_header( 200 );
        echo '/* emojis disabled */';
        exit;
    }

    // Redirect all category, tag, or tax archives directly to /media/
    if ( is_category() || is_tag() || is_tax() ) {
        wp_safe_redirect( home_url( '/media/' ), 301 );
        exit;
    }

    $uri = trim( (string) wp_parse_url( $req_uri, PHP_URL_PATH ), '/' );
    if ( strpos( $uri, 'category/' ) === 0 || $uri === 'category' ) {
        wp_safe_redirect( home_url( '/media/' ), 301 );
        exit;
    }

    if ( preg_match( '#^media/(\d+)$#i', $uri, $m ) ) {
        wp_safe_redirect( home_url( '/media/?pr_paged=' . $m[1] ), 301 );
        exit;
    }

    // Store Page Route: /store/, /cua-hang/, /shop/
    if ( $uri === 'store' || $uri === 'cua-hang' || $uri === 'shop' || is_page( 'store' ) ) {
        status_header( 200 );
        include DIGILENS_THEME_DIR . '/page-store.php';
        exit;
    }

    // Dynamic WordPress rendering: Let single posts, author, and search run through WordPress templates
    if ( is_single() || is_singular( 'post' ) || is_home() || is_archive() || is_search() ) {
        return;
    }

    $queried_id = get_queried_object_id();
    if ( $queried_id && get_post_type( $queried_id ) === 'post' ) {
        return;
    }

    $snapshot = digilens_snapshot_for_current_request();
    if ( $snapshot ) {
        status_header( 200 );
        digilens_render_snapshot( $snapshot );
        exit;
    }
}, 1 );

// Ensure 'store' page exists in database
add_action( 'init', function () {
    if ( ! get_page_by_path( 'store' ) ) {
        wp_insert_post( array(
            'post_title'     => 'Cửa hàng (Store)',
            'post_name'      => 'store',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ) );
    }
} );

add_filter( 'redirect_canonical', function ( $redirect_url ) {
    return digilens_snapshot_for_current_request() ? false : $redirect_url;
}, 10, 1 );

add_action( 'after_switch_theme', 'digilens_snapshot_install' );

add_action( 'admin_menu', function () {
    add_theme_page(
        __( 'DigiLens Snapshot', 'digilens-snapshot' ),
        __( 'DigiLens Snapshot', 'digilens-snapshot' ),
        'manage_options',
        'digilens-snapshot',
        'digilens_snapshot_admin_page'
    );
} );

/**
 * Initial one-time import of snapshot into WordPress Database on first run.
 * Once imported, database is the primary source of truth and user edits are preserved forever.
 */
add_action( 'init', function () {
    if ( get_option( 'digilens_initial_db_imported' ) ) { return; }
    update_option( 'digilens_initial_db_imported', 1 );
    if ( function_exists( 'digilens_snapshot_import_all' ) ) {
        digilens_snapshot_import_all();
    }
} );
