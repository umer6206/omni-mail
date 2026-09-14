<?php
/**
 * Dashboard page template
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin/views
 */

if (!defined('WPINC')) {
    die;
}

$enabled = OmniMail_Settings::is_enabled();
$configured = OmniMail_Settings::is_configured();
$ready = $enabled && $configured;
$platform_url = defined('OMNIMAIL_APP_URL') ? OMNIMAIL_APP_URL : 'https://omnimail-app.omninexttech.com';
$connections_url = defined('OMNIMAIL_CONNECTIONS_URL') ? OMNIMAIL_CONNECTIONS_URL : $platform_url;
$settings_url = admin_url('admin.php?page=omnimail-settings');
?>

<div class="wrap omnimail-dashboard">
    <div class="omnimail-page-wrapper">
        <div class="omnimail-hero">
            <div class="omnimail-hero-brand">
                <img src="<?php echo esc_url(OMNIMAIL_LOGO_URL); ?>" alt="<?php esc_attr_e('OmniMail', 'omnimail'); ?>" class="omnimail-logo">
                <p class="omnimail-hero-sub">
                    <?php
                    if ($ready) {
                        _e('Your store is connected. Automated emails follow your Behavioral Flows settings.', 'omnimail');
                    } else {
                        _e('Connect this store to OmniMail so abandoned carts and orders can trigger emails automatically.', 'omnimail');
                    }
                    ?>
                </p>
            </div>
            <?php if (!$configured): ?>
                <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary button-large omnimail-hero-cta">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Start setup (2 minutes)', 'omnimail'); ?>
                </a>
            <?php elseif (!$enabled): ?>
                <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary button-large omnimail-hero-cta">
                    <span class="dashicons dashicons-controls-play"></span>
                    <?php _e('Enable OmniMail in Settings', 'omnimail'); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-behavioral-flows')); ?>"
                    class="button button-primary button-large omnimail-hero-cta">
                    <span class="dashicons dashicons-randomize"></span>
                    <?php _e('Manage email flows', 'omnimail'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Status Cards -->
        <div class="omnimail-status-cards">
            <div class="omnimail-card omnimail-status-card">
                <div class="omnimail-status-card-icon">
                    <span class="dashicons dashicons-admin-plugins"></span>
                </div>
                <div class="omnimail-status-card-content">
                    <h3><?php _e('Plugin', 'omnimail'); ?></h3>
                    <?php if ($enabled): ?>
                        <p class="omnimail-status omnimail-status-active">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Enabled', 'omnimail'); ?>
                        </p>
                    <?php else: ?>
                        <p class="omnimail-status omnimail-status-inactive">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php _e('Disabled', 'omnimail'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="omnimail-card omnimail-status-card">
                <div class="omnimail-status-card-icon">
                    <span class="dashicons dashicons-admin-network"></span>
                </div>
                <div class="omnimail-status-card-content">
                    <h3><?php _e('Connection', 'omnimail'); ?></h3>
                    <?php if ($configured): ?>
                        <p class="omnimail-status omnimail-status-active">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Configured', 'omnimail'); ?>
                        </p>
                    <?php else: ?>
                        <p class="omnimail-status omnimail-status-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('Needs setup', 'omnimail'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="omnimail-card omnimail-status-card">
                <div class="omnimail-status-card-icon">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="omnimail-status-card-content">
                    <h3><?php _e('Overall', 'omnimail'); ?></h3>
                    <?php if ($ready): ?>
                        <p class="omnimail-status omnimail-status-active">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Ready', 'omnimail'); ?>
                        </p>
                    <?php else: ?>
                        <p class="omnimail-status omnimail-status-warning">
                            <span class="dashicons dashicons-clock"></span>
                            <?php _e('Finish setup', 'omnimail'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="omnimail-card omnimail-status-card">
                <div class="omnimail-status-card-icon">
                    <span class="dashicons dashicons-wordpress"></span>
                </div>
                <div class="omnimail-status-card-content">
                    <h3><?php _e('Version', 'omnimail'); ?></h3>
                    <p class="omnimail-stat-number">
                        <?php echo esc_html(OMNIMAIL_VERSION); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="omnimail-card">
            <h2><?php _e('Quick actions', 'omnimail'); ?></h2>
            <div class="omnimail-quick-actions">
                <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary button-large">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php echo $configured ? esc_html__('Settings', 'omnimail') : esc_html__('Connect store', 'omnimail'); ?>
                </a>
                <a href="<?php echo esc_url($connections_url); ?>" target="_blank" rel="noopener noreferrer"
                    class="button button-secondary button-large">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('Email Plugin Connection', 'omnimail'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-behavioral-flows')); ?>"
                    class="button button-secondary button-large">
                    <span class="dashicons dashicons-randomize"></span>
                    <?php _e('Behavioral Flows', 'omnimail'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-email-stats')); ?>"
                    class="button button-secondary button-large">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php _e('Email Statistics', 'omnimail'); ?>
                </a>
                <button type="button" id="omnimail-raise-ticket-btn" class="button button-primary button-large omnimail-ticket-btn">
                    <span class="dashicons dashicons-sos" aria-hidden="true"></span>
                    <?php _e('Raise Ticket', 'omnimail'); ?>
                </button>
            </div>
        </div>

        <?php if ($configured): ?>
            <!-- Email overview stats -->
            <div id="omnimail-dashboard-stats" data-configured="1">
                <div id="omnimail-dash-stats-loading" class="omnimail-card" style="text-align:center;padding:28px;">
                    <span class="spinner is-active" style="float:none;margin:0;"></span>
                    <p><?php _e('Loading email statistics...', 'omnimail'); ?></p>
                </div>

                <div id="omnimail-dash-stats-error" class="omnimail-card" style="display:none;">
                    <p id="omnimail-dash-stats-error-message" style="margin:0;color:#ff6b81;"></p>
                    <p style="margin:12px 0 0;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-settings')); ?>" class="button button-secondary">
                            <?php _e('Check Settings', 'omnimail'); ?>
                        </a>
                    </p>
                </div>

                <div id="omnimail-dash-stats-content" style="display:none;">
                    <div class="omnimail-status-cards">
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-email-alt"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Total Sent', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-total-sent">0</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Sent Today', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-sent-today">0</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-chart-line"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('This Week', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-sent-week">0</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-chart-bar"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('This Month', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-sent-month">0</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-visibility"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Opened', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-opened">0</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-admin-links"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Clicked', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-clicked">0</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-chart-area"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Open Rate', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number"><span id="dash-stat-open-rate">0</span>%</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Click Rate', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number"><span id="dash-stat-click-rate">0</span>%</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-warning"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Bounced', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-bounced">0</p>
                            </div>
                        </div>
                        <div class="omnimail-card omnimail-status-card">
                            <div class="omnimail-status-card-icon">
                                <span class="dashicons dashicons-dismiss"></span>
                            </div>
                            <div class="omnimail-status-card-content">
                                <h3><?php _e('Unsubscribed', 'omnimail'); ?></h3>
                                <p class="omnimail-stat-number" id="dash-stat-unsubscribed">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="omnimail-card">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                            <h2 style="margin:0;"><?php _e('Latest emails', 'omnimail'); ?></h2>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-email-stats')); ?>" class="button button-secondary">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php _e('Full statistics', 'omnimail'); ?>
                            </a>
                        </div>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Type', 'omnimail'); ?></th>
                                    <th><?php _e('Subject', 'omnimail'); ?></th>
                                    <th><?php _e('Recipient', 'omnimail'); ?></th>
                                    <th><?php _e('Sent At', 'omnimail'); ?></th>
                                    <th><?php _e('Opened', 'omnimail'); ?></th>
                                    <th><?php _e('Clicked', 'omnimail'); ?></th>
                                    <th><?php _e('Bounced', 'omnimail'); ?></th>
                                    <th><?php _e('Unsubscribed', 'omnimail'); ?></th>
                                    <th><?php _e('Status', 'omnimail'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="dash-recent-emails-tbody">
                                <tr>
                                    <td colspan="9" style="text-align:center;"><?php _e('Loading...', 'omnimail'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="omnimail-card">
                <h2><?php _e('Email activity', 'omnimail'); ?></h2>
                <p><?php _e('Connect your store in Settings to see email totals and the latest messages here.', 'omnimail'); ?></p>
                <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Open Settings', 'omnimail'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php
        $omni_suite_exclude = 'mail';
        $omni_suite_assets_url = OMNIMAIL_PLUGIN_URL . 'assets/';
        require OMNIMAIL_PLUGIN_DIR . 'admin/views/partials/more-from-us.php';
        ?>
    </div>
</div>

<!-- Raise Ticket Modal -->
<style>
.omnimail-modal-logs {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(10,18,36,0.85);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.omnimail-modal-content {
  background: #181e2a;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  box-shadow: 0 8px 32px rgba(0,0,0,0.32);
  padding: 0;
  animation: fadeIn 0.2s;
  color: #f6f8fa;
  display: flex;
  flex-direction: column;
}
@keyframes fadeIn { from { opacity: 0; transform: scale(0.95);} to { opacity: 1; transform: scale(1);} }
.omnimail-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px 10px 24px;
  border-bottom: 1px solid #232b3b;
  background: #181e2a;
  color: #fff;
  flex-shrink: 0;
}
.omnimail-modal-header h3 {
  color: #00e6fb;
  font-weight: 600;
  margin: 0;
}
.omnimail-modal-body {
  padding: 18px 24px 24px 24px;
  background: #181e2a;
  overflow-y: auto;
  flex: 1;
}
.omnimail-modal-logs button.omnimail-modal-close {
  background: none;
  border: none;
  color: #00e6fb;
  font-size: 22px;
  cursor: pointer;
  padding: 0;
  margin: 0;
  transition: color 0.2s;
}
.omnimail-modal-logs button.omnimail-modal-close:hover {
  color: #fff;
}
</style>

<div id="omnimail-ticket-modal" class="omnimail-modal-logs" style="display: none;">
  <div class="omnimail-modal-content">
    <div class="omnimail-modal-header">
      <h3><?php _e('Raise Support Ticket', 'omnimail'); ?></h3>
      <button type="button" class="omnimail-modal-close" id="close-ticket-modal">
        <span class="dashicons dashicons-no"></span>
      </button>
    </div>
    <div class="omnimail-modal-body">
      <div id="ticket-modal-error" class="omnimail-no-data" style="display: none; color: #ff3b5c; border: 1px solid #ff3b5c; padding: 10px; border-radius: 4px; margin-bottom: 15px; background: rgba(255, 59, 92, 0.1);"></div>

      <form id="omnimail-ticket-form">
        <div class="ticket-auto-subject-box" style="background: #1e293b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed #2d3e50;">
           <label style="display: block; font-size: 10px; color: var(--color-cyan); letter-spacing: 1px; margin-bottom: 8px; font-weight: bold;"><?php _e('AUTO-GENERATED SUBJECT', 'omnimail'); ?></label>
           <div class="subject-display" style="display: flex; justify-content: space-between; align-items: center;">
             <span id="generated-subject-text" style="font-size: 18px; color: var(--color-cyan); font-weight: bold;">TKT-630058-OmniMail</span>
             <button type="button" id="copy-subject-btn" style="background: none; border: none; color: var(--color-cyan); cursor: pointer; padding: 5px;">
               <span class="dashicons dashicons-admin-page"></span>
             </button>
           </div>
        </div>

        <div class="omnimail-form-group" style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #fff; font-size: 14px;"><?php _e('Subject', 'omnimail'); ?> <span style="color: #ff3b5c;">*</span></label>
          <input type="text" id="ticket-subject" name="subject" required readonly style="width: 100%; background: #0f1728; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 4px;">
        </div>

        <input type="hidden" id="ticket-type" name="type" value="General Inquiry">

        <div class="omnimail-form-group" style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #fff; font-size: 14px;"><?php _e('Priority', 'omnimail'); ?> <span style="color: #ff3b5c;">*</span></label>
          <select id="ticket-priority" name="priority" required style="width: 100%; background: #0f1728; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 4px;">
            <option value="Low"><?php _e('Low', 'omnimail'); ?></option>
            <option value="Medium" selected><?php _e('Medium', 'omnimail'); ?></option>
            <option value="High"><?php _e('High', 'omnimail'); ?></option>
          </select>
        </div>

        <div class="omnimail-form-group" style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #fff; font-size: 14px;"><?php _e('Loom Link', 'omnimail'); ?></label>
          <input type="url" id="ticket-loom" name="loomLink" placeholder="https://www.loom.com/share/..." style="width: 100%; background: #0f1728; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 4px;">
          <small style="color: #9ca3af; font-size: 12px; display: block; margin-top: 5px;"><?php _e('Optional: Add a Loom video link for additional context', 'omnimail'); ?></small>
        </div>

        <div class="omnimail-form-group" style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #fff; font-size: 14px;"><?php _e('Description', 'omnimail'); ?> <span style="color: #ff3b5c;">*</span></label>
          <textarea id="ticket-description" name="description" rows="4" placeholder="<?php esc_attr_e('Describe your issue in detail...', 'omnimail'); ?>" required style="width: 100%; background: #0f1728; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 4px; resize: vertical;"></textarea>
        </div>

        <div class="omnimail-form-group" style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 5px; color: #fff; font-size: 14px;"><?php _e('Attachments (Optional)', 'omnimail'); ?></label>
          <div id="ticket-upload-area" style="background: rgba(0, 255, 255, 0.05); border: 2px dashed #0a738c; border-radius: 8px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.2s;">
             <span class="dashicons dashicons-upload" style="font-size: 40px; width: 40px; height: 40px; color: var(--color-cyan); margin-bottom: 10px;"></span>
             <h3 style="margin: 0; color: #fff; font-size: 18px;"><?php _e('Click to Upload Files', 'omnimail'); ?></h3>
             <p style="margin: 5px 0 0 0; color: #9ca3af; font-size: 12px;"><?php _e('Supported formats (PDF, DOC, Images and Videos)', 'omnimail'); ?></p>
             <input type="file" id="ticket-files" name="attachments[]" multiple style="display: none;">
          </div>
          <div id="selected-files-list" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;"></div>
        </div>

        <div class="ticket-form-actions" style="border-top: 1px solid #232b3b; padding-top: 20px; text-align: right;">
          <button type="button" id="submit-ticket-btn" class="button button-primary button-large" style="background: var(--color-cyan); color: var(--color-midnight); border: none; font-weight: 600; padding: 0 30px;">
            <?php _e('Submit Ticket', 'omnimail'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
