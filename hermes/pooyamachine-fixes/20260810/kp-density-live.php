<?php
/**
 * kp-density-live v2 — پنل داده زنده روی صفحات density
 * چاپ در wp_body_open (مستقل از the_content — برای Elementor/Xtra)
 */
add_action( 'wp_body_open', function () {
    if ( ! is_page() ) return;
    $slug = get_post_field( 'post_name', get_the_ID() );
    $map = array(
        'portland-cement'  => 'سیمان',
        'wheat-flour'      => 'آرد گندم',
        'cocoa-powder'     => 'پودر کاکائو',
        'corn-starch'      => 'نشاسته ذرت',
        'powdered-sugar'   => 'شکر پودری (پودر قند)',
        'whey-protein'     => 'پودر پروتئین وی',
        'milk-powder'      => 'پودر شیر کامل',
        'table-salt'       => 'نمک خوراکی ریز',
        'baking-soda'      => 'سدیم بی‌کربنات',
        'bentonite'        => 'بنتونیت',
        'calcium-carbonate'=> 'کلسیم کربنات',
        'gypsum-powder'    => 'گچ',
        'industrial-talc'  => 'تالک صنعتی',
        'iron-oxide-pigment' => 'آهن اکسید',
        '%da%86%da%af%d8%a7%d9%84%db%8c-%d8%a7%da%a9%d8%b3%db%8c%d8%af-%d8%a2%d9%87%d9%86-%d8%b1%d9%86%da%af%db%8c' => 'آهن اکسید',
        'silica-flour'     => 'پودر سیلیس',
    );
    if ( ! isset( $map[ $slug ] ) ) return;
    $mname = $map[ $slug ];

    $db = get_transient( 'kp_powder_db' );
    if ( ! is_array( $db ) ) {
        $raw = @file_get_contents( WP_CONTENT_DIR . '/uploads/powder-db.json' );
        if ( $raw === false ) return;
        $db = json_decode( $raw, true );
        if ( ! is_array( $db ) ) return;
        set_transient( 'kp_powder_db', $db, 12 * HOUR_IN_SECONDS );
    }

    $rec = null;
    foreach ( $db as $it ) {
        if ( isset( $it['name'] ) && $it['name'] === $mname ) { $rec = $it; break; }
    }
    if ( ! $rec ) return;

    $d = isset( $rec['density'] ) ? $rec['density'] : array();
    $min  = isset( $d['min'] ) ? $d['min'] : null;
    $typ  = isset( $d['typical'] ) ? $d['typical'] : null;
    $max  = isset( $d['max'] ) ? $d['max'] : null;
    $v = isset( $rec['validation_status'] ) ? $rec['validation_status'] : 'unverified';
    if ( $v === 'verified' ) {
        $badge = '<span style="background:#16a34a;color:#fff;padding:2px 12px;border-radius:12px;font-size:12px;font-weight:700">✅ داده تأییدشده</span>';
    } elseif ( $v === 'partial' ) {
        $badge = '<span style="background:#d97706;color:#fff;padding:2px 12px;border-radius:12px;font-size:12px;font-weight:700">⚠️ داده با اعتبار جزئی</span>';
    } else {
        $badge = '<span style="background:#64748b;color:#fff;padding:2px 12px;border-radius:12px;font-size:12px;font-weight:700">بدون منبع مستند</span>';
    }
    $src = isset( $rec['sources'] ) && is_array( $rec['sources'] ) ? implode( '، ', $rec['sources'] ) : '';

    $row = function ( $label, $val ) {
        return '<tr><td style="padding:9px 12px;border:1px solid #e2e8f0;background:#f8fafc;font-weight:700;width:45%">' . $label . '</td>'
             . '<td style="padding:9px 12px;border:1px solid #e2e8f0;text-align:center">' . $val . '</td></tr>';
    };

    echo '<div style="direction:rtl;font-family:Tahoma,Arial,sans-serif;background:#fff;border:2px solid #075fba;border-radius:14px;padding:18px 20px;margin:0 auto 26px;max-width:1100px;line-height:1.8;color:#1e293b">'
       . '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">'
       . '<h2 style="margin:0;color:#075fba;font-size:18px">📊 داده زنده «' . esc_html( $mname ) . '» — از دیتابیس مرکزی</h2>'
       . $badge . '</div>'
       . '<table style="width:100%;border-collapse:collapse;font-size:14px">'
       . $row( 'چگالی توده‌ای — حداقل', $min !== null ? $min . ' kg/L' : '—' )
       . $row( 'چگالی توده‌ای — متداول', $typ !== null ? $typ . ' kg/L' : '—' )
       . $row( 'چگالی توده‌ای — حداکثر', $max !== null ? $max . ' kg/L' : '—' )
       . $row( 'منبع', $src !== '' ? esc_html( $src ) : '—' )
       . '</table>'
       . '<p style="margin:14px 0 0;font-size:14px"><strong>ماشین‌حساب تبدیل:</strong> '
       . '<a href="/litr-be-kilo/?material=' . rawurlencode( $mname ) . '" style="background:#075fba;color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;display:inline-block">محاسبه لیتر ↔ کیلوگرم برای ' . esc_html( $mname ) . ' ←</a></p>'
       . '<p style="margin:10px 0 0;font-size:12px;color:#64748b">این مقادیر از یک منبع مرکزی خوانده می‌شوند و با ماشین‌حساب هماهنگ‌اند.</p>'
       . '</div>';
}, 5 );
