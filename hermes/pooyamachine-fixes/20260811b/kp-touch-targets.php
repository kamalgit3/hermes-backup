<?php
/**
 * کد لمسی موبایل + بهینه‌سازی فونت — MiniMax
 * ۳۶ دکمه کوچک + font-display: swap
 */

add_action('wp_head', 'kp_touch_fonts_optimize', 5);

function kp_touch_fonts_optimize() {
    ?>
    <style id="kp-touch-fonts">
        /* ۱. المان‌های لمسی ≥ 44px (استاندارد اپل و گوگل) */
        .xtra-header-mobile .xtra-nav li a,
        .xtra-mobile-menu a,
        .elementor-button,
        a.button,
        .wp-block-button__link,
        .kp-call-btn,
        button,
        .menu-item a,
        .nav-links a,
        .woocommerce a.button,
        .woocommerce button.button,
        .read-more,
        .post-password-required input[type="submit"],
        .pm-cta,
        .kp-mobile-call {
            min-height: 44px !important;
            min-width: 44px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 8px 14px !important;
        }

        /* ۲. فاصله لمسی بین دکمه‌های مجاور */
        .menu-item + .menu-item,
        .nav-links > * + * {
            margin-top: 4px !important;
        }

        /* ۳. font-display: swap — جلوی مکث فونت را می‌گیرد */
        @font-face {
            font-family: 'iranyekan';
            src: local('iranyekan'), local('IRANYekan');
            font-display: swap;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: local('Vazirmatn');
            font-display: swap;
        }

        /* ۴. تکست سایز readonly برای iOS — جلوی زوم ناخواسته */
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea,
        select {
            font-size: 16px !important;
        }

        /* ۵. URL bar stable — رفع viewport jumping */
        body {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
    </style>
    <?php
}
