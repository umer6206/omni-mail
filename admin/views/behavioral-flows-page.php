<?php
/**
 * Behavioral Flows Settings page template
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin/views
 */

if (!defined('WPINC')) {
    die;
}

$connection_id = get_option('omnimail_connection_id', '');
$api_key = get_option('omnimail_api_key', '');
$is_configured = !empty($connection_id) && !empty($api_key);
$pro_url = defined('OMNIMAIL_SUBSCRIPTION_URL')
    ? OMNIMAIL_SUBSCRIPTION_URL
    : 'https://omnimail-app.omninexttech.com/dashboard/mail-blaze/subscription';

$flows = array(
    array(
        'id' => 'flow_cart_abandonment',
        'data' => 'cartAbandonmentEnabled',
        'sms_data' => 'smsCartAbandonmentEnabled',
        'type' => 'cartAbandonment',
        'label' => __('Cart Abandonment', 'omnimail'),
        'desc' => __('Send email after 1 hour if cart value > $20', 'omnimail'),
    ),
    array(
        'id' => 'flow_browse_abandonment',
        'data' => 'browseAbandonmentEnabled',
        'sms_data' => 'smsBrowseAbandonmentEnabled',
        'type' => 'browseAbandonment',
        'label' => __('Browse Abandonment', 'omnimail'),
        'desc' => __('Send email after 24 hours if product viewed but not added to cart', 'omnimail'),
    ),
    array(
        'id' => 'flow_checkout_abandonment',
        'data' => 'checkoutAbandonmentEnabled',
        'sms_data' => 'smsCheckoutAbandonmentEnabled',
        'type' => 'checkoutAbandonment',
        'label' => __('Checkout Abandonment', 'omnimail'),
        'desc' => __('Send email after 2 hours if checkout started but not completed', 'omnimail'),
    ),
    array(
        'id' => 'flow_wishlist_reminder',
        'data' => 'wishlistReminderEnabled',
        'sms_data' => 'smsWishlistReminderEnabled',
        'type' => 'wishlistReminder',
        'label' => __('Wishlist Reminder', 'omnimail'),
        'desc' => __('Send reminder after 7 days', 'omnimail'),
    ),
    array(
        'id' => 'flow_post_purchase',
        'data' => 'postPurchaseEnabled',
        'sms_data' => 'smsPostPurchaseEnabled',
        'type' => 'postPurchase',
        'label' => __('Post-Purchase', 'omnimail'),
        'desc' => __('Send thank you + review request after 7 days', 'omnimail'),
    ),
    array(
        'id' => 'flow_re_engagement',
        'data' => 'reEngagementEnabled',
        'sms_data' => 'smsReEngagementEnabled',
        'type' => 'reEngagement',
        'label' => __('Re-engagement', 'omnimail'),
        'desc' => __('Send email after 30 days of no activity', 'omnimail'),
    ),
    array(
        'id' => 'flow_back_in_stock',
        'data' => 'backInStockEnabled',
        'sms_data' => 'smsBackInStockEnabled',
        'type' => 'backInStock',
        'label' => __('Back in Stock', 'omnimail'),
        'desc' => __('Send immediately when product restocks', 'omnimail'),
    ),
    array(
        'id' => 'flow_price_drop',
        'data' => 'priceDropEnabled',
        'sms_data' => 'smsPriceDropEnabled',
        'type' => 'priceDrop',
        'label' => __('Price Drop', 'omnimail'),
        'desc' => __('Send when price drops significantly', 'omnimail'),
    ),
);
?>

<div class="wrap omnimail-settings">
    <div class="omnimail-page-wrapper">
        <h1>
            <img src="<?php echo esc_url(OMNIMAIL_LOGO_URL); ?>" alt="<?php esc_attr_e('OmniMail', 'omnimail'); ?>" class="omnimail-logo omnimail-logo-inline">
            <span class="screen-reader-text"><?php _e('Behavioral Email Flows', 'omnimail'); ?></span>
        </h1>
        <p class="omnimail-hero-sub" style="margin-top:-6px;margin-bottom:18px;">
            <?php _e('Automation Flows', 'omnimail'); ?>
        </p>

        <?php if (!$is_configured): ?>
            <div class="omnimail-card omnimail-empty-state">
                <span class="dashicons dashicons-admin-network"></span>
                <h2><?php _e('Connect OmniMail first', 'omnimail'); ?></h2>
                <p>
                    <?php _e('You need a Connection ID and API Key from the OmniMail app before you can manage these flows.', 'omnimail'); ?>
                </p>
                <div class="omnimail-quick-actions">
                    <a href="<?php echo esc_url(defined('OMNIMAIL_CONNECTIONS_URL') ? OMNIMAIL_CONNECTIONS_URL : 'https://omnimail-app.omninexttech.com/dashboard/email-plugin-connection'); ?>"
                        target="_blank" rel="noopener noreferrer" class="button button-secondary button-large">
                        <span class="dashicons dashicons-external"></span>
                        <?php _e('Get credentials in OmniMail', 'omnimail'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-settings')); ?>"
                        class="button button-primary button-large">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Paste them in Settings', 'omnimail'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="omnimail-card">
            <h2><?php _e('Automated Email Flows', 'omnimail'); ?></h2>
            <p class="description">
                <?php _e('Control which automated emails are sent to your customers based on their behavior.', 'omnimail'); ?>
            </p>
            <div class="omnimail-callout omnimail-callout-info" style="margin-top: 12px;">
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %s: subscription upgrade URL */
                        __('These emails also need: (1) email/SMTP configured on your OmniMail connection, and (2) a <a href="%s" target="_blank" rel="noopener noreferrer" class="omnimail-pro-link">PRO OmniMail plan</a>.', 'omnimail'),
                        esc_url($pro_url)
                    ),
                    array(
                        'a' => array(
                            'href' => array(),
                            'target' => array(),
                            'rel' => array(),
                            'class' => array(),
                        ),
                    )
                );
                ?>
            </div>

            <div id="omnimail-pro-locked-banner" class="omnimail-callout omnimail-callout-pro" style="display:none;margin-top:12px;">
                <strong><?php _e('PRO required', 'omnimail'); ?></strong>
                <?php _e('Your plan does not include behavioral flows. Upgrade to PRO to enable them.', 'omnimail'); ?>
                <a href="<?php echo esc_url($pro_url); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary" style="margin-left:10px;">
                    <?php _e('Upgrade to PRO', 'omnimail'); ?>
                </a>
            </div>

            <div id="behavioral-flows-loading" style="text-align: center; padding: 40px;">
                <span class="spinner is-active" style="float: none; margin: 0;"></span>
                <p><?php _e('Loading flow settings...', 'omnimail'); ?></p>
            </div>

            <div id="behavioral-flows-content" style="display: none;" data-pro-url="<?php echo esc_url($pro_url); ?>">

                <div class="omnimail-behavioral-banner">
                    <div class="omnimail-banner-icon">
                        <span class="dashicons dashicons-info-outline"></span>
                    </div>
                    <div class="omnimail-banner-text">
                        <?php _e('Use Email flows, SMS flows, or both together to create a comprehensive communication strategy.', 'omnimail'); ?>
                    </div>
                    <a href="<?php echo esc_url($pro_url); ?>" target="_blank" rel="noopener noreferrer" class="omnimail-banner-button">
                        <?php _e('Learn More', 'omnimail'); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>
                </div>

                <div class="omnimail-behavioral-layout">
                    <div class="omnimail-flow-panel omnimail-email-panel">
                        <div class="omnimail-panel-header">
                            <div class="omnimail-panel-header-left">
                                <span class="omnimail-panel-header-icon"><span class="dashicons dashicons-email-alt"></span></span>
                                <h2><?php _e('Automation Flows', 'omnimail'); ?></h2>
                                <span class="omnimail-panel-active-badge" id="omnimail-email-active-badge">0 <?php _e('Active', 'omnimail'); ?></span>
                            </div>
                            <div class="omnimail-panel-header-right" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <button type="button" id="enable-all-flows" class="button button-secondary omnimail-header-action-btn">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Enable All', 'omnimail'); ?>
                                </button>
                                <button type="button" id="disable-all-flows" class="button button-secondary omnimail-header-action-btn">
                                    <span class="dashicons dashicons-dismiss"></span>
                                    <?php _e('Disable All', 'omnimail'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="omnimail-flow-columns" aria-hidden="true">
                            <span><?php _e('Flow', 'omnimail'); ?></span>
                            <span><?php _e('Email', 'omnimail'); ?></span>
                            <span><?php _e('SMS', 'omnimail'); ?></span>
                            <span><?php _e('Settings', 'omnimail'); ?></span>
                        </div>

                        <div class="omnimail-flows-grid">
                            <?php foreach ($flows as $flow): ?>
                                <div class="omnimail-flow-card" data-flow-type="<?php echo esc_attr($flow['type']); ?>" data-flow-label="<?php echo esc_attr($flow['label']); ?>">
                                    <div class="omnimail-flow-card-top">
                                        <div class="omnimail-flow-info">
                                            <div class="omnimail-flow-title">
                                                <label for="<?php echo esc_attr($flow['id']); ?>">
                                                    <?php echo esc_html($flow['label']); ?>
                                                </label>
                                                <span class="omnimail-pro-badge" title="<?php esc_attr_e('Requires PRO plan', 'omnimail'); ?>">
                                                    <span class="dashicons dashicons-lock"></span>
                                                    <?php _e('PRO', 'omnimail'); ?>
                                                </span>
                                                <a href="<?php echo esc_url($pro_url); ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="omnimail-pro-unlock"
                                                    style="display:none;">
                                                    <?php _e('Unlock', 'omnimail'); ?>
                                                </a>
                                            </div>
                                            <p class="description omnimail-flow-desc">
                                                <?php echo esc_html($flow['desc']); ?>
                                            </p>
                                        </div>
                                        <div class="omnimail-flow-actions">
                                            <div class="omnimail-channel-toggle">
                                                <span><?php _e('Email', 'omnimail'); ?></span>
                                                <label class="omnimail-toggle">
                                                    <input type="checkbox"
                                                        id="<?php echo esc_attr($flow['id']); ?>"
                                                           data-flow="<?php echo esc_attr($flow['data']); ?>"
                                                           data-channel="email">
                                                    <span class="omnimail-toggle-slider"></span>
                                                </label>
                                            </div>
                                            <div class="omnimail-channel-toggle">
                                                <span><?php _e('SMS', 'omnimail'); ?></span>
                                                <label class="omnimail-toggle">
                                                    <input type="checkbox"
                                                        id="<?php echo esc_attr($flow['id']); ?>_sms"
                                                           data-flow="<?php echo esc_attr($flow['sms_data']); ?>"
                                                           data-channel="sms">
                                                    <span class="omnimail-toggle-slider"></span>
                                                </label>
                                            </div>
                                            <button type="button" class="button button-secondary omnimail-manage-btn-sm omnimail-manage-followups-btn">
                                                <span class="dashicons dashicons-admin-generic"></span>
                                                <?php _e('Manage', 'omnimail'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <p class="submit">
                            <button type="button" id="save-flows-btn" class="button button-primary button-large">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Save Flow Settings', 'omnimail'); ?>
                            </button>
                        </p>
                    </div>

                </div>
            </div>

            <div id="behavioral-flows-error" style="display: none;">
                <div class="notice notice-error">
                    <p id="error-message"></p>
                </div>
            </div>
        </div>

        <div class="omnimail-card omnimail-help-card">
            <h2><?php _e('About Behavioral Flows', 'omnimail'); ?></h2>
            <p><?php _e('Behavioral flows are automated emails triggered by customer actions:', 'omnimail'); ?></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong><?php _e('Cart Abandonment:', 'omnimail'); ?></strong> <?php _e('Recover lost sales by reminding customers about items left in their cart', 'omnimail'); ?></li>
                <li><strong><?php _e('Browse Abandonment:', 'omnimail'); ?></strong> <?php _e('Re-engage customers who viewed products but didn\'t add them to cart', 'omnimail'); ?></li>
                <li><strong><?php _e('Checkout Abandonment:', 'omnimail'); ?></strong> <?php _e('Capture customers who started checkout but didn\'t complete', 'omnimail'); ?></li>
                <li><strong><?php _e('Wishlist Reminder:', 'omnimail'); ?></strong> <?php _e('Remind customers about products they saved for later', 'omnimail'); ?></li>
                <li><strong><?php _e('Post-Purchase:', 'omnimail'); ?></strong> <?php _e('Thank customers and request reviews after purchase', 'omnimail'); ?></li>
                <li><strong><?php _e('Re-engagement:', 'omnimail'); ?></strong> <?php _e('Win back inactive customers', 'omnimail'); ?></li>
                <li><strong><?php _e('Back in Stock:', 'omnimail'); ?></strong> <?php _e('Notify customers when out-of-stock products are available', 'omnimail'); ?></li>
                <li><strong><?php _e('Price Drop:', 'omnimail'); ?></strong> <?php _e('Alert customers when products they viewed go on sale', 'omnimail'); ?></li>
            </ul>
            <p>
                <strong><?php _e('Tier Requirement:', 'omnimail'); ?></strong>
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %s: subscription upgrade URL */
                        __('Behavioral flows (Email and SMS) require a <a href="%s" target="_blank" rel="noopener noreferrer" class="omnimail-pro-link">PRO OmniMail plan</a>.', 'omnimail'),
                        esc_url($pro_url)
                    ),
                    array(
                        'a' => array(
                            'href' => array(),
                            'target' => array(),
                            'rel' => array(),
                            'class' => array(),
                        ),
                    )
                );
                ?>
            </p>
        </div>
    </div>
</div>

<!-- Enhance Your Flow (follow-up sequences prompt) -->
<div id="omnimail-enhance-flow-modal" class="omnimail-modal-logs" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="omnimail-enhance-flow-title">
  <div class="omnimail-modal-content omnimail-enhance-modal">
    <div class="omnimail-modal-header">
      <h3 id="omnimail-enhance-flow-title">
        <span class="dashicons dashicons-star-filled"></span>
        <?php _e('Enhance Your Flow', 'omnimail'); ?>
      </h3>
      <button type="button" class="omnimail-modal-close" id="omnimail-enhance-flow-close" aria-label="<?php esc_attr_e('Close', 'omnimail'); ?>">
        <span class="dashicons dashicons-no"></span>
      </button>
    </div>
    <div class="omnimail-modal-body">
      <p class="omnimail-enhance-lead">
        <?php _e('We offer pre-built follow-up sequences that can automatically engage with your recipients based on their behavior.', 'omnimail'); ?>
      </p>
      <div class="omnimail-enhance-benefits">
        <h4><?php _e("What you'll get:", 'omnimail'); ?></h4>
        <ul>
          <li><span class="omnimail-enhance-check">✓</span> <?php _e('Professional follow-up sequences (No Engagement Recovery, Engaged Lead, No Response Recovery)', 'omnimail'); ?></li>
          <li><span class="omnimail-enhance-check">✓</span> <?php _e('Behavior-based triggers (opened, clicked, not opened)', 'omnimail'); ?></li>
          <li><span class="omnimail-enhance-check">✓</span> <?php _e('Automated email sending and tagging', 'omnimail'); ?></li>
          <li><span class="omnimail-enhance-check">✓</span> <?php _e('Proven templates that drive engagement', 'omnimail'); ?></li>
        </ul>
      </div>
      <p class="omnimail-enhance-question">
        <?php _e('Would you like to add a pre-built follow-up sequence to this flow?', 'omnimail'); ?>
      </p>
      <div class="omnimail-enhance-actions">
        <button type="button" class="button button-secondary button-large" id="omnimail-enhance-flow-later">
          <?php _e('Maybe Later', 'omnimail'); ?>
        </button>
        <button type="button" class="button button-primary button-large" id="omnimail-enhance-flow-yes">
          <span class="dashicons dashicons-star-filled"></span>
          <?php _e('Yes, Show Me', 'omnimail'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Follow-up Sequences modal -->
<div id="omnimail-followups-modal" class="omnimail-modal-logs" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="omnimail-followups-title">
  <div class="omnimail-modal-content omnimail-followups-modal">
    <div class="omnimail-modal-header">
      <h3 id="omnimail-followups-title">
        <span class="dashicons dashicons-randomize"></span>
        <span id="omnimail-followups-title-text"><?php _e('Follow-up Sequences', 'omnimail'); ?></span>
      </h3>
      <button type="button" class="omnimail-modal-close" id="omnimail-followups-close" aria-label="<?php esc_attr_e('Close', 'omnimail'); ?>">
        <span class="dashicons dashicons-no"></span>
      </button>
    </div>
    <div class="omnimail-modal-body">
      <p class="omnimail-enhance-question" style="margin-top:0;">
        <?php _e('Automatically send follow-up emails based on recipient behavior.', 'omnimail'); ?>
      </p>

      <div class="omnimail-followups-banner" id="omnimail-followups-banner" style="display:none;">
        <div>
          <strong><?php _e('Choose a follow-up sequence for this flow', 'omnimail'); ?></strong>
          <p><?php _e('Pick None, a pre-built sequence, or build a custom sequence with your own steps.', 'omnimail'); ?></p>
        </div>
        <button type="button" class="button button-primary" id="omnimail-browse-prebuilt-btn">
          <span class="dashicons dashicons-star-filled"></span>
          <?php _e('Choose Sequence', 'omnimail'); ?>
        </button>
      </div>

      <div id="omnimail-followups-loading" style="text-align:center;padding:28px;display:none;">
        <span class="spinner is-active" style="float:none;margin:0;"></span>
        <p><?php _e('Loading follow-ups...', 'omnimail'); ?></p>
      </div>

      <div id="omnimail-followups-empty" class="omnimail-followups-empty" style="display:none;">
        <span class="dashicons dashicons-email-alt"></span>
        <p><strong><?php _e('No follow-ups configured yet', 'omnimail'); ?></strong></p>
        <p class="description"><?php _e('Pick None, a pre-built sequence, or build a custom sequence.', 'omnimail'); ?></p>
        <button type="button" class="button button-primary" id="omnimail-followups-get-started">
          <span class="dashicons dashicons-star-filled"></span>
          <?php _e('Choose a Sequence', 'omnimail'); ?>
        </button>
        <button type="button" class="button button-secondary" id="omnimail-followups-build-custom" style="margin-left:8px;">
          <span class="dashicons dashicons-edit"></span>
          <?php _e('Build Custom', 'omnimail'); ?>
        </button>
      </div>

      <div id="omnimail-followups-list-wrap" style="display:none;">
        <div class="omnimail-followups-list-header">
          <strong id="omnimail-followups-count"><?php _e('Active Follow-ups', 'omnimail'); ?></strong>
          <div>
            <button type="button" class="button button-secondary" id="omnimail-edit-custom-btn">
              <?php _e('Edit as Custom', 'omnimail'); ?>
            </button>
            <button type="button" class="button button-secondary" id="omnimail-replace-prebuilt-btn">
              <?php _e('Change Sequence', 'omnimail'); ?>
            </button>
          </div>
        </div>
        <div id="omnimail-followups-list" class="omnimail-followups-list"></div>
      </div>
    </div>
  </div>
</div>

<!-- Pre-built sequence picker + preview modal -->
<div id="omnimail-prebuilt-modal" class="omnimail-modal-logs" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="omnimail-prebuilt-title">
  <div class="omnimail-modal-content omnimail-followups-modal">
    <div class="omnimail-modal-header">
      <h3 id="omnimail-prebuilt-title">
        <span class="dashicons dashicons-star-filled"></span>
        <span><?php _e('Choose Follow-up Sequence', 'omnimail'); ?></span>
      </h3>
      <button type="button" class="omnimail-modal-close" id="omnimail-prebuilt-close" aria-label="<?php esc_attr_e('Close', 'omnimail'); ?>">
        <span class="dashicons dashicons-no"></span>
      </button>
    </div>
    <div class="omnimail-modal-body">
      <p id="omnimail-prebuilt-hint" class="omnimail-enhance-question" style="margin-top:0;"></p>
      <div id="omnimail-prebuilt-options" class="omnimail-prebuilt-options"></div>
      <div id="omnimail-prebuilt-preview-wrap" style="display:none;margin-top:16px;">
        <strong id="omnimail-prebuilt-name"></strong>
        <p id="omnimail-prebuilt-desc" class="description" style="margin:6px 0 12px;"></p>
        <div id="omnimail-prebuilt-steps" class="omnimail-prebuilt-steps"></div>
      </div>
      <div class="omnimail-enhance-actions">
        <button type="button" class="button button-secondary button-large" id="omnimail-prebuilt-cancel">
          <?php _e('Cancel', 'omnimail'); ?>
        </button>
        <button type="button" class="button button-secondary button-large" id="omnimail-prebuilt-customize" style="display:none;">
          <span class="dashicons dashicons-edit"></span>
          <?php _e('Customize Steps', 'omnimail'); ?>
        </button>
        <button type="button" class="button button-primary button-large" id="omnimail-prebuilt-apply" disabled>
          <span class="dashicons dashicons-yes"></span>
          <span id="omnimail-prebuilt-apply-label"><?php _e('Use This Sequence', 'omnimail'); ?></span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Custom sequence builder modal -->
<div id="omnimail-custom-modal" class="omnimail-modal-logs" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="omnimail-custom-title">
  <div class="omnimail-modal-content omnimail-followups-modal omnimail-custom-modal">
    <div class="omnimail-modal-header">
      <h3 id="omnimail-custom-title">
        <span class="dashicons dashicons-edit"></span>
        <span><?php _e('Custom Follow-up Sequence', 'omnimail'); ?></span>
      </h3>
      <button type="button" class="omnimail-modal-close" id="omnimail-custom-close" aria-label="<?php esc_attr_e('Close', 'omnimail'); ?>">
        <span class="dashicons dashicons-no"></span>
      </button>
    </div>
    <div class="omnimail-modal-body">
      <p class="omnimail-enhance-question" style="margin-top:0;">
        <?php _e('Add steps that send after the default behavioral email based on open/click behavior.', 'omnimail'); ?>
      </p>
      <div id="omnimail-custom-steps" class="omnimail-custom-steps"></div>
      <button type="button" class="button button-secondary" id="omnimail-custom-add-step">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php _e('Add Step', 'omnimail'); ?>
      </button>
      <div class="omnimail-enhance-actions" style="margin-top:20px;">
        <button type="button" class="button button-secondary button-large" id="omnimail-custom-cancel">
          <?php _e('Cancel', 'omnimail'); ?>
        </button>
        <button type="button" class="button button-primary button-large" id="omnimail-custom-save">
          <span class="dashicons dashicons-yes"></span>
          <?php _e('Save Custom Sequence', 'omnimail'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * omnimail-behavioral-badges-ui
 * Purely visual/additive script for the "X Active" badges shown in each
 * panel header. It does NOT replace or duplicate the existing behavioral-flows
 * JS (loading data, saving, follow-ups, etc.) — it only watches the checkboxes
 * that script already controls and keeps the badges in sync.
 */
(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        var emailBadge = document.getElementById('omnimail-email-active-badge');

        function updateBadge(selector, badgeEl, suffix) {
            if (!badgeEl) return;
            var boxes = document.querySelectorAll(selector);
            var active = 0;
            boxes.forEach(function (b) { if (b.checked) active++; });
            badgeEl.textContent = active + ' ' + suffix;
        }

        function refreshEmailBadge() {
            updateBadge('#behavioral-flows-content input[data-channel="email"]', emailBadge, '<?php echo esc_js(__('Active', 'omnimail')); ?>');
        }

        // Recalculate whenever any flow checkbox changes (delegated, so it also
        // covers checkboxes populated dynamically by the existing loader script).
        document.addEventListener('change', function (e) {
            if (!(e.target instanceof HTMLInputElement) || e.target.type !== 'checkbox') return;
            if (e.target.dataset.channel === 'email') refreshEmailBadge();
        });

        // Recalculate once content becomes visible (existing loader flips this
        // container's display from none to block once data has loaded).
        var content = document.getElementById('behavioral-flows-content');
        if (content) {
            var mo = new MutationObserver(function () {
                if (content.style.display !== 'none') {
                    refreshEmailBadge();
                }
            });
            mo.observe(content, { attributes: true, attributeFilter: ['style'] });
        }
        refreshEmailBadge();
    });
})();
</script>