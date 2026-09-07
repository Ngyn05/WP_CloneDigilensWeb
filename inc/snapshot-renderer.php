<?php
/**
 * DigiLens Snapshot Renderer & HTML Rewriter
 *
 * @package DigiLens
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function digilens_normalize_snapshot_path( $path ) {
    $path = trim( str_replace( '\\', '/', $path ), '/' );
    return preg_replace( '#/+#', '/', $path );
}

function digilens_snapshot_route_from_html( $rel ) {
    $rel = digilens_normalize_snapshot_path( $rel );
    if ( $rel === 'index.htm' || $rel === '' ) { return ''; }
    $route = preg_replace( '#(?:/)?index\.htm$#i', '', $rel );
    $route = preg_replace( '/\.htm$/i', '', $route );
    return trim( $route, '/' );
}

function digilens_snapshot_candidates_for_request() {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
    if ( $path === '' ) { return array( 'index.htm' ); }
    return array(
        $path . '/index.htm',
        $path . '.htm',
        $path,
    );
}

function digilens_snapshot_for_current_request() {
    if ( is_admin() ) { return false; }
    if ( is_single() || is_singular( 'post' ) || is_category() || is_tag() || is_tax() || is_home() || is_archive() || is_search() ) {
        return false;
    }

    $queried_id = get_queried_object_id();
    if ( $queried_id && get_post_type( $queried_id ) === 'post' ) {
        return false;
    }

    // 1. Ưu tiên kiểm tra file snapshot theo đúng đường dẫn REQUEST_URI thực tế
    foreach ( digilens_snapshot_candidates_for_request() as $rel ) {
        $rel = digilens_normalize_snapshot_path( $rel );
        if ( is_file( DIGILENS_SNAPSHOT_DIR . '/' . $rel ) && preg_match( '/\.htm$/i', $rel ) ) {
            return $rel;
        }
    }

    // 2. Fallback về post meta nếu không match trực tiếp từ URI
    if ( $queried_id ) {
        $meta = get_post_meta( $queried_id, '_digilens_snapshot_path', true );
        if ( $meta ) {
            $meta = digilens_normalize_snapshot_path( $meta );
            $file = DIGILENS_SNAPSHOT_DIR . '/' . $meta;
            if ( is_file( $file ) ) { return $meta; }
        }
    }

    return false;
}

function digilens_snapshot_asset_url( $local_path ) {
    $local_path = digilens_normalize_snapshot_path( $local_path );
    return trailingslashit( DIGILENS_SNAPSHOT_URI ) . ltrim( $local_path, '/' );
}

function digilens_rewrite_url( $original, $snapshot_rel ) {
    $trimmed = trim( $original );
    if ( $trimmed === '' || preg_match( '~^(?:#|javascript:|mailto:|tel:|data:)~i', $trimmed ) ) {
        return $original;
    }

    $parts = wp_parse_url( $trimmed );
    $host = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
    $path = isset( $parts['path'] ) ? $parts['path'] : '';
    $query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
    $fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

    $allowed_hosts = array( 'www.digilens.com', 'digilens.com' );
    $is_absolute = false;
    if ( $host !== '' ) {
        if ( ! in_array( $host, $allowed_hosts, true ) ) {
            return $original;
        }
        $is_absolute = true;
    }

    if ( $path === '' || $path === '/' ) {
        return home_url( '/' ) . $query . $fragment;
    }

    $current_dir = dirname( $snapshot_rel );
    if ( $current_dir === '.' ) { $current_dir = ''; }

    if ( strpos( $path, '/' ) === 0 || $is_absolute ) {
        $local_path = ltrim( $path, '/' );
    } else {
        $combined = ( $current_dir !== '' ? $current_dir . '/' : '' ) . $path;
        $segments = array();
        foreach ( explode( '/', str_replace( '\\', '/', $combined ) ) as $segment ) {
            if ( $segment === '' || $segment === '.' ) { continue; }
            if ( $segment === '..' ) { array_pop( $segments ); continue; }
            $segments[] = $segment;
        }
        $local_path = implode( '/', $segments );
    }

    $local_path = digilens_normalize_snapshot_path( $local_path );
    $candidate = DIGILENS_SNAPSHOT_DIR . '/' . $local_path;

    if ( is_dir( $candidate ) && is_file( $candidate . '/index.htm' ) ) {
        $route = digilens_snapshot_route_from_html( $local_path . '/index.htm' );
        return home_url( '/' . ( $route !== '' ? $route . '/' : '' ) ) . $query . $fragment;
    }
    if ( is_file( $candidate ) && preg_match( '/\.htm$/i', $candidate ) ) {
        $route = digilens_snapshot_route_from_html( $local_path );
        return home_url( '/' . ( $route !== '' ? $route . '/' : '' ) ) . $query . $fragment;
    }
    if ( is_file( $candidate ) ) {
        return digilens_snapshot_asset_url( $local_path ) . $query . $fragment;
    }

    // Fallback for assets
    if ( preg_match( '#^(?:wp-content|wp-includes|gtag|forms|@googlemaps|s|af|count)/#i', $local_path ) ) {
        if ( $is_absolute ) { return $original; }
        return 'https://www.digilens.com/' . implode( '/', array_map( 'rawurlencode', explode( '/', $local_path ) ) ) . $query . $fragment;
    }

    // Internal page link not captured locally
    if ( $is_absolute || preg_match( '/(?:index\.htm|\.htm)$/i', $local_path ) ) {
        $route = preg_replace( '#(?:/)?index\.htm$#i', '/', $local_path );
        $route = preg_replace( '/\.htm$/i', '/', $route );
        return home_url( '/' . ltrim( $route, '/' ) ) . $query . $fragment;
    }
    return $original;
}

function digilens_replace_entire_header( $html ) {
    if ( function_exists( 'digilens_render_master_header' ) ) {
        $master_header = digilens_render_master_header();
        $pattern = '#<header\b[^>]*id=["\']masthead["\'][^>]*>.*?</header>#is';
        if ( preg_match( $pattern, $html ) ) {
            return preg_replace( $pattern, $master_header, $html, 1 );
        }
    }
    return $html;
}

function digilens_build_complete_footer() {
    $logo_url = function_exists( 'digilens_snapshot_asset_url' )
        ? digilens_snapshot_asset_url( 'wp-content/uploads/2021/03/LRG-Logo-White-Full-MAR21.png' )
        : get_template_directory_uri() . '/snapshot/wp-content/uploads/2021/03/LRG-Logo-White-Full-MAR21.png';
    $home_url = esc_url( home_url( '/' ) );

    return '<footer class="digilens-master-footer">
        <div class="dl-f-container">
            <!-- 1. Header Bar: Brand + Slogan -->
            <div class="dl-f-header">
                <div class="dl-f-brand">
                    <a href="' . $home_url . '" class="dl-f-logo-link" title="DigiLens Việt Nam">
                        <img src="' . esc_url( $logo_url ) . '" alt="DigiLens" class="dl-f-logo-img">
                        <span class="dl-f-vn-badge">VIỆT NAM</span>
                    </a>
                </div>
                <div class="dl-f-slogan">
                    <p>Công nghệ ống dẫn sóng quang học đột phá, nâng cao trải nghiệm thực tế ảo tăng cường (AR/XR) thế hệ mới.</p>
                </div>
            </div>

            <!-- 2. Customer Support Section (3 Cards) -->
            <div class="dl-f-support-section">
                <div class="dl-f-support-title">HỖ TRỢ KHÁCH HÀNG</div>
                <div class="dl-f-support-grid">
                    <!-- Card 1: Hà Nội -->
                    <div class="dl-f-card">
                        <div class="dl-f-card-header">
                            <div class="dl-f-icon-box">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            </div>
                            <span class="dl-f-card-badge">MIỀN BẮC</span>
                        </div>
                        <h4 class="dl-f-card-title">Văn phòng Hà Nội</h4>
                        <p class="dl-f-card-address">226 Đường Láng, Phường Thịnh Quang,<br>Quận Đống Đa, Hà Nội</p>
                        <div class="dl-f-card-hotline">
                            <a href="tel:02473053268" class="dl-f-phone-link">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                024.7305.3268
                            </a>
                        </div>
                    </div>

                    <!-- Card 2: Hồ Chí Minh -->
                    <div class="dl-f-card">
                        <div class="dl-f-card-header">
                            <div class="dl-f-icon-box">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            </div>
                            <span class="dl-f-card-badge">MIỀN NAM</span>
                        </div>
                        <h4 class="dl-f-card-title">Văn phòng Hồ Chí Minh</h4>
                        <p class="dl-f-card-address">137 Hòa Hưng, Phường Hòa Hưng,<br>TP. Hồ Chí Minh</p>
                        <div class="dl-f-card-hotline">
                            <a href="tel:02873053268" class="dl-f-phone-link">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                028.7305.3268
                            </a>
                        </div>
                    </div>

                    <!-- Card 3: Hotline Tổng Đài -->
                    <div class="dl-f-card dl-f-card-cta">
                        <div class="dl-f-card-header">
                            <div class="dl-f-icon-box">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>
                            </div>
                            <span class="dl-f-card-badge">TƯ VẤN 24/7</span>
                        </div>
                        <h4 class="dl-f-card-title">Hotline Tổng Đài</h4>
                        <div class="dl-f-cta-wrapper">
                            <a href="tel:1900638400" class="dl-f-btn-hotline">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.44-5.15-3.75-6.59-6.59l1.97-1.57c.28-.28.36-.67.25-1.02A11.36 11.36 0 0 1 9 4.38c0-.55-.45-1-1-1H4.5c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.5c0-.55-.45-1-1-1z"/></svg>
                                1900.63.8400
                            </a>
                            <span class="dl-f-cta-subtitle">Hỗ trợ và tư vấn mọi lúc, mọi nơi</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Navigation Links: 3 Cột căn giữa (Desktop) & Sổ Accordion (Mobile) -->
            <div class="dl-f-nav-grid">
                <div class="dl-f-nav-col">
                    <h5 class="dl-f-nav-title" role="button" tabindex="0">
                        <span>CÔNG NGHỆ</span>
                        <svg class="dl-f-acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </h5>
                    <ul class="dl-f-nav-list">
                        <li><a href="' . esc_url( home_url( '/waveguides/' ) ) . '">Ống dẫn sóng</a></li>
                        <li><a href="' . esc_url( home_url( '/argo/' ) ) . '">ARGO™</a></li>
                    </ul>
                </div>

                <div class="dl-f-nav-col">
                    <h5 class="dl-f-nav-title" role="button" tabindex="0">
                        <span>GIỚI THIỆU</span>
                        <svg class="dl-f-acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </h5>
                    <ul class="dl-f-nav-list">
                        <li><a href="' . esc_url( home_url( '/company/' ) ) . '">Công ty</a></li>
                        <li><a href="' . esc_url( home_url( '/media/' ) ) . '">Trung tâm truyền thông</a></li>
                        <li><a href="' . esc_url( home_url( '/careers/' ) ) . '">Tuyển dụng</a></li>
                        <li><a href="' . esc_url( home_url( '/partners/' ) ) . '">Đối tác</a></li>
                    </ul>
                </div>

                <div class="dl-f-nav-col">
                    <h5 class="dl-f-nav-title" role="button" tabindex="0">
                        <span>PHÁP LÝ</span>
                        <svg class="dl-f-acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </h5>
                    <ul class="dl-f-nav-list">
                        <li><a href="' . esc_url( home_url( '/terms-of-use/' ) ) . '">Điều khoản sử dụng</a></li>
                        <li><a href="' . esc_url( home_url( '/argo/terms-of-use/' ) ) . '">Điều khoản sử dụng ARGO</a></li>
                        <li><a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">Chính sách quyền riêng tư</a></li>
                        <li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Liên hệ</a></li>
                    </ul>
                </div>
            </div>

            <script>
            (function() {
                function initFooterAccordion() {
                    var titles = document.querySelectorAll(".dl-f-nav-col .dl-f-nav-title");
                    titles.forEach(function(title) {
                        if (title._accBound) return;
                        title._accBound = true;
                        title.addEventListener("click", function(e) {
                            if (window.innerWidth <= 767) {
                                e.preventDefault();
                                var parent = this.closest(".dl-f-nav-col");
                                if (parent) {
                                    parent.classList.toggle("is-open");
                                }
                            }
                        });
                    });
                }
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", initFooterAccordion);
                } else {
                    initFooterAccordion();
                }
            })();
            </script>

            <!-- 4. Bottom Row: Copyright + Social Icons -->
            <div class="dl-f-bottom-row">
                <div class="dl-f-copyright">
                    &copy; ' . gmdate( 'Y' ) . ' DigiLens Inc. DigiLens Việt Nam. Tất cả quyền được bảo lưu.
                </div>
                <div class="dl-f-socials">
                    <a href="https://twitter.com/DigiLensInc" target="_blank" rel="noopener" aria-label="X Twitter" class="dl-f-social-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://www.facebook.com/digilensinc/" target="_blank" rel="noopener" aria-label="Facebook" class="dl-f-social-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="https://www.linkedin.com/company/digilensinc/" target="_blank" rel="noopener" aria-label="LinkedIn" class="dl-f-social-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/@DigiLensInc" target="_blank" rel="noopener" aria-label="YouTube" class="dl-f-social-btn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>';
}

function digilens_replace_entire_footer( $html ) {
    $new_footer = digilens_build_complete_footer();

    // Match the outer Elementor location footer container
    $pattern = '#<footer\b[^>]*class=["\'][^"\']*elementor-location-footer[^"\']*["\'][^>]*>.*?</footer>\s*</footer>#is';
    if ( preg_match( $pattern, $html ) ) {
        return preg_replace( $pattern, $new_footer, $html, 1 );
    }

    // Fallback: match any outermost <footer class="elementor...
    $fallback_pattern = '#<footer\b[^>]*class=["\'][^"\']*elementor-24518[^"\']*["\'][^>]*>.*?</footer>\s*</footer>#is';
    if ( preg_match( $fallback_pattern, $html ) ) {
        return preg_replace( $fallback_pattern, $new_footer, $html, 1 );
    }

    return $html;
}

function digilens_strip_cookie_consent( $html ) {
    $html = preg_replace( '#<!--googleoff:\s*all-->\s*<div[^>]*id=["\']cookie-law-info-bar["\'].*?<!--googleon:\s*all-->#is', '', $html );
    $html = preg_replace( '#<div\b[^>]*id=["\']cookie-law-info-bar["\'][^>]*>.*?</div>\s*</span>\s*</div>#is', '', $html );
    $html = preg_replace( '#<div\b[^>]*id=["\']cookie-law-info-again["\'][^>]*>.*?</div>#is', '', $html );
    $html = preg_replace( '#<div\b[^>]*id=["\']cliSettingsPopup["\'][^>]*>.*?(?:<div class=["\']cli-modal-backdrop[^>]*></div>\s*)+#is', '', $html );
    $html = preg_replace( '#<div\b[^>]*class=["\'][^"\']*cli-modal-backdrop[^"\']*["\'][^>]*></div>#is', '', $html );

    $html = preg_replace( '#<link\b[^>]*id=["\']cookie-law-info[^"\'\s>]*["\'][^>]*>#is', '', $html );
    $html = preg_replace( '#<script\b[^>]*id=["\']cookie-law-info[^"\'\s>]*["\'][^>]*>.*?</script>#is', '', $html );
    $html = preg_replace( '#<script\b[^>]*src=["\'][^"\']*cookie-law-info[^"\']*["\'][^>]*>\s*</script>#is', '', $html );

    $html = preg_replace( '#<script\b[^>]*>\s*var\s+Cli_Data\s*=.*?</script>#is', '', $html );
    $html = preg_replace( '#<script\b[^>]*>\s*var\s+cli_cookiebar_settings\s*=.*?</script>#is', '', $html );
    $html = preg_replace( '#<script\b[^>]*type=["\']speculationrules["\'][^>]*>[\s\S]*?</script>#is', '', $html );
    $html = preg_replace( '#<script\b[^>]*>\s*window\._wpemojiSettings[\s\S]*?</script>#is', '', $html );

    return $html;
}

function digilens_native_form_markup( $type = 'contact' ) {
    $sent = isset( $_GET['dl_sent'] ) ? sanitize_key( wp_unslash( $_GET['dl_sent'] ) ) : '';
    $notice = '';
    if ( $sent === $type ) { $notice = '<div class="digilens-form-notice">Cảm ơn bạn. Yêu cầu đã được gửi thành công.</div>'; }

    $action = esc_url( admin_url( 'admin-post.php' ) );
    $nonce = wp_nonce_field( 'digilens_' . $type, '_dl_nonce', true, false );
    if ( $type === 'newsletter' ) {
        return $notice . '<form class="digilens-native-form" method="post" action="' . $action . '">' . $nonce .
            '<input type="hidden" name="action" value="digilens_newsletter">' .
            '<label for="dl-newsletter-email">Email</label><input id="dl-newsletter-email" type="email" name="email" required autocomplete="email" aria-required="true">' .
            '<div class="dl-hp" aria-hidden="true"><label for="dl-newsletter-website">Không điền trường này</label><input id="dl-newsletter-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>' .
            '<button type="submit">Đăng ký</button></form>';
    }
    return $notice . '<form class="digilens-native-form" method="post" action="' . $action . '">' . $nonce .
        '<input type="hidden" name="action" value="digilens_contact">' .
        '<div class="dl-row"><div><label for="dl-contact-name">Họ và tên</label><input id="dl-contact-name" type="text" name="name" required autocomplete="name" aria-required="true"></div><div><label for="dl-contact-email">Email</label><input id="dl-contact-email" type="email" name="email" required autocomplete="email" aria-required="true"></div></div>' .
        '<div class="dl-row"><div><label for="dl-contact-company">Công ty</label><input id="dl-contact-company" type="text" name="company" autocomplete="organization"></div><div><label for="dl-contact-phone">Số điện thoại</label><input id="dl-contact-phone" type="tel" name="phone" autocomplete="tel"></div></div>' .
        '<label for="dl-contact-message">Nội dung</label><textarea id="dl-contact-message" name="message" required aria-required="true"></textarea>' .
        '<div class="dl-hp" aria-hidden="true"><label for="dl-contact-website">Không điền trường này</label><input id="dl-contact-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>' .
        '<button type="submit">Gửi yêu cầu</button></form>';
}

function digilens_mark_duplicate_headings( $html ) {
    $seen = array();
    return preg_replace_callback( '#<h([1-6])\b([^>]*)>([\s\S]*?)</h\1>#i', function ( $match ) use ( &$seen ) {
        $text = trim( preg_replace( '#\s+#u', ' ', wp_strip_all_tags( html_entity_decode( $match[3], ENT_QUOTES, 'UTF-8' ) ) ) );
        if ( $text === '' ) { return $match[0]; }
        $key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
        if ( ! isset( $seen[ $key ] ) ) { $seen[ $key ] = true; return $match[0]; }
        $attrs = preg_replace( '#\sclass=("|\')(.*?)\1#i', '', $match[2] );
        return '<div' . $attrs . ' class="dl-responsive-heading-clone" aria-hidden="true" role="presentation">' . $match[3] . '</div>';
    }, $html );
}

function digilens_replace_hubspot_forms( $html ) {
    $forms = array(
        'c5265497-b30e-4bec-ab39-f832063f1401' => 'contact',
        'c43fead5-e9a3-4760-a56b-8d8b68ba70c4' => 'newsletter',
        'e6e8e8cc-c724-4c02-8710-12c785ae0892' => 'contact',
    );
    foreach ( $forms as $id => $type ) {
        $pattern = '#<script>\s*window\.hsFormsOnReady.*?formId:\s*["\']' . preg_quote( $id, '#' ) . '["\'].*?</script>\s*<div class="hbspt-form"[^>]*></div>#is';
        $html = preg_replace( $pattern, digilens_native_form_markup( $type ), $html );
    }
    return $html;
}

function digilens_replace_contact_section( $html ) {
    $pattern = '#<section\b[^>]*data-id=["\']40b9a0be["\'][^>]*>.*?</section>#is';

    $offices_html = '<section class="elementor-section dl-offices-section" data-id="40b9a0be">
        <div class="dl-offices-container">
            <!-- Card 1: Hà Nội -->
            <div class="dl-office-card">
                <div class="dl-office-top">
                    <div class="dl-office-icon-box">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <div class="dl-office-badge">MIỀN BẮC</div>
                </div>
                <h3 class="dl-office-title">Văn phòng Hà Nội</h3>
                <div class="dl-office-address">226 Đường Láng, Phường Thịnh Quang, Quận Đống Đa, Hà Nội</div>
                <a href="tel:02473053268" class="dl-office-phone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span>024.7305.3268</span>
                </a>
                <div class="dl-office-map-container">
                    <iframe 
                        src="https://maps.google.com/maps?q=226%20%C4%90%C6%B0%E1%BB%9Dng%20L%C3%A1ng%2C%20Th%E1%BB%8Bnh%20Quang%2C%20%C4%90%E1%BB%91ng%20%C4%90a%2C%20H%C3%A0%20N%E1%BB%99i&amp;t=m&amp;z=16&amp;output=embed&amp;iwloc=near" 
                        loading="lazy" 
                        title="Bản đồ Văn phòng Hà Nội"
                        class="dl-office-map-iframe">
                    </iframe>
                </div>
            </div>

            <!-- Card 2: Hồ Chí Minh -->
            <div class="dl-office-card">
                <div class="dl-office-top">
                    <div class="dl-office-icon-box">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <div class="dl-office-badge">MIỀN NAM</div>
                </div>
                <h3 class="dl-office-title">Văn phòng Hồ Chí Minh</h3>
                <div class="dl-office-address">137 Hòa Hưng, Phường Hòa Hưng, TP. Hồ Chí Minh</div>
                <a href="tel:02873053268" class="dl-office-phone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span>028.7305.3268</span>
                </a>
                <div class="dl-office-map-container">
                    <iframe 
                        src="https://maps.google.com/maps?q=137%20H%C3%B2a%20H%C6%B0ng%2C%20Ph%C6%B0%E1%BB%9Dng%20H%C3%B2a%20H%C6%B0ng%2C%20TP.%20H%E1%BB%93%20Ch%C3%AD%20Minh&amp;t=m&amp;z=16&amp;output=embed&amp;iwloc=near" 
                        loading="lazy" 
                        title="Bản đồ Văn phòng Hồ Chí Minh"
                        class="dl-office-map-iframe">
                    </iframe>
                </div>
            </div>
        </div>
    </section>';

    if ( preg_match( $pattern, $html ) ) {
        $html = preg_replace( $pattern, $offices_html, $html, 1 );
    }

    return $html;
}

function digilens_fix_pagination( $html, $snapshot_rel ) {
    $script = '<script>
    (function() {
        window.addEventListener("click", function(e) {
            var link = e.target ? (e.target.closest ? e.target.closest(".elementor-pagination a, .ast-pagination a, a.page-numbers") : null) : null;
            if (link) {
                var href = link.getAttribute("href");
                if (href && href !== "#" && !href.startsWith("javascript:")) {
e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    window.location.href = href;
                }
            }
        }, true);
    })();
    </script>';

    if ( strpos( $html, 'elementor-pagination' ) !== false || strpos( $html, 'page-numbers' ) !== false ) {
        $html = str_replace( '</body>', $script . "\n</body>", $html );
    }

    return $html;
}

function digilens_get_card_thumbnail( $post_id, $title = '' ) {
    // Verified pools of real images physically existing on disk
    $pool_pr = array(
        'wp-content/uploads/2025/10/PR-Next-Altoura-Frontline-01-300x157.png',
        'wp-content/uploads/2025/09/PR-Kognitiv-Spark-RemoteSpark-300x157.png',
        'wp-content/uploads/2025/06/PR_DG_TAQ_SOC-300x157.jpg',
        'wp-content/uploads/2025/05/PR-DGL_RMBR_postimage-300x157.jpg',
        'wp-content/uploads/2024/09/DL-Avegant-PR-graphic-090424-01.png',
        'wp-content/uploads/2024/09/2024-AUG-HL-NVIDIA-HL-Image-03-1800px.png',
        'wp-content/uploads/2024/07/taqtile-announces-manifest-for-argo.png',
        'wp-content/uploads/2023/05/Tactile-PR-Social-Image.png',
        'wp-content/uploads/2023/05/Mojo-DL-Announcement-e1685481564178-1024x678.png',
        'wp-content/uploads/2021/05/about-dv1-separated-768x597.jpg',
        'wp-content/uploads/2020/05/designv1-exploded-768x397.jpg',
        'wp-content/uploads/2023/08/DL-Argo-080923-01.jpg',
    );

    $pool_blog = array(
        'wp-content/uploads/2023/08/DL-Argo-080923-01.jpg',
        'wp-content/uploads/2022/07/Thought-Piece-01.png',
        'wp-content/uploads/2022/07/Throught-Piece-02.png',
        'wp-content/uploads/2022/07/Thought-Piece-03.png',
        'wp-content/uploads/2022/07/Thought-Piece-04.png',
        'wp-content/uploads/2021/05/dv1-smartglasses-hero-v3-1024x559.jpg',
        'wp-content/uploads/2023/04/DL-SRG-Social-1919-x-886-031023-01.png',
        'wp-content/uploads/2023/05/DL-Spaces-1200x1200-051523-03.png',
        'wp-content/uploads/2024/07/taqtile-announces-manifest-for-argo.png',
        'wp-content/uploads/2023/03/DL-Industry-Social-031623-1919-x-886.png',
    );

    $pool_media = array(
        'wp-content/uploads/2025/03/gsw-trinet-digilens-1024x533.png',
        'wp-content/uploads/2023/05/Mojo-DL-Announcement-e1685481564178-1024x678.png',
        'wp-content/uploads/2023/05/Tactile-PR-Social-Image.png',
        'wp-content/uploads/2023/05/wisear-graphic-1024x707.jpeg',
        'wp-content/uploads/2023/03/DL-Industry-Social-031623-1919-x-886.png',
        'wp-content/uploads/2023/08/DL-Argo-080923-01.jpg',
    );

    $pool_news = array(
        'wp-content/uploads/2023/01/forbes-2-logo-png-transparent-300x116.png',
        'wp-content/uploads/2023/01/forbes-2-logo-png-transparent-1024x397.png',
    );

    // 1. Check custom database meta or WordPress featured image
    $thumb_id = get_post_thumbnail_id( $post_id );
    if ( $thumb_id ) {
        $url = wp_get_attachment_image_url( $thumb_id, 'medium_large' );
        if ( $url ) { return $url; }
    }

    $meta_img = get_post_meta( $post_id, '_digilens_featured_image_url', true );
    if ( $meta_img && strpos( $meta_img, 'http' ) === 0 && strpos( $meta_img, 'staging.digilens' ) === false ) {
        return $meta_img;
    }

    // 2. Extract first image from post content if it exists locally on disk
    $content = get_post_field( 'post_content', $post_id );
    if ( $content && preg_match( '#<img[^>]+src=["\']([^"\']+)["\']#i', $content, $cm ) ) {
        $csrc = trim( $cm[1] );
        if ( ! preg_match( '#(?:icon|spacer|emoji|blank|staging\.digilens)#i', $csrc ) ) {
            $rel = ltrim( str_replace( array( home_url(), 'http://digilens-vn.local', 'https://www.digilens.com', 'http://www.digilens.com', '../', 'wp-content/themes/digilens-theme/snapshot/' ), '', $csrc ), '/' );
            if ( is_file( DIGILENS_SNAPSHOT_DIR . '/' . $rel ) ) {
                return trailingslashit( DIGILENS_SNAPSHOT_URI ) . $rel;
            }
        }
    }

    // 3. Match unique image by post slug if it exists on disk
    $slug = get_post_field( 'post_name', $post_id );
    $known_slug_images = array(
        'eradex-argospower-fow'                              => 'wp-content/uploads/2023/08/DL-Argo-080923-01.jpg',
        'eradex-results'                                     => 'wp-content/uploads/2022/07/Thought-Piece-01.png',
        'evaluating-the-impact-of-ar-on-manufacturing-training' => 'wp-content/uploads/2022/07/Throught-Piece-02.png',
        'evaluating-ar-impact-on-manufacturing-training'     => 'wp-content/uploads/2022/07/Throught-Piece-02.png',
        'niche-to-necessity-market-readiness'                => 'wp-content/uploads/2022/07/Thought-Piece-03.png',
        'ar-niche-to-necessary'                              => 'wp-content/uploads/2022/07/Thought-Piece-03.png',
        'work-first-play-later'                              => 'wp-content/uploads/2023/03/DL-Industry-Social-031623-1919-x-886.png',
        'visualizing-the-future-of-head-worn'                => 'wp-content/uploads/2021/05/dv1-smartglasses-hero-v3-1024x559.jpg',
        'xr-will-be-next-big-thing'                          => 'wp-content/uploads/2022/07/Thought-Piece-04.png',
        'xr-will-shape-the-future-of-work'                   => 'wp-content/uploads/2023/04/DL-SRG-Social-1919-x-886-031023-01.png',
        'building-sota-headworn-company'                     => 'wp-content/uploads/2021/05/about-dv1-separated-768x597.jpg',
        'digilens-is-making-strides-in-extending-reality-and-augmenting-life-in-2022' => 'wp-content/uploads/2023/05/DL-Spaces-1200x1200-051523-03.png',
        'pr-argo-next'                                       => 'wp-content/uploads/2025/10/PR-Next-Altoura-Frontline-01-300x157.png',
        'pr-remotespark'                                     => 'wp-content/uploads/2025/09/PR-Kognitiv-Spark-RemoteSpark-300x157.png',
        'pr-digisaastaq'                                     => 'wp-content/uploads/2025/06/PR_DG_TAQ_SOC-300x157.jpg',
        'pr-dglxramblr-2'                                    => 'wp-content/uploads/2025/05/PR-DGL_RMBR_postimage-300x157.jpg',
        'pr-avegantc304th'                                   => 'wp-content/uploads/2024/09/DL-Avegant-PR-graphic-090424-01.png',
        'pr-googlecloud0624'                                 => 'wp-content/uploads/2024/09/2024-AUG-HL-NVIDIA-HL-Image-03-1800px.png',
        'pr-cotupgrade'                                      => 'wp-content/uploads/2024/07/taqtile-announces-manifest-for-argo.png',
        'pr-msftintune'                                      => 'wp-content/uploads/2023/08/DL-Argo-080923-01.jpg',
        'pr-kaynestech'                                      => 'wp-content/uploads/2020/05/designv1-exploded-768x397.jpg',
        'pr-argo-snapdragon-spaces'                          => 'wp-content/uploads/2023/05/Mojo-DL-Announcement-e1685481564178-1024x678.png',
        'taqtile-digilens'                                   => 'wp-content/uploads/2023/05/Tactile-PR-Social-Image.png',
        'wisear-announce-partnership'                        => 'wp-content/uploads/2023/05/wisear-graphic-1024x707.jpeg',
        'mojovision-partnership'                             => 'wp-content/uploads/2023/05/Mojo-DL-Announcement-e1685481564178-1024x678.png',
        'digilens-inc-introduces-argo-2'                     => 'wp-content/uploads/2023/08/DL-Argo-080923-01.jpg',
        'golden-state-warriors-trinet-va-digilens'           => 'wp-content/uploads/2025/03/gsw-trinet-digilens-1024x533.png',
        'golden-state-warriors-trinet-va-digilens-hop-tac-thuc-day-do' => 'wp-content/uploads/2025/03/gsw-trinet-digilens-1024x533.png',
        'snapdragon-ai-xr'                                   => 'wp-content/uploads/2023/05/Mojo-DL-Announcement-e1685481564178-1024x678.png',
        'snapdragon-tich-hop-ai-vao-thiet-bi-xr'             => 'wp-content/uploads/2023/05/Mojo-DL-Announcement-e1685481564178-1024x678.png',
        'area-webinar-ar-industrial'                         => 'wp-content/uploads/2023/05/Tactile-PR-Social-Image.png',
        'tro-chuyen-cung-area-nhung-bien-gioi-moi-cho-digilens-va-cac' => 'wp-content/uploads/2023/05/Tactile-PR-Social-Image.png',
        'stanford-ewear-symposium'                           => 'wp-content/uploads/2023/05/wisear-graphic-1024x707.jpeg',
        'hoi-thao-stanford-ewear-ong-dan-song-holographic-digilens-la' => 'wp-content/uploads/2023/05/wisear-graphic-1024x707.jpeg',
        'awe-23-kien-tao-thuc-te-moi-voi-snapdragon-ngay-hom-nay' => 'wp-content/uploads/2023/08/DL-Argo-080923-01.jpg',
        'awe-23-kinh-thong-minh-digilens-argo-cach-phat-trien-nen-tan' => 'wp-content/uploads/2021/05/dv1-smartglasses-hero-v3-1024x559.jpg',
        'video-hoi-nghi-thuong-dinh-chinh-phu-the-gioi-the-gioi-ao-va' => 'wp-content/uploads/2021/05/about-dv1-separated-768x597.jpg',
        'the-ar-show-tao-ong-dan-song-de-hien-thuc-hoa-kinh-thong-min' => 'wp-content/uploads/2023/03/DL-Industry-Social-031623-1919-x-886.png',
        'video-nguyen-ly-hoat-dong-cua-ong-dan-song-digilens' => 'wp-content/uploads/2023/05/Mojo-DL-Announcement-e1685481564178-1024x678.png',
        'awe-22-kien-tao-nen-tang-xr-cho-cong-nghiep-nhe'    => 'wp-content/uploads/2024/09/DL-Avegant-PR-graphic-090424-01.png',
    );

    if ( isset( $known_slug_images[ $slug ] ) && is_file( DIGILENS_SNAPSHOT_DIR . '/' . $known_slug_images[ $slug ] ) ) {
        return trailingslashit( DIGILENS_SNAPSHOT_URI ) . $known_slug_images[ $slug ];
    }

    // 4. Check category specific rotating pool
    $cats = wp_get_post_categories( $post_id, array( 'fields' => 'slugs' ) );
    if ( is_array( $cats ) ) {
        if ( in_array( 'news', $cats, true ) ) {
            $pick = $pool_news[ $post_id % count( $pool_news ) ];
            return trailingslashit( DIGILENS_SNAPSHOT_URI ) . $pick;
        }
        if ( in_array( 'media', $cats, true ) ) {
            $pick = $pool_media[ $post_id % count( $pool_media ) ];
            return trailingslashit( DIGILENS_SNAPSHOT_URI ) . $pick;
        }
        if ( in_array( 'blogs', $cats, true ) ) {
            $pick = $pool_blog[ $post_id % count( $pool_blog ) ];
            return trailingslashit( DIGILENS_SNAPSHOT_URI ) . $pick;
        }
        if ( in_array( 'press-release', $cats, true ) ) {
            $pick = $pool_pr[ $post_id % count( $pool_pr ) ];
            return trailingslashit( DIGILENS_SNAPSHOT_URI ) . $pick;
        }
    }

    // Guaranteed fallback
    $pick = $pool_blog[ $post_id % count( $pool_blog ) ];
    return trailingslashit( DIGILENS_SNAPSHOT_URI ) . $pick;
}

function digilens_inject_dynamic_media_widgets( $html ) {
    // Only apply if the page contains one of the media widgets
    if ( strpos( $html, 'data-id="e945b06"' ) === false && strpos( $html, 'data-id="c3ac88f"' ) === false ) {
        return $html;
    }

    $widgets = array(
        array(
            'id'          => 'e945b06',
            'cat_slug'    => 'press-release',
            'count'       => 6,
            'btn_text'    => 'ĐỌC THÊM »',
            'paged_param' => 'pr_paged',
        ),
        array(
            'id'          => 'c3ac88f',
            'cat_slug'    => 'blogs',
            'count'       => 4,
            'btn_text'    => 'ĐỌC THÊM »',
            'paged_param' => 'blog_paged',
        ),
        array(
            'id'          => 'ba15212',
            'cat_slug'    => 'media',
            'count'       => 3,
            'btn_text'    => 'XEM THÊM »',
            'paged_param' => 'media_paged',
        ),
        array(
            'id'          => 'fad78d8',
            'cat_slug'    => 'news',
            'count'       => 4,
            'btn_text'    => 'ĐỌC THÊM »',
            'paged_param' => 'news_paged',
        ),
    );

    foreach ( $widgets as $cfg ) {
        $wid         = $cfg['id'];
        $cat_slug    = $cfg['cat_slug'];
        $count       = $cfg['count'];
        $btn_text    = $cfg['btn_text'];
        $paged_param = $cfg['paged_param'];

        $paged = 1;
        if ( isset( $_GET[ $paged_param ] ) ) {
            $paged = max( 1, (int) $_GET[ $paged_param ] );
        } elseif ( $cat_slug === 'press-release' ) {
            $uri_path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
            if ( preg_match( '#^media/(\d+)#i', $uri_path, $pm ) ) {
                $paged = max( 1, (int) $pm[1] );
            }
        }

        $query = new WP_Query( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'category_name'  => $cat_slug,
            'posts_per_page' => $count,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( ! $query->have_posts() && $query->max_num_pages > 0 && $paged > $query->max_num_pages ) {
            $paged = $query->max_num_pages;
            $query = new WP_Query( array(
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'category_name'  => $cat_slug,
                'posts_per_page' => $count,
                'paged'          => $paged,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );
        }

        if ( ! $query->have_posts() ) {
            continue;
        }

        $cards_html = '';
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id   = get_the_ID();
            $title     = get_the_title();
            $permalink = get_permalink();

            $ext_url = get_post_meta( $post_id, '_digilens_external_url', true );
            $card_link = $ext_url ? $ext_url : $permalink;
            $target_attr = $ext_url ? ' target="_blank" rel="noopener noreferrer"' : '';

            $feat_img = digilens_get_card_thumbnail( $post_id, $title );
            $date_str = get_the_date( 'j \t\h\á\n\g n, Y' );

            $img_tag = '<a class="elementor-post__thumbnail__link" href="' . esc_url( $card_link ) . '" tabindex="-1"' . $target_attr . '>'
                     . '<div class="elementor-post__thumbnail">'
                     . '<img alt="' . esc_attr( $title ) . '" class="attachment-medium size-medium" decoding="async" height="157" src="' . esc_url( $feat_img ) . '" width="300" />'
                     . '</div>'
                     . '</a>';

            $cards_html .= '<article class="elementor-post elementor-grid-item post-' . esc_attr( $post_id ) . ' post type-post status-publish format-standard has-post-thumbnail hentry category-' . esc_attr( $cat_slug ) . '" role="listitem">'
                         . '<div class="elementor-post__card">'
                         . $img_tag
                         . '<div class="elementor-post__avatar"></div>'
                         . '<div class="elementor-post__text">'
                         . '<h3 class="elementor-post__title">'
                         . '<a href="' . esc_url( $card_link ) . '"' . $target_attr . '>' . esc_html( $title ) . '</a>'
                         . '</h3>'
                         . '<a aria-label="' . esc_attr( $btn_text . ' ' . $title ) . '" class="elementor-post__read-more" href="' . esc_url( $card_link ) . '" tabindex="-1"' . $target_attr . '>'
                         . esc_html( $btn_text )
                         . '</a>'
                         . '</div>'
                         . '<div class="elementor-post__meta-data">'
                         . '<span class="elementor-post-date">' . esc_html( $date_str ) . '</span>'
                         . '</div>'
                         . '</div>'
                         . '</article>';
        }
        wp_reset_postdata();

        $pag_html = '';
        if ( $query->max_num_pages > 1 ) {
            $base_url = home_url( '/media/' );
            $pag_links = paginate_links( array(
                'base'      => add_query_arg( $paged_param, '%#%', $base_url ),
                'format'    => '',
                'current'   => $paged,
                'total'     => $query->max_num_pages,
                'prev_text' => '« Trước',
                'next_text' => 'Tiếp »',
                'type'      => 'plain',
            ) );
            if ( $pag_links ) {
                $pag_html = '<nav class="elementor-pagination" aria-label="Phân trang">' . $pag_links . '</nav>';
            }
        }

        $wid_needle = 'data-id="' . $wid . '"';
        $w_idx = strpos( $html, $wid_needle );
        if ( $w_idx === false ) {
            continue;
        }

        $c_tag = '<div class="elementor-widget-container">';
        $c_idx = strpos( $html, $c_tag, $w_idx );
        if ( $c_idx === false ) {
            continue;
        }

        $content_start = $c_idx + strlen( $c_tag );
        $chunk = substr( $html, $content_start, 25000 );

        $nav_idx = strpos( $chunk, '<nav' );
        if ( $nav_idx !== false && strpos( substr( $chunk, $nav_idx, 300 ), 'elementor-pagination' ) !== false ) {
            $nav_end = strpos( $chunk, '</nav>', $nav_idx );
            $content_end = ( $nav_end !== false ) ? ( $content_start + $nav_end + strlen( '</nav>' ) ) : ( $content_start + strlen( $chunk ) );
        } else {
            $last_art = strrpos( $chunk, '</article>' );
            if ( $last_art !== false ) {
                $div_after = strpos( $chunk, '</div>', $last_art );
                $content_end = ( $div_after !== false ) ? ( $content_start + $div_after + strlen( '</div>' ) ) : ( $content_start + strlen( $chunk ) );
            } else {
                $content_end = $content_start;
            }
        }

        $new_inner = "\n" . '<div class="elementor-posts-container elementor-posts elementor-posts--skin-cards elementor-grid" role="list">'
                   . $cards_html
                   . '</div>'
                   . "\n"
                   . $pag_html
                   . "\n";

        // Perform exact slice replacement with zero broken tags
        $html = substr( $html, 0, $content_start ) . $new_inner . substr( $html, $content_end );
    }

    return $html;
}

/**
 * Automatically inject iframe players for all Elementor video widgets (YouTube, Vimeo, etc.)
 */
function digilens_inject_embedded_videos( $html ) {
    return preg_replace_callback(
        '#<div\b([^>]*\bclass=["\'][^"\']*elementor-widget-video[^"\']*["\'][^>]*data-settings=(["\'])(.*?)\2[^>]*)>([\s\S]*?)</div>\s*</div>\s*</div>#i',
        function( $matches ) {
            $widget_open = $matches[1];
            $raw_settings = html_entity_decode( $matches[3], ENT_QUOTES, 'UTF-8' );
            $inner_content = $matches[4];

            $settings = json_decode( $raw_settings, true );
            if ( ! is_array( $settings ) ) {
                return $matches[0];
            }

            $youtube_url = isset( $settings['youtube_url'] ) ? trim( $settings['youtube_url'] ) : '';
            $vimeo_url   = isset( $settings['vimeo_url'] ) ? trim( $settings['vimeo_url'] ) : '';

            $player_html = '';

            if ( ! empty( $youtube_url ) ) {
                if ( preg_match( '#(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})#i', $youtube_url, $ym ) ) {
                    $vid_id   = $ym[1];
                    $autoplay = ! empty( $settings['autoplay'] ) && $settings['autoplay'] === 'yes' ? 1 : 0;
                    $loop     = ! empty( $settings['loop'] ) && $settings['loop'] === 'yes' ? 1 : 0;
                    $controls = ! isset( $settings['controls'] ) || $settings['controls'] === 'yes' ? 1 : 0;

                    $query_params = array(
                        'controls'       => $controls,
                        'rel'            => 0,
                        'playsinline'    => 1,
                        'modestbranding' => 1,
                        'enablejsapi'    => 1,
                    );
                    if ( $autoplay ) {
                        $query_params['autoplay'] = 1;
                        $query_params['mute']     = 1;
                    }
                    if ( $loop ) {
                        $query_params['loop']     = 1;
                        $query_params['playlist'] = $vid_id;
                    }

                    $embed_src = 'https://www.youtube-nocookie.com/embed/' . $vid_id . '?' . http_build_query( $query_params );

                    $player_html = '<iframe class="elementor-video-iframe" src="' . esc_url( $embed_src ) . '" title="DigiLens Video Player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="width:100%;height:100%;aspect-ratio:16/9;border:none;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);"></iframe>';
                }
            } elseif ( ! empty( $vimeo_url ) ) {
                if ( preg_match( '#vimeo\.com\/(?:video\/)?(\d+)#i', $vimeo_url, $vm ) ) {
                    $vimeo_id = $vm[1];
                    $embed_src = 'https://player.vimeo.com/video/' . $vimeo_id . '?title=0&byline=0&portrait=0';
                    $player_html = '<iframe class="elementor-video-iframe" src="' . esc_url( $embed_src ) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="width:100%;height:100%;aspect-ratio:16/9;border:none;border-radius:12px;"></iframe>';
                }
            }

            if ( ! empty( $player_html ) ) {
                if ( preg_match( '#<div\b[^>]*\bclass=["\'][^"\']*elementor-video[^"\']*["\'][^>]*>[\s]*</div>#i', $inner_content ) ) {
                    $inner_content = preg_replace(
                        '#<div\b([^>]*\bclass=["\'][^"\']*elementor-video[^"\']*["\'][^>]*)>[\s]*</div>#i',
                        '<div $1>' . $player_html . '</div>',
                        $inner_content
                    );
                } else {
                    $inner_content .= '<div class="elementor-video" style="aspect-ratio:16/9;width:100%;">' . $player_html . '</div>';
                }
            }

            return '<div ' . $widget_open . '>' . $inner_content . '</div></div></div>';
        },
        $html
    );
}

function digilens_standardize_all_emails( $html ) {
    // 1. Standardize all mailto links to contact@digilens.vn
    $html = preg_replace( '#mailto:[a-zA-Z0-9._%+-]+@(?:digilens|DigiLens)\.[a-zA-Z0-9._%+-]+#i', 'mailto:contact@digilens.vn', $html );

    // 2. Standardize all visible digilens email addresses in HTML text
    $html = preg_replace( '#\b[a-zA-Z0-9._%+-]+@(?:digilens|DigiLens)\.(?:com|vn)\b#i', 'contact@digilens.vn', $html );

    // Standardize the Vietnam office phone numbers in visible copy and tel links.
    $html = str_replace(
        array( '024.7304.8700', '028.7304.8700', '02473048700', '02873048700' ),
        array( '024.7305.3268', '028.7305.3268', '02473053268', '02873053268' ),
        $html
    );

    return $html;
}

/** Correct prominent machine-translated copy and stale market references. */
function digilens_localize_snapshot_copy( $html, $snapshot_rel ) {
    $replacements = array(
        'ARGO là kính thông minh tất cả trong một đầu tiên, <span style="color:#ff9933">AR thực thụ</span> kính thông minh' => 'ARGO™ — kính thông minh AR tất cả trong một dành cho doanh nghiệp',
        'Thiết bị tất cả trong một đầu tiên, AR thực thụ kính thông minh' => 'Kính thông minh AR tất cả trong một dành cho doanh nghiệp',
        'Thiết bị tất cả trong một đầu tiên, <span style="color:#ff9933">AR thực thụ</span> kính thông minh' => 'Kính thông minh AR tất cả trong một dành cho doanh nghiệp',
        'Bạn nên trải nghiệm điều này' => 'Trải nghiệm ARGO™ trong công việc thực tế',
        'Tương lai của Điện toán đeo được' => 'Tương lai của điện toán đeo',
        'Mở rộng lực lượng lao động' => 'Kết nối và hỗ trợ lực lượng lao động',
    );
    $html = str_replace( array_keys( $replacements ), array_values( $replacements ), $html );

    if ( trim( $snapshot_rel, '/' ) === 'terms-of-use/index.htm' ) {
        $html = str_replace( 'truy cập và sử dụng digilens.com', 'truy cập và sử dụng digilens.vn', $html );
        $html = str_replace(
            'Các Điều khoản này được điều chỉnh bởi pháp luật California, với thẩm quyền xét xử độc quyền tại các tòa án thuộc Santa Clara County, California. Tranh chấp có thể phải giải quyết bằng trọng tài ràng buộc.',
            'Đối với việc truy cập, giao dịch và dịch vụ được cung cấp trực tiếp trên digilens.vn tại Việt Nam, các Điều khoản này được điều chỉnh bởi pháp luật Việt Nam và tranh chấp được giải quyết tại cơ quan có thẩm quyền của Việt Nam. Các điều khoản riêng của DigiLens Inc. hoặc dịch vụ do DigiLens Inc. trực tiếp cung cấp có thể chịu sự điều chỉnh của pháp luật California; phạm vi đó sẽ được nêu rõ tại điều khoản áp dụng cho dịch vụ tương ứng.',
            $html
        );
    }
    return $html;
}

function digilens_rewrite_snapshot_html( $html, $snapshot_rel ) {
    if ( function_exists( 'digilens_clean_snapshot_seo' ) ) {
        $html = digilens_clean_snapshot_seo( $html );
    }
    $html = digilens_strip_cookie_consent( $html );
    $html = digilens_replace_entire_header( $html );
    $html = digilens_replace_entire_footer( $html );
    $html = digilens_inject_dynamic_media_widgets( $html );
    $html = digilens_replace_hubspot_forms( $html );
    $html = digilens_replace_contact_section( $html );
    $html = digilens_inject_embedded_videos( $html );
    $html = digilens_standardize_all_emails( $html );
    $html = digilens_localize_snapshot_copy( $html, $snapshot_rel );
    $html = digilens_mark_duplicate_headings( $html );
    $html = digilens_fix_pagination( $html, $snapshot_rel );

    // 0. Strip broken speculation rules and emoji scripts from snapshot
    $html = preg_replace( '#<script\b[^>]*type=["\']speculationrules["\'][^>]*>[\s\S]*?</script>#is', '', $html );
    $html = preg_replace( '#<script\b[^>]*>\s*window\._wpemojiSettings[\s\S]*?</script>#is', '', $html );

    $head_safety_script = '<script>
    (function() {
        window.addEventListener("unhandledrejection", function(e) {
            if (e && e.reason && (e.reason.name === "ChunkLoadError" || (e.reason.message && e.reason.message.indexOf("ChunkLoadError") !== -1))) {
                e.preventDefault();
            }
        });
    })();
    </script>';
    if ( strpos( $html, '</head>' ) !== false ) {
        $html = str_replace( '</head>', $head_safety_script . "\n</head>", $html );
    }

    // 1. Rewrite AJAX & REST endpoints from remote domain to local WordPress
    $local_ajax = admin_url( 'admin-ajax.php' );
    $local_ajax_esc = str_replace( '/', '\\/', $local_ajax );
    $local_rest = rest_url();
    $local_rest_esc = str_replace( '/', '\\/', $local_rest );

    $domain_replacements = array(
        'https://www.digilens.com/wp-admin/admin-ajax.php'     => $local_ajax,
        'http://www.digilens.com/wp-admin/admin-ajax.php'      => $local_ajax,
        'https://digilens.com/wp-admin/admin-ajax.php'          => $local_ajax,
        'https:\/\/www.digilens.com\/wp-admin\/admin-ajax.php' => $local_ajax_esc,
        'http:\/\/www.digilens.com\/wp-admin\/admin-ajax.php'  => $local_ajax_esc,
        'https:\/\/digilens.com\/wp-admin\/admin-ajax.php'     => $local_ajax_esc,

        'https://www.digilens.com/wp-json/'                    => $local_rest,
        'http://www.digilens.com/wp-json/'                     => $local_rest,
        'https://digilens.com/wp-json/'                         => $local_rest,
        'https:\/\/www.digilens.com\/wp-json\/'                => $local_rest_esc,
        'http:\/\/www.digilens.com\/wp-json\/'                 => $local_rest_esc,
        'https:\/\/digilens.com\/wp-json\/'                    => $local_rest_esc,
    );
    $html = str_replace( array_keys( $domain_replacements ), array_values( $domain_replacements ), $html );

    // 2. Exact original-domain asset roots inside JS/CSS strings.
    $asset_roots = array( 'wp-content/', 'wp-includes/', 'forms/', 'gtag/', '@googlemaps/', 's/', 'af/', 'count/' );
    foreach ( $asset_roots as $root ) {
        $html = str_replace(
            array( 'https://www.digilens.com/' . $root, 'http://www.digilens.com/' . $root, 'https://digilens.com/' . $root ),
            trailingslashit( DIGILENS_SNAPSHOT_URI ) . $root,
            $html
        );
        $html = str_replace(
            array( 'https:\/\/www.digilens.com\/' . str_replace('/', '\\/', $root), 'http:\/\/www.digilens.com\/' . str_replace('/', '\\/', $root) ),
            str_replace('/', '\\/', trailingslashit( DIGILENS_SNAPSHOT_URI ) . $root),
            $html
        );
    }

    $html = preg_replace_callback(
        '#\b(href|src|action|poster|data-src|data-lazy-src)=("|\')(.*?)\2#is',
        function ( $m ) use ( $snapshot_rel ) {
            return $m[1] . '=' . $m[2] . esc_attr( digilens_rewrite_url( $m[3], $snapshot_rel ) ) . $m[2];
        },
        $html
    );

    $html = preg_replace_callback(
        '#\bsrcset=("|\')(.*?)\1#is',
        function ( $m ) use ( $snapshot_rel ) {
            $items = array();
            foreach ( explode( ',', $m[2] ) as $item ) {
                $item = trim( $item );
                if ( $item === '' ) { continue; }
                $parts = preg_split( '/\s+/', $item, 2 );
                $items[] = digilens_rewrite_url( $parts[0], $snapshot_rel ) . ( isset( $parts[1] ) ? ' ' . $parts[1] : '' );
            }
            return 'srcset=' . $m[1] . esc_attr( implode( ', ', $items ) ) . $m[1];
        },
        $html
    );

    $html = preg_replace_callback(
        '#url\(\s*(["\']?)([^)"\']+)\1\s*\)#i',
        function ( $m ) use ( $snapshot_rel ) {
            return 'url("' . esc_url( digilens_rewrite_url( trim( $m[2] ), $snapshot_rel ) ) . '")';
        },
        $html
    );

    // Add intrinsic dimensions to local images to prevent layout shifts.
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

    // Add WordPress hooks without replacing the captured document shell.
    ob_start(); wp_head(); $wp_head = ob_get_clean();
    ob_start(); wp_footer(); $wp_footer = ob_get_clean();
    if ( stripos( $html, '</head>' ) !== false ) { $html = preg_replace( '#</head>#i', $wp_head . "\n</head>", $html, 1 ); }
    if ( stripos( $html, '<body' ) !== false && function_exists( 'wp_body_open' ) ) {
        ob_start(); wp_body_open(); $body_open = ob_get_clean();
        $html = preg_replace( '#(<body\b[^>]*>)#i', '$1' . $body_open, $html, 1 );
    }
    if ( stripos( $html, '</body>' ) !== false ) { $html = preg_replace( '#</body>#i', $wp_footer . "\n</body>", $html, 1 ); }
    // Clean up speculation rules from both snapshot and WordPress core
    $html = preg_replace( '#<script\b[^>]*type=["\']speculationrules["\'][^>]*>[\s\S]*?</script>#is', '', $html );
    return $html;
}

function digilens_render_snapshot( $snapshot_rel ) {
    $file = DIGILENS_SNAPSHOT_DIR . '/' . digilens_normalize_snapshot_path( $snapshot_rel );
    if ( ! is_file( $file ) ) {
        status_header( 404 );
        include get_template_directory() . '/404.php';
        return;
    }
    $html = file_get_contents( $file );
    echo digilens_rewrite_snapshot_html( $html, $snapshot_rel ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
