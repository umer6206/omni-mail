<?php
/**
 * "More From Us" suite promo section.
 *
 * Set $omni_suite_exclude before including:
 *   'mail' | 'seo' | 'tracking'
 * Set $omni_suite_assets_url to the plugin assets base URL ending with /assets/
 *
 * @package OmniNext
 */

if (!defined('WPINC')) {
  die;
}

if (empty($omni_suite_assets_url) || empty($omni_suite_exclude)) {
  return;
}

$omni_suite_exclude = sanitize_key($omni_suite_exclude);
$suite_base = trailingslashit($omni_suite_assets_url) . 'images/suite/';
$suite_ver = defined('OMNIMAIL_VERSION') ? OMNIMAIL_VERSION : (defined('OMNISEO_VERSION') ? OMNISEO_VERSION : (defined('OMNITRACKING_VERSION') ? OMNITRACKING_VERSION : '1'));

$all_products = array(
  'chat' => array(
    'id' => 'chat',
    'label' => 'OmniChat',
    'title' => 'Omni Chat',
    'description' => 'AI-powered live chat that engages your visitors 24/7, captures leads, and drives conversions automatically.',
    'logo' => 'omnichat-logo.png',
    'url' => 'https://platform.omninexttech.com/dashboard/omni-chat/agents',
  ),
  'seo' => array(
    'id' => 'seo',
    'label' => 'OmniSEO',
    'title' => 'Omni SEO',
    'description' => 'Smart SEO automation that optimizes your content, tracks rankings, and boosts organic traffic with zero guesswork.',
    'logo' => 'omniseo-logo.png',
    'url' => 'https://platform.omninexttech.com/dashboard/omni-seo',
  ),
  'mail' => array(
    'id' => 'mail',
    'label' => 'OmniMail',
    'title' => 'Omni Mail',
    'description' => 'Intelligent email marketing that personalizes campaigns, automates sequences, and maximises open rates at scale.',
    'logo' => 'omnimail-logo.png',
    'url' => 'https://omnimail-app.omninexttech.com',
  ),
  'tracking' => array(
    'id' => 'tracking',
    'label' => 'OmniTracking',
    'title' => 'Omni Tracking',
    'description' => 'End-to-end analytics that tracks every customer touchpoint, attribution, and funnel stage so you know exactly what drives revenue.',
    'logo' => 'omnitracking-logo.png',
    'url' => 'https://platform.omninexttech.com/dashboard/omni-roi',
  ),
  'pay' => array(
    'id' => 'pay',
    'label' => 'OmniExchange',
    'title' => 'OmniExchange',
    'description' => 'Seamless payment processing that accepts any method, reduces cart abandonment, and gets you paid faster with zero friction.',
    'logo' => 'omnipay-logo.png',
    'url' => 'https://omniexchange-app.omninexttech.com/',
  ),
  'voice' => array(
    'id' => 'voice',
    'label' => 'OmniVoice',
    'title' => 'Omni Voice',
    'description' => 'AI voice agents that answer calls, qualify leads, and book appointments automatically — so you never miss a conversation.',
    'logo' => 'omnivoice-logo.png',
    'url' => 'https://omnivoice-app.omninexttech.com/',
  ),
);

// Default order matches the design; current product is replaced by Omni Voice.
$order = array('chat', 'seo', 'mail', 'tracking', 'pay');
$products = array();
foreach ($order as $key) {
  if ($key === $omni_suite_exclude) {
    $products[] = $all_products['voice'];
    continue;
  }
  $products[] = $all_products[$key];
}
?>

<style>
.omni-suite-section {
  margin-top: 28px;
  margin-bottom: 24px;
  padding: 28px 24px 32px;
  border-radius: 12px;
  border: 1px solid rgba(0, 210, 255, 0.18);
  background: linear-gradient(180deg, rgba(10, 14, 33, 0.95) 0%, rgba(8, 12, 28, 0.98) 100%);
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
  box-sizing: border-box;
  display: flow-root;
  overflow: visible;
}
.omni-suite-header {
  margin-bottom: 20px;
}
.omni-suite-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 8px;
  color: #fff;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.2;
}
.omni-suite-title .dashicons {
  color: #00d2ff;
  font-size: 22px;
  width: 22px;
  height: 22px;
}
.omni-suite-sub {
  margin: 0;
  color: #9ca3af;
  font-size: 14px;
  line-height: 1.5;
  max-width: 720px;
}
.omni-suite-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
  align-items: stretch;
  width: 100%;
  margin: 0;
  padding: 0 0 4px;
  box-sizing: border-box;
}
.omni-suite-card {
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
  padding: 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(15, 23, 42, 0.85);
  box-sizing: border-box;
  transition: border-color 0.15s ease, transform 0.15s ease;
}
.omni-suite-card:hover {
  border-color: rgba(0, 210, 255, 0.45);
  transform: translateY(-1px);
}
.omni-suite-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  min-height: 36px;
}
.omni-suite-logo-wrap {
  height: 32px;
  width: auto;
  max-width: 150px;
  border-radius: 6px;
  overflow: hidden;
  background: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  flex-shrink: 0;
}
.omni-suite-logo-wrap img {
  height: 32px;
  width: auto;
  max-width: 150px;
  object-fit: contain;
  object-position: left center;
  display: block;
}
.omni-suite-brand-label {
  display: none;
}
.omni-suite-card h3 {
  margin: 0 0 8px;
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  line-height: 1.3;
}
.omni-suite-card p {
  margin: 0 0 16px;
  color: #9ca3af;
  font-size: 13px;
  line-height: 1.55;
  flex: 1;
}
.omni-suite-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  align-self: flex-start;
  margin-top: auto;
  padding: 7px 12px;
  border-radius: 6px;
  border: 1px solid #00d2ff;
  color: #00d2ff !important;
  background: transparent;
  text-decoration: none !important;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.2;
  transition: background 0.15s ease, color 0.15s ease;
}
.omni-suite-cta:hover {
  background: rgba(0, 210, 255, 0.12);
  color: #7df0ff !important;
}
@media (max-width: 1200px) {
  .omni-suite-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
@media (max-width: 782px) {
  .omni-suite-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<section class="omni-suite-section" aria-labelledby="omni-suite-heading">
  <div class="omni-suite-header">
    <h2 id="omni-suite-heading" class="omni-suite-title">
      <span class="dashicons dashicons-screenoptions" aria-hidden="true"></span>
      <?php echo esc_html__('More From Us', 'omninext'); ?>
    </h2>
    <p class="omni-suite-sub">
      <?php echo esc_html__('Explore the full Omni suite — powerful tools built to grow your business end-to-end.', 'omninext'); ?>
    </p>
  </div>
  <div class="omni-suite-grid">
    <?php foreach ($products as $product) : ?>
      <article class="omni-suite-card">
        <div class="omni-suite-brand">
          <span class="omni-suite-logo-wrap">
            <img
              src="<?php echo esc_url($suite_base . $product['logo'] . '?v=' . rawurlencode($suite_ver)); ?>"
              alt="<?php echo esc_attr($product['label']); ?>"
              loading="lazy"
            >
          </span>
          <span class="omni-suite-brand-label"><?php echo esc_html($product['label']); ?></span>
        </div>
        <h3><?php echo esc_html($product['title']); ?></h3>
        <p><?php echo esc_html($product['description']); ?></p>
        <a
          class="omni-suite-cta"
          href="<?php echo esc_url($product['url']); ?>"
          target="_blank"
          rel="noopener noreferrer"
        >
          <?php echo esc_html__('Learn More', 'omninext'); ?>
          <span aria-hidden="true">&gt;</span>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
