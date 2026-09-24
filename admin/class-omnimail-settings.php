<?php
/**
 * Settings functionality for the plugin.
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin
 */

class OmniMail_Settings
{
  public function __construct()
  {
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_post_omnimail_save_settings', array($this, 'save_settings'));
    add_action('wp_ajax_omnimail_test_connection', array($this, 'ajax_test_connection'));
    add_action('wp_ajax_omnimail_sync_products', array($this, 'ajax_sync_products'));
    add_action('wp_ajax_omnimail_generate_wc_keys', array($this, 'ajax_generate_wc_keys'));
    add_action('wp_ajax_omnimail_save_woocommerce_credentials', array($this, 'ajax_save_woocommerce_credentials'));
    add_action('wp_ajax_omnimail_test_email', array($this, 'ajax_test_email'));
    add_action('wp_ajax_omnimail_get_behavioral_flows', array($this, 'ajax_get_behavioral_flows'));
    add_action('wp_ajax_omnimail_update_behavioral_flows', array($this, 'ajax_update_behavioral_flows'));
    add_action('wp_ajax_omnimail_save_sms_contact', array($this, 'ajax_save_sms_contact'));
    add_action('wp_ajax_nopriv_omnimail_save_sms_contact', array($this, 'ajax_save_sms_contact'));
    add_action('wp_ajax_omnimail_enable_all_flows', array($this, 'ajax_enable_all_flows'));
    add_action('wp_ajax_omnimail_disable_all_flows', array($this, 'ajax_disable_all_flows'));
    add_action('wp_ajax_omnimail_get_flow_followups', array($this, 'ajax_get_flow_followups'));
    add_action('wp_ajax_omnimail_delete_flow_followup', array($this, 'ajax_delete_flow_followup'));
    add_action('wp_ajax_omnimail_apply_prebuilt_followups', array($this, 'ajax_apply_prebuilt_followups'));
    add_action('wp_ajax_omnimail_get_stripe_connect_status', array($this, 'ajax_get_stripe_connect_status'));
    add_action('wp_ajax_omnimail_get_email_stats', array($this, 'ajax_get_email_stats'));
    add_action('wp_ajax_omnimail_get_auto_replies', array($this, 'ajax_get_auto_replies'));
    add_action('wp_ajax_omnimail_get_all_emails', array($this, 'ajax_get_all_emails'));
  }

  /**
   * Register plugin settings.
   */
  public function register_settings()
  {
    register_setting('omnimail_settings', 'omnimail_enabled');
    register_setting('omnimail_settings', 'omnimail_api_key');
    register_setting('omnimail_settings', 'omnimail_connection_id');
  }

  /**
   * Save settings.
   */
  public function save_settings()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'omnimail'));
    }

    // Check nonce
    check_admin_referer('omnimail_settings_nonce');

    // Sanitize and save enabled option
    $enabled = isset($_POST['omnimail_enabled']) ? '1' : '0';
    update_option('omnimail_enabled', $enabled);

    // Sanitize and save API key
    $api_key = isset($_POST['omnimail_api_key']) ? sanitize_text_field($_POST['omnimail_api_key']) : '';
    update_option('omnimail_api_key', $api_key);

    // Sanitize and save Connection ID
    $connection_id = isset($_POST['omnimail_connection_id']) ? sanitize_text_field($_POST['omnimail_connection_id']) : '';
    update_option('omnimail_connection_id', $connection_id);

    // Redirect back with success message
    wp_redirect(add_query_arg(
      array(
        'page' => 'omnimail-settings',
        'settings-updated' => 'true'
      ),
      admin_url('admin.php')
    ));
    exit;
  }

  /**
   * AJAX: Test connection
   */
  public function ajax_test_connection()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->test_connection();

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Sync WooCommerce products to OmniMail (batched).
   * Upsert on the backend is keyed by externalId + connectionId (no duplicates).
   */
  public function ajax_sync_products()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    if (!class_exists('WooCommerce')) {
      wp_send_json_error(array('message' => __('WooCommerce is not active', 'omnimail')));
    }

    if (!self::is_configured()) {
      wp_send_json_error(array('message' => __('API Key and Connection ID are required', 'omnimail')));
    }

    if (!self::is_enabled()) {
      wp_send_json_error(array('message' => __('Enable OmniMail and save settings before syncing products', 'omnimail')));
    }

    if (!self::has_woocommerce_credentials()) {
      wp_send_json_error(array('message' => __('Save WooCommerce REST API credentials before synchronizing products.', 'omnimail')));
    }

    $batch = max(0, intval(isset($_POST['batch']) ? $_POST['batch'] : 0));
    $batch_size = 5;

    $total = (int) wp_count_posts('product')->publish;

    $products = wc_get_products(array(
      'status'  => 'publish',
      'limit'   => $batch_size,
      'offset'  => $batch * $batch_size,
      'orderby' => 'ID',
      'order'   => 'ASC',
      'return'  => 'objects',
    ));

    if (empty($products)) {
      wp_send_json_success(array(
        'done'    => true,
        'total'   => $total,
        'synced'  => $batch * $batch_size,
        'message' => __('All products synced successfully.', 'omnimail'),
      ));
    }

    $connection_id = self::get_connection_id();
    $payload_products = array();

    foreach ($products as $product) {
      $image_id = $product->get_image_id();
      $image_url = $image_id ? wp_get_attachment_url($image_id) : '';

      $gallery_urls = array();
      foreach ($product->get_gallery_image_ids() as $gid) {
        $url = wp_get_attachment_url($gid);
        if ($url) {
          $gallery_urls[] = $url;
        }
      }

      $category_names = array();
      foreach ($product->get_category_ids() as $cat_id) {
        $term = get_term($cat_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
          $category_names[] = $term->name;
        }
      }

      $tag_names = array();
      foreach ($product->get_tag_ids() as $tag_id) {
        $term = get_term($tag_id, 'product_tag');
        if ($term && !is_wp_error($term)) {
          $tag_names[] = $term->name;
        }
      }

      $attributes = array();
      foreach ($product->get_attributes() as $attr_key => $attr_obj) {
        $attributes[$attr_key] = implode(', ', $attr_obj->get_options());
      }

      $payload_products[] = array(
        'externalId'       => (string) $product->get_id(),
        'name'             => $product->get_name(),
        'slug'             => $product->get_slug(),
        'sku'              => $product->get_sku(),
        'type'             => $product->get_type(),
        'status'           => $product->get_status(),
        'description'      => wp_strip_all_tags($product->get_description()),
        'shortDescription' => wp_strip_all_tags($product->get_short_description()),
        'price'            => $product->get_price(),
        'regularPrice'     => $product->get_regular_price(),
        'salePrice'        => $product->get_sale_price(),
        'currency'         => get_woocommerce_currency(),
        'stockStatus'      => $product->get_stock_status(),
        'stockQuantity'    => $product->get_stock_quantity(),
        'manageStock'      => $product->get_manage_stock(),
        'weight'           => $product->get_weight(),
        'categories'       => $category_names,
        'tags'             => $tag_names,
        'imageUrl'         => $image_url,
        'galleryImages'    => $gallery_urls,
        'attributes'       => $attributes,
        'permalink'        => get_permalink($product->get_id()),
        'siteUrl'          => get_site_url(),
        'connectionId'     => $connection_id,
      );
    }

    $api = new OmniMail_API();
    $result = $api->sync_products($payload_products);

    if (!$result['success']) {
      wp_send_json_error(array(
        'message' => isset($result['message']) ? $result['message'] : __('Product sync failed', 'omnimail'),
      ));
    }

    $synced_so_far = ($batch * $batch_size) + count($products);
    $has_more = $synced_so_far < $total;

    wp_send_json_success(array(
      'done'      => !$has_more,
      'batch'     => $batch,
      'nextBatch' => $has_more ? $batch + 1 : null,
      'total'     => $total,
      'synced'    => $synced_so_far,
      'message'   => sprintf(
        __('Synced %d of %d products…', 'omnimail'),
        $synced_so_far,
        $total
      ),
    ));
  }

  /**
   * AJAX: Test email
   */
  public function ajax_test_email()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';

    if (empty($test_email) || !is_email($test_email)) {
      wp_send_json_error(array('message' => __('Invalid email address', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->test_email_config($test_email);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Get behavioral flows
   */
  public function ajax_get_behavioral_flows()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->get_behavioral_flows();

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Save a visitor phone number for SMS behavioral flows.
   */
  public function ajax_save_sms_contact()
  {
    if (!check_ajax_referer('omnimail_sms_contact_nonce', 'nonce', false)) {
      wp_send_json_error(array('message' => __('Security check failed. Please refresh and try again.', 'omnimail')), 403);
    }

    if (!OmniMail_API::has_pro_subscription()) {
      wp_send_json_error(array('message' => __('An active PRO OmniMail plan is required for SMS behavioral flows.', 'omnimail')), 403);
    }

    $phone_number = isset($_POST['phoneNumber']) ? sanitize_text_field(wp_unslash($_POST['phoneNumber'])) : '';
    $contact_uuid = isset($_POST['uuid']) ? sanitize_text_field(wp_unslash($_POST['uuid'])) : '';
    $browser_details = isset($_POST['browserDetails']) && is_string($_POST['browserDetails'])
      ? json_decode(wp_unslash($_POST['browserDetails']), true)
      : array();

    if (!is_array($browser_details)) {
      $browser_details = array();
    }

    $api = new OmniMail_API();
    $result = $api->save_sms_contact($phone_number, $contact_uuid, $browser_details);

    if ($result['success']) {
      wp_send_json_success($result);
    }

    wp_send_json_error($result);
  }

  /**
   * AJAX: Update behavioral flows
   */
  public function ajax_update_behavioral_flows()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array();

    if (empty($settings)) {
      wp_send_json_error(array('message' => __('No settings provided', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->update_behavioral_flows($settings);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Enable all flows
   */
  public function ajax_enable_all_flows()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->enable_all_flows();

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Disable all flows
   */
  public function ajax_disable_all_flows()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->disable_all_flows();

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Get follow-ups for a flow
   */
  public function ajax_get_flow_followups()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $flow_type = isset($_POST['flow_type']) ? sanitize_text_field(wp_unslash($_POST['flow_type'])) : '';
    if ($flow_type === '') {
      wp_send_json_error(array('message' => __('Flow type is required', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->get_flow_followups($flow_type);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Delete a follow-up
   */
  public function ajax_delete_flow_followup()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $followup_id = isset($_POST['followup_id']) ? sanitize_text_field(wp_unslash($_POST['followup_id'])) : '';
    if ($followup_id === '') {
      wp_send_json_error(array('message' => __('Follow-up ID is required', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->delete_flow_followup($followup_id);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Apply prebuilt follow-up sequence
   */
  public function ajax_apply_prebuilt_followups()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    $flow_type = isset($_POST['flow_type']) ? sanitize_text_field(wp_unslash($_POST['flow_type'])) : '';
    $followups_raw = isset($_POST['followups']) ? wp_unslash($_POST['followups']) : '';
    $followups = json_decode($followups_raw, true);

    if ($flow_type === '') {
      wp_send_json_error(array('message' => __('Flow type is required', 'omnimail')));
    }

    // Empty array is valid: "None" clears existing follow-ups for this flow.
    if (!is_array($followups)) {
      wp_send_json_error(array('message' => __('Invalid follow-up payload', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->apply_prebuilt_followups($flow_type, $followups);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Generate WooCommerce REST API keys on this site.
   */
  public function ajax_generate_wc_keys()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    if (!class_exists('WooCommerce')) {
      wp_send_json_error(array('message' => __('WooCommerce is not installed/active.', 'omnimail')));
    }

    $keys = $this->create_woocommerce_api_keys();
    if (is_wp_error($keys)) {
      wp_send_json_error(array('message' => $keys->get_error_message()));
    }

    wp_send_json_success(array(
      'message' => __('WooCommerce REST API keys generated. Click Save WooCommerce Credentials to store them in Omni.', 'omnimail'),
      'consumerKey' => $keys['consumer_key'],
      'consumerSecret' => $keys['consumer_secret'],
    ));
  }

  /**
   * AJAX: Save WooCommerce credentials to Omni (per organization).
   */
  public function ajax_save_woocommerce_credentials()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    if (!self::is_configured()) {
      wp_send_json_error(array('message' => __('Save a valid API Key and Connection ID before connecting WooCommerce.', 'omnimail')));
    }

    $consumer_key = isset($_POST['consumerKey'])
      ? trim(sanitize_text_field(wp_unslash($_POST['consumerKey'])))
      : '';
    $submitted_secret = isset($_POST['consumerSecret'])
      ? trim(sanitize_text_field(wp_unslash($_POST['consumerSecret'])))
      : '';

    $remote = self::get_woocommerce_credentials(true);
    $remote_secret = !empty($remote['success']) ? (string) $remote['consumerSecret'] : '';
    $consumer_secret = $submitted_secret === self::mask_woocommerce_consumer_secret($remote_secret)
      ? $remote_secret
      : $submitted_secret;

    if ('' === $consumer_key || '' === $consumer_secret) {
      wp_send_json_error(array('message' => __('Consumer Key and Consumer Secret are required.', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->save_woocommerce_credentials($consumer_key, $consumer_secret);

    if (empty($result['success'])) {
      wp_send_json_error(array(
        'message' => isset($result['message']) ? $result['message'] : __('The Omni backend rejected the WooCommerce credentials.', 'omnimail'),
      ));
    }

    delete_option('omnimail_wc_consumer_key');
    delete_option('omnimail_wc_consumer_secret');

    wp_send_json_success(array(
      'message' => __('WooCommerce credentials were saved and connected to Omni successfully.', 'omnimail'),
      'consumerKey' => $consumer_key,
      'maskedSecret' => self::mask_woocommerce_consumer_secret($consumer_secret),
    ));
  }

  /**
   * Retrieve WooCommerce credentials stored for this org on Omni.
   */
  public static function get_woocommerce_credentials($force_refresh = false)
  {
    static $cached_result = null;

    if (!$force_refresh && null !== $cached_result) {
      return $cached_result;
    }

    $empty = array(
      'success' => false,
      'consumerKey' => '',
      'consumerSecret' => '',
    );

    if (!self::is_configured()) {
      $cached_result = $empty;
      return $cached_result;
    }

    $api = new OmniMail_API();
    $result = $api->get_woocommerce_credentials();
    if (empty($result['success'])) {
      $cached_result = $empty;
      return $cached_result;
    }

    $cached_result = array(
      'success' => true,
      'consumerKey' => isset($result['consumerKey']) ? (string) $result['consumerKey'] : '',
      'consumerSecret' => isset($result['consumerSecret']) ? (string) $result['consumerSecret'] : '',
    );

    return $cached_result;
  }

  /**
   * Whether Omni already has Woo REST keys for this organization.
   */
  public static function has_woocommerce_credentials()
  {
    $credentials = self::get_woocommerce_credentials();
    return !empty($credentials['success']);
  }

  /**
   * Display-safe consumer secret (last four characters only).
   */
  public static function mask_woocommerce_consumer_secret($consumer_secret)
  {
    $consumer_secret = trim((string) $consumer_secret);
    if ('' === $consumer_secret) {
      return '';
    }

    return '********' . substr($consumer_secret, -4);
  }

  /**
   * Create Read/Write WooCommerce REST API keys for the current admin.
   */
  private function create_woocommerce_api_keys()
  {
    global $wpdb;

    if (!function_exists('wc_rand_hash') || !function_exists('wc_api_hash')) {
      return new WP_Error('omnimail_wc_missing', __('WooCommerce API helpers are unavailable.', 'omnimail'));
    }

    $user_id = get_current_user_id();
    $description = 'OmniMail - ' . gmdate('Y-m-d H:i:s');
    $consumer_key = 'ck_' . wc_rand_hash();
    $consumer_secret = 'cs_' . wc_rand_hash();

    $inserted = $wpdb->insert(
      $wpdb->prefix . 'woocommerce_api_keys',
      array(
        'user_id' => $user_id,
        'description' => $description,
        'permissions' => 'read_write',
        'consumer_key' => wc_api_hash($consumer_key),
        'consumer_secret' => $consumer_secret,
        'truncated_key' => substr($consumer_key, -7),
      ),
      array('%d', '%s', '%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
      $db_error = $wpdb->last_error ? ' ' . $wpdb->last_error : '';
      return new WP_Error(
        'omnimail_key_insert_failed',
        __('Failed to create WooCommerce API keys.', 'omnimail') . $db_error
      );
    }

    return array(
      'consumer_key' => $consumer_key,
      'consumer_secret' => $consumer_secret,
      'key_id' => $wpdb->insert_id,
    );
  }

  /**
   * Display the settings page.
   */
  public function display()
  {
    require_once OMNIMAIL_PLUGIN_DIR . 'admin/views/settings-page.php';
  }

  /**
   * Get the enabled status.
   */
  public static function is_enabled()
  {
    return get_option('omnimail_enabled', '0') === '1';
  }

  /**
   * Get the API key.
   */
  public static function get_api_key()
  {
    return get_option('omnimail_api_key', '');
  }

  /**
   * Get the Connection ID.
   */
  public static function get_connection_id()
  {
    return get_option('omnimail_connection_id', '');
  }

  /**
   * Check if configured.
   */
  public static function is_configured()
  {
    return !empty(self::get_api_key()) && !empty(self::get_connection_id());
  }

  /**
   * AJAX: Get Stripe Connect account status
   */
  public function ajax_get_stripe_connect_status()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Insufficient permissions', 'omnimail')));
    }

    if (!self::is_configured()) {
      wp_send_json_error(array('message' => __('API Key and Connection ID are required', 'omnimail')));
    }

    $api = new OmniMail_API();
    $result = $api->get_stripe_connect_account();

    if ($result['success'] || isset($result['connected'])) {
      wp_send_json_success($result);
    }

    wp_send_json_error($result);
  }

  /**
   * AJAX: Get email statistics
   */
  public function ajax_get_email_stats()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    $auto_reply_page = isset($_POST['auto_reply_page']) ? intval($_POST['auto_reply_page']) : 1;
    $auto_reply_limit = isset($_POST['auto_reply_limit']) ? intval($_POST['auto_reply_limit']) : 10;

    $api = new OmniMail_API();
    $result = $api->get_email_stats($auto_reply_page, $auto_reply_limit);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Get paginated AI auto-replies
   */
  public function ajax_get_auto_replies()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

    $api = new OmniMail_API();
    $result = $api->get_auto_replies($page, $limit);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * AJAX: Get all emails
   */
  public function ajax_get_all_emails()
  {
    check_ajax_referer('omnimail_nonce', 'nonce');

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
    $email_type = isset($_POST['email_type']) ? sanitize_text_field($_POST['email_type']) : '';

    $api = new OmniMail_API();
    $result = $api->get_all_emails($page, $limit, $email_type);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }
}

new OmniMail_Settings();
