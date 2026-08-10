<?php
/**
 * one-shot: آپدیت متای Rank Math صفحه litr-be-kilo — اجرا روی اولین بازدید عمومی
 */
add_action( 'wp', function () {
    if ( is_admin() ) return;
    if ( get_option( 'kp_meta_fill_fix_done' ) ) return;
    $pid = 19446;
    $desc = 'تبدیل لیتر به کیلوگرم و بالعکس برای ۱۸۰ ماده پودری صنعتی و غذایی. محاسبه حجم واقعی میکسر با ضریب پرشدگی ۸۰٪ + پیشنهاد دستگاه. ابزار مهندسی پویا ماشین البرز.';
    update_post_meta( $pid, 'rank_math_description', $desc );
    update_option( 'kp_meta_fill_fix_done', 1 );
    error_log( 'kp-meta-fill-fix: rank_math_description updated for 19446' );
} );
