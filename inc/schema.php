<?php
/**
 * Schema JSON-LD – DigiLens Vietnam
 * Theo SOP Hậu triển khai website – Chương 7
 *
 * Bao gồm:
 *  - Organization + WebSite (trang chủ)
 *  - BreadcrumbList (tất cả trang)
 *  - Product + Offer (WooCommerce products)
 *  - Article / BlogPosting (bài viết)
 *  - WebPage (pages)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ─── Hằng số thương hiệu ───────────────────────────────────────────────────
define( 'DIGILENS_SITE_URL',  'https://digilens.vn' );
define( 'DIGILENS_ORG_NAME',  'DigiLens Việt Nam' );
define( 'DIGILENS_ORG_ID',    'https://digilens.vn/#organization' );
define( 'DIGILENS_SITE_ID',   'https://digilens.vn/#website' );
define( 'DIGILENS_LOGO_URL',  'https://digilens.vn/wp-content/themes/digilens-theme/assets/images/digilens-logo.svg' );
define( 'DIGILENS_FACEBOOK',  'https://www.facebook.com/digilensvietnam' );
define( 'DIGILENS_LINKEDIN',  'https://www.linkedin.com/company/digilens' );

/**
 * Helper: xuất JSON-LD inline
 */
function digilens_schema_output( array $data ): void {
    echo "\n<script type=\"application/ld+json\">\n"
        . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT )
        . "\n</script>\n";
}

/**
 * Helper: lấy URL thật của trang hiện tại (không dùng digilens.vn.local)
 */
function digilens_canonical_url( ?int $post_id = null ): string {
    $base = rtrim( DIGILENS_SITE_URL, '/' );
    if ( is_front_page() ) {
        return $base . '/';
    }
    if ( $post_id ) {
        $path = wp_make_link_relative( get_permalink( $post_id ) );
        return $base . $path;
    }
    global $wp;
    $path = '/' . ltrim( $wp->request, '/' );
    return $base . $path . ( str_ends_with( $path, '/' ) ? '' : '/' );
}

// ─── HOOK: wp_head priority 5 ──────────────────────────────────────────────
add_action( 'wp_head', 'digilens_output_all_schema', 5 );

function digilens_output_all_schema(): void {
    global $post;
    $graph = [];

    // Organization is present in every graph so Product/Offer seller references
    // always resolve on the same page (not only on the homepage).
    $graph[] = [
            '@type'  => 'OnlineStore',
            '@id'    => DIGILENS_ORG_ID,
            'name'   => DIGILENS_ORG_NAME,
            'url'    => DIGILENS_SITE_URL . '/',
            'description' => 'Đơn vị cung cấp kính AR và giải pháp công nghệ quang học DigiLens tại Việt Nam.',
            'logo'   => [
                '@type' => 'ImageObject',
                'url'   => DIGILENS_LOGO_URL,
            ],
            'sameAs' => [
                DIGILENS_FACEBOOK,
                DIGILENS_LINKEDIN,
                'https://digilens.com',
            ],
            'contactPoint' => [
                '@type'             => 'ContactPoint',
                'contactType'       => 'customer service',
                'availableLanguage' => 'Vietnamese',
                'email'             => 'contact@digilens.vn',
                'telephone'         => '+84-1900-638-400',
            ],
            'email'     => 'contact@digilens.vn',
            'telephone' => '+84-1900-638-400',
            'address'   => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => '226 Đường Láng, Phường Thịnh Quang, Quận Đống Đa',
                'addressLocality' => 'Hà Nội',
                'addressCountry'  => 'VN',
            ],
        ];

    // WebSite/site-name schema only belongs on the canonical homepage.
    if ( is_front_page() ) {
        $graph[] = [
            '@type'         => 'WebSite',
            '@id'           => DIGILENS_SITE_ID,
            'url'           => DIGILENS_SITE_URL . '/',
            'name'          => DIGILENS_ORG_NAME,
            'alternateName' => 'DigiLens VN',
            'publisher'     => [ '@id' => DIGILENS_ORG_ID ],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => DIGILENS_SITE_URL . '/?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    // 2. BreadcrumbList → tất cả trang (trừ trang chủ không cần)
    $breadcrumb = digilens_build_breadcrumb();
    if ( ! empty( $breadcrumb ) ) {
        $graph[] = $breadcrumb;
    }

    // 3. Schema theo loại trang
    if ( is_singular( 'product' ) && isset( $post->ID ) ) {
        $product_schema = digilens_build_product_schema( $post->ID );
        if ( $product_schema ) {
            $graph[] = $product_schema;
        }
    } elseif ( is_singular( 'post' ) && isset( $post->ID ) ) {
        $article_schema = digilens_build_article_schema( $post->ID );
        if ( $article_schema ) {
            $graph[] = $article_schema;
        }
    } elseif ( is_page() && isset( $post->ID ) && ! is_front_page() ) {
        $graph[] = digilens_build_webpage_schema( $post->ID );
    }

    // Output
    if ( ! empty( $graph ) ) {
        digilens_schema_output( [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ] );
    }
}

// ─── BreadcrumbList ────────────────────────────────────────────────────────
function digilens_build_breadcrumb(): ?array {
    global $post;
    $items = [];
    $base  = rtrim( DIGILENS_SITE_URL, '/' );
    $pos   = 1;

    // Luôn có trang chủ
    $items[] = [
        '@type'    => 'ListItem',
        'position' => $pos++,
        'name'     => 'Trang chủ',
        'item'     => $base . '/',
    ];

    if ( is_front_page() ) {
        return null; // Trang chủ không cần breadcrumb
    }

    // Category archive
    if ( is_category() ) {
        $cat = get_queried_object();
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => $cat->name,
            'item'     => $base . '/' . ltrim( get_category_link( $cat->term_id ), '/' ),
        ];
        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    // Single post
    if ( is_singular( 'post' ) && isset( $post->ID ) ) {
        $cats = get_the_category( $post->ID );
        if ( $cats ) {
            $cat     = $cats[0];
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => $cat->name,
                'item'     => $base . wp_make_link_relative( get_category_link( $cat->term_id ) ),
            ];
        }
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => get_the_title( $post->ID ),
            'item'     => $base . wp_make_link_relative( get_permalink( $post->ID ) ),
        ];
        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    // WooCommerce product
    if ( is_singular( 'product' ) && isset( $post->ID ) ) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => 'Cửa hàng',
            'item'     => $base . '/store/',
        ];
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => get_the_title( $post->ID ),
            'item'     => $base . wp_make_link_relative( get_permalink( $post->ID ) ),
        ];
        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    // Page có parent
    if ( is_page() && isset( $post->ID ) ) {
        $ancestors = array_reverse( get_post_ancestors( $post->ID ) );
        foreach ( $ancestors as $ancestor_id ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title( $ancestor_id ),
                'item'     => $base . wp_make_link_relative( get_permalink( $ancestor_id ) ),
            ];
        }
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => get_the_title( $post->ID ),
        ];
        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    return null;
}

// ─── Product Schema ────────────────────────────────────────────────────────
function digilens_build_product_schema( int $post_id ): ?array {
    if ( ! function_exists( 'wc_get_product' ) ) {
        return null;
    }

    $product = wc_get_product( $post_id );
    if ( ! $product ) {
        return null;
    }

    $base        = rtrim( DIGILENS_SITE_URL, '/' );
    $url         = $base . wp_make_link_relative( get_permalink( $post_id ) );
    $title       = get_the_title( $post_id );
    $description = wp_strip_all_tags( $product->get_description() ?: $product->get_short_description() );
    $sku         = $product->get_sku();
    $price       = $product->get_price();
    $currency    = get_woocommerce_currency();
    $in_stock    = $product->is_in_stock();

    // Hình ảnh sản phẩm
    $image_id  = $product->get_image_id();
    $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
    if ( ! $image_url ) {
        $image_url = (string) get_post_meta( $post_id, '_digilens_image_url', true );
    }

    $schema = [
        '@type'       => 'Product',
        '@id'         => $url . '#product',
        'name'        => $title,
        'url'         => $url,
        'description' => $description ?: $title,
        'brand'       => [
            '@type' => 'Brand',
            'name'  => 'DigiLens',
        ],
        'seller' => [ '@id' => DIGILENS_ORG_ID ],
    ];

    if ( $sku ) {
        $schema['sku'] = $sku;
    }

    if ( $image_url ) {
        $schema['image'] = $image_url;
    }

    // Offer
    $offer = [
        '@type'           => 'Offer',
        'url'             => $url,
        'priceCurrency'   => $currency,
        'availability'    => $in_stock
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock',
        'seller'          => [ '@id' => DIGILENS_ORG_ID ],
    ];

    if ( $price !== '' && $price !== null ) {
        $offer['price'] = (float) $price;
    } else {
        // Sản phẩm không có giá niêm yết (liên hệ báo giá)
        $offer['priceSpecification'] = [
            '@type'         => 'PriceSpecification',
            'priceCurrency' => $currency,
        ];
    }

    $schema['offers'] = $offer;

    return $schema;
}

// ─── Article Schema ────────────────────────────────────────────────────────
function digilens_build_article_schema( int $post_id ): ?array {
    $base        = rtrim( DIGILENS_SITE_URL, '/' );
    $url         = $base . wp_make_link_relative( get_permalink( $post_id ) );
    $title       = get_the_title( $post_id );
    $excerpt     = wp_strip_all_tags( get_the_excerpt( $post_id ) );
    $date        = get_the_date( 'c', $post_id );
    $modified    = get_the_modified_date( 'c', $post_id );
    $thumbnail   = get_the_post_thumbnail_url( $post_id, 'large' );

    // Lấy Yoast meta description nếu có
    $meta_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
    $description = $meta_desc ?: $excerpt;

    $schema = [
        '@type'            => 'Article',
        '@id'              => $url . '#article',
        'headline'         => $title,
        'description'      => $description,
        'url'              => $url,
        'datePublished'    => $date,
        'dateModified'     => $modified,
        'author'           => [
            '@type' => 'Organization',
            '@id'   => DIGILENS_ORG_ID,
            'name'  => DIGILENS_ORG_NAME,
        ],
        'publisher'        => [
            '@type' => 'Organization',
            '@id'   => DIGILENS_ORG_ID,
            'name'  => DIGILENS_ORG_NAME,
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => DIGILENS_LOGO_URL,
            ],
        ],
        'isPartOf'         => [ '@id' => DIGILENS_SITE_ID ],
        'inLanguage'       => 'vi',
    ];

    if ( $thumbnail ) {
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url'   => $thumbnail,
        ];
    }

    // Thêm keywords từ category
    $cats = get_the_category( $post_id );
    if ( $cats ) {
        $schema['keywords'] = implode( ', ', array_map( fn( $c ) => $c->name, $cats ) );
        $schema['articleSection'] = $cats[0]->name;
    }

    return $schema;
}

// ─── WebPage Schema ────────────────────────────────────────────────────────
function digilens_build_webpage_schema( int $post_id ): array {
    $base        = rtrim( DIGILENS_SITE_URL, '/' );
    $url         = $base . wp_make_link_relative( get_permalink( $post_id ) );
    $title       = get_post_meta( $post_id, '_yoast_wpseo_title', true ) ?: get_the_title( $post_id );
    $description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );

    return [
        '@type'         => 'WebPage',
        '@id'           => $url . '#webpage',
        'url'           => $url,
        'name'          => $title,
        'description'   => $description,
        'isPartOf'      => [ '@id' => DIGILENS_SITE_ID ],
        'publisher'     => [ '@id' => DIGILENS_ORG_ID ],
        'inLanguage'    => 'vi',
        'dateModified'  => get_the_modified_date( 'c', $post_id ),
    ];
}
