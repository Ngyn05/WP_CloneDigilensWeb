<?php
/**
 * DigiLens SEO - XML Sitemap & Robots.txt Engine
 *
 * @package DigiLens
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Build list of all indexable URLs for the DigiLens XML Sitemap.
 *
 * @return array
 */
function digilens_get_sitemap_entries() {
    $entries = array();
    $now = gmdate( 'Y-m-d\TH:i:s+00:00' );

    // 1. Trang chủ (Homepage)
    $front_id = (int) get_option( 'page_on_front' );
    $front_lastmod = $front_id ? get_post_modified_time( 'c', true, $front_id ) : $now;
    $entries[] = array(
        'loc'        => home_url( '/' ),
        'lastmod'    => $front_lastmod ? $front_lastmod : $now,
        'changefreq' => 'daily',
        'priority'   => '1.0',
        'type'       => 'Trang chủ',
        'title'      => 'Trang chủ DigiLens Việt Nam',
        'image'      => get_template_directory_uri() . '/snapshot/wp-content/uploads/2025/06/New-Site-Icon-v3.png',
    );

    // Danh sách trang tiện ích cần loại khỏi Sitemap (không có giá trị SEO trực tiếp)
    $excluded_slugs = array( 'cart', 'checkout', 'my-account', 'sample-page', 'gio-hang', 'thanh-toan', 'tai-khoan' );

    // 2. Tất cả các trang Pages đã xuất bản
    $pages = get_posts( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ) );

    foreach ( $pages as $page ) {
        if ( $front_id && $page->ID === $front_id ) {
            continue; // Bỏ qua vì đã thêm ở Homepage
        }

        $slug = $page->post_name;
        if ( in_array( $slug, $excluded_slugs, true ) ) {
            continue;
        }

        // Bỏ qua các sub-page phân trang cũ (ví dụ media/2-2, 3-2...)
        if ( preg_match( '#^\d+-\d+$#', $slug ) || preg_match( '#^\d+$#', $slug ) ) {
            continue;
        }

        $permalink = get_permalink( $page->ID );
        if ( ! $permalink ) {
            continue;
        }

        // Thiết lập Priority & Changefreq theo nhóm nội dung
        $priority = '0.7';
        $changefreq = 'weekly';

        if ( in_array( $slug, array( 'argo', 'waveguides', 'optics', 'next', 'store', 'shop' ), true ) ) {
            $priority = '0.9';
            $changefreq = 'weekly';
        } elseif ( in_array( $slug, array( 'company', 'careers', 'partners', 'contact', 'media' ), true ) ) {
            $priority = '0.8';
            $changefreq = 'weekly';
        } elseif ( in_array( $slug, array( 'privacy-policy', 'terms-of-use' ), true ) ) {
            $priority = '0.5';
            $changefreq = 'monthly';
        }

        // Hình ảnh đại diện
        $image = get_post_meta( $page->ID, '_digilens_featured_image_url', true );
        if ( ! $image && has_post_thumbnail( $page->ID ) ) {
            $image = get_the_post_thumbnail_url( $page->ID, 'full' );
        }

        $entries[] = array(
            'loc'        => $permalink,
            'lastmod'    => get_post_modified_time( 'c', true, $page->ID ),
            'changefreq' => $changefreq,
            'priority'   => $priority,
            'type'       => 'Trang (Page)',
            'title'      => get_the_title( $page->ID ),
            'image'      => $image ? $image : '',
        );
    }

    // 3. Toàn bộ Sản phẩm WooCommerce (Products)
    $products = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ) );

    foreach ( $products as $prod ) {
        $permalink = get_permalink( $prod->ID );
        if ( ! $permalink ) { continue; }

        $image = get_post_meta( $prod->ID, '_digilens_featured_image_url', true );
        if ( ! $image && has_post_thumbnail( $prod->ID ) ) {
            $image = get_the_post_thumbnail_url( $prod->ID, 'full' );
        }

        $entries[] = array(
            'loc'        => $permalink,
            'lastmod'    => get_post_modified_time( 'c', true, $prod->ID ),
            'changefreq' => 'weekly',
            'priority'   => '0.85',
            'type'       => 'Sản phẩm (Product)',
            'title'      => get_the_title( $prod->ID ),
            'image'      => $image ? $image : '',
        );
    }

    // 4. Toàn bộ Bài viết & Thông cáo báo chí (Posts)
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    foreach ( $posts as $post_item ) {
        $permalink = get_permalink( $post_item->ID );
        if ( ! $permalink ) { continue; }

        $image = get_post_meta( $post_item->ID, '_digilens_featured_image_url', true );
        if ( ! $image && has_post_thumbnail( $post_item->ID ) ) {
            $image = get_the_post_thumbnail_url( $post_item->ID, 'full' );
        }

        $cats = get_the_category( $post_item->ID );
        $cat_name = ! empty( $cats ) ? $cats[0]->name : 'Tin tức';

        $entries[] = array(
            'loc'        => $permalink,
            'lastmod'    => get_post_modified_time( 'c', true, $post_item->ID ),
            'changefreq' => 'weekly',
            'priority'   => '0.7',
            'type'       => 'Bài viết: ' . $cat_name,
            'title'      => get_the_title( $post_item->ID ),
            'image'      => $image ? $image : '',
        );
    }

    return $entries;
}

/**
 * Generate XML Sitemap string.
 *
 * @return string
 */
function digilens_build_sitemap_xml() {
    $entries = digilens_get_sitemap_entries();
    $xsl_url = home_url( '/sitemap.xsl' );

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_url( $xsl_url ) . '"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n";
    $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
    $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd">' . "\n";

    foreach ( $entries as $entry ) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . esc_url( $entry['loc'] ) . "</loc>\n";
        if ( ! empty( $entry['lastmod'] ) ) {
            $xml .= '    <lastmod>' . esc_html( $entry['lastmod'] ) . "</lastmod>\n";
        }
        if ( ! empty( $entry['changefreq'] ) ) {
            $xml .= '    <changefreq>' . esc_html( $entry['changefreq'] ) . "</changefreq>\n";
        }
        if ( ! empty( $entry['priority'] ) ) {
            $xml .= '    <priority>' . esc_html( $entry['priority'] ) . "</priority>\n";
        }
        if ( ! empty( $entry['image'] ) ) {
            $xml .= "    <image:image>\n";
            $xml .= '      <image:loc>' . esc_url( $entry['image'] ) . "</image:loc>\n";
            if ( ! empty( $entry['title'] ) ) {
                $xml .= '      <image:title>' . esc_html( $entry['title'] ) . "</image:title>\n";
            }
            $xml .= "    </image:image>\n";
        }
        $xml .= "  </url>\n";
    }

    $xml .= "</urlset>\n";
    return $xml;
}

/**
 * Generate XSL Stylesheet for visual viewing in web browsers.
 *
 * @return string
 */
function digilens_build_sitemap_xsl() {
    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url( '/' );

    $xsl = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns:html="http://www.w3.org/1999/xhtml"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
  <xsl:template match="/">
    <html xmlns="http://www.w3.org/1999/xhtml" lang="vi">
      <head>
        <title>XML Sitemap - ' . esc_html( $site_name ) . '</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <style type="text/css">
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #0d1117;
            color: #e6edf3;
            line-height: 1.6;
            padding: 30px 20px;
          }
          .container {
            max-width: 1200px;
            margin: 0 auto;
          }
          .header {
            background: linear-gradient(135deg, #161b22 0%, #0d1117 100%);
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
          }
          .header h1 {
            color: #00B0F0;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
          }
          .header p {
            color: #8b949e;
            font-size: 15px;
          }
          .header a {
            color: #58a6ff;
            text-decoration: none;
          }
          .header a:hover {
            text-decoration: underline;
          }
          .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0 10px;
          }
          .stat-card {
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
          }
          .stat-card .num {
            font-size: 26px;
            font-weight: 700;
            color: #03FEA9;
          }
          .stat-card .label {
            font-size: 13px;
            color: #8b949e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
          }
          .table-container {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
          }
          table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
          }
          th {
            background: #21262d;
            color: #f0f6fc;
            padding: 14px 18px;
            font-weight: 600;
            border-bottom: 1px solid #30363d;
          }
          td {
            padding: 12px 18px;
            border-bottom: 1px solid #21262d;
            color: #c9d1d9;
          }
          tr:hover td {
            background: #1c2128;
          }
          a.url-link {
            color: #58a6ff;
            text-decoration: none;
            word-break: break-all;
            font-weight: 500;
          }
          a.url-link:hover {
            color: #00B0F0;
            text-decoration: underline;
          }
          .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: #238636;
            color: #ffffff;
          }
          .badge-high { background: rgba(3, 254, 169, 0.15); color: #03FEA9; border: 1px solid rgba(3, 254, 169, 0.3); }
          .badge-med  { background: rgba(0, 176, 240, 0.15); color: #00B0F0; border: 1px solid rgba(0, 176, 240, 0.3); }
          .badge-low  { background: rgba(139, 148, 158, 0.15); color: #8b949e; border: 1px solid rgba(139, 148, 158, 0.3); }
          .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 13px;
            color: #8b949e;
          }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">
            <h1>🌐 XML Sitemap Index</h1>
            <p>Sitemap chính thức của website <strong>' . esc_html( $site_name ) . '</strong> (<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_url ) . '</a>). Hỗ trợ tối ưu hóa công cụ tìm kiếm Google, Bing, Yandex.</p>
            <div class="stats-grid">
              <div class="stat-card">
                <div class="num"><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></div>
                <div class="label">Tổng số liên kết (URLs)</div>
              </div>
              <div class="stat-card">
                <div class="num"><xsl:value-of select="count(sitemap:urlset/sitemap:url[image:image])"/></div>
                <div class="label">URLs có hình ảnh SEO</div>
              </div>
              <div class="stat-card">
                <div class="num"><xsl:value-of select="count(sitemap:urlset/sitemap:url[sitemap:priority &gt;= 0.8])"/></div>
                <div class="label">URLs ưu tiên cao (&gt;= 0.8)</div>
              </div>
            </div>
          </div>

          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th style="width: 5%;">#</th>
                  <th style="width: 50%;">Đường dẫn URL (<loc/>)</th>
                  <th style="width: 15%;">Cập nhật (<lastmod/>)</th>
                  <th style="width: 15%;">Tần suất (<changefreq/>)</th>
                  <th style="width: 15%;">Độ ưu tiên (<priority/>)</th>
                </tr>
              </thead>
              <tbody>
                <xsl:for-each select="sitemap:urlset/sitemap:url">
                  <tr>
                    <td><xsl:value-of select="position()"/></td>
                    <td>
                      <a class="url-link" href="{sitemap:loc}" target="_blank">
                        <xsl:value-of select="sitemap:loc"/>
                      </a>
                    </td>
                    <td>
                      <xsl:value-of select="substring(sitemap:lastmod, 1, 10)"/>
                    </td>
                    <td>
                      <xsl:value-of select="sitemap:changefreq"/>
                    </td>
                    <td>
                      <xsl:choose>
                        <xsl:when test="sitemap:priority &gt;= 0.9">
                          <span class="badge badge-high"><xsl:value-of select="sitemap:priority"/></span>
                        </xsl:when>
                        <xsl:when test="sitemap:priority &gt;= 0.7">
                          <span class="badge badge-med"><xsl:value-of select="sitemap:priority"/></span>
                        </xsl:when>
                        <xsl:otherwise>
                          <span class="badge badge-low"><xsl:value-of select="sitemap:priority"/></span>
                        </xsl:otherwise>
                      </xsl:choose>
                    </td>
                  </tr>
                </xsl:for-each>
              </tbody>
            </table>
          </div>

          <div class="footer">
            <p>DigiLens Vietnam XML Sitemap Engine &bull; Tự động cập nhật theo thời gian thực.</p>
          </div>
        </div>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>';

    return $xsl;
}

/**
 * Generate Robots.txt content.
 *
/**
 * Generate Robots.txt content using Yoast SEO sitemap index.
 *
 * @return string
 */
function digilens_build_robots_txt() {
    $sitemap_url = home_url( '/sitemap_index.xml' );
    
    $robots  = "# =========================================\n";
    $robots .= "# DigiLens Vietnam - Robots.txt Rules\n";
    $robots .= "# Website: " . home_url( '/' ) . "\n";
    $robots .= "# =========================================\n\n";
    $robots .= "User-agent: *\n";
    $robots .= "Allow: /\n";
    $robots .= "Allow: /wp-admin/admin-ajax.php\n";
    $robots .= "Disallow: /wp-admin/\n";
    $robots .= "Disallow: /cart/\n";
    $robots .= "Disallow: /checkout/\n";
    $robots .= "Disallow: /my-account/\n";
    $robots .= "Disallow: /gio-hang/\n";
    $robots .= "Disallow: /thanh-toan/\n";
    $robots .= "Disallow: /tai-khoan/\n";
    $robots .= "Disallow: /*?*pr_paged=\n";
    $robots .= "Disallow: /*?*s=\n";
    $robots .= "Disallow: /xmlrpc.php\n\n";
    $robots .= "User-agent: OAI-SearchBot\n";
    $robots .= "Allow: /\n";
    $robots .= "Disallow: /wp-admin/\n";
    $robots .= "Disallow: /cart/\n";
    $robots .= "Disallow: /checkout/\n";
    $robots .= "Disallow: /my-account/\n\n";
    $robots .= "User-agent: GPTBot\n";
    $robots .= "Disallow: /\n\n";
    $robots .= "# XML Sitemap Location (Yoast SEO)\n";
    $robots .= "Sitemap: " . esc_url( $sitemap_url ) . "\n";

    return $robots;
}

/**
 * Remove legacy physical sitemap.xml to avoid blocking Yoast dynamic routes.
 */
function digilens_clean_physical_sitemap_file() {
    $root_path = ABSPATH;
    if ( ! empty( $root_path ) && is_dir( $root_path ) ) {
        $sitemap_file = trailingslashit( $root_path ) . 'sitemap.xml';
        if ( file_exists( $sitemap_file ) ) {
            @unlink( $sitemap_file );
        }
        $robots_file = trailingslashit( $root_path ) . 'robots.txt';
        @file_put_contents( $robots_file, digilens_build_robots_txt() );
    }
}

/**
 * WordPress filter to provide robots.txt output.
 */
add_filter( 'robots_txt', function( $output, $public ) {
    return digilens_build_robots_txt();
}, 999, 2 );

/**
 * Handle direct template requests for /robots.txt and forward /sitemap.xml to Yoast /sitemap_index.xml
 */
add_action( 'template_redirect', function() {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );

    // Redirect /sitemap.xml and /wp-sitemap.xml to Yoast /sitemap_index.xml
    if ( $path === 'sitemap.xml' || $path === 'wp-sitemap.xml' || $path === 'sitemap_xml' ) {
        wp_safe_redirect( home_url( '/sitemap_index.xml' ), 301 );
        exit;
    }

    // Robots.txt
    if ( $path === 'robots.txt' ) {
        while ( ob_get_level() > 0 ) {
            @ob_end_clean();
        }
        header( 'Content-Type: text/plain; charset=UTF-8' );
        status_header( 200 );
        echo digilens_build_robots_txt();
        exit;
    }
}, -9999 );

/**
 * Register rewrite rules for robots
 */
add_action( 'init', function() {
    add_rewrite_rule( '^robots\.txt$', 'index.php?digilens_robots=1', 'top' );
}, -900 );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'digilens_robots';
    return $vars;
} );

// Clean legacy static sitemap file once on initialization
add_action( 'init', function() {
    $engine_version = '2026-09-07.yoast';
    if ( get_option( 'digilens_seo_files_version' ) === $engine_version ) { return; }
    digilens_clean_physical_sitemap_file();
    update_option( 'digilens_seo_files_version', $engine_version, false );
}, 100 );
