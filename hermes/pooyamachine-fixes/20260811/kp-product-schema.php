<?php
/**
 * kp-product-schema — اسکیمای Product روی صفحات محصول (WooCommerce)
 * بدون offers چون محصولات قیمت ندارند (سفارشی/تماس بگیرید)
 */
add_action( 'wp_head', function () {
    if ( ! is_singular( 'product' ) ) return;
    $p = wc_get_product( get_the_ID() );
    if ( ! $p ) return;

    $name = $p->get_name();
    $desc = wp_strip_all_tags( $p->get_short_description() );
    if ( $desc === '' ) {
        $desc = wp_strip_all_tags( $p->get_description() );
    }
    $desc = mb_substr( $desc, 0, 300 );

    $img = '';
    $img_id = $p->get_image_id();
    if ( $img_id ) {
        $img = wp_get_attachment_image_url( $img_id, 'full' );
    }

    $sku = $p->get_sku();
    $url = get_permalink( $p->get_id() );

    $schema = array(
        '@context' => 'https://schema.org',
        '@type'    => 'Product',
        'name'     => $name,
        'url'      => $url,
        'image'    => $img,
        'description' => $desc,
        'brand'    => array(
            '@type' => 'Brand',
            'name'  => 'پویا ماشین البرز',
        ),
        'category' => 'Industrial Mixer',
        'inLanguage' => 'fa-IR',
    );
    if ( $sku ) $schema['sku'] = $sku;

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
} );
