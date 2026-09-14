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

$enabled = get_option('omnimail_enabled', '0');
$api_key = get_option('omnimail_api_key', '');
$connection_id = get_option('omnimail_connection_id', '');
$settings_updated = isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true';
$is_configured = !empty($api_key) && !empty($connection_id);
$remote_wc_credentials = OmniMail_Settings::get_woocommerce_credentials();
$has_wc_credentials = !empty($remote_wc_credentials['success']);
$wc_consumer_key = $has_wc_credentials ? (string) $remote_wc_credentials['consumerKey'] : '';
$masked_wc_consumer_secret = $has_wc_credentials
    ? OmniMail_Settings::mask_woocommerce_consumer_secret($remote_wc_credentials['consumerSecret'])
    : '';
$can_sync = $is_configured && $enabled === '1' && $has_wc_credentials;
$woo_active = class_exists('WooCommerce');
$wc_keys_url = admin_url('admin.php?page=wc-settings&tab=advanced&section=keys');
$step1_done = $is_configured;
$step2_done = $enabled === '1';
$platform_url = defined('OMNIMAIL_APP_URL') ? OMNIMAIL_APP_URL : 'https://omnimail-app.omninexttech.com';
$connections_url = defined('OMNIMAIL_CONNECTIONS_URL') ? OMNIMAIL_CONNECTIONS_URL : $platform_url . '/dashboard/email-plugin-connection';
$dashboard_url = $platform_url . '/dashboard';
$pro_url = defined('OMNIMAIL_SUBSCRIPTION_URL')
    ? OMNIMAIL_SUBSCRIPTION_URL
    : 'https://omnimail-app.omninexttech.com/dashboard/mail-blaze/subscription';
$knowledge_base_url = defined('OMNIMAIL_KNOWLEDGE_BASE_URL')
    ? OMNIMAIL_KNOWLEDGE_BASE_URL
    : 'https://omnimail-app.omninexttech.com/dashboard/mail-blaze/knowledgebase';
$connect_url = $platform_url . '/dashboard/mail-blaze/connect';
$stripe_connect_url = defined('OMNIMAIL_STRIPE_CONNECT_URL')
    ? OMNIMAIL_STRIPE_CONNECT_URL
    : $platform_url . '/dashboard/billings/stripe-connect';
$platform_host = preg_replace('#^https?://#', '', $platform_url);
?>

<div class="wrap omnimail-settings">
    <div class="omnimail-page-wrapper">
        <div class="omnimail-hero">
            <div class="omnimail-hero-brand">
                <img src="<?php echo esc_url(OMNIMAIL_LOGO_URL); ?>" alt="<?php esc_attr_e('OmniMail', 'omnimail'); ?>" class="omnimail-logo">
                <h1><?php _e('Set up OmniMail', 'omnimail'); ?></h1>
                <p class="omnimail-hero-sub">
                    <?php _e('Connect this WooCommerce store to your OmniMail account in a few simple steps. No coding required.', 'omnimail'); ?>
                </p>
            </div>
            <a href="<?php echo esc_url($connections_url); ?>" target="_blank" rel="noopener noreferrer"
                class="button button-primary button-large omnimail-hero-cta">
                <span class="dashicons dashicons-external"></span>
                <?php _e('Open Omni Plugin Settings', 'omnimail'); ?>
            </a>
        </div>

        <?php if ($settings_updated): ?>
            <div class="notice notice-success is-dismissible omnimail-inline-notice">
                <p><?php _e('Settings saved. Next: enable OmniMail (if needed) and click Test Connection.', 'omnimail'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Progress -->
        <div class="omnimail-progress">
            <div class="omnimail-progress-step <?php echo $step1_done ? 'is-done' : 'is-current'; ?>">
                <span class="omnimail-progress-num">1</span>
                <span><?php _e('Add credentials', 'omnimail'); ?></span>
            </div>
            <div class="omnimail-progress-line <?php echo $step1_done ? 'is-done' : ''; ?>"></div>
            <div class="omnimail-progress-step <?php echo $step2_done ? 'is-done' : ($step1_done ? 'is-current' : ''); ?>">
                <span class="omnimail-progress-num">2</span>
                <span><?php _e('Turn on &amp; test', 'omnimail'); ?></span>
            </div>
            <div class="omnimail-progress-line <?php echo $step2_done ? 'is-done' : ''; ?>"></div>
            <div class="omnimail-progress-step <?php echo ($step1_done && $step2_done) ? 'is-current' : ''; ?>">
                <span class="omnimail-progress-num">3</span>
                <span><?php _e('Products &amp; payments', 'omnimail'); ?></span>
            </div>
            <div class="omnimail-progress-line"></div>
            <div class="omnimail-progress-step">
                <span class="omnimail-progress-num">4</span>
                <span><?php _e('Email &amp; flows', 'omnimail'); ?></span>
            </div>
        </div>

        <!-- Guided steps as cards -->
        <div class="omnimail-card omnimail-howto-card">
            <h2>
                <span class="dashicons dashicons-lightbulb"></span>
                <?php _e('Where do I get the Connection ID and API Key?', 'omnimail'); ?>
            </h2>

            <div class="omnimail-setup-steps">
                <!-- Step 1 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">1</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php _e('Open the OmniMail platform', 'omnimail'); ?></h3>
                        <p>
                            <?php _e('Go to', 'omnimail'); ?>
                            <a href="<?php echo esc_url($platform_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($platform_host); ?></a>
                            <?php _e('and log in with your OmniMail account.', 'omnimail'); ?>
                        </p>
                    </div>
                </div>

                <!-- Step 2: create credentials + paste/save -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">2</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php _e('Add your credentials', 'omnimail'); ?></h3>
                        <p><?php _e('Create an API Key and Connection ID from Plugin Secret, then enter both values below. Enable OmniMail and click Test Connection.', 'omnimail'); ?></p>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="omnimail-settings-form" class="omnimail-setup-form">
                            <input type="hidden" name="action" value="omnimail_save_settings">
                            <?php wp_nonce_field('omnimail_settings_nonce'); ?>

                            <div class="omnimail-field-grid">
                                <div class="omnimail-field">
                                    <label for="omnimail_api_key">
                                        <?php _e('API Key', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <div class="omnimail-api-key-wrapper">
                                        <input type="password" name="omnimail_api_key" id="omnimail_api_key"
                                            value="<?php echo esc_attr($api_key); ?>"
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
                                        <?php _e('Connection ID', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <input type="text" name="omnimail_connection_id" id="omnimail_connection_id"
                                        value="<?php echo esc_attr($connection_id); ?>"
                                        class="regular-text"
                                        autocomplete="off"
                                        placeholder="<?php esc_attr_e('e.g. 604e4a9f-4934-43dc-84d2-e7494a68a037', 'omnimail'); ?>">
                                </div>

                                <div class="omnimail-field omnimail-field-toggle">
                                    <label for="omnimail_enabled"><?php _e('Enable OmniMail', 'omnimail'); ?></label>
                                    <div class="omnimail-toggle-row">
                                        <label class="omnimail-toggle">
                                            <input type="checkbox" name="omnimail_enabled" id="omnimail_enabled" value="1"
                                                <?php checked($enabled, '1'); ?>>
                                            <span class="omnimail-toggle-slider"></span>
                                        </label>
                                        <span class="description">
                                            <?php _e('When ON, WooCommerce events (orders, carts, etc.) are sent to OmniMail.', 'omnimail'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <p class="submit omnimail-submit-row">
                                <button type="submit" class="button button-primary button-large">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Save', 'omnimail'); ?>
                                </button>
                                <button type="button" class="button button-secondary button-large" id="test-connection-btn"
                                    <?php disabled(!$is_configured); ?>>
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                    <?php _e('Test Connection', 'omnimail'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Step 3: Products & Stripe -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">3</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php _e('Sync WooCommerce Products & Connect Stripe', 'omnimail'); ?></h3>
                        <p><?php _e('Connect WooCommerce REST API keys, sync your catalog with Omni, and connect Stripe to accept payments.', 'omnimail'); ?></p>

                        <?php if (!$woo_active) : ?>
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
                                    <a class="button button-secondary" href="<?php echo esc_url($wc_keys_url); ?>" target="_blank" rel="noopener noreferrer">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php esc_html_e('Open WooCommerce REST API Keys', 'omnimail'); ?>
                                    </a>
                                </p>
                            </div>

                            <div class="omnimail-field-grid" style="margin-top:14px;">
                                <div class="omnimail-field">
                                    <label for="omnimail_wc_consumer_key">
                                        <?php _e('WooCommerce Consumer Key', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <input type="text"
                                           name="omnimail_wc_consumer_key"
                                           id="omnimail_wc_consumer_key"
                                           value="<?php echo esc_attr($wc_consumer_key); ?>"
                                           class="regular-text"
                                           autocomplete="off"
                                           placeholder="ck_...">
                                </div>
                                <div class="omnimail-field">
                                    <label for="omnimail_wc_consumer_secret">
                                        <?php _e('WooCommerce Consumer Secret', 'omnimail'); ?>
                                        <span class="omnimail-required">*</span>
                                    </label>
                                    <input type="text"
                                           name="omnimail_wc_consumer_secret"
                                           id="omnimail_wc_consumer_secret"
                                           value="<?php echo esc_attr($masked_wc_consumer_secret); ?>"
                                           class="regular-text"
                                           autocomplete="new-password"
                                           data-saved-mask="<?php echo esc_attr($masked_wc_consumer_secret); ?>"
                                           placeholder="cs_...">
                                    <p class="description">
                                        <?php echo $has_wc_credentials
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
                                    <?php disabled(!$is_configured); ?>>
                                    <span class="dashicons dashicons-yes"></span>
                                    <span class="omnimail-button-label"><?php esc_html_e('Save WooCommerce Credentials', 'omnimail'); ?></span>
                                </button>
                            </p>
                            <p id="omnimail-wc-message" class="description" style="margin-top:8px;"></p>
                        <?php endif; ?>

                        <p class="omnimail-setup-step-actions omnimail-step3-actions">
                            <?php if ($woo_active): ?>
                            <button type="button" class="button button-primary button-large" id="sync-products-btn"
                                <?php disabled(!$can_sync); ?>
                                aria-disabled="<?php echo $can_sync ? 'false' : 'true'; ?>">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Sync Products', 'omnimail'); ?>
                            </button>
                            <?php endif; ?>
                            <a href="<?php echo esc_url($stripe_connect_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-secondary button-large<?php echo $is_configured ? '' : ' disabled'; ?>"
                                id="stripe-connect-btn"
                                <?php echo $is_configured ? '' : ' aria-disabled="true" onclick="return false;"'; ?>>
                                <span class="dashicons dashicons-money-alt" id="stripe-connect-icon"></span>
                                <span id="stripe-connect-label"><?php _e('Connect Stripe Account', 'omnimail'); ?></span>
                            </a>
                        </p>
                        <?php if ($woo_active && !$can_sync) : ?>
                            <p id="omnimail-sync-required" class="description">
                                <?php esc_html_e('Save API Key, Connection ID, enable OmniMail, and save WooCommerce credentials before syncing products.', 'omnimail'); ?>
                            </p>
                        <?php endif; ?>
                        <p id="stripe-connect-status" class="description" style="margin-top:8px;<?php echo $is_configured ? '' : ' display:none;'; ?>">
                            <?php _e('Checking Stripe connection…', 'omnimail'); ?>
                        </p>
                        <?php if ($woo_active): ?>
                        <div id="product-sync-progress" style="display: none; margin-top: 20px; padding: 20px; background: #1e293b; border-radius: 8px; border: 1px solid #2d3e50;">
                            <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                <span class="dashicons dashicons-update" style="color: #22d3ee; font-size: 24px; width: 24px; height: 24px; margin-right: 12px; animation: rotation 1s infinite linear;"></span>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0; color: #fff; font-size: 16px;"><?php _e('Syncing Products...', 'omnimail'); ?></h3>
                                    <p id="sync-progress-text" style="margin: 4px 0 0 0; color: #9ca3af; font-size: 14px;"><?php _e('Preparing...', 'omnimail'); ?></p>
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
                        <h3><?php _e('Connect your email provider', 'omnimail'); ?></h3>
                        <p><?php _e('Connect AWS SES, Mailgun, SMTP, or another provider so OmniMail can send automated emails from your store.', 'omnimail'); ?></p>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url($connect_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-primary">
                                <span class="dashicons dashicons-email-alt"></span>
                                <?php _e('Connect Email Provider', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">5</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php _e('Use a PRO plan for automated emails', 'omnimail'); ?></h3>
                        <p>
                            <?php
                            echo wp_kses(
                                sprintf(
                                    /* translators: %s: subscription upgrade URL */
                                    __('Use a <a href="%s" target="_blank" rel="noopener noreferrer" class="omnimail-pro-link">PRO OmniMail plan</a> if you want cart abandonment and other automated emails.', 'omnimail'),
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
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url($pro_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-primary">
                                <span class="dashicons dashicons-star-filled"></span>
                                <?php _e('View PRO Plans', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 6: AI Auto Replies -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">6</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php _e('Configure AI Auto Replies', 'omnimail'); ?></h3>
                        <p><?php _e('Set up AI-powered automatic email responses by connecting a Knowledge Base to your email inbox.', 'omnimail'); ?></p>
                        <ol class="omnimail-steps-list" style="margin: 8px 0 12px 1.2em;">
                            <li><?php _e('Click Configure.', 'omnimail'); ?></li>
                            <li><?php _e('Enter a Name and Description for your AI Auto Reply configuration.', 'omnimail'); ?></li>
                            <li><?php _e('Add knowledge for the AI by entering a prompt, uploading files, or importing content from a URL.', 'omnimail'); ?></li>
                            <li><?php _e('Select the email address you want to connect.', 'omnimail'); ?></li>
                            <li><?php _e('Click Configure to save and activate your AI Auto Replies.', 'omnimail'); ?></li>
                        </ol>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url($knowledge_base_url); ?>" target="_blank" rel="noopener noreferrer"
                                class="button button-primary">
                                <span class="dashicons dashicons-book-alt"></span>
                                <?php _e('Configure', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 7 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">7</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php _e('Turn on Behavioral Flows', 'omnimail'); ?></h3>
                        <p><?php _e('Enable the automated emails you want (cart abandonment, browse abandonment, and more).', 'omnimail'); ?></p>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-behavioral-flows')); ?>"
                                class="button button-primary">
                                <span class="dashicons dashicons-randomize"></span>
                                <?php _e('Open Behavioral Flows', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Step 8 -->
                <div class="omnimail-setup-step">
                    <div class="omnimail-setup-step-num">8</div>
                    <div class="omnimail-setup-step-body">
                        <h3><?php _e('Check Email Statistics', 'omnimail'); ?></h3>
                        <p><?php _e('After customers abandon carts or place orders, review sent emails, opens, clicks, and delivery stats here.', 'omnimail'); ?></p>
                        <p class="omnimail-setup-step-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-email-stats')); ?>"
                                class="button button-primary">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php _e('Open Email Statistics', 'omnimail'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
