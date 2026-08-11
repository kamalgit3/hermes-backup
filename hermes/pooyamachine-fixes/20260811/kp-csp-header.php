<?php
/**
 * kp-csp-header — Content-Security-Policy header
 * انعطاف‌پذیر برای اسکریپت‌های موجود (Botpress, Elementor, etc.)
 */
add_action( 'send_headers', function () {
    if ( is_admin() ) return;
    
    // CSP که با اسکریپت‌های موجود سازگار است
    $csp = implode( '; ', array(
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.botpress.cloud https://files.bpcontent.cloud https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
        "img-src 'self' data: https:",
        "connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud https://www.google-analytics.com https://region1.google-analytics.com",
        "frame-src 'self' https://cdn.botpress.cloud",
        "form-action 'self'",
        "base-uri 'self'",
        "object-src 'none'",
    ) );
    
    header( "Content-Security-Policy: $csp" );
}, 1 );