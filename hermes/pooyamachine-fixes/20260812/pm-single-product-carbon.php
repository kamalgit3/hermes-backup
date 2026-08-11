<?php
/**
 * Template Name: Product Landing — Carbon
 * Description: قالب پایه صفحه محصول با طراحی Carbon (IBM) — بدون نصب، فقط نمونه
 * جایگذاری: فایل را در قالب فرزند (child theme) قرار دهید:
 *   wp-content/themes/<child-theme>/single-product-custom.php
 * سپس صفحه را با این قالب بسازید.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

// ===== دیتای داینامیک از ووکامرس =====
$product_id = get_the_ID();
$_product   = wc_get_product( $product_id );
$name       = $_product ? $_product->get_name() : get_the_title();
$price      = $_product ? $_product->get_price_html() : '';
$sku        = $_product ? $_product->get_sku() : '';
$gallery    = $_product ? $_product->get_gallery_image_ids() : array();
$image      = $_product ? wp_get_attachment_image_url( $_product->get_image_id(), 'large' ) : '';
$cats       = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );

// مشخصات فنی (از ویژگی‌های محصول)
$attrs = array();
if ( $_product ) {
    foreach ( $_product->get_attributes() as $attr_key => $attr ) {
        if ( $attr->is_taxonomy() ) {
            $terms = wp_get_post_terms( $product_id, $attr->get_name(), array( 'fields' => 'names' ) );
            $attrs[ $attr->get_name() ] = implode( ', ', $terms );
        } else {
            $attrs[ $attr->get_name() ] = $attr->get_options() ? implode( ', ', $attr->get_options() ) : $attr->get_value();
        }
    }
}

// CTA — شماره تماس (ثابت از تنظیمات سایت)
$phone = get_option( 'pm_phone', '09124154262' );
$whatsapp = 'https://wa.me/98' . ltrim( $phone, '0' );
?>

<div class="pm-carbon-wrap">

  <!-- ===== هدر چسبان ===== -->
  <header class="pm-topbar">
    <div class="pm-brand">
      <div class="pm-logo"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
      <span class="pm-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
    </div>
    <span class="pm-model"><?php echo esc_html( $name ); ?></span>
  </header>

  <div class="pm-page">

    <!-- ===== هیرو: تصویر + خلاصه ===== -->
    <div class="pm-hero">
      <div class="pm-hero-img">
        <?php if ( $image ) : ?>
          <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
        <?php else : ?>
          <span>عکس محصول</span>
        <?php endif; ?>
      </div>
      <div class="pm-hero-body">
        <h1><?php echo esc_html( $name ); ?></h1>
        <p class="pm-subtitle">
          <?php
          $short = $_product ? $_product->get_short_description() : '';
          echo esc_html( wp_strip_all_tags( $short ) );
          ?>
        </p>
        <div class="pm-tags">
          <?php if ( $price ) : ?><span class="pm-tag pm-price"><?php echo wp_kses_post( $price ); ?></span><?php endif; ?>
          <?php if ( $cats ) : ?><span class="pm-tag"><?php echo esc_html( implode( ' | ', $cats ) ); ?></span><?php endif; ?>
        </div>
        <div class="pm-cta-row">
          <a class="pm-btn pm-btn-primary" href="#pm-contact">درخواست پیش‌فاکتور</a>
          <a class="pm-btn pm-btn-secondary" href="tel:<?php echo esc_attr( $phone ); ?>">تماس</a>
        </div>
      </div>
    </div>

    <!-- ===== خلاصه محصول (توضیحات) ===== -->
    <section class="pm-section">
      <h2>خلاصه محصول</h2>
      <div class="pm-desc">
        <?php echo wp_kses_post( apply_filters( 'the_content', $_product ? $_product->get_description() : '' ) ); ?>
      </div>
    </section>

    <!-- ===== ویژگی‌های برجسته ===== -->
    <?php if ( $attrs ) : ?>
    <section class="pm-section">
      <h2>ویژگی‌های برجسته</h2>
      <div class="pm-features">
        <?php foreach ( array_slice( $attrs, 0, 4 ) as $k => $v ) : ?>
          <div class="pm-feature">
            <strong><?php echo esc_html( wc_attribute_label( $k ) ); ?></strong>
            <span><?php echo esc_html( $v ); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- ===== مشخصات فنی ===== -->
    <?php if ( $attrs ) : ?>
    <section class="pm-section">
      <h2>مشخصات فنی</h2>
      <ul class="pm-specs">
        <?php foreach ( $attrs as $k => $v ) : ?>
          <li><span class="pm-k"><?php echo esc_html( wc_attribute_label( $k ) ); ?></span><span class="pm-v"><?php echo esc_html( $v ); ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <!-- ===== گالری تصاویر ===== -->
    <?php if ( $gallery ) : ?>
    <section class="pm-section">
      <h2>گالری تصاویر</h2>
      <div class="pm-gallery">
        <?php foreach ( $gallery as $gid ) : ?>
          <?php $gurl = wp_get_attachment_image_url( $gid, 'large' ); ?>
          <?php if ( $gurl ) : ?>
            <a href="<?php echo esc_url( $gurl ); ?>" class="pm-gallery-item">
              <img src="<?php echo esc_url( $gurl ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- ===== FAQ / توضیحات تکمیلی (اگر موجود باشد) ===== -->
    <?php
    $faq = get_post_meta( $product_id, '_pm_faq', true );
    if ( $faq && is_array( $faq ) ) : ?>
    <section class="pm-section">
      <h2>سؤالات متداول</h2>
      <?php foreach ( $faq as $item ) : ?>
        <details>
          <summary><?php echo esc_html( $item['q'] ); ?></summary>
          <dd><?php echo esc_html( $item['a'] ); ?></dd>
        </details>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- ===== فرم تماس (پیش‌فاکتور) ===== -->
    <section class="pm-section" id="pm-contact">
      <h2>درخواست پیش‌فاکتور</h2>
      <form class="pm-form" method="post" action="">
        <?php wp_nonce_field( 'pm_rfq', 'pm_rfq_nonce' ); ?>
        <input type="hidden" name="pm_product" value="<?php echo esc_attr( $name ); ?>">
        <div class="pm-field">
          <label for="pm-name">نام و نام خانوادگی</label>
          <input id="pm-name" name="pm_name" type="text" required>
        </div>
        <div class="pm-field">
          <label for="pm-company">نام شرکت</label>
          <input id="pm-company" name="pm_company" type="text">
        </div>
        <div class="pm-field">
          <label for="pm-phone">تلفن / واتساپ</label>
          <input id="pm-phone" name="pm_phone" type="tel" required>
        </div>
        <div class="pm-field">
          <label for="pm-note">نیازمندی‌های خاص</label>
          <textarea id="pm-note" name="pm_note" rows="3"></textarea>
        </div>
        <button class="pm-btn pm-btn-primary pm-block" type="submit">ارسال درخواست پیش‌فاکتور</button>
      </form>

      <?php
      // پردازش فرم (خروجی ساده — باید با WP Mail یا افزونه CRM متصل شود)
      if ( isset( $_POST['pm_rfq_nonce'] ) && wp_verify_nonce( $_POST['pm_rfq_nonce'], 'pm_rfq' ) ) {
          $to      = get_option( 'admin_email' );
          $subject = 'درخواست پیش‌فاکتور: ' . sanitize_text_field( $_POST['pm_product'] );
          $body    = 'نام: ' . sanitize_text_field( $_POST['pm_name'] ) . "\n"
                   . 'شرکت: ' . sanitize_text_field( $_POST['pm_company'] ) . "\n"
                   . 'تلفن: ' . sanitize_text_field( $_POST['pm_phone'] ) . "\n"
                   . 'نیازمندی‌ها: ' . sanitize_textarea_field( $_POST['pm_note'] ) . "\n";
          wp_mail( $to, $subject, $body );
          echo '<p class="pm-success">درخواست شما ثبت شد. تیم فروش به‌زودی تماس می‌گیرد.</p>';
      }
      ?>
    </section>

  </div>

  <!-- ===== نوار چسبان موبایل ===== -->
  <nav class="pm-sticky">
    <a class="pm-btn pm-btn-primary" href="#pm-contact">پیش‌فاکتور</a>
    <a class="pm-btn pm-btn-tertiary" href="tel:<?php echo esc_attr( $phone ); ?>">تماس</a>
    <a class="pm-btn pm-btn-tertiary" href="<?php echo esc_url( $whatsapp ); ?>">واتساپ</a>
  </nav>

</div>

<style>
/* ===== Carbon Design — اقتباس برای پویا ماشین ===== */
:root{
  --pm-accent:#0f62fe; --pm-accent-h:#0353e9; --pm-accent-a:#002d9c; --pm-accent-10:#edf5ff;
  --pm-bg:#f4f4f4; --pm-surface:#fff; --pm-border:#c6c6c6;
  --pm-text:#161616; --pm-text2:#525252; --pm-text3:#6f6f6f;
  --pm-success:#24a148; --pm-success-10:#defbe6;
}
*{box-sizing:border-box}
body{font-family:'Vazirmatn',system-ui,sans-serif;background:var(--pm-bg);color:var(--pm-text);line-height:1.7;margin:0;padding-bottom:76px}
img{max-width:100%}
.pm-topbar{background:var(--pm-text);color:#fff;display:flex;justify-content:space-between;align-items:center;padding:12px 16px;position:sticky;top:0;z-index:50}
.pm-brand{display:flex;gap:10px;align-items:center}
.pm-logo{background:var(--pm-accent);width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-weight:700}
.pm-model{font-size:12px;background:#393939;padding:4px 10px;color:#c6c6c6}
.pm-hero{background:var(--pm-surface);border-bottom:1px solid var(--pm-border)}
.pm-hero-img{background:#e8eef9;display:flex;align-items:center;justify-content:center;aspect-ratio:16/9;width:100%}
.pm-hero-img img{width:100%;height:100%;object-fit:cover}
.pm-hero-body{padding:24px 16px 28px}
.pm-hero h1{font-size:24px;margin:0 0 6px;line-height:1.4}
.pm-subtitle{color:var(--pm-text2);font-size:15px;margin:0 0 16px}
.pm-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
.pm-tag{background:var(--pm-accent-10);color:var(--pm-accent);padding:6px 12px;font-size:12.5px}
.pm-tag.pm-price{background:var(--pm-success-10);color:#0e6027;font-weight:600}
.pm-cta-row{display:flex;gap:10px}
.pm-btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:12px 20px;font-family:inherit;font-size:15px;font-weight:600;border:1px solid transparent;border-radius:0;cursor:pointer;text-decoration:none}
.pm-btn-primary{background:var(--pm-accent);color:#fff}.pm-btn-primary:hover{background:var(--pm-accent-h)}
.pm-btn-secondary{background:#393939;color:#fff}
.pm-btn-tertiary{background:transparent;color:var(--pm-accent);border-color:var(--pm-accent)}
.pm-block{width:100%}
.pm-section{background:var(--pm-surface);border-bottom:1px solid var(--pm-border);padding:28px 16px}
.pm-section h2{font-size:18px;margin:0 0 16px;display:flex;gap:8px;align-items:center}
.pm-section h2::before{content:"";width:4px;height:20px;background:var(--pm-accent)}
.pm-features{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--pm-border);border:1px solid var(--pm-border)}
.pm-feature{background:var(--pm-surface);padding:16px 14px}
.pm-feature strong{display:block;font-size:14.5px;margin-bottom:6px}
.pm-feature span{font-size:13px;color:var(--pm-text2)}
.pm-specs{list-style:none;margin:0;padding:0;border:1px solid var(--pm-border)}
.pm-specs li{display:flex;justify-content:space-between;gap:12px;padding:12px 14px;border-bottom:1px solid var(--pm-border);font-size:14px}
.pm-specs .pm-k{color:var(--pm-text2)}
.pm-specs .pm-v{font-weight:600;text-align:left}
.pm-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px}
.pm-gallery-item img{width:100%;height:120px;object-fit:cover}
details{border-bottom:1px solid var(--pm-border)}
summary{padding:14px 2px;font-weight:600;cursor:pointer;list-style:none;min-height:48px;display:flex;justify-content:space-between}
summary::after{content:"+";color:var(--pm-accent)}
details[open] summary::after{content:"−"}
details dd{margin:0;padding:0 2px 16px;color:var(--pm-text2);font-size:14px}
.pm-form{display:grid;gap:14px}
.pm-field label{font-size:12px;color:var(--pm-text2);margin-bottom:6px;display:block}
.pm-field input,.pm-field textarea{width:100%;font-family:inherit;font-size:16px;background:var(--pm-bg);border:none;border-bottom:2px solid transparent;padding:12px 14px;min-height:48px}
.pm-field input:focus,.pm-field textarea:focus{outline:none;border-bottom:2px solid var(--pm-accent);background:var(--pm-surface)}
.pm-sticky{position:fixed;bottom:0;left:0;right:0;z-index:100;background:#fff;border-top:1px solid var(--pm-border);display:flex;gap:10px;padding:12px 16px}
.pm-sticky .pm-btn{flex:1;min-height:52px}
.pm-success{background:var(--pm-success-10);color:#0e6027;padding:12px 16px;margin-top:12px}
@media(min-width:768px){
  body{padding-bottom:0}
  .pm-page{max-width:1080px;margin:0 auto;padding:32px 24px 64px}
  .pm-section{border:1px solid var(--pm-border);margin-bottom:16px;padding:32px}
  .pm-hero{display:grid;grid-template-columns:1fr 1fr;padding:0}
  .pm-hero-img{aspect-ratio:auto;min-height:340px}
  .pm-hero-body{padding:40px 36px}
  .pm-hero h1{font-size:32px}
  .pm-specs{display:grid;grid-template-columns:1fr 1fr}
  .pm-sticky{display:none}
}
</style>

<?php get_footer(); ?>
