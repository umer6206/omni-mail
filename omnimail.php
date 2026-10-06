<?php
/**
 * Plugin Name: OmniMail
 * Plugin URI: https://omnimail-app.omninexttech.com
 * Description: Integrate WooCommerce with OmniMail for intelligent email automation, SMTP configuration, and event tracking.
 * Version: 1.0.37
 * Author: Naveed
 * Author URI: https://omninexttech.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: omnimail
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('OMNIMAIL_VERSION', '1.0.37');
define('OMNIMAIL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OMNIMAIL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OMNIMAIL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('OMNIMAIL_API_BASE_URL', 'https://backend.omninexttech.com');
// define('OMNIMAIL_API_BASE_URL', 'https://7ddgj060-8085.inc1.devtunnels.ms');
define('OMNIMAIL_APP_URL', 'https://omnimail-app.omninexttech.com');
define('OMNIMAIL_CONNECTIONS_URL', OMNIMAIL_APP_URL . '/dashboard/email-plugin-connection');
define('OMNIMAIL_SUBSCRIPTION_URL', OMNIMAIL_APP_URL . '/dashboard/mail-blaze/subscription');
define('OMNIMAIL_KNOWLEDGE_BASE_URL', OMNIMAIL_APP_URL . '/dashboard/mail-blaze/knowledgebase');
define('OMNIMAIL_STRIPE_CONNECT_URL', OMNIMAIL_APP_URL . '/dashboard/billings/stripe-connect');
define('OMNIMAIL_LOGO_URL', OMNIMAIL_PLUGIN_URL . 'assets/images/omni-mail-logo.png');

/**
 * The code that runs during plugin activation.
 */
function activate_omnimail()
{
    require_once OMNIMAIL_PLUGIN_DIR . 'includes/class-omnimail-activator.php';
    OmniMail_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_omnimail()
{
    require_once OMNIMAIL_PLUGIN_DIR . 'includes/class-omnimail-deactivator.php';
    OmniMail_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_omnimail');
register_deactivation_hook(__FILE__, 'deactivate_omnimail');

/**
 * Declare WooCommerce HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Add custom cron schedule for cart abandonment checks
 */
add_filter('cron_schedules', function($schedules) {
    $schedules['omnimail_15min'] = array(
        'interval' => 900, // 15 minutes in seconds
        'display'  => __('Every 15 Minutes', 'omnimail')
    );
    return $schedules;
});

/**
 * Begin execution of the plugin.
 */
function run_omnimail()
{
    // Load core classes (needed everywhere)
    require_once OMNIMAIL_PLUGIN_DIR . 'admin/class-omnimail-settings.php';
    require_once OMNIMAIL_PLUGIN_DIR . 'admin/class-omnimail-api.php';

    // Load admin functionality
    if (is_admin()) {
        require_once OMNIMAIL_PLUGIN_DIR . 'admin/class-omnimail-admin.php';

        $plugin_admin = new OmniMail_Admin();
        $plugin_admin->init();
    }

    // Load WooCommerce integration
    if (class_exists('WooCommerce')) {
        require_once OMNIMAIL_PLUGIN_DIR . 'includes/class-omnimail-woocommerce.php';
        new OmniMail_WooCommerce();
    }
}

/**
 * Add custom cron schedule for cart abandonment checks
 */
function omnimail_add_cron_schedules($schedules)
{
    $schedules['omnimail_15min'] = array(
        'interval' => 900, // 15 minutes in seconds
        'display'  => __('Every 15 Minutes (OmniMail)', 'omnimail')
    );
    return $schedules;
}
add_filter('cron_schedules', 'omnimail_add_cron_schedules');

add_action('plugins_loaded', 'run_omnimail');

/**
 * Enqueue storefront SMS contact modal for OmniMail.
 */
function omnimail_enqueue_frontend_contact() {
    if (!class_exists('WooCommerce') || !OmniMail_Settings::is_enabled() || !OmniMail_Settings::is_configured()) {
        return;
    }

    if (!OmniMail_API::has_pro_subscription()) {
        return;
    }

    if (!is_cart() && !is_checkout() && !is_product() && !is_account_page() && !is_shop()) {
        return;
    }

    wp_enqueue_script(
        'omnimail-sms-contact',
        OMNIMAIL_PLUGIN_URL . 'assets/js/omnimail-contact.js',
        array('jquery'),
        OMNIMAIL_VERSION,
        true
    );
    wp_localize_script('omnimail-sms-contact', 'omnimailSmsContact', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('omnimail_sms_contact_nonce'),
        'cookieName' => 'omnimail_sms_contact',
        'dismissalKey' => 'omnimail_sms_contact_dismissed',
        'delay' => 30000,
    ));
    wp_enqueue_style(
        'omnimail-sms-contact',
        OMNIMAIL_PLUGIN_URL . 'assets/css/omnimail-contact.css',
        array(),
        OMNIMAIL_VERSION
    );
}
add_action('wp_enqueue_scripts', 'omnimail_enqueue_frontend_contact');
