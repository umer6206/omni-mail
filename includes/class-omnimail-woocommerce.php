<?php
/**
 * WooCommerce Integration
 *
 * @package    OmniMail
 * @subpackage OmniMail/includes
 */

class OmniMail_WooCommerce
{
  private $api;
  private $prices_before_save       = array();
  private $stock_status_before_save = array();
  private $stock_events_processed   = array();

  public function __construct()
  {
    // Always register the cart-remove link filter (no auth required)
    add_filter('woocommerce_cart_item_remove_link', array($this, 'add_remove_link_product_data'), 10, 2);

    if (!OmniMail_Settings::is_enabled() || !OmniMail_Settings::is_configured()) {
      return;
    }

    $this->api = new OmniMail_API();
    $this->init_hooks();
  }

  /**
   * Initialize WooCommerce hooks
   */
  private function init_hooks()
  {
    // Order events
    add_action('woocommerce_new_order', array($this, 'handle_order_created'), 10, 1);
    add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_changed'), 10, 4);

    // Contact-based storefront behavioral events
    add_action('woocommerce_before_single_product', array($this, 'handle_product_viewed'));
    add_action('woocommerce_checkout_init', array($this, 'handle_checkout_started'));
    add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'handle_checkout_started'));
    add_action('woocommerce_thankyou', array($this, 'handle_purchase_completed'), 10, 1);

    // Cart abandonment
    add_action('woocommerce_add_to_cart', array($this, 'handle_added_to_cart'), 10, 6);
    add_action('woocommerce_cart_item_removed', array($this, 'handle_removed_from_cart'), 10, 2);
    add_action('woocommerce_store_api_cart_item_removed', array($this, 'handle_removed_from_cart'), 10, 2);
    add_action('woocommerce_cart_updated', array($this, 'track_cart_update'));

    // Product events
    add_action('woocommerce_product_set_stock',            array($this, 'handle_stock_change'), 10, 1);
    add_action('woocommerce_before_product_object_save',   array($this, 'capture_pre_save_state'), 10, 2);
    add_action('woocommerce_product_object_updated_props', array($this, 'handle_price_change'), 10, 2);
    add_action('woocommerce_product_object_updated_props', array($this, 'handle_stock_status_prop_change'), 10, 2);

    // Customer events
    add_action('woocommerce_created_customer', array($this, 'handle_customer_created'), 10, 3);

    // Schedule cart abandonment check (runs every 15 minutes)
    if (!wp_next_scheduled('omnimail_check_abandoned_carts')) {
      wp_schedule_event(time(), 'omnimail_15min', 'omnimail_check_abandoned_carts');
    }
    add_action('omnimail_check_abandoned_carts', array($this, 'check_abandoned_carts'));
  }

  /**
   * Add product identity attributes to the classic cart remove link.
   * Matches OmniVoice so the JS can read product IDs on removal.
   */
  public function add_remove_link_product_data($link, $cart_item_key)
  {
    if (!function_exists('WC') || !WC()->cart) {
      return $link;
    }

    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    $product   = isset($cart_item['data']) && is_object($cart_item['data']) ? $cart_item['data'] : null;

    if (!$product || !method_exists($product, 'get_id')) {
      return $link;
    }

    $line_id      = $product->get_id();
    $parent_id    = $product->get_parent_id() ?: $line_id;
    $variation_id = $product->is_type('variation') ? $line_id : '';
    $sku          = $product->get_sku();

    $attributes = sprintf(
      ' data-product_id="%s" data-product-id="%s" data-variation_id="%s" data-variation-id="%s" data-parent_id="%s" data-product_sku="%s" data-product-sku="%s"',
      esc_attr($parent_id),
      esc_attr($parent_id),
      esc_attr($variation_id),
      esc_attr($variation_id),
      esc_attr($parent_id),
      esc_attr($sku),
      esc_attr($sku)
    );

    return preg_replace('/^<a\b/', '<a' . $attributes, $link, 1);
  }

  /**
   * Handle order created
   */
  public function handle_order_created($order_id)
  {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Clear cart abandonment data when order is created
    $customer_id = $order->get_customer_id();
    if ($customer_id) {
      delete_user_meta($customer_id, '_omnimail_cart_data');
    }

    $this->api->send_event('order.created', array(
      'orderId' => $order_id,
      'orderNumber' => $order->get_order_number(),
      'status' => $order->get_status(),
      'total' => $order->get_total(),
      'currency' => $order->get_currency(),
      'customerEmail' => $order->get_billing_email(),
      'customerName' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
      'items' => $this->get_order_items($order),
      'billingAddress' => $order->get_address('billing'),
      'shippingAddress' => $order->get_address('shipping')
    ));
  }

  /**
   * Handle order status changed
   */
  public function handle_order_status_changed($order_id, $old_status, $new_status, $order)
  {
    $this->api->send_event('order.status_changed', array(
      'orderId' => $order_id,
      'orderNumber' => $order->get_order_number(),
      'oldStatus' => $old_status,
      'newStatus' => $new_status,
      'customerEmail' => $order->get_billing_email(),
      'customerName' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
      'total' => $order->get_total()
    ));
  }

  /**
   * Track cart activity.
   */
  public function handle_added_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
  {
    if (!function_exists('WC') || !WC()->cart) return;

    $product = wc_get_product($variation_id ?: $product_id);
    if (!$product) return;

    // Email flow event for logged-in users
    if (is_user_logged_in()) {
      $user = wp_get_current_user();
      $cart = WC()->cart;
      $this->api->send_event('cart.item_added', array(
        'productId'     => $product_id,
        'variationId'   => $variation_id,
        'quantity'      => $quantity,
        'customerEmail' => $user->user_email,
        'cartTotal'     => $cart->get_cart_contents_total(),
        'cartItemCount' => $cart->get_cart_contents_count(),
      ));
    }

    // SMS behavioral event — payload matches OmniVoice exactly
    $this->api->send_sms_behavioral_event('added_to_cart', array(
      'productId'     => $product->get_id(),
      'parentId'      => $product->get_parent_id() ?: $product->get_id(),
      'variationId'   => $variation_id,
      'productName'   => $product->get_name(),
      'sku'           => $product->get_sku(),
      'price'         => $product->get_price(),
      'quantity'      => $quantity,
      'cartTotal'     => WC()->cart->get_cart_contents_total(),
      'cartItemCount' => WC()->cart->get_cart_contents_count(),
      'url'           => home_url('/'),
    ));
  }

  /** Handle a native WooCommerce cart removal. */
  public function handle_removed_from_cart($cart_item_key, $cart)
  {
    if (!$this->api || !is_object($cart)) {
      return;
    }

    $removed_items = method_exists($cart, 'get_removed_cart_contents')
      ? $cart->get_removed_cart_contents()
      : (isset($cart->removed_cart_contents) ? $cart->removed_cart_contents : array());

    if (!isset($removed_items[$cart_item_key])) {
      return;
    }

    $cart_item = $removed_items[$cart_item_key];
    $product_id = !empty($cart_item['variation_id'])
      ? $cart_item['variation_id']
      : (!empty($cart_item['product_id']) ? $cart_item['product_id'] : 0);

    $product = $product_id ? wc_get_product($product_id) : null;
    if (!$product || !is_object($product)) return;

    // SMS behavioral event — payload matches OmniVoice exactly
    $this->api->send_sms_behavioral_event('removed_from_cart', array(
      'productId'     => $product->get_id(),
      'parentId'      => $product->get_parent_id() ?: $product->get_id(),
      'variationId'   => $product->is_type('variation') ? $product->get_id() : '',
      'productName'   => $product->get_name(),
      'sku'           => $product->get_sku(),
      'quantity'      => isset($cart_item['quantity']) ? $cart_item['quantity'] : 1,
      'cartItemCount' => $cart->get_cart_contents_count(),
      'cartTotal'     => $cart->get_cart_contents_total(),
    ));
  }

  /** Handle native WooCommerce page lifecycle events. */
  public function handle_product_viewed()
  {
    global $product;

    if (!$product && function_exists('wc_get_product')) {
      $product = wc_get_product(get_the_ID());
    }

    if (!$this->api || !$product || !is_object($product)) {
      return;
    }

    // SMS behavioral event — payload matches OmniVoice exactly
    $this->api->send_sms_behavioral_event('product_viewed', array(
      'productId'   => $product->get_id(),
      'parentId'    => $product->get_parent_id() ?: $product->get_id(),
      'variationId' => $product->is_type('variation') ? $product->get_id() : '',
      'productName' => $product->get_name(),
      'sku'         => $product->get_sku(),
      'price'       => $product->get_price(),
      'url'         => get_permalink($product->get_id()),
    ));
  }

  public function handle_checkout_started()
  {
    if (!$this->api || !function_exists('WC') || !WC()->cart) return;

    // SMS behavioral event — payload matches OmniVoice exactly
    $this->api->send_sms_behavioral_event('checkout_started', array(
      'cartItemCount' => WC()->cart->get_cart_contents_count(),
      'cartTotal'     => WC()->cart->get_cart_contents_total(),
      'url'           => wc_get_checkout_url(),
    ));
  }

  public function handle_purchase_completed($order_id)
  {
    if (!$this->api) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // SMS behavioral event — payload matches OmniVoice exactly
    $this->api->send_sms_behavioral_event('purchase_completed', array(
      'orderId'     => $order_id,
      'orderNumber' => $order->get_order_number(),
      'total'       => $order->get_total(),
      'currency'    => $order->get_currency(),
      'items'       => $this->get_order_items($order),
    ));
  }

  /**
   * Track cart update
   */
  public function track_cart_update()
  {
    if (!is_user_logged_in()) return;

    $user = wp_get_current_user();
    $cart = WC()->cart;

    if ($cart->is_empty()) {
      // Clear cart data if cart is empty
      delete_user_meta($user->ID, '_omnimail_cart_data');
      return;
    }

    // Prepare cart items for storage
    $cart_items = array();
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
      $product = $cart_item['data'];
      $cart_items[] = array(
        'product_id' => $cart_item['product_id'],
        'name' => $product->get_name(),
        'quantity' => $cart_item['quantity'],
        'price' => $product->get_price(),
        'line_total' => $cart_item['line_total'],
      );
    }

    // Store cart data for abandonment tracking
    update_user_meta($user->ID, '_omnimail_cart_data', array(
      'items' => $cart_items,
      'total' => $cart->get_cart_contents_total(),
      'timestamp' => current_time('timestamp'),
      'user_email' => $user->user_email,
    ));
  }

  /**
   * Check for abandoned carts (runs every 15 minutes via cron)
   */
  public function check_abandoned_carts()
  {
    global $wpdb;

    // Get all users with cart data
    $results = $wpdb->get_results(
      "SELECT user_id, meta_value 
       FROM {$wpdb->usermeta} 
       WHERE meta_key = '_omnimail_cart_data'"
    );

    $current_time = current_time('timestamp');
    $abandonment_threshold = 30 * 60; // 30 minutes

    foreach ($results as $result) {
      $cart_data = maybe_unserialize($result->meta_value);
      
      if (!$cart_data || !isset($cart_data['timestamp']) || empty($cart_data['items'])) {
        continue;
      }

      $time_elapsed = $current_time - $cart_data['timestamp'];

      // Check if cart has been abandoned for at least 30 minutes
      if ($time_elapsed >= $abandonment_threshold) {
        $user = get_user_by('id', $result->user_id);
        
        if (!$user || !$user->user_email) continue;

        // Check if user has completed an order since cart was created
        $recent_orders = wc_get_orders(array(
          'customer_id' => $user->ID,
          'date_created' => '>' . date('Y-m-d H:i:s', $cart_data['timestamp']),
          'limit' => 1,
        ));

        // If user has ordered, don't send abandonment event
        if (!empty($recent_orders)) {
          delete_user_meta($user->ID, '_omnimail_cart_data');
          continue;
        }

        // Check if we already sent an abandonment event for this cart
        $sent_flag = get_user_meta($user->ID, '_omnimail_cart_abandonment_sent_' . $cart_data['timestamp'], true);
        if ($sent_flag) {
          continue;
        }

        // Send cart abandonment event
        $this->api->send_event('cart.abandoned', array(
          'user_id' => $user->ID,
          'user_email' => $user->user_email,
          'cart_items' => $cart_data['items'],
          'cart_total' => $cart_data['total'],
          'abandoned_at' => date('c', $cart_data['timestamp']),
          'time_elapsed_minutes' => round($time_elapsed / 60),
        ));

        // Mark as sent
        update_user_meta($user->ID, '_omnimail_cart_abandonment_sent_' . $cart_data['timestamp'], true);
        
        // Clean up old cart data after 7 days
        if ($time_elapsed > (7 * 24 * 60 * 60)) {
          delete_user_meta($user->ID, '_omnimail_cart_data');
          delete_user_meta($user->ID, '_omnimail_cart_abandonment_sent_' . $cart_data['timestamp']);
        }
      }
    }
  }

  /**
   * Capture price and stock status before a product save so we can diff after.
   */
  public function capture_pre_save_state($product, $data_store)
  {
    $this->prices_before_save[$product->get_id()]       = $product->get_price();
    $this->stock_status_before_save[$product->get_id()] = $product->get_stock_status();
  }

  /**
   * Handle stock change via woocommerce_product_set_stock (quantity change path).
   */
  public function handle_stock_change($product)
  {
    if (!$this->api) return;

    $product_id      = $product->get_id();
    $previous_status = get_post_meta($product_id, '_previous_stock_status', true);

    $this->evaluate_stock_transition($product, $previous_status);
  }

  /**
   * Handle stock_status property change via woocommerce_product_object_updated_props.
   */
  public function handle_stock_status_prop_change($product, $updated_props)
  {
    if (!in_array('stock_status', $updated_props, true)) {
      return;
    }

    $product_id      = $product->get_id();
    $previous_status = isset($this->stock_status_before_save[$product_id])
      ? $this->stock_status_before_save[$product_id]
      : get_post_meta($product_id, '_previous_stock_status', true);

    unset($this->stock_status_before_save[$product_id]);

    $this->evaluate_stock_transition($product, $previous_status);
  }

  /**
   * Shared logic for in-stock / out-of-stock transitions (deduped per save).
   */
  private function evaluate_stock_transition($product, $previous_status)
  {
    if (!$this->api) return;

    $product_id    = $product->get_id();
    $stock_status  = $product->get_stock_status();
    $stock_qty     = $product->get_stock_quantity();

    update_post_meta($product_id, '_previous_stock_status', $stock_status);

    // Dedupe: only fire once per product per status per save cycle
    $dedupe_key = $product_id . ':' . $stock_status;
    if (isset($this->stock_events_processed[$dedupe_key])) {
      return;
    }
    $this->stock_events_processed[$dedupe_key] = true;

    if ($stock_status === 'outofstock' && $previous_status !== 'outofstock') {
      $this->api->send_event('product.out_of_stock', array(
        'productId'   => $product_id,
        'parentId'    => $product->get_parent_id() ?: $product_id,
        'productName' => $product->get_name(),
        'sku'         => $product->get_sku(),
        'url'         => get_permalink($product->get_parent_id() ?: $product_id),
      ));
    } elseif ($stock_status === 'instock' && $previous_status === 'outofstock') {
      $this->api->send_event('product.back_in_stock', array(
        'productId'   => $product_id,
        'productName' => $product->get_name(),
        'sku'         => $product->get_sku(),
        'stockQuantity' => $stock_qty,
      ));

      // SMS broadcast — payload matches OmniVoice exactly
      $this->api->send_sms_behavioral_broadcast('back_in_stock', array(
        'productId'     => $product_id,
        'parentId'      => $product->get_parent_id() ?: $product_id,
        'productName'   => $product->get_name(),
        'sku'           => $product->get_sku(),
        'stockQuantity' => $stock_qty,
        'url'           => get_permalink($product->get_parent_id() ?: $product_id),
      ));
    }
  }

  /**
   * Handle price change via woocommerce_product_object_updated_props.
   */
  public function handle_price_change($product, $updated_props)
  {
    if (!$this->api) return;

    if (!in_array('regular_price', $updated_props, true) && !in_array('sale_price', $updated_props, true)) {
      return;
    }

    $product_id     = $product->get_id();
    $current_price  = floatval($product->get_price());
    $previous_price = isset($this->prices_before_save[$product_id])
      ? floatval($this->prices_before_save[$product_id])
      : floatval(get_post_meta($product_id, '_previous_price', true));

    update_post_meta($product_id, '_previous_price', $current_price);
    unset($this->prices_before_save[$product_id]);

    if ($previous_price > 0 && $current_price < $previous_price) {
      $price_change_percent = (($previous_price - $current_price) / $previous_price) * 100;

      if ($price_change_percent >= 5) {
        $this->api->send_event('product.price_dropped', array(
          'productId'          => $product_id,
          'productName'        => $product->get_name(),
          'sku'                => $product->get_sku(),
          'currentPrice'       => $current_price,
          'previousPrice'      => $previous_price,
          'priceChangePercent' => round($price_change_percent, 2),
          'currency'           => get_woocommerce_currency(),
        ));

        // SMS broadcast — payload matches OmniVoice exactly
        $this->api->send_sms_behavioral_broadcast('price_drop', array(
          'productId'          => $product_id,
          'parentId'           => $product->get_parent_id() ?: $product_id,
          'productName'        => $product->get_name(),
          'sku'                => $product->get_sku(),
          'currentPrice'       => $current_price,
          'previousPrice'      => $previous_price,
          'priceChangePercent' => round($price_change_percent, 2),
          'currency'           => get_woocommerce_currency(),
          'url'                => get_permalink($product->get_parent_id() ?: $product_id),
        ));
      }
    }
  }

  /**
   * Handle customer created
   */
  public function handle_customer_created($customer_id, $new_customer_data, $password_generated)
  {
    $customer = new WC_Customer($customer_id);

    $this->api->send_event('customer.created', array(
      'customerId' => $customer_id,
      'email' => $customer->get_email(),
      'firstName' => $customer->get_first_name(),
      'lastName' => $customer->get_last_name(),
      'username' => $customer->get_username()
    ));
  }

  /**
   * Get order items
   */
  private function get_order_items($order)
  {
    $items = array();

    foreach ($order->get_items() as $item) {
      $product = $item->get_product();

      if (!$product) {
        $items[] = array(
          'productId'   => null,
          'productName' => $item->get_name(),
          'quantity'    => $item->get_quantity(),
          'price'       => $item->get_total(),
          'sku'         => null,
        );
        continue;
      }

      $items[] = array(
        'productId'   => $product->get_id(),
        'productName' => $item->get_name(),
        'quantity'    => $item->get_quantity(),
        'price'       => $item->get_total(),
        'sku'         => $product->get_sku(),
      );
    }

    return $items;
  }
}
