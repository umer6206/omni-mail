<?php
/**
 * Fired during plugin activation
 *
 * @package    OmniMail
 * @subpackage OmniMail/includes
 */

class OmniMail_Activator
{
  /**
   * Activate the plugin.
   */
  public static function activate()
  {
    // Set default options
    if (!get_option('omnimail_enabled')) {
      add_option('omnimail_enabled', '0');
    }
    if (!get_option('omnimail_api_key')) {
      add_option('omnimail_api_key', '');
    }
    if (!get_option('omnimail_connection_id')) {
      add_option('omnimail_connection_id', '');
    }
    if (!get_option('omnimail_version')) {
      add_option('omnimail_version', OMNIMAIL_VERSION);
    }

    // Create logs table
    global $wpdb;
    $table_name = $wpdb->prefix . 'omnimail_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      log_type varchar(50) NOT NULL,
      message text NOT NULL,
      details longtext,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id),
      KEY log_type (log_type),
      KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }
}
