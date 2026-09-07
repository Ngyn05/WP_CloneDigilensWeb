<?php
/**
 * Template Name: DigiLens Store
 * Description: Clean, bright, professional hardware store showcasing products introduced on DigiLens.com
 *
 * @package DigiLens
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$theme_uri = get_template_directory_uri();
$snapshot_uri = $theme_uri . '/snapshot';

// Fetch products dynamically from WordPress / WooCommerce Database
$products = function_exists( 'digilens_get_store_products' ) ? digilens_get_store_products() : array();

// Fallback if DB was empty before init
if ( empty( $products ) && function_exists( 'digilens_seed_woocommerce_products_if_empty' ) ) {
    digilens_seed_woocommerce_products_if_empty();
    $products = digilens_get_store_products();
}

get_header();
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

        .dl-store-wrapper {
            background: #ffffff;
            min-height: 80vh;
            padding: 30px 20px 80px 20px;
            max-width: 1240px;
            margin: 0 auto;
            color: var(--dl-text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            box-sizing: border-box;
            width: 100%;
        }

        @media (max-width: 640px) {
            .dl-store-wrapper {
                padding: 16px 12px 60px 12px;
            }
        }

        /* 1. Hero Section */
        .dl-store-hero {
            text-align: center;
            padding: 20px 10px 35px 10px;
            max-width: 820px;
            margin: 0 auto;
        }

        @media (max-width: 640px) {
            .dl-store-hero {
                padding: 10px 5px 24px 5px;
            }
        }

        .dl-store-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .dl-store-title {
            font-size: clamp(1.35rem, 5vw, 2.35rem);
            font-weight: 800;
            color: var(--dl-text-main);
            margin: 0 0 12px 0;
            line-height: 1.28;
            letter-spacing: -0.5px;
            word-break: break-word;
        }

        .dl-store-subtitle {
            font-size: 0.95rem;
            color: var(--dl-text-muted);
            line-height: 1.6;
            margin: 0 auto 20px auto;
        }

        .dl-store-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 15px;
        }

        @media (max-width: 640px) {
            .dl-store-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
        }

        .dl-stat-card {
            background: #f8fafc;
            border: 1px solid var(--dl-border);
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
        }

        @media (max-width: 640px) {
            .dl-stat-card {
                padding: 10px 6px;
            }
        }

        .dl-stat-val {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--dl-primary-dark);
            margin-bottom: 2px;
        }

        .dl-stat-lbl {
            font-size: 0.76rem;
            color: var(--dl-text-light);
            font-weight: 500;
        }

        /* 2. Category Filter Bar */
        .dl-filter-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 25px 0 32px 0;
            padding: 6px;
            background: #f1f5f9;
            border-radius: 10px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            max-width: 100%;
            box-sizing: border-box;
        }

        .dl-filter-btn {
            background: transparent;
            border: none;
            color: var(--dl-text-muted);
            font-size: 0.88rem;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dl-filter-btn:hover {
            color: var(--dl-text-main);
        }

        .dl-filter-btn.active {
            background: #ffffff;
            color: var(--dl-text-main);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 640px) {
            .dl-filter-bar {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                background: transparent;
                padding: 0;
                margin: 16px 0 24px 0;
            }
            .dl-filter-btn {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 10px 8px !important;
                font-size: 0.8rem !important;
                font-weight: 700;
                color: #475569;
                line-height: 1.3;
                min-height: 44px;
                box-sizing: border-box;
            }
            .dl-filter-btn.active {
                background: #0f172a;
                color: #ffffff !important;
                border-color: #0f172a;
                box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
            }
        }

        /* 3. Product Cards Grid */
        .dl-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 24px;
            margin-bottom: 50px;
        }

        @media (max-width: 640px) {
            .dl-products-grid {
                grid-template-columns: 1fr;
                gap: 16px;
                margin-bottom: 35px;
            }
        }

        .dl-product-card {
            background: #ffffff;
            border: 1px solid var(--dl-border);
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .dl-product-card:hover {
            transform: translateY(-4px);
            border-color: #cbd5e1;
            box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.08);
        }

        .dl-card-img-wrap {
            position: relative;
            width: 100%;
            height: 250px;
            background: #f8fafc;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f1f5f9;
            padding: 14px;
            box-sizing: border-box;
        }

        @media (max-width: 640px) {
            .dl-card-img-wrap {
                height: 210px;
                padding: 10px;
            }
        }

        .dl-card-img-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            text-decoration: none;
        }

        .dl-card-img {
            width: 100%;
            height: 100%;
            max-height: 220px;
            object-fit: contain !important;
            transition: transform 0.35s ease;
        }

        @media (max-width: 640px) {
            .dl-card-img {
                max-height: 190px;
            }
        }

        .dl-product-card:hover .dl-card-img {
            transform: scale(1.04);
        }

        .dl-card-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
            color: #334155;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .dl-card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        @media (max-width: 640px) {
            .dl-card-body {
                padding: 16px 14px;
            }
        }

        .dl-card-cat {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--dl-primary);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
        }

        .dl-card-title-link {
            text-decoration: none;
            color: inherit;
        }

        .dl-card-title {
            font-size: 1.12rem;
            font-weight: 800;
            color: var(--dl-text-main);
            margin: 0 0 8px 0;
            line-height: 1.35;
            transition: color 0.2s ease;
        }

        @media (max-width: 640px) {
            .dl-card-title {
                font-size: 1.05rem;
            }
        }

        .dl-card-title-link:hover .dl-card-title {
            color: var(--dl-primary);
        }

        .dl-card-stock {
            margin-bottom: 16px;
        }

        .dl-stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 3px 10px;
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
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .dl-card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }

        @media (max-width: 360px) {
            .dl-card-actions {
                grid-template-columns: 1fr;
            }
        }

        .dl-btn-detail {
            background: #ffffff;
            color: var(--dl-text-main);
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.86rem;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dl-btn-detail:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .dl-btn-quote-card {
            background: var(--dl-dark-btn);
            color: #ffffff;
            border: 1px solid var(--dl-dark-btn);
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.86rem;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dl-btn-quote-card:hover {
            background: var(--dl-primary);
            border-color: var(--dl-primary);
        }

        /* 4. Guarantee Section */
        .dl-guarantee-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            background: #f8fafc;
            border: 1px solid var(--dl-border);
            border-radius: 12px;
            padding: 24px;
            margin-top: 20px;
        }

        @media (max-width: 640px) {
            .dl-guarantee-bar {
                grid-template-columns: 1fr;
                gap: 14px;
                padding: 18px 14px;
            }
        }

        .dl-guarantee-col {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .dl-guarantee-heading {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--dl-text-main);
        }

        .dl-guarantee-desc {
            font-size: 0.85rem;
            color: var(--dl-text-muted);
            line-height: 1.5;
        }

        /* 5. Modal Styling */
        .dl-modal-overlay {
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
            .dl-modal-overlay {
                padding: 10px;
            }
        }

        .dl-modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .dl-modal-container {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            width: 100%;
            max-width: 720px;
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
            .dl-modal-container {
                padding: 22px 14px;
                border-radius: 14px;
                max-height: 92vh;
            }
        }

        .dl-modal-overlay.active .dl-modal-container {
            transform: scale(1);
        }

        .dl-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #475569;
            width: 32px;
            height: 32px;
            border-radius: 50% !important;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
            margin: 0 !important;
            outline: none !important;
            box-shadow: none !important;
            overflow: hidden !important;
            -webkit-appearance: none !important;
            appearance: none !important;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .dl-modal-close:hover {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .dl-modal-header {
            margin-bottom: 20px;
            padding-right: 35px;
        }

        .dl-modal-cat {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--dl-primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 4px;
        }

        .dl-modal-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dl-text-main);
            margin: 0 0 10px 0;
            line-height: 1.3;
        }

        .dl-modal-img-wrap {
            background: #f8fafc;
            border: 1px solid var(--dl-border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 260px;
        }

        .dl-modal-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .dl-modal-section-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--dl-text-main);
            margin: 20px 0 10px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .dl-specs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 0.88rem;
        }

        .dl-specs-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .dl-specs-table td {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
        }

        .dl-specs-table td:first-child {
            color: #64748b;
            font-weight: 700;
            width: 35%;
        }

        .dl-specs-table td:last-child {
            color: var(--dl-text-main);
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
            background: linear-gradient(180deg, #1e70bf 0%, #0e4c8d 100%);
            border: 1px solid #0d47a1;
            color: #ffffff !important;
            font-size: 1rem;
            font-weight: 800;
            padding: 13px 16px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(14, 76, 141, 0.35);
            transition: all 0.2s ease;
        }

        .dl-cta-btn-consult:hover {
            background: linear-gradient(180deg, #2584e0 0%, #155ea8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(14, 76, 141, 0.45);
        }

        .dl-cta-btn-quote {
            background: linear-gradient(180deg, #ff8c00 0%, #e65100 100%);
            border: 1px solid #cc4400;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(230, 81, 0, 0.35);
            transition: all 0.2s ease;
        }

        .dl-cta-btn-quote:hover {
            background: linear-gradient(180deg, #ffa022 0%, #f45d07 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(230, 81, 0, 0.45);
        }

        .dl-cta-quote-title {
            font-size: 1.1rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .dl-cta-quote-sub {
            font-size: 0.78rem;
            font-weight: 500;
            opacity: 0.95;
            margin-top: 3px;
        }

        /* Orange Callout Box */
        .dl-cta-phone-box {
            background: linear-gradient(135deg, #ff7a00 0%, #ff5100 100%);
            border-radius: 10px;
            padding: 16px 20px;
            box-shadow: 0 6px 18px rgba(255, 81, 0, 0.3);
            color: #ffffff;
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
            gap: 12px;
            margin-bottom: 12px;
        }

        .dl-cta-phone-icon-wrap {
            width: 34px;
            height: 34px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }

        .dl-cta-phone-text {
            font-size: 0.94rem;
            font-weight: 600;
            line-height: 1.4;
            margin: 0;
        }

        .dl-cta-phone-text strong {
            color: #ffee00;
            font-weight: 800;
        }

        .dl-cta-input-group {
            display: flex;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            background: #ffffff;
        }

        .dl-cta-phone-input {
            flex: 1;
            padding: 12px 14px;
            border: none;
            outline: none;
            font-size: 0.95rem;
            color: #0f172a;
            background: #ffffff;
            min-width: 0;
        }

        .dl-cta-phone-input::placeholder {
            color: #94a3b8;
        }

        .dl-cta-btn-send {
            background: #8b0000;
            color: #ffffff;
            border: none;
            padding: 0 22px;
            font-size: 0.95rem;
            font-weight: 900;
            letter-spacing: 0.5px;
            cursor: pointer;
            text-transform: uppercase;
            transition: background 0.2s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dl-cta-btn-send:hover {
            background: #a00000;
        }

        @media (max-width: 480px) {
            .dl-cta-input-group {
                flex-direction: column;
                background: transparent;
                box-shadow: none;
                border: none;
                gap: 8px;
            }
            .dl-cta-phone-input {
                border-radius: 6px;
                width: 100%;
                box-sizing: border-box;
                padding: 12px 14px;
            }
            .dl-cta-btn-send {
                width: 100%;
                padding: 12px 16px;
                border-radius: 6px;
            }
        }

        .dl-cta-btn-send:hover {
            background: #a00000;
        }

        .dl-phone-quick-success {
            display: none;
            background: rgba(0, 0, 0, 0.25);
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-top: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Order Form */
        .dl-order-form {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .dl-form-group {
            margin-bottom: 14px;
        }

        .dl-form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 5px;
        }

        .dl-form-control {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
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
            gap: 14px;
        }

        @media (max-width: 600px) {
            .dl-form-row {
                grid-template-columns: 1fr;
            }
        }

        .dl-submit-btn {
            background: var(--dl-dark-btn);
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 0.98rem;
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
    </style>

    <main class="dl-store-wrapper" id="main-store">
        <!-- 1. Hero Section -->
        <section class="dl-store-hero">
            <div class="dl-store-badge">
                SẢN PHẨM DIGILENS • VIỆT NAM
            </div>
            <h1 class="dl-store-title">SẢN PHẨM & CÔNG NGHỆ QUANG HỌC DIGILENS</h1>
            <p class="dl-store-subtitle">
                Tìm hiểu các thiết bị kính thông minh ARGO™, thấu kính dẫn sóng Crystal™ và các giải pháp thực tế tăng cường (AR) được cung cấp tại Việt Nam cho doanh nghiệp và kỹ sư nghiên cứu.
            </p>

            <!-- Clean Stats Grid -->
            <div class="dl-store-stats">
                <div class="dl-stat-card">
                    <div class="dl-stat-val">50° FOV</div>
                    <div class="dl-stat-lbl">Góc nhìn rộng</div>
                </div>
                <div class="dl-stat-card">
                    <div class="dl-stat-val">85%+</div>
                    <div class="dl-stat-lbl">Độ truyền quang</div>
                </div>
                <div class="dl-stat-card">
                    <div class="dl-stat-val">Snapdragon®</div>
                    <div class="dl-stat-lbl">XR2 Gen 1</div>
                </div>
                <div class="dl-stat-card">
                    <div class="dl-stat-val">ANSI Z87.1</div>
                    <div class="dl-stat-lbl">Chuẩn an toàn</div>
                </div>
            </div>
        </section>

        <!-- 2. Category Filter Bar -->
        <section class="dl-filter-bar" aria-label="Bộ lọc danh mục">
            <button type="button" class="dl-filter-btn active" data-cat="all">TẤT CẢ SẢN PHẨM</button>
            <button type="button" class="dl-filter-btn" data-cat="smartglasses">KÍNH THÔNG MINH ARGO™</button>
            <button type="button" class="dl-filter-btn" data-cat="waveguides">THẤU KÍNH DẪN SÓNG</button>
            <button type="button" class="dl-filter-btn" data-cat="accessories,devkits">PHẦN MỀM & PHỤ KIỆN</button>
        </section>

        <!-- 3. Product Cards Grid -->
        <section class="dl-products-grid" id="products-grid">
            <?php foreach ( $products as $prod ) : 
                $c_slug = isset( $prod['category_slug'] ) ? $prod['category_slug'] : ( isset( $prod['category'] ) ? $prod['category'] : 'all' );
            ?>
                <article class="dl-product-card" data-cat="<?php echo esc_attr( $c_slug ); ?>">
                    <div class="dl-card-img-wrap">
                        <?php if ( ! empty( $prod['badge'] ) ) : ?>
                            <div class="dl-card-badge"><?php echo esc_html( $prod['badge'] ); ?></div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( $prod['permalink'] ); ?>" class="dl-card-img-link" title="<?php echo esc_attr( $prod['name'] ); ?>">
                            <img src="<?php echo esc_url( $prod['image'] ); ?>" alt="<?php echo esc_attr( $prod['name'] ); ?>" class="dl-card-img" loading="lazy" />
                        </a>
                    </div>
                    <div class="dl-card-body">
                        <div>
                            <span class="dl-card-cat"><?php echo esc_html( $prod['category_name'] ); ?></span>
                            <a href="<?php echo esc_url( $prod['permalink'] ); ?>" class="dl-card-title-link">
                                <h3 class="dl-card-title"><?php echo esc_html( $prod['name'] ); ?></h3>
                            </a>
                            <div class="dl-card-stock">
                                <span class="dl-stock-badge in-stock"><span class="dl-stock-dot"></span> Còn hàng</span>
                            </div>
                        </div>

                        <div class="dl-card-actions">
                            <a href="<?php echo esc_url( $prod['permalink'] ); ?>" class="dl-btn-detail">Chi tiết</a>
                            <button type="button" class="dl-btn-quote-card" onclick="openProductModal('<?php echo esc_js( $prod['id'] ); ?>')">Báo giá</button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <!-- 4. Quality Guarantee Bar -->
        <section class="dl-guarantee-bar">
            <div class="dl-guarantee-col">
                <div class="dl-guarantee-heading">Sản phẩm DigiLens</div>
                <div class="dl-guarantee-desc">Thông tin nguồn hàng, hóa đơn và chứng từ đi kèm được xác nhận theo từng đơn hàng.</div>
            </div>
            <div class="dl-guarantee-col">
                <div class="dl-guarantee-heading">Bảo Hành 24 Tháng</div>
                <div class="dl-guarantee-desc">Chính sách bảo hành 24 tháng, hỗ trợ linh kiện quang học và kỹ thuật tận nơi theo điều kiện áp dụng.</div>
            </div>
            <div class="dl-guarantee-col">
                <div class="dl-guarantee-heading">Demo & Dùng Thử</div>
                <div class="dl-guarantee-desc">Đăng ký trải nghiệm thực tế kính ARGO™ trước khi thanh toán và triển khai dự án doanh nghiệp.</div>
            </div>
        </section>
    </main>

    <!-- 5. Product Quote Modal Window -->
    <div class="dl-modal-overlay" id="product-modal">
        <div class="dl-modal-container" style="max-width:580px;">
            <button type="button" class="dl-modal-close" onclick="closeProductModal()" aria-label="Đóng">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <div class="dl-modal-header" style="margin-bottom:16px;">
                <span class="dl-modal-cat" id="modal-cat">Danh mục</span>
                <h2 class="dl-modal-title" id="modal-title" style="margin-bottom:6px;">Tên sản phẩm</h2>
                <div id="modal-stock-badge">
                    <span class="dl-stock-badge in-stock"><span class="dl-stock-dot"></span> Còn hàng</span>
                </div>
            </div>

            <div class="dl-modal-body">
                <!-- Order / Quotation Form -->
                <div class="dl-order-form" id="order-form-box" style="margin-top:0;">
                    <div class="dl-modal-section-title" style="margin-top:0;border-bottom:none;padding-bottom:0;font-size:1.15rem;font-weight:800;color:#0f172a;">GỬI YÊU CẦU BÁO GIÁ & TƯ VẤN DOANH NGHIỆP</div>
                    <p style="color:#64748b;font-size:0.88rem;margin:4px 0 16px 0;">Điền thông tin bên dưới để nhận bảng báo giá chi tiết, hợp đồng mẫu hoặc đăng ký trải nghiệm Demo sản phẩm.</p>
                    <form id="store-order-form" onsubmit="handleOrderSubmit(event)">
                        <input type="hidden" id="order-product-id" name="product_id" value="" />
                        <input type="hidden" id="order-product-name" name="product_name" value="" />

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
                            <label for="order-address">Địa chỉ / Ghi chú yêu cầu</label>
                            <textarea id="order-address" class="dl-form-control" rows="2" placeholder="Nhập địa chỉ nhận hàng hoặc ghi chú thêm..."></textarea>
                        </div>

                        <button type="submit" class="dl-submit-btn" id="btn-submit-order">GỬI YÊU CẦU BÁO GIÁ</button>
                    </form>

                    <div class="dl-form-success" id="order-success-msg">
                        <h3 style="font-size:1.35rem;margin:0 0 8px 0;color:var(--dl-text-main);">GỬI YÊU CẦU THÀNH CÔNG!</h3>
                        <p style="color:var(--dl-text-muted);font-size:0.92rem;margin-bottom:15px;">
                            Mã đơn hàng: <strong id="success-order-id" style="color:var(--dl-primary);">#DL-<?php echo time(); ?></strong><br>
                            Chuyên viên tư vấn của DigiLens Việt Nam đã nhận được thông tin và sẽ liên hệ lại với bạn trong thời gian sớm nhất!
                        </p>
                        <button type="button" class="dl-btn-detail" onclick="closeProductModal()" style="padding:10px 24px;margin-top:10px;cursor:pointer;">ĐÓNG CỬA SỔ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Client-Side JavaScript -->
    <script>
    var currentProducts = <?php echo wp_json_encode( $products ); ?>;

    // Filter by Category
    document.querySelectorAll('.dl-filter-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.dl-filter-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            var targetCat = this.getAttribute('data-cat') || 'all';
            var allowedCats = targetCat.split(',').map(function(s) { return s.trim(); });

            document.querySelectorAll('.dl-product-card').forEach(function(card) {
                var cardCat = card.getAttribute('data-cat') || '';
                if (targetCat === 'all' || allowedCats.indexOf(cardCat) !== -1) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Quick Quote Modal
    function openProductModal(prodId) {
        var prod = currentProducts.find(function(p) { return p.id === prodId || p.db_id == prodId; });
        if (!prod) return;

        document.getElementById('modal-cat').innerText = prod.category_name;
        document.getElementById('modal-title').innerText = prod.name;

        // Pre-fill hidden fields
        document.getElementById('order-product-id').value = prod.db_id || prod.id;
        document.getElementById('order-product-name').value = prod.name;

        // Reset form
        document.getElementById('store-order-form').style.display = 'block';
        document.getElementById('order-success-msg').style.display = 'none';

        // Show Modal
        var modal = document.getElementById('product-modal');
        modal.classList.add('active');

        var container = document.querySelector('.dl-modal-container');
        if (container) {
            container.scrollTop = 0;
        }

        setTimeout(function() {
            var phoneInput = document.getElementById('order-phone');
            if (phoneInput) {
                phoneInput.focus();
            }
        }, 150);
    }

    function closeProductModal() {
        var modal = document.getElementById('product-modal');
        if (modal) modal.classList.remove('active');
    }

    document.getElementById('product-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeProductModal();
        }
    });

    function focusQuoteForm() {
        var formBox = document.getElementById('order-form-box');
        if (formBox) {
            formBox.scrollIntoView({ behavior: 'smooth' });
            var phoneInput = document.getElementById('order-phone');
            if (phoneInput) phoneInput.focus();
        }
    }

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
        formData.append('product_id', document.getElementById('order-product-id') ? document.getElementById('order-product-id').value : '0');
        formData.append('name', 'Khách hàng SĐT ' + phone);
        formData.append('phone', phone);
        formData.append('method', 'phone');
        formData.append('address', 'Yêu cầu gọi lại tư vấn từ Cửa hàng DigiLens');

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

    function handleOrderSubmit(e) {
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
            document.getElementById('order-form-box').style.display = 'none';
            document.getElementById('order-success-msg').style.display = 'block';
            if (data.data && data.data.order_code) {
                document.getElementById('success-order-id').innerText = data.data.order_code;
            }
            btn.disabled = false;
            btn.innerText = 'GỬI YÊU CẦU BÁO GIÁ';
        })
        .catch(function(err) {
            document.getElementById('order-form-box').style.display = 'none';
            document.getElementById('order-success-msg').style.display = 'block';
            document.getElementById('success-order-id').innerText = '#DL-' + Math.floor(100000 + Math.random() * 900000);
            btn.disabled = false;
            btn.innerText = 'GỬI YÊU CẦU BÁO GIÁ';
        });
    }
    </script>
<?php
get_footer();
