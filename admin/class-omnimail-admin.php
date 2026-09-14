<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin
 */

class OmniMail_Admin
{
  public function init()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  /**
   * Register the stylesheets for the admin area.
   */
  public function enqueue_styles($hook)
  {
    // Only load on our plugin pages
    if (strpos($hook, 'omnimail') === false) {
      return;
    }

    wp_enqueue_style(
      'omnimail-admin',
      OMNIMAIL_PLUGIN_URL . 'assets/css/admin-style.css',
      array(),
      OMNIMAIL_VERSION,
      'all'
    );
  }

  /**
   * Register the JavaScript for the admin area.
   */
  public function enqueue_scripts($hook)
  {
    // Only load on our plugin pages
    if (strpos($hook, 'omnimail') === false) {
      return;
    }

    wp_enqueue_script(
      'omnimail-prebuilt-followups',
      OMNIMAIL_PLUGIN_URL . 'assets/js/prebuilt-followups.js',
      array(),
      OMNIMAIL_VERSION,
      true
    );

    wp_enqueue_script(
      'omnimail-admin',
      OMNIMAIL_PLUGIN_URL . 'assets/js/admin-script.js',
      array('jquery', 'omnimail-prebuilt-followups'),
      OMNIMAIL_VERSION,
      true
    );

    // Localize script for AJAX
    wp_localize_script('omnimail-admin', 'omnimailAjax', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('omnimail_nonce'),
      'confirmTest' => __('Send test email?', 'omnimail'),
      'connectionId' => OmniMail_Settings::get_connection_id(),
      'connectionsUrl' => defined('OMNIMAIL_CONNECTIONS_URL')
        ? OMNIMAIL_CONNECTIONS_URL
        : 'https://omnimail-app.omninexttech.com/dashboard/email-plugin-connection',
      'stripeConnectUrl' => defined('OMNIMAIL_STRIPE_CONNECT_URL')
        ? OMNIMAIL_STRIPE_CONNECT_URL
        : 'https://omnimail-app.omninexttech.com/dashboard/billings/stripe-connect',
      'isConfigured' => OmniMail_Settings::is_configured() ? '1' : '0',
      'hasWooCredentials' => OmniMail_Settings::has_woocommerce_credentials() ? '1' : '0',
      'savingWooCredentialsText' => __('Saving & Connecting...', 'omnimail'),
      'generatingKeysText' => __('Generating keys...', 'omnimail'),
      'stripeI18n' => array(
        'connectedLabel' => __('Stripe Connected — Manage', 'omnimail'),
        'pendingLabel' => __('Complete Stripe Setup', 'omnimail'),
        'connectLabel' => __('Connect Stripe Account', 'omnimail'),
        'connectedStatus' => __('Stripe is connected and ready for payment links.', 'omnimail'),
        'pendingStatus' => __('Stripe account found — finish onboarding to accept payments.', 'omnimail'),
        'connectStatus' => __('Connect Stripe to send product payment links from AI auto-replies.', 'omnimail'),
        'checkFailed' => __('Could not check Stripe status. Open Stripe Connect to verify.', 'omnimail'),
      ),
    ));
  }

  /**
   * Add admin menu pages.
   */
  public function add_admin_menu()
  {
    $configured = OmniMail_Settings::is_configured();
    $settings_label = $configured
      ? __('Settings', 'omnimail')
      : __('Settings — Get started', 'omnimail');

    // Main menu page
    add_menu_page(
      __('OmniMail', 'omnimail'),
      __('OmniMail', 'omnimail'),
      'manage_options',
      'omnimail',
      array($this, 'display_dashboard_page'),
      'dashicons-email-alt',
      56
    );

    // Dashboard submenu
    add_submenu_page(
      'omnimail',
      __('Dashboard', 'omnimail'),
      __('Dashboard', 'omnimail'),
      'manage_options',
      'omnimail',
      array($this, 'display_dashboard_page')
    );

    // Settings submenu
    add_submenu_page(
      'omnimail',
      __('Settings', 'omnimail'),
      $settings_label,
      'manage_options',
      'omnimail-settings',
      array($this, 'display_settings_page')
    );

    // Behavioral Flows submenu
    add_submenu_page(
      'omnimail',
      __('Behavioral Flows', 'omnimail'),
      __('Behavioral Flows', 'omnimail'),
      'manage_options',
      'omnimail-behavioral-flows',
      array($this, 'display_behavioral_flows_page')
    );

    // Email Statistics submenu
    add_submenu_page(
      'omnimail',
      __('Email Statistics', 'omnimail'),
      __('Email Statistics', 'omnimail'),
      'manage_options',
      'omnimail-email-stats',
      array($this, 'display_email_stats_page')
    );

    // All Emails submenu
    add_submenu_page(
      'omnimail',
      __('All Emails', 'omnimail'),
      __('All Emails', 'omnimail'),
      'manage_options',
      'omnimail-all-emails',
      array($this, 'display_all_emails_page')
    );
  }

  /**
   * Display the dashboard page.
   */
  public function display_dashboard_page()
  {
    require_once OMNIMAIL_PLUGIN_DIR . 'admin/views/dashboard-page.php';
  }

  /**
   * Display the settings page.
   */
  public function display_settings_page()
  {
    $settings = new OmniMail_Settings();
    $settings->display();
  }

  /**
   * Display the behavioral flows page.
   */
  public function display_behavioral_flows_page()
  {
    require_once OMNIMAIL_PLUGIN_DIR . 'admin/views/behavioral-flows-page.php';
  }

  /**
   * Display the email statistics page.
   */
  public function display_email_stats_page()
  {
    require_once OMNIMAIL_PLUGIN_DIR . 'admin/views/email-stats-page.php';
  }

  /**
   * Display the all emails page.
   */
  public function display_all_emails_page()
  {
    require_once OMNIMAIL_PLUGIN_DIR . 'admin/views/all-emails-page.php';
  }
}
