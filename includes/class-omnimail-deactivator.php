<?php
/**
 * Fired during plugin deactivation
 *
 * @package    OmniMail
 * @subpackage OmniMail/includes
 */

class OmniMail_Deactivator
{
  /**
   * Deactivate the plugin.
   */
  public static function deactivate()
  {
    // Clear any scheduled events
    wp_clear_scheduled_hook('omnimail_sync_events');
    wp_clear_scheduled_hook('omnimail_check_abandoned_carts');
  }
}
