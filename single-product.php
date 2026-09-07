<?php
/**
 * The template for displaying all single DigiLens products.
 * Dedicated, bright, clean, premium product presentation page with Quote Modal Window.
 *
 * @package DigiLens
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$theme_uri    = get_template_directory_uri();
$snapshot_uri = $theme_uri . '/snapshot';

get_header();

while ( have_posts() ) : the_post();
    $p_id    = get_the_ID();
    $wc_prod = function_exists( 'wc_get_product' ) ? wc_get_product( $p_id ) : null;

    // Categories
    $terms = get_the_terms( $p_id, 'product_cat' );
    $cat_slug = 'all';
    $cat_name = 'Sản phẩm';
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        $cat_slug = $terms[0]->slug;
        $cat_name = $terms[0]->name;
    }

    // Thumbnail
    $thumb = get_the_post_thumbnail_url( $p_id, 'full' );
    if ( ! $thumb ) {
        $thumb = get_post_meta( $p_id, '_digilens_image_url', true );
    }
    if ( ! $thumb ) {
        $thumb = $snapshot_uri . '/wp-content/uploads/2023/08/DL-Argo-080923-01.jpg';
    }

    $badge       = get_post_meta( $p_id, '_digilens_badge', true ) ?: 'DIGILENS';
    $ref_url     = get_post_meta( $p_id, '_digilens_ref_url', true ) ?: '';
    $ref_title   = get_post_meta( $p_id, '_digilens_ref_title', true ) ?: '';
    $tagline     = get_the_excerpt() ?: wp_trim_words( get_the_content(), 35 );

    // Specs & Highlights
    $specs = get_post_meta( $p_id, '_digilens_specs', true );
    if ( ! is_array( $specs ) ) { $specs = array(); }

    $highlights = get_post_meta( $p_id, '_digilens_highlights', true );
    if ( ! is_array( $highlights ) ) { $highlights = array(); }

    // Stock
    $in_stock = $wc_prod ? $wc_prod->is_in_stock() : ( get_post_meta( $p_id, '_stock_status', true ) !== 'outofstock' );

    // Other related products
    $all_products = function_exists( 'digilens_get_store_products' ) ? digilens_get_store_products() : array();
    $related = array();
    foreach ( $all_products as $other_prod ) {
        if ( $other_prod['db_id'] != $p_id ) {
            $related[] = $other_prod;
            if ( count( $related ) >= 3 ) break;
        }
    }
?>
    <style>
        :root {
            --dl-bg: #ffffff;
            --dl-bg-alt: #f8fafc;
            --dl-card-bg: #ffffff;
            --dl-border: #e2e8f0;
            --dl-border-hover: #cbd5e1;
            --dl-text-main: #0f172a;
            --dl-text-muted: #475569;
            --dl-text-light: #64748b;
            --dl-primary: #0284c7;
            --dl-primary-dark: #0369a1;
            --dl-dark-btn: #0f172a;
        }

        .dl-single-prod-wrapper {
            background: #ffffff;
            min-height: 80vh;
            padding: 30px 20px 80px 20px;
            max-width: 1200px;
            margin: 0 auto;
            color: var(--dl-text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            box-sizing: border-box;
            width: 100%;
        }

        @media (max-width: 640px) {
            .dl-single-prod-wrapper {
                padding: 16px 12px 60px 12px;
            }
        }

        /* 1. Breadcrumbs */
        .dl-single-breadcrumbs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--dl-text-light);
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--dl-border);
        }

        @media (max-width: 640px) {
            .dl-single-breadcrumbs {
                font-size: 0.78rem;
                gap: 6px;
                margin-bottom: 20px;
                padding-bottom: 12px;
            }
        }

        .dl-single-breadcrumbs a {
            color: var(--dl-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .dl-single-breadcrumbs a:hover {
            text-decoration: underline;
        }

        .dl-bc-sep {
            color: #cbd5e1;
        }

        /* 2. Main 2-Column Hero */
        .dl-single-hero {
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 40px;
            margin-bottom: 50px;
            align-items: start;
        }

        @media (max-width: 860px) {
            .dl-single-hero {
                grid-template-columns: 1fr;
                gap: 25px;
                margin-bottom: 35px;
            }
        }

        /* Left: Gallery & Visuals */
        .dl-single-visuals {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .dl-single-img-card {
            background: #f8fafc;
            border: 1px solid var(--dl-border);
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
        }

        @media (max-width: 640px) {
            .dl-single-img-card {
                padding: 12px;
                border-radius: 12px;
            }
        }

        .dl-single-main-img {
            width: 100%;
            height: auto;
            max-height: 440px;
            object-fit: contain;
            display: block;
            transition: transform 0.4s ease;
        }

        @media (max-width: 640px) {
            .dl-single-main-img {
                max-height: 280px;
            }
        }

        .dl-single-img-card:hover .dl-single-main-img {
            transform: scale(1.03);
        }

        .dl-single-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 10;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
            color: #334155;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        @media (max-width: 640px) {
            .dl-single-badge {
                top: 10px;
                left: 10px;
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }

        .dl-single-ref-card {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            text-decoration: none;
            color: var(--dl-primary-dark);
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        @media (max-width: 640px) {
            .dl-single-ref-card {
                padding: 10px 12px;
                font-size: 0.82rem;
            }
        }

        .dl-single-ref-card:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
        }

        /* Right: Info & 3 CTA Buttons */
        .dl-single-info {
            display: flex;
            flex-direction: column;
        }

        .dl-single-cat {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--dl-primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        .dl-single-title {
            font-size: clamp(1.4rem, 4.5vw, 2.3rem);
            font-weight: 800;
            color: var(--dl-text-main);
            margin: 0 0 12px 0;
            line-height: 1.28;
            word-break: break-word;
        }

        .dl-single-stock {
            margin-bottom: 16px;
        }

        .dl-stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.84rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            width: fit-content;
        }

        .dl-stock-badge.in-stock {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .dl-stock-badge.out-stock {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .dl-stock-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .dl-single-tagline {
            font-size: 0.96rem;
            color: var(--dl-text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* 3 ACTION CTA CLUSTER */
        .dl-cta-cluster {
            margin: 10px 0 25px 0;
        }

        .dl-cta-top-row {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        @media (max-width: 560px) {
            .dl-cta-top-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        .dl-cta-btn-consult {
            background: #0284c7;
            border: 1px solid #0284c7;
            color: #ffffff !important;
            font-size: 0.98rem;
            font-weight: 700;
            padding: 12px 18px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.15);
            transition: all 0.2s ease;
        }

        .dl-cta-btn-consult:hover {
            background: #0369a1;
            border-color: #0369a1;
        }

        .dl-cta-btn-quote {
            background: #f0f9ff;
            border: 1.5px solid #bae6fd;
            color: #0369a1;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.08);
            transition: all 0.2s ease;
        }

        .dl-cta-btn-quote:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
        }

        .dl-cta-quote-title {
            font-size: 0.98rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0369a1;
            line-height: 1.2;
        }

        .dl-cta-quote-sub {
            font-size: 0.75rem;
            font-weight: 500;
            color: #0284c7;
            margin-top: 2px;
        }

        /* Khung Đăng ký Gọi lại */
        .dl-cta-phone-box {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 18px;
            box-sizing: border-box;
        }

        @media (max-width: 640px) {
            .dl-cta-phone-box {
                padding: 14px 14px;
            }
        }

        .dl-cta-phone-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .dl-cta-phone-icon-wrap {
            width: 32px;
            height: 32px;
            background: #e0f2fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            flex-shrink: 0;
        }

        .dl-cta-phone-text {
            font-size: 0.92rem;
            font-weight: 500;
            color: #334155;
            line-height: 1.45;
            margin: 0;
        }

        .dl-cta-phone-text strong {
            color: #0284c7;
            font-weight: 700;
        }

        .dl-cta-input-group {
            display: flex;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .dl-cta-phone-input {
            flex: 1;
            padding: 10px 14px;
            border: none;
            outline: none;
            font-size: 0.9rem;
            color: #0f172a;
            background: transparent;
            min-width: 0;
        }

        .dl-cta-phone-input::placeholder {
            color: #94a3b8;
        }

        .dl-cta-btn-send {
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 0 22px;
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dl-cta-btn-send:hover {
            background: #0284c7;
        }

        @media (max-width: 480px) {
            .dl-cta-input-group {
                flex-direction: column;
                background: transparent;
                border: none;
                gap: 8px;
            }
            .dl-cta-phone-input {
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 11px 12px;
                background: #ffffff;
                width: 100%;
                box-sizing: border-box;
            }
            .dl-cta-btn-send {
                width: 100%;
                padding: 12px 14px;
                border-radius: 6px;
            }
        }

        .dl-phone-quick-success {
            display: none;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 500;
            margin-top: 10px;
            text-align: center;
        }

        /* Guarantee List */
        .dl-single-guarantee {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 14px 18px;
            background: #f8fafc;
            border: 1px solid var(--dl-border);
            border-radius: 10px;
            font-size: 0.85rem;
            color: var(--dl-text-muted);
        }

        @media (max-width: 640px) {
            .dl-single-guarantee {
                padding: 12px 14px;
                font-size: 0.82rem;
            }
        }

        .dl-guarantee-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dl-guarantee-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--dl-primary);
            display: inline-block;
            flex-shrink: 0;
        }

        /* 3. Detailed Specifications & Highlights */
        .dl-single-sections {
            margin-top: 50px;
            border-top: 1px solid var(--dl-border);
            padding-top: 40px;
        }

        @media (max-width: 640px) {
            .dl-single-sections {
                margin-top: 30px;
                padding-top: 25px;
            }
        }

        .dl-section-block {
            margin-bottom: 45px;
        }

        @media (max-width: 640px) {
            .dl-section-block {
                margin-bottom: 30px;
            }
        }

        .dl-section-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--dl-text-main);
            margin: 0 0 16px 0;
            letter-spacing: -0.2px;
        }

        @media (max-width: 640px) {
            .dl-section-title {
                font-size: 1.15rem;
                margin-bottom: 12px;
            }
        }

        .dl-specs-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid var(--dl-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .dl-specs-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .dl-specs-table td {
            padding: 12px 18px;
            font-size: 0.92rem;
            border-bottom: 1px solid var(--dl-border);
        }

        @media (max-width: 640px) {
            .dl-specs-table td {
                padding: 10px 10px;
                font-size: 0.84rem;
            }
            .dl-specs-table td:first-child {
                width: 38% !important;
            }
        }

        .dl-specs-table td:first-child {
            color: #64748b;
            font-weight: 700;
            width: 32%;
        }

        .dl-specs-table td:last-child {
            color: var(--dl-text-main);
        }

        .dl-highlights-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
        }

        @media (max-width: 640px) {
            .dl-highlights-list {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        .dl-highlight-item {
            background: #f8fafc;
            border: 1px solid var(--dl-border);
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--dl-text-muted);
            line-height: 1.5;
        }

        @media (max-width: 640px) {
            .dl-highlight-item {
                padding: 12px 14px;
                font-size: 0.85rem;
            }
        }

        .dl-highlight-bullet {
            color: var(--dl-primary);
            font-weight: 800;
            font-size: 1rem;
        }

        /* 4. Dedicated Quote Modal Window ("Cửa sổ báo giá") */
        .dl-quote-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 999999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        @media (max-width: 640px) {
            .dl-quote-modal-overlay {
                padding: 10px;
            }
        }

        .dl-quote-modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .dl-quote-modal-container {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.96);
            transition: transform 0.2s ease;
            padding: 30px;
            box-sizing: border-box;
        }

        @media (max-width: 640px) {
            .dl-quote-modal-container {
                padding: 22px 14px;
                border-radius: 14px;
                max-height: 92vh;
            }
        }

        .dl-quote-modal-overlay.active .dl-quote-modal-container {
            transform: scale(1);
        }

        .dl-quote-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .dl-quote-modal-close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .dl-quote-modal-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--dl-text-main);
            margin: 0 0 6px 0;
            padding-right: 30px;
        }

        .dl-quote-modal-subtitle {
            font-size: 0.9rem;
            color: var(--dl-text-muted);
            margin: 0 0 20px 0;
            line-height: 1.5;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .dl-form-group {
            margin-bottom: 15px;
        }

        .dl-form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .dl-form-control {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 11px 14px;
            color: #0f172a;
            font-size: 0.92rem;
            box-sizing: border-box;
            outline: none;
            transition: all 0.2s ease;
        }

        .dl-form-control:focus {
            border-color: var(--dl-primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }

        .dl-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 600px) {
            .dl-form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        .dl-submit-btn {
            background: var(--dl-dark-btn);
            color: #ffffff;
            border: none;
            padding: 13px 28px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 10px;
        }

        .dl-submit-btn:hover {
            background: var(--dl-primary);
        }

        .dl-form-success {
            display: none;
            text-align: center;
            padding: 25px 15px;
        }

        /* 5. Related Products */
        .dl-related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 640px) {
            .dl-related-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }

        .dl-related-card {
            background: #ffffff;
            border: 1px solid var(--dl-border);
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .dl-related-card:hover {
            transform: translateY(-3px);
            border-color: #cbd5e1;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
        }

        .dl-related-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }

        .dl-related-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .dl-related-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--dl-text-main);
            margin: 0 0 10px 0;
            line-height: 1.35;
        }
    </style>

    <main class="dl-single-prod-wrapper">
        <!-- 1. Breadcrumbs -->
        <nav class="dl-single-breadcrumbs" aria-label="Điều hướng">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
            <span class="dl-bc-sep">/</span>
            <a href="<?php echo esc_url( home_url( '/store/' ) ); ?>">Cửa hàng DigiLens</a>
            <span class="dl-bc-sep">/</span>
            <span><?php echo esc_html( $cat_name ); ?></span>
            <span class="dl-bc-sep">/</span>
            <span style="color: var(--dl-text-main); font-weight: 600;"><?php the_title(); ?></span>
        </nav>

        <!-- 2. Main Hero Section -->
        <section class="dl-single-hero">
            <!-- Left Visuals -->
            <div class="dl-single-visuals">
                <div class="dl-single-img-card">
                    <?php if ( ! empty( $badge ) ) : ?>
                        <div class="dl-single-badge"><?php echo esc_html( $badge ); ?></div>
                    <?php endif; ?>
                    <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>" class="dl-single-main-img" />
                </div>

                <?php if ( ! empty( $ref_url ) ) : ?>
                    <a href="<?php echo esc_url( $ref_url ); ?>" class="dl-single-ref-card" target="_blank" rel="noopener">
                        <span><?php echo esc_html( $ref_title ); ?></span>
                        <span>→</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Right Product Information & 3 CTA Buttons -->
            <div class="dl-single-info">
                <span class="dl-single-cat"><?php echo esc_html( $cat_name ); ?></span>
                <h1 class="dl-single-title"><?php the_title(); ?></h1>

                <!-- Stock Status -->
                <div class="dl-single-stock">
                    <?php if ( $in_stock ) : ?>
                        <span class="dl-stock-badge in-stock"><span class="dl-stock-dot"></span> Còn hàng</span>
                    <?php else : ?>
                        <span class="dl-stock-badge out-stock"><span class="dl-stock-dot"></span> Hết hàng</span>
                    <?php endif; ?>
                </div>

                <!-- Short Tagline -->
                <p class="dl-single-tagline"><?php echo esc_html( $tagline ); ?></p>

                <!-- 3 Action CTA Cluster (Matching Mockup) -->
                <div class="dl-cta-cluster">
                    <div class="dl-cta-top-row">
                        <a href="https://zalo.me/0917834532" target="_blank" rel="noopener noreferrer" class="dl-cta-btn-consult">
                            TƯ VẤN NGAY
                        </a>
                        <button type="button" class="dl-cta-btn-quote" onclick="openQuoteModal('quote')">
                            <span class="dl-cta-quote-title">BÁO GIÁ</span>
                            <span class="dl-cta-quote-sub">(Được dùng thử trước khi thanh toán)</span>
                        </button>
                    </div>

                    <!-- Orange Callout Box -->
                    <div class="dl-cta-phone-box">
                        <div class="dl-cta-phone-header">
                            <div class="dl-cta-phone-icon-wrap">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <p class="dl-cta-phone-text">
                                Hãy để lại <strong>số điện thoại</strong>, chúng tôi sẽ gọi ngay cho bạn <strong>tư vấn miễn phí!</strong>
                            </p>
                        </div>
                        <form class="dl-cta-input-group" onsubmit="handleQuickPhoneSubmit(event)">
                            <input type="tel" id="quick-phone-input" class="dl-cta-phone-input" placeholder="Nhập sđt tư vấn miễn phí" required />
                            <button type="submit" class="dl-cta-btn-send" id="btn-quick-phone-send">GỬI ĐI</button>
                        </form>
                        <div class="dl-phone-quick-success" id="quick-phone-success"></div>
                    </div>
                </div>

                <!-- Guarantee Summary -->
                <div class="dl-single-guarantee">
                    <div class="dl-guarantee-item">
                        <span class="dl-guarantee-dot"></span> <strong>Sản phẩm DigiLens mới</strong> — Được cung cấp tại Việt Nam
                    </div>
                    <div class="dl-guarantee-item">
                        <span class="dl-guarantee-dot"></span> <strong>Bảo hành 24 tháng</strong> — Hỗ trợ kỹ thuật & đổi mới linh kiện
                    </div>
                    <div class="dl-guarantee-item">
                        <span class="dl-guarantee-dot"></span> <strong>Giao hàng toàn quốc</strong> — Vận chuyển bọc bảo hiểm an toàn
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Lower Content: Detailed Specs & Highlights -->
        <section class="dl-single-sections">
            <!-- Full Technical Specifications -->
            <?php if ( ! empty( $specs ) ) : ?>
                <div class="dl-section-block">
                    <h2 class="dl-section-title">THÔNG SỐ KỸ THUẬT CHI TIẾT</h2>
                    <table class="dl-specs-table">
                        <tbody>
                            <?php foreach ( $specs as $k => $v ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $k ); ?></td>
                                    <td><?php echo esc_html( $v ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Highlights -->
            <?php if ( ! empty( $highlights ) ) : ?>
                <div class="dl-section-block">
                    <h2 class="dl-section-title">ĐẶC ĐIỂM NỔI BẬT & TÍNH NĂNG</h2>
                    <ul class="dl-highlights-list">
                        <?php foreach ( $highlights as $item ) : ?>
                            <li class="dl-highlight-item">
                                <span class="dl-highlight-bullet">•</span>
                                <span><?php echo esc_html( $item ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Related Products in DigiLens Ecosystem -->
            <?php if ( ! empty( $related ) ) : ?>
                <div class="dl-section-block" style="margin-top:50px;">
                    <h2 class="dl-section-title">SẢN PHẨM CÙNG HỆ SINH THÁI DIGILENS</h2>
                    <div class="dl-related-grid">
                        <?php foreach ( $related as $rel ) : ?>
                            <a href="<?php echo esc_url( $rel['permalink'] ); ?>" class="dl-related-card">
                                <img src="<?php echo esc_url( $rel['image'] ); ?>" alt="<?php echo esc_attr( $rel['name'] ); ?>" class="dl-related-img" loading="lazy" />
                                <div class="dl-related-body">
                                    <div>
                                        <span class="dl-single-cat" style="font-size:0.72rem;"><?php echo esc_html( $rel['category_name'] ); ?></span>
                                        <h4 class="dl-related-title"><?php echo esc_html( $rel['name'] ); ?></h4>
                                    </div>
                                    <div>
                                        <span class="dl-stock-badge in-stock" style="font-size:0.75rem;padding:3px 8px;"><span class="dl-stock-dot"></span> Còn hàng</span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <details class="dl-product-geo-summary">
            <summary>Thông tin cung cấp và hỗ trợ tại Việt Nam</summary>
            <div class="dl-product-geo-summary-content">
                <p><strong><?php the_title(); ?></strong> được DigiLens Việt Nam cung cấp tại Việt Nam. Khách hàng có thể yêu cầu báo giá trên website, liên hệ hotline 1900.63.8400 hoặc đến văn phòng DigiLens để được tư vấn.</p>
                <p>Đơn vị bán và hỗ trợ: DigiLens Việt Nam · Tình trạng: <?php echo $in_stock ? 'Còn hàng' : 'Hết hàng'; ?> · Bảo hành: 24 tháng theo điều kiện áp dụng · Cập nhật: <?php echo esc_html( get_the_modified_date( 'd/m/Y', $p_id ) ); ?>.</p>
            </div>
        </details>
    </main>

    <!-- 4. Dedicated Quote Modal Window ("Cửa sổ báo giá") -->
    <div class="dl-quote-modal-overlay" id="single-quote-modal">
        <div class="dl-quote-modal-container">
            <button type="button" class="dl-quote-modal-close" onclick="closeQuoteModal()">&times;</button>
            <div class="dl-quote-modal-header">
                <h3 class="dl-quote-modal-title">GỬI YÊU CẦU BÁO GIÁ & TƯ VẤN DOANH NGHIỆP</h3>
                <p class="dl-quote-modal-subtitle">
                    Điền thông tin bên dưới để nhận bảng báo giá chi tiết, hợp đồng mẫu hoặc đăng ký trải nghiệm Demo sản phẩm <strong><?php the_title(); ?></strong>.
                </p>
            </div>

            <div class="dl-quote-modal-body">
                <form id="single-quote-form" onsubmit="handleSingleOrderSubmit(event)">
                    <input type="hidden" id="order-product-id" value="<?php echo esc_attr( $p_id ); ?>" />
                    <input type="hidden" id="order-product-name" value="<?php the_title_attribute(); ?>" />

                    <div class="dl-form-row">
                        <div class="dl-form-group">
                            <label for="order-phone">Số điện thoại *</label>
                            <input type="tel" id="order-phone" class="dl-form-control" placeholder="0901 234 567" required />
                        </div>
                        <div class="dl-form-group">
                            <label for="order-email">Địa chỉ Email</label>
                            <input type="email" id="order-email" class="dl-form-control" placeholder="contact@digilens.vn" />
                        </div>
                    </div>

                    <div class="dl-form-group">
                        <label for="order-name">Họ và tên</label>
                        <input type="text" id="order-name" class="dl-form-control" placeholder="Nguyễn Văn A" />
                    </div>

                    <div class="dl-form-group">
                        <label for="order-address">Địa chỉ nhận hàng</label>
                        <textarea id="order-address" class="dl-form-control" rows="2" placeholder="Nhập địa chỉ nhận hàng hoặc ghi chú liên hệ..."></textarea>
                    </div>

                    <button type="submit" class="dl-submit-btn" id="btn-submit-order">GỬI YÊU CẦU BÁO GIÁ</button>
                </form>

                <div class="dl-form-success" id="single-order-success">
                    <h3 style="font-size:1.35rem;margin:0 0 8px 0;color:var(--dl-text-main);">GỬI YÊU CẦU THÀNH CÔNG!</h3>
                    <p style="color:var(--dl-text-muted);font-size:0.92rem;margin-bottom:15px;">
                        Mã đơn hàng: <strong id="success-order-id" style="color:var(--dl-primary);">#DL-<?php echo time(); ?></strong><br>
                        Chuyên viên tư vấn của DigiLens Việt Nam đã nhận được đơn hàng và sẽ liên hệ lại với bạn trong thời gian sớm nhất!
                    </p>
                    <button type="button" class="dl-submit-btn" onclick="closeQuoteModal()" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:10px 24px;margin-top:10px;">ĐÓNG CỬA SỔ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Client JavaScript -->
    <script>
    function openQuoteModal() {
        var modal = document.getElementById('single-quote-modal');
        if (!modal) return;

        document.getElementById('single-quote-form').style.display = 'block';
        document.getElementById('single-order-success').style.display = 'none';

        modal.classList.add('active');

        setTimeout(function() {
            var phoneInput = document.getElementById('order-phone');
            if (phoneInput) phoneInput.focus();
        }, 100);
    }

    function closeQuoteModal() {
        var modal = document.getElementById('single-quote-modal');
        if (modal) modal.classList.remove('active');
    }

    document.getElementById('single-quote-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeQuoteModal();
        }
    });

    function handleQuickPhoneSubmit(e) {
        e.preventDefault();
        var input = document.getElementById('quick-phone-input');
        var phone = input.value.trim();
        var btn = document.getElementById('btn-quick-phone-send');
        if (!phone) return;

        btn.disabled = true;
        btn.innerText = '...';

        var formData = new FormData();
        formData.append('action', 'digilens_create_order');
        formData.append('product_id', '<?php echo esc_attr( $p_id ); ?>');
        formData.append('name', 'Khách hàng SĐT ' + phone);
        formData.append('phone', phone);
        formData.append('method', 'phone');
        formData.append('address', 'Yêu cầu gọi lại tư vấn từ trang: <?php the_title_attribute(); ?>');

        fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var msg = document.getElementById('quick-phone-success');
            var code = data.data && data.data.order_code ? ' (Mã đơn: ' + data.data.order_code + ')' : '';
            msg.innerHTML = 'Cảm ơn bạn! Đã ghi nhận số điện thoại <strong>' + phone + '</strong>' + code + '. Chuyên viên DigiLens sẽ gọi lại tư vấn ngay!';
            msg.style.display = 'block';
            input.value = '';
            btn.disabled = false;
            btn.innerText = 'GỬI ĐI';
        })
        .catch(function(err) {
            var msg = document.getElementById('quick-phone-success');
            msg.innerHTML = 'Cảm ơn bạn! Đã nhận số điện thoại <strong>' + phone + '</strong>. Chuyên viên DigiLens sẽ gọi lại tư vấn ngay!';
            msg.style.display = 'block';
            input.value = '';
            btn.disabled = false;
            btn.innerText = 'GỬI ĐI';
        });
    }

    function handleSingleOrderSubmit(e) {
        e.preventDefault();
        var btn = document.getElementById('btn-submit-order');
        btn.disabled = true;
        btn.innerText = 'ĐANG TẠO ĐƠN YÊU CẦU...';

        var formData = new FormData();
        formData.append('action', 'digilens_create_order');
        formData.append('product_id', document.getElementById('order-product-id').value);
        formData.append('phone', document.getElementById('order-phone').value);
        formData.append('email', document.getElementById('order-email').value);
        formData.append('name', document.getElementById('order-name').value);
        formData.append('address', document.getElementById('order-address').value);
        formData.append('method', 'quote');

        fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            document.getElementById('single-quote-form').style.display = 'none';
            document.getElementById('single-order-success').style.display = 'block';
            if (data.data && data.data.order_code) {
                document.getElementById('success-order-id').innerText = data.data.order_code;
            }
            btn.disabled = false;
            btn.innerText = 'GỬI YÊU CẦU BÁO GIÁ';
        })
        .catch(function(err) {
            document.getElementById('single-quote-form').style.display = 'none';
            document.getElementById('single-order-success').style.display = 'block';
            document.getElementById('success-order-id').innerText = '#DL-' + Math.floor(100000 + Math.random() * 900000);
            btn.disabled = false;
            btn.innerText = 'GỬI YÊU CẦU BÁO GIÁ';
        });
    }
    </script>
<?php 
endwhile;

get_footer();
