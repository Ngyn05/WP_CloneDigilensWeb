<?php
/**
 * DigiLens Floating Quick Contact & Office Network Widget
 * Floating button at bottom-right with quick contact dropdown and full office modal.
 * Palette: Đen (#000a73, #0f172a), Trắng (#ffffff), Xanh nhạt (#00b0f0, #38bdf8, #e0f2fe).
 *
 * @package DigiLens
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', 'digilens_render_floating_contact_widget' );

function digilens_render_floating_contact_widget() {
    ?>
    <!-- DigiLens Floating Action Button & Contact Widgets -->
    <style>
        /* 1. Floating Action Button (FAB) */
        .dl-fab-wrap {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 99998;
            font-family: inherit;
        }
        .dl-fab-btn {
            position: relative;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, #000a73 0%, #00b0f0 100%);
            border: 2px solid #ffffff;
            box-shadow: 0 6px 20px rgba(0, 176, 240, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #ffffff;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            outline: none;
            padding: 0;
            margin: 0;
            -webkit-appearance: none;
            appearance: none;
        }
        .dl-fab-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 8px 25px rgba(0, 176, 240, 0.6);
        }
        .dl-fab-btn:active {
            transform: scale(0.95);
        }
        .dl-fab-pulse {
            position: absolute;
            top: -6px;
            left: -6px;
            right: -6px;
            bottom: -6px;
            border-radius: 50%;
            border: 2px solid rgba(0, 176, 240, 0.7);
            animation: dl-fab-ripple 2s infinite cubic-bezier(0, 0.2, 0.8, 1);
            pointer-events: none;
        }
        .dl-fab-pulse-2 {
            position: absolute;
            top: -12px;
            left: -12px;
            right: -12px;
            bottom: -12px;
            border-radius: 50%;
            border: 1.5px solid rgba(0, 176, 240, 0.4);
            animation: dl-fab-ripple 2s infinite cubic-bezier(0, 0.2, 0.8, 1) 0.5s;
            pointer-events: none;
        }
        @keyframes dl-fab-ripple {
            0% { transform: scale(0.85); opacity: 1; }
            100% { transform: scale(1.4); opacity: 0; }
        }

        /* 2. Quick Contact Card (Popup Card) */
        .dl-quick-card {
            position: fixed;
            bottom: 96px;
            right: 28px;
            width: 320px;
            max-width: calc(100vw - 32px);
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(0, 10, 115, 0.18), 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(0, 176, 240, 0.2);
            overflow: hidden;
            z-index: 99999;
            display: none;
            animation: dl-slide-up 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .dl-quick-card.active {
            display: block;
        }
        @keyframes dl-slide-up {
            from { opacity: 0; transform: translateY(12px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .dl-quick-card-header {
            background: linear-gradient(135deg, #000a73 0%, #0084c7 100%);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
        }
        .dl-quick-card-title {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
            letter-spacing: 0.2px;
        }
        .dl-quick-card-close {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #ffffff;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            transition: background 0.2s;
        }
        .dl-quick-card-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .dl-quick-card-body {
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #ffffff;
        }
        .dl-contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            text-decoration: none !important;
            transition: all 0.2s ease;
            cursor: pointer;
            color: inherit;
        }
        .dl-contact-item:hover {
            border-color: #00b0f0;
            background: #f0f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 176, 240, 0.12);
        }
        .dl-contact-icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dl-icon-hotline {
            background: linear-gradient(135deg, #000a73 0%, #00b0f0 100%);
            color: #ffffff;
        }
        .dl-icon-zalo {
            background: #0068ff;
            color: #ffffff;
        }
        .dl-icon-office {
            background: #0284c7;
            color: #ffffff;
        }
        .dl-contact-info {
            flex-grow: 1;
            min-width: 0;
        }
        .dl-contact-lbl {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 2px;
            font-weight: 500;
        }
        .dl-contact-val {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .dl-contact-val.hotline-highlight {
            color: #000a73;
            font-weight: 800;
            font-size: 1.05rem;
        }
        .dl-contact-val.sub-action {
            color: #0284c7;
            font-size: 0.88rem;
            font-weight: 600;
        }

        /* 3. Office Network Full Modal */
        .dl-office-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            z-index: 999999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .dl-office-overlay.active {
            display: flex;
        }
        .dl-office-modal {
            background: #ffffff;
            width: 100%;
            max-width: 820px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 10, 115, 0.25);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            animation: dl-modal-pop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes dl-modal-pop {
            from { opacity: 0; transform: scale(0.94); }
            to { opacity: 1; transform: scale(1); }
        }
        .dl-office-header {
            background: linear-gradient(135deg, #000a73 0%, #0084c7 100%);
            padding: 20px 24px;
            color: #ffffff;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dl-office-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .dl-office-header-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }
        .dl-office-header-sub {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #7dd3fc;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .dl-office-header-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
        }
        .dl-office-close {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #ffffff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            transition: background 0.2s, transform 0.2s;
        }
        .dl-office-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.08);
        }
        .dl-office-body {
            padding: 24px;
            overflow-y: auto;
            background: #f8fafc;
        }
        .dl-office-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .dl-office-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .dl-office-card:hover {
            border-color: #00b0f0;
            box-shadow: 0 8px 20px rgba(0, 176, 240, 0.08);
        }
        .dl-office-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .dl-office-badge {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .dl-office-region {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
        }
        .dl-office-name {
            font-size: 1.08rem;
            font-weight: 700;
            color: #000a73;
            margin: 0 0 16px 0;
            line-height: 1.35;
        }
        .dl-office-detail-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 0.88rem;
            line-height: 1.5;
            color: #334155;
        }
        .dl-office-icon-dot {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .dl-office-maps-link {
            display: inline-block;
            color: #0284c7;
            font-weight: 700;
            font-size: 0.84rem;
            text-decoration: none;
            margin-top: 4px;
            transition: color 0.2s;
        }
        .dl-office-maps-link:hover {
            color: #000a73;
            text-decoration: underline;
        }
        .dl-office-footer-bar {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .dl-office-footer-text h4 {
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 2px 0;
        }
        .dl-office-footer-text p {
            font-size: 0.84rem;
            color: #64748b;
            margin: 0;
        }
        .dl-office-btn-hotline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #000a73 0%, #00b0f0 100%);
            color: #ffffff !important;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none !important;
            box-shadow: 0 4px 14px rgba(0, 176, 240, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .dl-office-btn-hotline:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 176, 240, 0.5);
        }

        /* Responsive Mobile Styles */
        @media (max-width: 640px) {
            .dl-fab-wrap {
                bottom: 20px;
                right: 18px;
            }
            .dl-fab-btn {
                width: 52px;
                height: 52px;
            }
            .dl-quick-card {
                right: 18px;
                bottom: 84px;
                width: calc(100vw - 36px);
            }
            .dl-office-modal {
                max-height: 95vh;
            }
            .dl-office-header {
                padding: 16px 18px;
            }
            .dl-office-header-title {
                font-size: 1.15rem;
            }
            .dl-office-body {
                padding: 16px;
            }
            .dl-office-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            .dl-office-footer-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 14px 18px;
                text-align: center;
            }
            .dl-office-btn-hotline {
                justify-content: center;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>

    <!-- 1. FAB (Floating Action Button) -->
    <div class="dl-fab-wrap">
        <button type="button" class="dl-fab-btn" id="dl-btn-fab-trigger" aria-label="Liên hệ nhanh DigiLens">
            <span class="dl-fab-pulse"></span>
            <span class="dl-fab-pulse-2"></span>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
        </button>
    </div>

    <!-- 2. Quick Contact Card -->
    <div class="dl-quick-card" id="dl-quick-contact-card">
        <div class="dl-quick-card-header">
            <h3 class="dl-quick-card-title">Liên hệ với DigiLens VN</h3>
            <button type="button" class="dl-quick-card-close" id="dl-close-quick-card" aria-label="Đóng">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="dl-quick-card-body">
            <!-- Hotline -->
            <a href="tel:1900638400" class="dl-contact-item">
                <div class="dl-contact-icon-wrap dl-icon-hotline">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div class="dl-contact-info">
                    <div class="dl-contact-lbl">Hotline Tổng Đài</div>
                    <div class="dl-contact-val hotline-highlight">1900.63.8400</div>
                </div>
            </a>

            <!-- Zalo -->
            <a href="https://zalo.me/0917834532" target="_blank" rel="noopener noreferrer" class="dl-contact-item">
                <div class="dl-contact-icon-wrap dl-icon-zalo">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.87 1.5 5.43 3.86 7.08-.18.79-.69 2.52-.79 2.92-.12.48.18.47.37.35.15-.1 2.12-1.44 2.98-2.03.51.08 1.04.12 1.58.12 5.52 0 10-4.03 10-9s-4.48-9-10-9zm.86 12.83h-3.4c-.28 0-.46-.24-.46-.48 0-.15.07-.3.2-.42l2.67-3.04H8.78c-.28 0-.46-.22-.46-.48 0-.25.18-.48.46-.48h3.32c.28 0 .46.23.46.48 0 .15-.07.3-.2.43l-2.67 3.03h3.17c.28 0 .46.22.46.48 0 .26-.18.48-.46.48z"/></svg>
                </div>
                <div class="dl-contact-info">
                    <div class="dl-contact-lbl">Chat qua Zalo</div>
                    <div class="dl-contact-val sub-action">Nhắn tin tư vấn ngay</div>
                </div>
            </a>

            <!-- Office Network Trigger -->
            <div class="dl-contact-item" id="dl-trigger-office-modal">
                <div class="dl-contact-icon-wrap dl-icon-office">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="dl-contact-info">
                    <div class="dl-contact-lbl">Công ty DigiLens Việt Nam</div>
                    <div class="dl-contact-val sub-action">Xem hệ thống văn phòng</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Office Network Full Modal -->
    <div class="dl-office-overlay" id="dl-office-modal-overlay">
        <div class="dl-office-modal">
            <!-- Modal Header -->
            <div class="dl-office-header">
                <div class="dl-office-header-left">
                    <div class="dl-office-header-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 7v14M21 7v14M6 11h4M6 15h4M14 11h4M14 15h4M10 21V3l4 4"/></svg>
                    </div>
                    <div>
                        <div class="dl-office-header-sub">DIGILENS VIỆT NAM</div>
                        <h3 class="dl-office-header-title">Hệ thống văn phòng</h3>
                    </div>
                </div>
                <button type="button" class="dl-office-close" id="dl-close-office-modal" aria-label="Đóng">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <!-- Modal Body (2 Branches Grid) -->
            <div class="dl-office-body">
                <div class="dl-office-grid">
                    <!-- Branch 1: Hanoi -->
                    <div class="dl-office-card">
                        <div class="dl-office-card-top">
                            <span class="dl-office-badge">HÀ NỘI</span>
                            <span class="dl-office-region">Chi nhánh miền Bắc</span>
                        </div>
                        <h4 class="dl-office-name">Công ty DigiLens Việt Nam – Chi nhánh Hà Nội</h4>
                        
                        <div class="dl-office-detail-row">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Địa chỉ:</strong>
                                <span>Số 226 Đường Láng, Phường Thịnh Quang, Quận Đống Đa, Hà Nội</span><br>
                                <a href="https://maps.google.com/?q=226+Đường+Láng+Thịnh+Quang+Đống+Đa+Hà+Nội" target="_blank" rel="noopener noreferrer" class="dl-office-maps-link">
                                    Xem trên Google Maps &rarr;
                                </a>
                            </div>
                        </div>

                        <div class="dl-office-detail-row">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Điện thoại:</strong>
                                <a href="tel:02473053268" style="color:#0284c7;text-decoration:none;font-weight:700;">024.7305.3268</a>
                            </div>
                        </div>

                        <div class="dl-office-detail-row">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Email:</strong>
                                <a href="mailto:contact@digilens.vn" style="color:#0284c7;text-decoration:none;font-weight:600;">contact@digilens.vn</a>
                            </div>
                        </div>

                        <div class="dl-office-detail-row" style="margin-bottom:0;">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Giờ làm việc:</strong>
                                <span style="color:#64748b;">8:00 – 18:00 · Thứ 2 đến Thứ 7</span>
                            </div>
                        </div>
                    </div>

                    <!-- Branch 2: Ho Chi Minh City -->
                    <div class="dl-office-card">
                        <div class="dl-office-card-top">
                            <span class="dl-office-badge" style="background:#e0f2fe;color:#0284c7;">TP. HỒ CHÍ MINH</span>
                            <span class="dl-office-region">Chi nhánh miền Nam</span>
                        </div>
                        <h4 class="dl-office-name">Công ty DigiLens Việt Nam – Chi nhánh Hồ Chí Minh</h4>
                        
                        <div class="dl-office-detail-row">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Địa chỉ:</strong>
                                <span>Số 137 Đường Hòa Hưng, Phường 12, Quận 10, TP. Hồ Chí Minh</span><br>
                                <a href="https://maps.google.com/?q=137+Đường+Hòa+Hưng+Phường+12+Quận+10+Hồ+Chí+Minh" target="_blank" rel="noopener noreferrer" class="dl-office-maps-link">
                                    Xem trên Google Maps &rarr;
                                </a>
                            </div>
                        </div>

                        <div class="dl-office-detail-row">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Điện thoại:</strong>
                                <a href="tel:02873053268" style="color:#0284c7;text-decoration:none;font-weight:700;">028.7305.3268</a>
                            </div>
                        </div>

                        <div class="dl-office-detail-row">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Email:</strong>
                                <a href="mailto:contact@digilens.vn" style="color:#0284c7;text-decoration:none;font-weight:600;">contact@digilens.vn</a>
                            </div>
                        </div>

                        <div class="dl-office-detail-row" style="margin-bottom:0;">
                            <div class="dl-office-icon-dot">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div>
                                <strong style="color:#0f172a;display:block;margin-bottom:2px;">Giờ làm việc:</strong>
                                <span style="color:#64748b;">8:00 – 18:00 · Thứ 2 đến Thứ 7</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Bar -->
            <div class="dl-office-footer-bar">
                <div class="dl-office-footer-text">
                    <h4>Cần tư vấn ngay?</h4>
                    <p>Đội ngũ chuyên gia DigiLens luôn sẵn sàng hỗ trợ bạn</p>
                </div>
                <a href="tel:1900638400" class="dl-office-btn-hotline">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>HOTLINE 1900.63.8400</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Client-Side Toggle Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var fabBtn       = document.getElementById('dl-btn-fab-trigger');
        var quickCard    = document.getElementById('dl-quick-contact-card');
        var closeCardBtn = document.getElementById('dl-close-quick-card');
        var officeTrigger= document.getElementById('dl-trigger-office-modal');
        var officeModal  = document.getElementById('dl-office-modal-overlay');
        var closeOffices = document.getElementById('dl-close-office-modal');

        // Toggle Quick Card
        if (fabBtn && quickCard) {
            fabBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                quickCard.classList.toggle('active');
            });
        }

        // Close Quick Card
        if (closeCardBtn && quickCard) {
            closeCardBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                quickCard.classList.remove('active');
            });
        }

        // Open Office Modal
        if (officeTrigger && officeModal) {
            officeTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                if (quickCard) quickCard.classList.remove('active');
                officeModal.classList.add('active');
            });
        }

        // Close Office Modal
        if (closeOffices && officeModal) {
            closeOffices.addEventListener('click', function() {
                officeModal.classList.remove('active');
            });
        }

        if (officeModal) {
            officeModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    officeModal.classList.remove('active');
                }
            });
        }

        // Close quick card on document click outside
        document.addEventListener('click', function(e) {
            if (quickCard && quickCard.classList.contains('active')) {
                if (!quickCard.contains(e.target) && !fabBtn.contains(e.target)) {
                    quickCard.classList.remove('active');
                }
            }
        });
    });
    </script>
    <?php
}
