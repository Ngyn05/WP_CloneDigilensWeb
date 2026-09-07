<?php
/**
 * Security Hardening Module for DigiLens Theme
 *
 * Provides layered protection against common WordPress attack vectors:
 * - Disables file editing from WP Admin
 * - Disables XML-RPC and Application Passwords
 * - Blocks username enumeration (author query & REST API)
 * - Removes WordPress version disclosures
 * - Enforces essential security HTTP headers
 * - Automatically protects wp-content/uploads against PHP execution via .htaccess
 * - Warns admin if protection rules cannot be written
 *
 * @package DigiLens
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// 1. Disable Theme / Plugin Code Editor in WP Admin
// -----------------------------------------------------------------------------
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}

// -----------------------------------------------------------------------------
// 2. Disable XML-RPC & Application Passwords
// -----------------------------------------------------------------------------

// Disable XML-RPC functionality
add_filter( 'xmlrpc_enabled', '__return_false', PHP_INT_MAX );
add_filter( 'xmlrpc_methods', '__return_empty_array', PHP_INT_MAX );

// Remove XML-RPC discovery links
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );

// Remove X-Pingback HTTP header
add_filter( 'wp_headers', function( $headers ) {
    if ( isset( $headers['X-Pingback'] ) ) {
        unset( $headers['X-Pingback'] );
    }
    return $headers;
} );

// Block direct access to xmlrpc.php
add_action( 'init', function() {
    if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
        wp_die(
            esc_html__( 'XML-RPC services are disabled on this site.', 'digilens' ),
            esc_html__( 'Forbidden', 'digilens' ),
            array( 'response' => 403 )
        );
    }
}, 1 );

// Disable Application Passwords feature (introduced in WP 5.6)
add_filter( 'wp_is_application_password_available', '__return_false', PHP_INT_MAX );

// -----------------------------------------------------------------------------
// 3. Block Username Enumeration & Information Disclosure
// -----------------------------------------------------------------------------

// Block ?author=N scanning in frontend URLs
add_action( 'init', function() {
    if ( ! is_admin() && isset( $_REQUEST['author'] ) ) {
        $author_val = sanitize_text_field( wp_unslash( $_REQUEST['author'] ) );
        if ( is_numeric( $author_val ) || ! empty( $author_val ) ) {
            wp_die(
                esc_html__( 'Author archive scanning is forbidden.', 'digilens' ),
                esc_html__( 'Forbidden', 'digilens' ),
                array( 'response' => 403 )
            );
        }
    }
}, 1 );

// Block author archive pages for visitors (set 404)
add_action( 'template_redirect', function() {
    if ( is_author() ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
    }
} );

// Restrict REST API user enumeration (/wp/v2/users)
add_filter( 'rest_endpoints', function( $endpoints ) {
    if ( ! current_user_can( 'list_users' ) ) {
        if ( isset( $endpoints['/wp/v2/users'] ) ) {
            unset( $endpoints['/wp/v2/users'] );
        }
        if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
            unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
        }
    }
    return $endpoints;
} );

// Generic login error message (prevents guessing existing usernames)
add_filter( 'login_errors', function() {
    return esc_html__( 'Thông tin đăng nhập không chính xác.', 'digilens' );
} );

// -----------------------------------------------------------------------------
// 4. Hide WordPress Version Disclosures
// -----------------------------------------------------------------------------

// Remove WP generator tag from headers & feeds
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Remove version query strings (?ver=x.x.x) matching WP core version from scripts & styles
function digilens_remove_wp_version_strings( $src ) {
    if ( ! is_admin() && $src && strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) !== false ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'digilens_remove_wp_version_strings', 9999 );
add_filter( 'script_loader_src', 'digilens_remove_wp_version_strings', 9999 );

// -----------------------------------------------------------------------------
// 5. Security HTTP Headers
// -----------------------------------------------------------------------------
add_action( 'send_headers', function() {
    if ( ! is_admin() && ! headers_sent() ) {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-XSS-Protection: 1; mode=block' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
    }
} );

// -----------------------------------------------------------------------------
// 6. Protect wp-content/uploads via .htaccess (Block PHP Execution)
// -----------------------------------------------------------------------------

/**
 * Ensures .htaccess exists in wp-content/uploads with rules blocking PHP execution.
 */
function digilens_protect_uploads_directory() {
    $upload_dir_info = wp_upload_dir();
    $basedir         = isset( $upload_dir_info['basedir'] ) ? $upload_dir_info['basedir'] : '';

    if ( empty( $basedir ) || ! is_dir( $basedir ) ) {
        return;
    }

    $htaccess_file = trailingslashit( $basedir ) . '.htaccess';
    $marker_start  = '# BEGIN DIGILENS_UPLOAD_PROTECTION';
    $marker_end    = '# END DIGILENS_UPLOAD_PROTECTION';

    $rules  = $marker_start . "\n";
    $rules .= "# Chặn thực thi mọi tệp mã nguồn PHP và script trong thư mục uploads\n";
    $rules .= "<FilesMatch \"(?i)\.(php|phtml|php3|php4|php5|php7|php8|phps|pht|phar|inc|pl|cgi|py|sh|bash)$\">\n";
    $rules .= "    <IfModule mod_authz_core.c>\n";
    $rules .= "        Require all denied\n";
    $rules .= "    </IfModule>\n";
    $rules .= "    <IfModule !mod_authz_core.c>\n";
    $rules .= "        Order deny,allow\n";
    $rules .= "        Deny from all\n";
    $rules .= "    </IfModule>\n";
    $rules .= "</FilesMatch>\n";
    $rules .= $marker_end . "\n";

    if ( file_exists( $htaccess_file ) ) {
        $existing_content = file_get_contents( $htaccess_file );
        if ( false !== $existing_content && strpos( $existing_content, 'DIGILENS_UPLOAD_PROTECTION' ) !== false ) {
            // Already protected
            delete_option( 'digilens_uploads_htaccess_error' );
            return;
        }

        // Append to existing file
        $new_content = $rules . "\n" . ( false !== $existing_content ? $existing_content : '' );
        $written     = @file_put_contents( $htaccess_file, $new_content );
    } else {
        // Create new file
        $written = @file_put_contents( $htaccess_file, $rules );
    }

    if ( false === $written ) {
        update_option( 'digilens_uploads_htaccess_error', sprintf(
            /* translators: %s: path to uploads .htaccess */
            __( 'Không thể tự động ghi file bảo mật tại <code>%s</code>. Vui lòng kiểm tra quyền ghi (permissions) hoặc tạo file thủ công.', 'digilens' ),
            esc_html( $htaccess_file )
        ) );
    } else {
        delete_option( 'digilens_uploads_htaccess_error' );
    }
}

// Check uploads protection on admin init and theme switch
add_action( 'admin_init', 'digilens_protect_uploads_directory' );
add_action( 'after_switch_theme', 'digilens_protect_uploads_directory' );

// Display admin notice if unable to write .htaccess
add_action( 'admin_notices', function() {
    $error_msg = get_option( 'digilens_uploads_htaccess_error' );
    if ( ! empty( $error_msg ) && current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>[DigiLens Security Warning]</strong> ' . wp_kses_post( $error_msg ) . '</p>';
        echo '</div>';
    }
} );
