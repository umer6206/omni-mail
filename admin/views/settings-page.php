<?php
/**
 * Settings page — guided setup for Connection ID + API Key
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin/views
 */

if (!defined('WPINC')) {
    die;
}

$omnimail_enabled = get_option('omnimail_enabled', '0');
$omnimail_api_key = get_option('omnimail_api_key', '');
$omnimail_connection_id = get_option('omnimail_connection_id', '');
$omnimail_settings_updated = false;
if (isset($_GET['settings-updated'])) {
    check_admin_referer('omnimail_settings_updated', '_omnimail_nonce');
    $omnimail_settings_updated = sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true';
}
$omnimail_is_configured = !empty($omnimail_api_key) && !empty($omnimail_connection_id);
$omnimail_remote_wc_credentials = OmniMail_Settings::get_woocommerce_credentials();
$omnimail_has_wc_credentials = !empty($omnimail_remote_wc_credentials['success']);
$omnimail_wc_consumer_key = $omnimail_has_wc_credentials ? (string) $omnimail_remote_wc_credentials['consumerKey'] : '';
$omnimail_masked_wc_consumer_secret = $omnimail_has_wc_credentials
    ? OmniMail_Settings::mask_woocommerce_consumer_secret($omnimail_remote_wc_credentials['consumerSecret'])
    : '';
$omnimail_can_sync = $omnimail_is_configured && $omnimail_enabled === '1' && $omnimail_has_wc_credentials;
$omnimail_woo_active = class_exists('WooCommerce');
$omnimail_wc_keys_url = admin_url('admin.php?page=wc-settings&tab=advanced&section=keys');
$omnimail_step1_done = $omnimail_is_configured;
$omnimail_step2_done = $omnimail_enabled === '1';
$omnimail_platform_url = defined('OMNIMAIL_APP_URL') ? OMNIMAIL_APP_URL : 'https://omninextmail-app.omninexttech.com';
$omnimail_connections_url = defined('OMNIMAIL_CONNECTIONS_URL') ? OMNIMAIL_CONNECTIONS_URL : $omnimail_platform_url . '/dashboard/email-plugin-connection';
$omnimail_dashboard_url = $omnimail_platform_url . '/dashboard';
$omnimail_pro_url = defined('OMNIMAIL_SUBSCRIPTION_URL')
    ? OMNIMAIL_SUBSCRIPTION_URL
    : 'https://omninextmail-app.omninexttech.com/dashboard/mail-blaze/subscription';
$omnimail_knowledge_base_url = defined('OMNIMAIL_KNOWLEDGE_BASE_URL')
    ? OMNIMAIL_KNOWLEDGE_BASE_URL
    : 'https://omninextmail-app.omninexttech.com/dashboard/mail-blaze/knowledgebase';
$omnimail_connect_url = $omnimail_platform_url . '/dashboard/mail-blaze/connect';
$omnimail_stripe_connect_url = defined('OMNIMAIL_STRIPE_CONNECT_URL')
    ? OMNIMAIL_STRIPE_CONNECT_URL
    : $omnimail_platform_url . '/dashboard/billings/stripe-connect';
$omnimail_platform_host = preg_replace('#^https?://#', '', $omnimail_platform_url);
?>

<div class="wrap omnimail-settings">
    <div class="omnimail-page-wrapper">
        <div class="omnimail-hero">
            <div class="omnimail-hero-brand">
                <img src="<?php echo esc_url(OMNIMAIL_LOGO_URL); ?>" alt="<?php esc_attr_e('OmniMail', 'omnimail'); ?>" class="omnimail-logo">
                <h1><?php esc_html_e('Set up OmniNextMail', 'omnimail'); ?></h1>
                <p class="omnimail-hero-sub">
                    <?php esc_html_e('Connect this WooCommerce store to your OmniNextMail account in a few simple steps. No coding required.', 'omnimail'); ?>
                </p>
            </div>
            <a href="<?php echo esc_url($omnimail_connections_url); ?>" target="_blank" rel="noopener noreferrer"
                class="button button-primary button-large omnimail-hero-cta">
                <span class="dashicons dashicons-external"></span>
                <?php esc_html_e('Open Omni Plugin Settings', 'omnimail'); ?>
            </a>
        </div>

        <?php if ($omnimail_settings_updated): ?>
            <div class="notice notice-success is-dismissible omnimail-inline-notice">
                <p><?php esc_html_e('Settings saved. Next: enable OmniMail (if needed) and click Test Connection.', 'omnimail'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Progress -->
        <div class="omnimail-progress">
            <div class="omnimail-progress-step <?php echo $omnimail_step1_done ? 'is-done' : 'is-current'; ?>">
                <span class="omnimail-progress-num">1</span>
                <span><?php esc_html_e('Add credentials', 'omnimail'); ?></span>
            </div>
            <div class="omnimail-progress-line <?php echo $omnimail_step1_done ? 'is-done' : ''; ?>"></div>
            <div class="omnimail-progress-step <?php echo $omnimail_step2_done ? 'is-done' : ($omnimail_step1_done ? 'is-current' : ''); ?>">
                <span class="omnimail-progress-num">2</span>
                <span><?php esc_html_e('Turn on &amp; test', 'omnimail'); ?></span>
            </div>
            <div class="omnimail-progress-line <?php echo $omnimail_step2_done ? 'is-done' : ''; ?>"></div>
            <div class="omnimail-progress-step <?php echo ($omnimail_step1_done && $omnimail_step2_done) ? 'is-current' : ''; ?>">
                <span class="omnimail-progress-num">3</span>
                <span><?php esc_html_e('Products &amp; payments', 'omnimail'); ?></span>
            </div>
            <div class="omnimail-progress-line"></div>
            <div class="omnimail-progress-step">
                <span class="omnimail-progress-num">4</span>
                <span><?php esc_html_e('Email &amp; flows', 'omnimail'); ?></span>
            </div>
        </div>

        <!-- Guided steps as cards -->
        <div class="omnimail-card omnimail-howto-card">
            <h2>
                <span class="dashicons dashicons-lightbulb"></span>
                <?php esc_html_e('Where do I get the Connection ID and API Key?', 'omnimail'); ?>
            </h2>

            <div class="omnimail-setup-steps">
                <!-- Step 1 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">1</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Open the OmniMail platform', 'omnimail'); ?></h3>
                        <p>
                            <?php esc_html_e('Go to', 'omnimail'); ?>
                            <a href="<?php echo esc_url($omnimail_platform_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($omnimail_platform_host); ?></a>
                            <?php esc_html_e('and log in with your OmniMail account.', 'omnimail'); ?>
                        </p>
                    </div>
                </div>

                <!-- Step 2: create credentials + paste/save -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">2</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Add your credentials', 'omnimail'); ?></h3>
                        <p><?php esc_html_e('Create an API Key and Connection ID from Plugin Secret, then enter both values below. Enable OmniNextMail and click Test Connection.', 'omnimail'); ?></p>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="omnimail-settings-form" class="omnimail-setup-form">
                            <input type="hidden" name="action" value="omnimail_save_settings">
                            <?php wp_nonce_field('omnimail_settings_nonce'); ?>

                            <div class="omnimail-field-grid">
                                <div class="omnimail-field">
                                    <label for="omnimail_api_key">
                                        <?php esc_html_e('API Key', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <div class="omnimail-api-key-wrapper">
                                        <input type="password" name="omnimail_api_key" id="omnimail_api_key"
                                            value="<?php echo esc_attr($omnimail_api_key); ?>"
                                            class="regular-text omnimail-api-key-input"
                                            autocomplete="off"
                                            placeholder="<?php esc_attr_e('omni_…', 'omnimail'); ?>">
                                        <button type="button" class="button omnimail-toggle-visibility" id="toggle-api-key" title="<?php esc_attr_e('Show / hide', 'omnimail'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="omnimail-field">
                                    <label for="omnimail_connection_id">
                                        <?php esc_html_e('Connection ID', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <input type="text" name="omnimail_connection_id" id="omnimail_connection_id"
                                        value="<?php echo esc_attr($omnimail_connection_id); ?>"
                                        class="regular-text"
                                        autocomplete="off"
                                        placeholder="<?php esc_attr_e('e.g. 604e4a9f-4934-43dc-84d2-e7494a68a037', 'omnimail'); ?>">
                                </div>

                                <div class="omnimail-field omnimail-field-toggle">
                                    <label for="omnimail_enabled"><?php esc_html_e('Enable OmniMail', 'omnimail'); ?></label>
                                    <div class="omnimail-toggle-row">
                                        <label class="omnimail-toggle">
                                            <input type="checkbox" name="omnimail_enabled" id="omnimail_enabled" value="1"
                                                <?php checked($omnimail_enabled, '1'); ?>>
                                            <span class="omnimail-toggle-slider"></span>
                                        </label>
                                        <span class="description">
                                            <?php esc_html_e('When ON, WooCommerce events (orders, carts, etc.) are sent to OmniNextMail.', 'omnimail'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <p class="submit omnimail-submit-row">
                                <button type="submit" class="button button-primary button-large">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php esc_html_e('Save', 'omnimail'); ?>
                                </button>
                                <button type="button" class="button button-secondary button-large" id="test-connection-btn"
                                    <?php disabled(!$omnimail_is_configured); ?>>
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                    <?php esc_html_e('Test Connection', 'omnimail'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Step 3: Products & Stripe -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">3</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Sync WooCommerce Products & Connect Stripe', 'omnimail'); ?></h3>
                        <p><?php esc_html_e('Connect WooCommerce REST API keys, sync your catalog with Omni, and connect Stripe to accept payments.', 'omnimail'); ?></p>

                        <?php if (!$omnimail_woo_active) : ?>
                            <p class="description"><?php esc_html_e('Install and activate WooCommerce to generate keys and synchronize products.', 'omnimail'); ?></p>
                        <?php else : ?>
                            <div class="omnimail-guide-box">
                                <p><strong><?php esc_html_e('Option A — Easiest: let this plugin create keys for you', 'omnimail'); ?></strong></p>
                                <ol class="omnimail-steps-list">
                                    <li><?php esc_html_e('Click Generate Keys below.', 'omnimail'); ?></li>
                                    <li><?php esc_html_e('The Consumer Key and Consumer Secret fields will fill in automatically.', 'omnimail'); ?></li>
                                    <li><?php esc_html_e('Click Save WooCommerce Credentials. Sync Products will then unlock.', 'omnimail'); ?></li>
                                </ol>
                            </div>
                            <div class="omnimail-guide-box">
                                <p><strong><?php esc_html_e('Option B — Manual: copy keys from WooCommerce', 'omnimail'); ?></strong></p>
                                <ol class="omnimail-steps-list">
                                    <li><?php esc_html_e('Go to WooCommerce → Settings → Advanced → REST API.', 'omnimail'); ?></li>
                                    <li><?php esc_html_e('Add a key with Read/Write permissions, then Generate API key.', 'omnimail'); ?></li>
                                    <li><?php esc_html_e('Paste the Consumer key (ck_) and Consumer secret (cs_) below.', 'omnimail'); ?></li>
                                </ol>
                                <p class="omnimail-setup-step-actions">
                                    <a class="button button-secondary" href="<?php echo esc_url($omnimail_wc_keys_url); ?>" target="_blank" rel="noopener noreferrer">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php esc_html_e('Open WooCommerce REST API Keys', 'omnimail'); ?>
                                    </a>
                                </p>
                            </div>

                            <div class="omnimail-field-grid" style="margin-top:14px;">
                                <div class="omnimail-field">
                                    <label for="omnimail_wc_consumer_key">
                                        <?php esc_html_e('WooCommerce Consumer Key', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <input type="text"
                                           name="omnimail_wc_consumer_key"
                                           id="omnimail_wc_consumer_key"
                                           value="<?php echo esc_attr($omnimail_wc_consumer_key); ?>"
                                           class="regular-text"
                                           autocomplete="off"
                                           placeholder="ck_...">
                                </div>
                                <div class="omnimail-field">
                                    <label for="omnimail_wc_consumer_secret">
                                        <?php esc_html_e('WooCommerce Consumer Secret', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <input type="text"
                                           name="omnimail_wc_consumer_secret"
                                           id="omnimail_wc_consumer_secret"
                                           value="<?php echo esc_attr($omnimail_masked_wc_consumer_secret); ?>"
                                           class="regular-text"
                                           autocomplete="new-password"
                                           data-saved-mask="<?php echo esc_attr($omnimail_masked_wc_consumer_secret); ?>"
                                           placeholder="cs_...">
                                    <p class="description">
                                        <?php echo $omnimail_has_wc_credentials
                                            ? esc_html__('The secret is saved on Omni. Only its last four characters are shown; enter a new secret only to replace it.', 'omnimail')
                                            : esc_html__('Enter the Consumer Secret, or generate keys above.', 'omnimail'); ?>
                                    </p>
                                </div>
                            </div>

                            <p class="submit omnimail-submit-row">
                                <button type="button" class="button button-secondary button-large" id="omnimail-generate-wc-keys">
                                    <span class="dashicons dashicons-admin-network"></span>
                                    <?php esc_html_e('Generate Keys', 'omnimail'); ?>
                                </button>
                                <button type="button" class="button button-primary button-large" id="omnimail-save-wc-credentials"
                                    <?php disabled(!$omnimail_is_configured); ?>>
                                    <span class="dashicons dashicons-yes"></span>
                                    <span class="omnimail-button-label"><?php esc_html_e('Save WooCommerce Credentials', 'omnimail'); ?></span>
                                </button>
                            </p>
                            <p id="omnimail-wc-message" class="description" style="margin-top:8px;"></p>
                        <?php endif; ?>

                        <p class="omnimail-setup-step-actions omnimail-step3-actions">
                            <?php if ($omnimail_woo_active): ?>
                            <button type="button" class="button button-primary button-large" id="sync-products-btn"
                                <?php disabled(!$omnimail_can_sync); ?>
                                aria-disabled="<?php echo $omnimail_can_sync ? 'false' : 'true'; ?>">
                                <span class="dashicons dashicons-update"></span>
                                <?php esc_html_e('Sync Products', 'omnimail'); ?>
                            </button>
                            <?php endif; ?>
                            <a href="<?php echo esc_url($omnimail_stripe_connect_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-secondary button-large<?php echo $omnimail_is_configured ? '' : ' disabled'; ?>"
                                id="stripe-connect-btn"
                                <?php echo $omnimail_is_configured ? '' : ' aria-disabled="true" onclick="return false;"'; ?>>
                                <span class="dashicons dashicons-money-alt" id="stripe-connect-icon"></span>
                                <span id="stripe-connect-label"><?php esc_html_e('Connect Stripe Account', 'omnimail'); ?></span>
                            </a>
                        </p>
                        <?php if ($omnimail_woo_active && !$omnimail_can_sync) : ?>
                            <p id="omnimail-sync-required" class="description">
                                <?php esc_html_e('Save API Key, Connection ID, enable OmniMail, and save WooCommerce credentials before syncing products.', 'omnimail'); ?>
                            </p>
                        <?php endif; ?>
                        <p id="stripe-connect-status" class="description" style="margin-top:8px;<?php echo $omnimail_is_configured ? '' : ' display:none;'; ?>">
                            <?php esc_html_e('Checking Stripe connection…', 'omnimail'); ?>
                        </p>
                        <?php if ($omnimail_woo_active): ?>
                        <div id="product-sync-progress" style="display: none; margin-top: 20px; padding: 20px; background: #1e293b; border-radius: 8px; border: 1px solid #2d3e50;">
                            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                <span class="dashicons dashicons-update" style="color: #22d3ee; font-size: 24px; width: 24px; height: 24px; margin-right: 12px; animation: rotation 1s infinite linear;"></span>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0; color: #fff; font-size: 16px;"><?php esc_html_e('Syncing Products...', 'omnimail'); ?></h3>
                                    <p id="sync-progress-text" style="margin: 4px 0 0 0; color: #9ca3af; font-size: 14px;"><?php esc_html_e('Preparing...', 'omnimail'); ?></p>
                                </div>
                            </div>
                            <div style="background: #0f1728; border-radius: 4px; height: 8px; overflow: hidden;">
                                <div id="sync-progress-bar" style="background: linear-gradient(90deg, #22d3ee, #00b8d4); height: 100%; width: 0%; transition: width 0.3s;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">4</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Connect your email provider', 'omnimail'); ?></h3>
                        <p><?php esc_html_e('Connect AWS SES, Mailgun, SMTP, or another provider so OmniNextMail can send automated emails from your store.', 'omnimail'); ?></p>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url($omnimail_connect_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-primary">
                                <span class="dashicons dashicons-email-alt"></span>
                                <?php esc_html_e('Connect Email Provider', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">5</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Use a PRO plan for automated emails', 'omnimail'); ?></h3>
                        <p>
                            <?php
                            echo wp_kses(
                                sprintf(
                                    /* translators: %s: subscription upgrade URL */
                                    __('Use a <a href="%s" target="_blank" rel="noopener noreferrer" class="omnimail-pro-link">PRO OmniNextMail plan</a> if you want cart abandonment and other automated emails.', 'omnimail'),
                                    esc_url($omnimail_pro_url)
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
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url($omnimail_pro_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-primary">
                                <span class="dashicons dashicons-star-filled"></span>
                                <?php esc_html_e('View PRO Plans', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 6: AI Auto Replies -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">6</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Configure AI Auto Replies', 'omnimail'); ?></h3>
                        <p><?php esc_html_e('Set up AI-powered automatic email responses by connecting a Knowledge Base to your email inbox.', 'omnimail'); ?></p>
                        <ol class="omnimail-steps-list" style="margin: 8px 0 12px 1.2em;">
                            <li><?php esc_html_e('Click Configure.', 'omnimail'); ?></li>
                            <li><?php esc_html_e('Enter a Name and Description for your AI Auto Reply configuration.', 'omnimail'); ?></li>
                            <li><?php esc_html_e('Add knowledge for the AI by entering a prompt, uploading files, or importing content from a URL.', 'omnimail'); ?></li>
                            <li><?php esc_html_e('Select the email address you want to connect.', 'omnimail'); ?></li>
                            <li><?php esc_html_e('Click Configure to save and activate your AI Auto Replies.', 'omnimail'); ?></li>
                        </ol>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url($omnimail_knowledge_base_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-primary">
                                <span class="dashicons dashicons-book-alt"></span>
                                <?php esc_html_e('Configure', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 7 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">7</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Turn on Behavioral Flows', 'omnimail'); ?></h3>
                        <p><?php esc_html_e('Enable the automated emails you want (cart abandonment, browse abandonment, and more).', 'omnimail'); ?></p>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-behavioral-flows')); ?>"
                                class="button button-primary">
                                <span class="dashicons dashicons-randomize"></span>
                                <?php esc_html_e('Open Behavioral Flows', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 8 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">8</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php esc_html_e('Check Email Statistics', 'omnimail'); ?></h3>
                        <p><?php esc_html_e('After customers abandon carts or place orders, review sent emails, opens, clicks, and delivery stats here.', 'omnimail'); ?></p>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-email-stats')); ?>"
                                class="button button-primary">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php esc_html_e('Open Email Statistics', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
