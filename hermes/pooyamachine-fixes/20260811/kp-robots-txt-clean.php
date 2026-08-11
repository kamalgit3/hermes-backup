<?php
/**
 * kp-robots-txt-clean v2 — سرو robots.txt تمیز با hook do_robots (بسیار زود)
 * این hook در wp-includes/functions.php:do_robots() صدا زده می‌شود و پیش از هر خروجی دیگری است
 */
add_action( 'do_robots', function () {
    $robots = <<<ROBOTS
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Disallow: /wp-content/uploads/wc-logs/
Disallow: /wp-content/uploads/woocommerce_transient_files/
Disallow: /wp-content/uploads/woocommerce_uploads/
Disallow: /*?add-to-cart=

# AI Bot Access - Allow major AI crawlers
User-agent: GPTBot
Allow: /
User-agent: ChatGPT-User
Allow: /
User-agent: Google-Extended
Allow: /
User-agent: Claude-Web
Allow: /
User-agent: anthropic-ai
Allow: /
User-agent: PerplexityBot
Allow: /
User-agent: CCBot
Allow: /
User-agent: DuckDuckBot
Allow: /
User-agent: FacebookBot
Allow: /
User-agent: OAI-SearchBot
Allow: /
User-agent: ClaudeBot
Allow: /

Sitemap: https://pooyamachine.com/sitemap_index.xml

# Content Signals
Content-Signal: ai-train=yes, search=yes, ai-input=yes
ROBOTS;

    header( 'Content-Type: text/plain; charset=utf-8' );
    echo $robots;
    exit;
}, 0 );