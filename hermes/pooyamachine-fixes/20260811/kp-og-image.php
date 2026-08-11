<?php
/**
 * kp-og-image v3 — og:image لوگو به عنوان آخرین مقدار (گوگل آخرین را می‌گیرد)
 */
add_action( 'wp_head', function () {
    if ( is_admin() ) return;
    $logo = 'https://pooyamachine.com/wp-content/uploads/2025/07/Frame-175d.png';
    echo '<meta property="og:image" content="' . esc_url( $logo ) . '" />' . "\n";
    echo '<meta property="og:image:width" content="512" />' . "\n";
    echo '<meta property="og:image:height" content="512" />' . "\n";
    echo '<meta property="og:image:alt" content="پویا ماشین البرز" />' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url( $logo ) . '" />' . "\n";
}, PHP_INT_MAX );
