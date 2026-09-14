<?php
/**
 * API Handler for OmniMail Backend
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin
 */

class OmniMail_API
{
  private $api_base_url;
  private $api_key;
  private $connection_id;

  public function __construct()
  {
    $this->api_base_url = OMNIMAIL_API_BASE_URL;
    $this->api_key = get_option('omnimail_api_key', '');
    $this->connection_id = get_option('omnimail_connection_id', '');
  }

  /**
   * Check whether the connected OmniMail account has an active PRO subscription.
   *
   * IMPORTANT: This used to call a separate OmniVoice subscription endpoint
   * (/stripe/connect/omnivoice-subscription). It no longer does that — SMS
   * behavioral flows are now gated by the exact same "isPro" flag returned by
   * the Email Behavioral Flows endpoint (/api/behavioral-flows/{connectionId}).
   * In other words: if the OmniMail account has a PRO plan, SMS behavioral
   * flows are unlocked too, no separate OmniVoice plan required.
   *
   * @return bool
   */
  public static function has_pro_subscription()
  {
    $connection_id = get_option('omnimail_connection_id', '');

    if (empty($connection_id)) {
      return false;
    }

    $api = new self();
    $result = $api->get_behavioral_flows();

    if (empty($result['success']) || empty($result['data']) || !is_array($result['data'])) {
      return false;
    }

    return !empty($result['data']['isPro']);
  }

  /**
   * Test connection by sending a test event to WordPress webhook
   */
  public function test_connection()
  {
    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'Connection ID is required'
      );
    }

    // Send a test event to the WordPress webhook endpoint
    $url = $this->api_base_url . "/api/integrations/wordpress/events/{$this->connection_id}";

    $payload = array(
      'event_type' => 'connection_test',
      'data' => array(
        'test' => true,
        'message' => 'Connection test from OmniVoice plugin',
        'site_url' => get_site_url(),
        'timestamp' => current_time('mysql')
      ),
      'source_id' => 'test',
      'user_email' => 'user@example.com',
    );

    $args = array(
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json'
      ),
      'body' => json_encode($payload),
      'timeout' => 30
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => 'Connection failed: ' . $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status_code >= 200 && $status_code < 300) {
      return array(
        'success' => true,
        'message' => 'Connection successful! Test event sent.',
        'data' => $body
      );
    }

    return array(
      'success' => false,
      'message' => $body['message'] ?? 'Connection failed with status ' . $status_code
    );
  }

  /**
   * Save a visitor phone number for SMS behavioral flows.
   */
  public function save_sms_contact($phone_number, $uuid, $browser_details = array())
  {
    if (!self::has_pro_subscription()) {
      return array(
        'success' => false,
        'message' => 'An active PRO OmniMail plan is required for SMS behavioral flows.'
      );
    }

    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'SMS marketing is not configured yet.'
      );
    }

    $phone_number = is_string($phone_number) ? trim($phone_number) : '';
    $phone_digits = preg_replace('/[^0-9+]/', '', $phone_number);
    if (!preg_match('/^\+[1-9][0-9]{7,14}$/', $phone_digits)) {
      return array(
        'success' => false,
        'message' => 'Please enter a valid phone number with country code, such as +14155552671.'
      );
    }

    $uuid = is_string($uuid) ? strtolower(trim($uuid)) : '';
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) {
      return array(
        'success' => false,
        'message' => 'The contact identifier is invalid. Please try again.'
      );
    }

    $normalized_browser_details = is_array($browser_details) ? $browser_details : array();

    $payload = array(
      'connectionId' => $this->connection_id,
      'uuid' => $uuid,
      'phoneNumber' => $phone_digits,
      'browserDetails' => array(
        'userAgent' => isset($normalized_browser_details['userAgent']) ? sanitize_text_field($normalized_browser_details['userAgent']) : '',
        'language' => isset($normalized_browser_details['language']) ? sanitize_text_field($normalized_browser_details['language']) : '',
        'languages' => isset($normalized_browser_details['languages']) && is_array($normalized_browser_details['languages'])
          ? array_map('sanitize_text_field', $normalized_browser_details['languages'])
          : array(),
        'platform' => isset($normalized_browser_details['platform']) ? sanitize_text_field($normalized_browser_details['platform']) : '',
        'screen' => isset($normalized_browser_details['screen']) && is_array($normalized_browser_details['screen']) ? $normalized_browser_details['screen'] : array(),
        'timezone' => isset($normalized_browser_details['timezone']) ? sanitize_text_field($normalized_browser_details['timezone']) : '',
        'url' => isset($normalized_browser_details['url']) ? esc_url_raw($normalized_browser_details['url']) : '',
        'referrer' => isset($normalized_browser_details['referrer']) ? esc_url_raw($normalized_browser_details['referrer']) : '',
      ),
      'siteUrl' => esc_url_raw(home_url('/')),
    );

    $response = wp_remote_post(
      $this->api_base_url . '/api/sms-behavioral-flows/contacts',
      array(
        'timeout' => 30,
        'headers' => array(
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          'X-API-Key' => $this->api_key,
        ),
        'body' => wp_json_encode($payload),
      )
    );

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message(),
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status_code < 200 || $status_code >= 300 || !is_array($body) || empty($body['success'])) {
      return array(
        'success' => false,
        'message' => isset($body['message']) ? sanitize_text_field($body['message']) : 'The SMS service could not save your number.',
      );
    }

    $saved_contact = isset($body['data']) && is_array($body['data']) ? $body['data'] : array();

    return array(
      'success' => true,
      'id' => isset($saved_contact['id']) ? sanitize_text_field((string) $saved_contact['id']) : '',
      'message' => isset($body['message']) ? sanitize_text_field($body['message']) : 'Your number was saved successfully.',
    );
  }

  /**
   * Send a storefront behavioral event for the contact stored in the cookie.
   */
  public function send_sms_behavioral_event($event_type, $event_data)
  {
    $flow_by_event = array(
      'added_to_cart' => 'cartAbandonmentEnabled',
      'removed_from_cart' => 'cartAbandonmentEnabled',
      'product_viewed' => 'browseAbandonmentEnabled',
      'checkout_started' => 'checkoutAbandonmentEnabled',
      'wishlist_item_added' => 'wishlistReminderEnabled',
      'purchase_completed' => 'postPurchaseEnabled',
      're_engagement' => 'reEngagementEnabled',
      'back_in_stock' => 'backInStockEnabled',
      'price_drop' => 'priceDropEnabled',
    );

    if (!isset($flow_by_event[$event_type])) {
      return false;
    }

    if (!self::has_pro_subscription()) {
      return false;
    }

    if (empty($this->connection_id)) {
      return false;
    }

    $cookie_value = isset($_COOKIE['omnimail_sms_contact'])
      ? rawurldecode(wp_unslash($_COOKIE['omnimail_sms_contact']))
      : '';
    $cookie_data = json_decode($cookie_value, true);
    $contact_id = is_string($cookie_data)
      ? sanitize_text_field($cookie_data)
      : (is_array($cookie_data) && isset($cookie_data['id'])
        ? sanitize_text_field((string) $cookie_data['id'])
        : '');

    if (!$contact_id) {
      return false;
    }

    $flow_response = $this->request_sms_flows('GET');
    if (is_wp_error($flow_response) || empty($flow_response['data'][$flow_by_event[$event_type]])) {
      return false;
    }

    $url = $this->api_base_url . '/api/sms-behavioral-flows/' . rawurlencode($contact_id) . '/events';
    $response = wp_remote_post($url, array(
      'timeout' => 15,
      'headers' => array(
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ),
      'body' => wp_json_encode(array(
        'eventType' => $event_type,
        'data' => is_array($event_data) ? $event_data : array(),
      )),
    ));

    return !is_wp_error($response)
      && wp_remote_retrieve_response_code($response) >= 200
      && wp_remote_retrieve_response_code($response) < 300;
  }

  /**
   * Broadcast a product-level SMS event.
   */
  public function send_sms_behavioral_broadcast($event_type, $product_data)
  {
    $flow_by_event = array(
      'back_in_stock' => 'backInStockEnabled',
      'price_drop' => 'priceDropEnabled',
    );

    if (!isset($flow_by_event[$event_type])) {
      return false;
    }

    if (!self::has_pro_subscription()) {
      return false;
    }

    if (empty($this->connection_id)) {
      return false;
    }

    $flow_response = $this->request_sms_flows('GET');
    if (is_wp_error($flow_response) || empty($flow_response['data'][$flow_by_event[$event_type]])) {
      return false;
    }

    $response = wp_remote_post(
      $this->api_base_url . '/api/sms-behavioral-flows/broadcast',
      array(
        'timeout' => 15,
        'headers' => array(
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          'X-API-Key' => $this->api_key,
        ),
        'body' => wp_json_encode(array(
          'connectionId' => $this->connection_id,
          'eventType' => $event_type,
          'data' => is_array($product_data) ? $product_data : array(),
        )),
      )
    );

    if (is_wp_error($response)) {
      return false;
    }

    $status = wp_remote_retrieve_response_code($response);
    return $status >= 200 && $status < 300;
  }

  private function request_sms_flows($method, $settings = array())
  {
    if (empty($this->connection_id)) {
      return new WP_Error('missing_configuration', 'API Key and Connection ID are required.');
    }

    $url = $this->api_base_url . '/api/sms-behavioral-flows/' . rawurlencode($this->connection_id);
    $args = array(
      'method' => $method,
      'timeout' => 30,
      'headers' => array(
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'X-API-Key' => $this->api_key,
      ),
    );

    if ('PUT' === $method) {
      $args['body'] = wp_json_encode($settings);
    }

    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
      return $response;
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($status < 200 || $status >= 300 || !is_array($body)) {
      return new WP_Error('sms_flows_request_failed', $body['message'] ?? sprintf('Backend returned HTTP %d.', $status), array('status' => $status));
    }

    return $body;
  }

  /**
   * Get SMS behavioral-flow settings.
   *
   * @return array|WP_Error
   */
  public function get_sms_flows()
  {
    return $this->request_sms_flows('GET');
  }

  /**
   * Update SMS behavioral-flow settings.
   *
   * @param array $settings
   * @return array|WP_Error
   */
  public function update_sms_flows($settings = array())
  {
    return $this->request_sms_flows('PUT', $settings);
  }

  /**
   * Sync a batch of products to POST /product-service/sync
   * Backend upserts by externalId + connectionId (no duplicates per store).
   *
   * @param array $products Product payload items
   * @return array
   */
  public function sync_products(array $products)
  {
    if (empty($this->api_key)) {
      return array(
        'success' => false,
        'message' => 'API Key is required',
      );
    }

    if (empty($products)) {
      return array(
        'success' => true,
        'message' => 'No products to sync',
        'data' => array('synced' => 0),
      );
    }

    $url = $this->api_base_url . '/product-service/sync';

    $args = array(
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json',
        'X-API-Key' => $this->api_key,
      ),
      'body' => wp_json_encode(array('products' => $products)),
      'timeout' => 60,
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message(),
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status_code >= 200 && $status_code < 300) {
      return array(
        'success' => true,
        'message' => isset($body['message']) ? $body['message'] : 'Products synced',
        'data' => $body,
      );
    }

    return array(
      'success' => false,
      'message' => isset($body['message'])
        ? $body['message']
        : sprintf('Backend returned HTTP %d', $status_code),
    );
  }

  /**
   * Save WooCommerce REST API keys for this organization.
   */
  public function save_woocommerce_credentials($consumer_key, $consumer_secret)
  {
    if (empty($this->api_key) || empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'API Key and Connection ID are required',
      );
    }

    $response = $this->make_request('POST', '/woocommerce-credentials', array(
      'connectionId' => $this->connection_id,
      'consumerKey' => $consumer_key,
      'consumerSecret' => $consumer_secret,
    ));

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message(),
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $application_error = is_array($body)
      && isset($body['responseCode'])
      && (int) $body['responseCode'] >= 4000;

    if ($status_code < 200 || $status_code > 299 || $application_error) {
      return array(
        'success' => false,
        'message' => isset($body['message'])
          ? $body['message']
          : sprintf('Backend returned HTTP %d', $status_code),
      );
    }

    return array(
      'success' => true,
      'message' => isset($body['message']) ? $body['message'] : 'WooCommerce credentials saved',
      'data' => isset($body['data']) ? $body['data'] : $body,
    );
  }

  /**
   * Fetch WooCommerce REST API keys stored for this organization.
   */
  public function get_woocommerce_credentials()
  {
    if (empty($this->api_key) || empty($this->connection_id)) {
      return array('success' => false);
    }

    $endpoint = '/woocommerce-credentials?connectionId=' . rawurlencode($this->connection_id);
    $response = $this->make_request('GET', $endpoint);

    if (is_wp_error($response)) {
      return array('success' => false);
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($status_code < 200 || $status_code > 299 || !is_array($body)) {
      return array('success' => false);
    }

    $data = isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;
    $consumer_key = isset($data['consumerKey']) ? (string) $data['consumerKey'] : '';
    $consumer_secret = isset($data['consumerSecret']) ? (string) $data['consumerSecret'] : '';

    if ('' === $consumer_key || '' === $consumer_secret) {
      return array('success' => false);
    }

    return array(
      'success' => true,
      'consumerKey' => $consumer_key,
      'consumerSecret' => $consumer_secret,
    );
  }

  /**
   * Get email configuration
   */
  public function get_email_config()
  {
    $response = $this->make_request(
      'GET',
      "/api/integrations/connections/{$this->connection_id}/email-config"
    );

    if (is_wp_error($response)) {
      return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code === 200) {
      return json_decode(wp_remote_retrieve_body($response), true);
    }

    return null;
  }

  /**
   * Save email configuration
   */
  public function save_email_config($config)
  {
    $response = $this->make_request(
      'POST',
      "/api/integrations/connections/{$this->connection_id}/email-config",
      $config
    );

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'message' => $body['message'] ?? 'Configuration saved',
      'data' => $body
    );
  }

  /**
   * Test email configuration
   */
  public function test_email_config($test_email)
  {
    $response = $this->make_request(
      'POST',
      "/api/integrations/connections/{$this->connection_id}/email-config/test",
      array('testEmail' => $test_email)
    );

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'message' => $body['message'] ?? 'Test email sent',
      'data' => $body
    );
  }

  /**
   * Get behavioral flow settings
   */
  public function get_behavioral_flows()
  {
    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'Connection ID is not configured. Please configure it in Settings.'
      );
    }

    $response = $this->make_request(
      'GET',
      "/api/behavioral-flows/{$this->connection_id}"
    );

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'data' => $body['data'] ?? null,
      'message' => $body['message'] ?? ''
    );
  }

  /**
   * Update behavioral flow settings
   */
  public function update_behavioral_flows($settings)
  {
    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'Connection ID is not configured. Please configure it in Settings.'
      );
    }

    $response = $this->make_request(
      'PUT',
      "/api/behavioral-flows/{$this->connection_id}",
      $settings
    );

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'message' => $body['message'] ?? 'Settings updated',
      'data' => $body['data'] ?? null
    );
  }

  /**
   * Enable all behavioral flows
   */
  public function enable_all_flows()
  {
    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'Connection ID is not configured. Please configure it in Settings.'
      );
    }

    $response = $this->make_request(
      'PUT',
      "/api/behavioral-flows/{$this->connection_id}/enable-all"
    );

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'message' => $body['message'] ?? 'All flows enabled',
      'data' => $body['data'] ?? null
    );
  }

  /**
   * Disable all behavioral flows
   */
  public function disable_all_flows()
  {
    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'Connection ID is not configured. Please configure it in Settings.'
      );
    }

    $response = $this->make_request(
      'PUT',
      "/api/behavioral-flows/{$this->connection_id}/disable-all"
    );

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'message' => $body['message'] ?? 'All flows disabled',
      'data' => $body['data'] ?? null
    );
  }

  /**
   * Get follow-ups for a behavioral flow type
   */
  public function get_flow_followups($flow_type)
  {
    if (empty($this->connection_id) || empty($this->api_key)) {
      return array(
        'success' => false,
        'message' => 'Connection ID / API Key is not configured.'
      );
    }

    $flow_type = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $flow_type);
    if ($flow_type === '') {
      return array('success' => false, 'message' => 'Invalid flow type');
    }

    $response = $this->make_request(
      'GET',
      '/api/behavioral-flows/followups/' . rawurlencode($flow_type) . '/' . rawurlencode($this->connection_id)
    );

    if (is_wp_error($response)) {
      return array('success' => false, 'message' => $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'data' => $body['data'] ?? array(),
      'message' => $body['message'] ?? ''
    );
  }

  /**
   * Create a single follow-up
   */
  public function create_flow_followup($payload)
  {
    if (empty($this->connection_id) || empty($this->api_key)) {
      return array(
        'success' => false,
        'message' => 'Connection ID / API Key is not configured.'
      );
    }

    $payload = is_array($payload) ? $payload : array();
    $payload['connectionId'] = $this->connection_id;

    $response = $this->make_request(
      'POST',
      '/api/behavioral-flows/followups',
      $payload
    );

    if (is_wp_error($response)) {
      return array('success' => false, 'message' => $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'data' => $body['data'] ?? null,
      'message' => $body['message'] ?? ''
    );
  }

  /**
   * Delete a follow-up by ID
   */
  public function delete_flow_followup($followup_id)
  {
    if (empty($this->api_key)) {
      return array('success' => false, 'message' => 'API Key is not configured.');
    }

    $followup_id = sanitize_text_field($followup_id);
    if ($followup_id === '') {
      return array('success' => false, 'message' => 'Invalid follow-up ID');
    }

    $response = $this->make_request(
      'DELETE',
      '/api/behavioral-flows/followups/' . rawurlencode($followup_id)
    );

    if (is_wp_error($response)) {
      return array('success' => false, 'message' => $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'message' => $body['message'] ?? 'Follow-up deleted',
      'data' => $body['data'] ?? null
    );
  }

  /**
   * Replace all follow-ups for a flow with a prebuilt sequence
   */
  public function apply_prebuilt_followups($flow_type, $followups)
  {
    if (empty($this->connection_id) || empty($this->api_key)) {
      return array(
        'success' => false,
        'message' => 'Connection ID / API Key is not configured.'
      );
    }

    if (!is_array($followups)) {
      return array('success' => false, 'message' => 'Invalid follow-ups payload');
    }

    $existing = $this->get_flow_followups($flow_type);
    if (!$existing['success']) {
      return $existing;
    }

    $current = isset($existing['data']) && is_array($existing['data']) ? $existing['data'] : array();
    foreach ($current as $item) {
      if (empty($item['id'])) {
        continue;
      }
      $deleted = $this->delete_flow_followup($item['id']);
      if (!$deleted['success']) {
        return array(
          'success' => false,
          'message' => $deleted['message'] ?: 'Failed to clear existing follow-ups'
        );
      }
    }

    $created = 0;
    foreach ($followups as $step) {
      if (!is_array($step)) {
        continue;
      }
      $payload = array(
        'behavioralFlowType' => $flow_type,
        'connectionId' => $this->connection_id,
        'trigger' => isset($step['trigger']) ? $step['trigger'] : 'not_opened',
        'actionType' => isset($step['actionType']) ? $step['actionType'] : 'email',
        'delayDays' => isset($step['delayDays']) ? intval($step['delayDays']) : 0,
        'followupEmailSubject' => isset($step['followupEmailSubject']) ? $step['followupEmailSubject'] : '',
        'followupEmailContent' => isset($step['followupEmailContent']) ? $step['followupEmailContent'] : '',
        'followupTag' => !empty($step['followupTag']) ? $step['followupTag'] : null,
      );
      if (!empty($step['followupSenderEmail'])) {
        $payload['followupSenderEmail'] = $step['followupSenderEmail'];
      }
      if (isset($step['activeDaySchedules']) && is_array($step['activeDaySchedules'])) {
        $payload['activeDaySchedules'] = $step['activeDaySchedules'];
      }

      $result = $this->create_flow_followup($payload);
      if (!$result['success']) {
        return array(
          'success' => false,
          'message' => $result['message'] ?: 'Failed while creating follow-up steps',
          'created' => $created
        );
      }
      $created++;
    }

    return array(
      'success' => true,
      'message' => $created === 0
        ? 'Cleared follow-ups (None selected)'
        : sprintf('Applied %d follow-up steps', $created),
      'created' => $created
    );
  }

  /**
   * Get email statistics for connection
   */
  public function get_email_stats($auto_reply_page = 1, $auto_reply_limit = 10)
  {
    if (empty($this->connection_id)) {
      error_log('OmniMail API: Connection ID is empty');
      return array(
        'success' => false,
        'message' => 'Connection ID is not configured.'
      );
    }

    $auto_reply_page = max(1, intval($auto_reply_page));
    $auto_reply_limit = max(1, min(50, intval($auto_reply_limit)));

    $url = $this->api_base_url . "/api/integrations/wordpress/connections/{$this->connection_id}/email-stats"
      . "?autoReplyPage={$auto_reply_page}&autoReplyLimit={$auto_reply_limit}";
    
    error_log('OmniMail API: Calling URL: ' . $url);
    error_log('OmniMail API: API Key: ' . substr($this->api_key, 0, 10) . '...');

    $args = array(
      'method' => 'GET',
      'headers' => array(
        'Content-Type' => 'application/json',
        'X-API-Key' => $this->api_key
      ),
      'timeout' => 30
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      error_log('OmniMail API: WP Error: ' . $response->get_error_message());
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    error_log('OmniMail API: Status Code: ' . $status_code);
    error_log('OmniMail API: Response Body: ' . wp_remote_retrieve_body($response));

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'data' => $body,
      'message' => $body['message'] ?? ''
    );
  }

  /**
   * Get paginated AI auto-replies for connection
   */
  public function get_auto_replies($page = 1, $limit = 10)
  {
    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'Connection ID is not configured.'
      );
    }

    $page = max(1, intval($page));
    $limit = max(1, min(50, intval($limit)));

    $url = $this->api_base_url
      . "/api/integrations/wordpress/connections/{$this->connection_id}/auto-replies"
      . "?page={$page}&limit={$limit}";

    $args = array(
      'method' => 'GET',
      'headers' => array(
        'Content-Type' => 'application/json',
        'X-API-Key' => $this->api_key
      ),
      'timeout' => 30
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'data' => $body,
      'message' => $body['message'] ?? ''
    );
  }

  /**
   * Get all emails for connection
   */
  public function get_all_emails($page = 1, $limit = 20, $email_type = '')
  {
    if (empty($this->connection_id)) {
      return array(
        'success' => false,
        'message' => 'Connection ID is not configured.'
      );
    }

    $url = $this->api_base_url . "/api/integrations/wordpress/connections/{$this->connection_id}/emails?page={$page}&limit={$limit}";
    
    if (!empty($email_type)) {
      $url .= "&emailType={$email_type}";
    }

    $args = array(
      'method' => 'GET',
      'headers' => array(
        'Content-Type' => 'application/json',
        'X-API-Key' => $this->api_key
      ),
      'timeout' => 30
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message()
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
      'success' => $status_code >= 200 && $status_code < 300,
      'data' => $body,
      'message' => $body['message'] ?? ''
    );
  }

  /**
   * Get Stripe Connect account status.
   * Auth: same as OmniVoice product sync — X-API-Key (plugin connection key or org key)
   * plus optional connectionId. Also accepts Bearer for compatibility.
   */
  public function get_stripe_connect_account()
  {
    if (empty($this->api_key)) {
      return array(
        'success' => false,
        'message' => 'API Key is required',
        'connected' => false,
        'pending' => false,
      );
    }

    $url = $this->api_base_url . '/stripe/connect/account';
    if (!empty($this->connection_id)) {
      $url .= '?connectionId=' . rawurlencode($this->connection_id);
    }

    $args = array(
      'method' => 'GET',
      'headers' => array(
        'Content-Type' => 'application/json',
        'X-API-Key' => $this->api_key,
        'Authorization' => 'Bearer ' . $this->api_key,
      ),
      'timeout' => 30,
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message(),
        'connected' => false,
        'pending' => false,
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $raw_body = wp_remote_retrieve_body($response);
    $body = json_decode($raw_body, true);

    if (!is_array($body)) {
      return array(
        'success' => false,
        'message' => 'Invalid response from Stripe Connect API',
        'connected' => false,
        'pending' => false,
      );
    }

    // Nest may return HTTP 200 with success:false when no account exists
    $account = isset($body['data']) && is_array($body['data']) ? $body['data'] : array();
    $connected = false;
    $pending = false;

    $has_account_id = !empty($account['stripe_account_id']);
    $charges_enabled = !empty($account['charges_enabled']);
    $platform_ready =
      !empty($account['uses_platform_stripe']) &&
      (!empty($account['connected']) || !empty($account['ready']));

    if ($platform_ready) {
      $connected = true;
    } elseif ($has_account_id && $charges_enabled) {
      $connected = true;
    } elseif ($has_account_id) {
      $pending = true;
    } elseif (
      isset($account['metadata']['status']) &&
      $account['metadata']['status'] === 'connected'
    ) {
      $connected = true;
    }

    $ok = ($status_code >= 200 && $status_code < 300) || $status_code === 200;

    return array(
      'success' => $ok,
      'message' => isset($body['message']) ? $body['message'] : '',
      'connected' => $connected,
      'pending' => $pending,
      'data' => $account,
    );
  }

  /**
   * Send event to backend (no API key required, only connection ID)
   */
  public function send_event($event_type, $event_data)
  {
    if (empty($this->connection_id)) {
      $this->log_error('Event send failed', 'Connection ID is not configured');
      return false;
    }

    $url = $this->api_base_url . "/api/integrations/wordpress/events/{$this->connection_id}";

    $payload = array(
      'event_type' => $event_type,
      'data' => $event_data,
      'source_id' => $event_data['orderId'] ?? $event_data['productId'] ?? null,
      'user_email' => $event_data['customerEmail'] ?? $event_data['email'] ?? $event_data['user_email'] ?? null,
      'timestamp' => current_time('mysql'),
      'site_url' => get_site_url()
    );

    $args = array(
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json'
      ),
      'body' => json_encode($payload),
      'timeout' => 30
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      $this->log_error('Event send failed', $response->get_error_message());
      return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code >= 200 && $status_code < 300) {
      $this->log_info('Event sent', $event_type);
      return true;
    }

    $this->log_error('Event failed', wp_remote_retrieve_body($response));
    return false;
  }

  /**
   * Make HTTP request to backend (uses Bearer token for regular API)
   */
  private function make_request($method, $endpoint, $body = null)
  {
    $url = $this->api_base_url . $endpoint;

    $args = array(
      'method' => $method,
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $this->api_key
      ),
      'timeout' => 30
    );

    if ($body !== null) {
      $args['body'] = json_encode($body);
    }

    return wp_remote_request($url, $args);
  }

  /**
   * Log info message
   */
  private function log_info($message, $details = '')
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'omnimail_logs';

    $wpdb->insert(
      $table_name,
      array(
        'log_type' => 'info',
        'message' => $message,
        'details' => is_array($details) ? json_encode($details) : $details,
        'created_at' => current_time('mysql')
      )
    );
  }

  /**
   * Log error message
   */
  private function log_error($message, $details = '')
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'omnimail_logs';

    $wpdb->insert(
      $table_name,
      array(
        'log_type' => 'error',
        'message' => $message,
        'details' => is_array($details) ? json_encode($details) : $details,
        'created_at' => current_time('mysql')
      )
    );
  }
}