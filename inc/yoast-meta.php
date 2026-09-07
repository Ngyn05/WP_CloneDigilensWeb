<?php
/**
 * One-time Yoast metadata optimization for published products and posts.
 * Values are stored in post meta, so they appear in the Yoast editor on host.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function digilens_meta_normalize( string $text ): string {
    $text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = preg_replace( '/[“”„]+/u', '', $text );
    return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

function digilens_meta_trim( string $text, int $limit ): string {
    $text = digilens_meta_normalize( $text );
    if ( mb_strlen( $text ) <= $limit ) { return $text; }
    $cut = mb_substr( $text, 0, $limit + 1 );
    $cut = preg_replace( '/\s+\S*$/u', '', $cut );
    return rtrim( $cut, " \t\n\r\0\x0B,;:–—-" );
}

function digilens_meta_trim_title( string $text, int $limit ): string {
    $text = digilens_meta_trim( $text, $limit );
    $stop_words = '(?:của|và|với|cho|trong|về|bằng|từ|tại|một|những|các|được|để|giúp|trên|theo|vào|cùng|mà|như|nền)';
    do {
        $before = $text;
        $text = preg_replace( '/\s+' . $stop_words . '$/iu', '', $text );
    } while ( $text !== $before );
    return rtrim( $text, " \t\n\r\0\x0B,;:–—-" );
}

function digilens_product_yoast_meta( WP_Post $post ): array {
    $map = array(
        'argo-enterprise' => array(
            'ARGO Enterprise – Kính AR công nghiệp | DigiLens',
            'Khám phá kính AR DigiLens ARGO Enterprise với camera 48MP, nền tảng Snapdragon XR2 và màn hình trong suốt. Xem tình trạng và yêu cầu báo giá.',
        ),
        'dv1-developer' => array(
            'Dv1 Developer – Kính AR cho lập trình viên | DigiLens',
            'Tìm hiểu kính DigiLens Dv1 Developer dành cho phát triển ứng dụng AR/XR, sử dụng ống dẫn sóng holographic và hỗ trợ Snapdragon Spaces.',
        ),
        'crystal30-waveguide' => array(
            'Crystal30 – Ống dẫn sóng holographic | DigiLens',
            'Crystal30 là mô-đun ống dẫn sóng holographic nhỏ gọn với trường nhìn 50°, dành cho kính thông minh và thiết bị AR/XR công nghiệp.',
        ),
        'crystal30-insight-kit' => array(
            'Crystal30 Insight Kit – Bộ kiểm thử quang học | DigiLens',
            'Đánh giá độ sáng, màu sắc, trường nhìn và hiệu năng ống dẫn sóng với Crystal30 Insight Kit. Xem thông số và yêu cầu tư vấn tại Việt Nam.',
        ),
        'crystal50-plastic' => array(
            'Crystal50 Plastic – Ống dẫn sóng AR | DigiLens',
            'Tìm hiểu Crystal50 Plastic Waveguide, mô-đun ống dẫn sóng bằng nhựa cứng dành cho thiết bị AR thế hệ mới và sản xuất quy mô lớn.',
        ),
        'argo-prescription-insert' => array(
            'ARGO Prescription Insert – Khung kính cận | DigiLens',
            'Khung gắn tròng cận và viễn ARGO Prescription Insert giúp người dùng kính thuốc sử dụng kính ARGO thoải mái trong suốt ca làm việc.',
        ),
        'argo-industrial-dock' => array(
            'ARGO Carry Case – Hộp bảo vệ và trạm sạc | DigiLens',
            'Bảo vệ và sạc kính ARGO với Carry Case chống va đập, tích hợp pin dự phòng và cổng USB-C. Xem thông số và yêu cầu báo giá tại Việt Nam.',
        ),
        'argo-next-program' => array(
            'ARGO Next – Chương trình nâng cấp kính AR | DigiLens',
            'ARGO Next hỗ trợ doanh nghiệp chuyển đổi ứng dụng và hạ tầng XR hiện có sang hệ sinh thái kính ARGO với dịch vụ tư vấn kỹ thuật.',
        ),
        'taqtile-manifest-software' => array(
            'Taqtile Manifest – Phần mềm AR công nghiệp | DigiLens',
            'Taqtile Manifest cho ARGO hỗ trợ hướng dẫn công việc 3D, đào tạo và kết nối chuyên gia từ xa cho lực lượng lao động công nghiệp.',
        ),
    );

    if ( isset( $map[ $post->post_name ] ) ) { return $map[ $post->post_name ]; }

    $name  = digilens_meta_trim( $post->post_title, 45 );
    $title = $name . ' | DigiLens Việt Nam';
    $desc  = digilens_meta_trim( 'Khám phá ' . $post->post_title . '. Xem thông tin, tình trạng và yêu cầu tư vấn sản phẩm DigiLens tại Việt Nam.', 155 );
    return array( $title, $desc );
}

function digilens_post_yoast_meta( WP_Post $post ): array {
    $brand_suffix = ' | DigiLens';
    $seo_title    = digilens_meta_trim_title( $post->post_title, 62 );
    if ( false === stripos( $seo_title, 'DigiLens' ) && mb_strlen( $seo_title . $brand_suffix ) <= 62 ) {
        $seo_title .= $brand_suffix;
    }

    $suffix       = '. Xem phân tích, ứng dụng thực tế và xu hướng công nghệ AR/XR từ DigiLens Việt Nam.';
    $topic_budget = 158 - mb_strlen( $suffix );
    $topic        = digilens_meta_trim_title( $post->post_title, min( 70, $topic_budget ) );
    $desc         = $topic . $suffix;
    if ( mb_strlen( $desc ) < 120 ) {
        $desc .= ' Khám phá nội dung chi tiết trong bài viết.';
    }

    return array( $seo_title, $desc );
}

function digilens_optimize_all_yoast_meta(): array {
    $updated = array( 'product' => 0, 'post' => 0 );
    foreach ( array( 'product', 'post' ) as $post_type ) {
        $items = get_posts( array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'all',
        ) );
        foreach ( $items as $item ) {
            $meta = 'product' === $post_type
                ? digilens_product_yoast_meta( $item )
                : digilens_post_yoast_meta( $item );
            update_post_meta( $item->ID, '_yoast_wpseo_title', $meta[0] );
            update_post_meta( $item->ID, '_yoast_wpseo_metadesc', $meta[1] );
            $updated[ $post_type ]++;
        }
    }
    return $updated;
}

add_action( 'init', function (): void {
    $version = '2026-08-28.4';
    if ( get_option( 'digilens_yoast_meta_version' ) === $version ) { return; }
    $updated = digilens_optimize_all_yoast_meta();
    update_option( 'digilens_yoast_meta_version', $version, false );
    update_option( 'digilens_yoast_meta_last_update', array(
        'time'    => time(),
        'updated' => $updated,
    ), false );
}, 30 );
