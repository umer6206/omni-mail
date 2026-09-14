<?php
/**
 * Email Statistics Page
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin/views
 */

// Exit if accessed directly
if (!defined('WPINC')) {
  die;
}

$connection_id = get_option('omnimail_connection_id', '');
$api_key = get_option('omnimail_api_key', '');
$is_configured = !empty($connection_id) && !empty($api_key);
?>

<div class="wrap omnimail-settings">
  <div class="omnimail-page-wrapper">
    <h1><?php _e('Email Statistics', 'omnimail'); ?></h1>

    <?php if (!$is_configured): ?>
      <div class="omnimail-card omnimail-empty-state">
        <span class="dashicons dashicons-chart-bar"></span>
        <h2><?php _e('Connect OmniMail to see stats', 'omnimail'); ?></h2>
        <p><?php _e('Paste your Connection ID and API Key from the OmniMail app, then come back here.', 'omnimail'); ?></p>
        <div class="omnimail-quick-actions">
          <a href="<?php echo esc_url(defined('OMNIMAIL_CONNECTIONS_URL') ? OMNIMAIL_CONNECTIONS_URL : 'https://omnimail-app.omninexttech.com/dashboard/email-plugin-connection'); ?>"
            target="_blank" rel="noopener noreferrer" class="button button-secondary button-large">
            <span class="dashicons dashicons-external"></span>
            <?php _e('Get credentials', 'omnimail'); ?>
          </a>
          <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-settings')); ?>" class="button button-primary button-large">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('Open Settings', 'omnimail'); ?>
          </a>
        </div>
      </div>
    <?php else: ?>

    <!-- Loading State -->
    <div id="stats-loading" style="text-align: center; padding: 40px;">
      <span class="spinner is-active" style="float: none; margin: 0;"></span>
      <p><?php _e('Loading email statistics...', 'omnimail'); ?></p>
    </div>

    <!-- Error State -->
    <div id="stats-error" style="display: none;">
      <div class="notice notice-error">
        <p id="stats-error-message"></p>
      </div>
    </div>

    <!-- Stats Content -->
    <div id="stats-content" style="display: none;">
      
      <!-- Overview Cards -->
      <div class="omnimail-status-cards">
        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-email-alt"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Total Sent', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-total-sent">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-calendar-alt"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Sent Today', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-sent-today">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-chart-line"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('This Week', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-sent-week">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-chart-bar"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('This Month', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-sent-month">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-visibility"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Opened', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-opened">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-admin-links"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Clicked', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-clicked">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-chart-area"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Open Rate', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number"><span id="stat-open-rate">0</span>%</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-performance"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Click Rate', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number"><span id="stat-click-rate">0</span>%</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-warning"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Bounced', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-bounced">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-dismiss"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Unsubscribed', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number" id="stat-unsubscribed">0</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-flag"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Bounce Rate', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number"><span id="stat-bounce-rate">0</span>%</p>
          </div>
        </div>

        <div class="omnimail-card omnimail-status-card">
          <div class="omnimail-status-card-icon">
            <span class="dashicons dashicons-no-alt"></span>
          </div>
          <div class="omnimail-status-card-content">
            <h3><?php _e('Unsubscribe Rate', 'omnimail'); ?></h3>
            <p class="omnimail-stat-number"><span id="stat-unsubscribe-rate">0</span>%</p>
          </div>
        </div>
      </div>

      <!-- Email Types Breakdown -->
      <div class="omnimail-card">
        <h2><?php _e('Emails by Type', 'omnimail'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php _e('Email Type', 'omnimail'); ?></th>
              <th><?php _e('Count', 'omnimail'); ?></th>
              <th><?php _e('Percentage', 'omnimail'); ?></th>
            </tr>
          </thead>
          <tbody id="email-types-tbody">
            <tr>
              <td colspan="3" style="text-align: center;"><?php _e('Loading...', 'omnimail'); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Emails -->
      <div class="omnimail-card">
        <h2><?php _e('Recent Behavioral Emails', 'omnimail'); ?></h2>
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
          <tbody id="recent-emails-tbody">
            <tr>
              <td colspan="9" style="text-align: center;"><?php _e('Loading...', 'omnimail'); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- AI Auto-Replies -->
      <div class="omnimail-card">
        <h2>
          <?php _e('AI Auto-Replies', 'omnimail'); ?>
          <span id="auto-reply-total-badge" class="omnimail-badge" style="margin-left:8px;display:none;"></span>
        </h2>
        <p class="description" style="margin-top:0;">
          <?php _e('Outbound replies sent by OmniMail AI from your linked knowledge-base inboxes.', 'omnimail'); ?>
        </p>
        <div id="auto-replies-loading" style="text-align: center; padding: 16px; display: none;">
          <span class="spinner is-active" style="float: none; margin: 0;"></span>
          <p><?php _e('Loading AI auto-replies...', 'omnimail'); ?></p>
        </div>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php _e('Subject', 'omnimail'); ?></th>
              <th><?php _e('To (Customer)', 'omnimail'); ?></th>
              <th><?php _e('From (Inbox)', 'omnimail'); ?></th>
              <th><?php _e('Sent At', 'omnimail'); ?></th>
              <th><?php _e('Opened', 'omnimail'); ?></th>
              <th><?php _e('Clicked', 'omnimail'); ?></th>
              <th><?php _e('Status', 'omnimail'); ?></th>
            </tr>
          </thead>
          <tbody id="auto-replies-tbody">
            <tr>
              <td colspan="7" style="text-align: center;"><?php _e('Loading...', 'omnimail'); ?></td>
            </tr>
          </tbody>
        </table>
        <div id="auto-replies-pagination" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;"></div>
      </div>

      <!-- View All Emails Button -->
      <p class="submit">
        <a href="<?php echo admin_url('admin.php?page=omnimail-all-emails'); ?>" class="button button-primary button-large">
          <span class="dashicons dashicons-list-view"></span>
          <?php _e('View All Emails', 'omnimail'); ?>
        </a>
        <button type="button" class="button button-secondary button-large" id="refresh-stats-btn">
          <span class="dashicons dashicons-update"></span>
          <?php _e('Refresh', 'omnimail'); ?>
        </button>
      </p>

    </div>
    <?php endif; ?>
  </div>
</div>

<script>
jQuery(document).ready(function($) {
  
  function loadEmailStats() {
    console.log('=== EMAIL STATS DEBUG ===');
    console.log('AJAX URL:', omnimailAjax.ajaxurl);
    console.log('Action:', 'omnimail_get_email_stats');
    console.log('Nonce:', omnimailAjax.nonce);
    
    $.ajax({
      url: omnimailAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'omnimail_get_email_stats',
        nonce: omnimailAjax.nonce
      },
      success: function(response) {
        console.log('=== AJAX SUCCESS ===');
        console.log('Response:', response);
        
        if (response.success && response.data) {
          // Handle nested data structure - could be response.data.data.data or response.data.data
          let statsData = response.data.data?.data || response.data.data || response.data;
          displayStats(statsData);
          $('#stats-loading').hide();
          $('#stats-content').fadeIn();
        } else {
          console.error('Invalid response structure:', response);
          showError(response.data?.message || 'Failed to load statistics');
        }
      },
      error: function(xhr, status, error) {
        console.error('=== AJAX ERROR ===');
        console.error('Status:', status);
        console.error('Error:', error);
        console.error('Response:', xhr.responseText);
        console.error('Status Code:', xhr.status);
        
        var errorMsg = 'Failed to load statistics';
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          errorMsg = xhr.responseJSON.data.message;
        } else if (xhr.responseText) {
          errorMsg = xhr.responseText;
        }
        showError(errorMsg);
      }
    });
  }

  function displayStats(data) {
    // Update overview cards with safe defaults
    $('#stat-total-sent').text((data.totalSent || 0).toLocaleString());
    $('#stat-sent-today').text((data.sentToday || 0).toLocaleString());
    $('#stat-sent-week').text((data.sentThisWeek || 0).toLocaleString());
    $('#stat-sent-month').text((data.sentThisMonth || 0).toLocaleString());
    $('#stat-opened').text((data.openedCount || 0).toLocaleString());
    $('#stat-clicked').text((data.clickedCount || 0).toLocaleString());
    $('#stat-bounced').text((data.bouncedCount || 0).toLocaleString());
    $('#stat-unsubscribed').text((data.unsubscribedCount || 0).toLocaleString());
    $('#stat-open-rate').text((data.openRate || 0).toLocaleString());
    $('#stat-click-rate').text((data.clickRate || 0).toLocaleString());
    $('#stat-bounce-rate').text((data.bounceRate || 0).toLocaleString());
    $('#stat-unsubscribe-rate').text((data.unsubscribeRate || 0).toLocaleString());

    // Display email types
    const emailTypes = data.byType || {};
    const total = data.totalSent || 0;
    let typesHtml = '';

    const typeLabels = {
      cart_abandonment: '<?php _e('Cart Abandonment', 'omnimail'); ?>',
      browse_abandonment: '<?php _e('Browse Abandonment', 'omnimail'); ?>',
      checkout_abandonment: '<?php _e('Checkout Abandonment', 'omnimail'); ?>',
      wishlist_reminder: '<?php _e('Wishlist Reminder', 'omnimail'); ?>',
      post_purchase: '<?php _e('Post-Purchase', 'omnimail'); ?>',
      re_engagement: '<?php _e('Re-engagement', 'omnimail'); ?>',
      back_in_stock: '<?php _e('Back in Stock', 'omnimail'); ?>',
      price_drop: '<?php _e('Price Drop', 'omnimail'); ?>'
    };

    for (const [type, count] of Object.entries(emailTypes)) {
      if (count > 0) {
        const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
        typesHtml += `
          <tr>
            <td>${typeLabels[type] || type}</td>
            <td>${count.toLocaleString()}</td>
            <td>${percentage}%</td>
          </tr>
        `;
      }
    }

    if (typesHtml === '') {
      typesHtml = '<tr><td colspan="3" style="text-align: center;"><?php _e('No emails sent yet', 'omnimail'); ?></td></tr>';
    }

    $('#email-types-tbody').html(typesHtml);

    // Display recent emails
    const recentEmails = data.recentEmails;
    let emailsHtml = '';

    if (recentEmails && recentEmails.length > 0) {
      recentEmails.forEach(function(email) {
        const rawDate = email.sentAt || email.createdAt;
        const sentDate = rawDate ? new Date(rawDate) : null;
        const formattedDate = sentDate && !isNaN(sentDate.getTime())
          ? sentDate.toLocaleString()
          : '—';
        const typeLabel = typeLabels[email.emailType] || email.emailType;
        const status = email.status || 'unknown';
        const openedLabel = email.wasOpened
          ? ('<?php echo esc_js(__('Yes', 'omnimail')); ?>' + (email.openCount > 1 ? ' (' + email.openCount + ')' : ''))
          : '<?php echo esc_js(__('No', 'omnimail')); ?>';
        const clickedLabel = email.wasClicked
          ? ('<?php echo esc_js(__('Yes', 'omnimail')); ?>' + (email.clickCount > 1 ? ' (' + email.clickCount + ')' : ''))
          : '<?php echo esc_js(__('No', 'omnimail')); ?>';
        const bouncedLabel = email.bounced
          ? '<?php echo esc_js(__('Yes', 'omnimail')); ?>'
          : '<?php echo esc_js(__('No', 'omnimail')); ?>';
        const unsubscribedLabel = email.unsubscribed
          ? '<?php echo esc_js(__('Yes', 'omnimail')); ?>'
          : '<?php echo esc_js(__('No', 'omnimail')); ?>';

        emailsHtml += `
          <tr>
            <td>${typeLabel}</td>
            <td>${email.subject || '—'}</td>
            <td>${email.recipientEmail || '—'}</td>
            <td>${formattedDate}</td>
            <td>${openedLabel}</td>
            <td>${clickedLabel}</td>
            <td>${bouncedLabel}</td>
            <td>${unsubscribedLabel}</td>
            <td><span class="omnimail-badge omnimail-badge-${status}">${status}</span></td>
          </tr>
        `;
      });
    } else {
      emailsHtml = '<tr><td colspan="9" style="text-align: center;"><?php _e('No recent emails', 'omnimail'); ?></td></tr>';
    }

    $('#recent-emails-tbody').html(emailsHtml);
  }

  let autoReplyPage = 1;
  const autoReplyLimit = 10;

  function loadAutoReplies(page) {
    page = page || 1;
    autoReplyPage = page;
    $('#auto-replies-loading').show();

    $.ajax({
      url: omnimailAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'omnimail_get_auto_replies',
        nonce: omnimailAjax.nonce,
        page: page,
        limit: autoReplyLimit
      },
      success: function(response) {
        $('#auto-replies-loading').hide();

        if (!response.success || !response.data) {
          $('#auto-replies-tbody').html(
            '<tr><td colspan="7" style="text-align: center;"><?php _e('Failed to load AI auto-replies', 'omnimail'); ?></td></tr>'
          );
          $('#auto-replies-pagination').empty();
          return;
        }

        const data = response.data.data?.data || response.data.data || response.data;
        const autoReplyTotal = data.autoReplyTotal || 0;
        if (autoReplyTotal > 0) {
          $('#auto-reply-total-badge')
            .text(autoReplyTotal.toLocaleString() + ' <?php echo esc_js(__('total', 'omnimail')); ?>')
            .show();
        } else {
          $('#auto-reply-total-badge').hide();
        }

        const autoReplies = data.autoReplies || data.recentAutoReplies || [];
        let autoHtml = '';
        if (autoReplies.length > 0) {
          autoReplies.forEach(function(email) {
            const rawDate = email.sentAt;
            const sentDate = rawDate ? new Date(rawDate) : null;
            const formattedDate = sentDate && !isNaN(sentDate.getTime())
              ? sentDate.toLocaleString()
              : '—';
            const openedLabel = email.wasOpened
              ? '<?php echo esc_js(__('Yes', 'omnimail')); ?>'
              : '<?php echo esc_js(__('No', 'omnimail')); ?>';
            const clickedLabel = email.wasClicked
              ? '<?php echo esc_js(__('Yes', 'omnimail')); ?>'
              : '<?php echo esc_js(__('No', 'omnimail')); ?>';
            const status = email.status || 'sent';
            const subject = $('<div>').text(email.subject || '—').html();
            const toEmail = $('<div>').text(email.recipientEmail || '—').html();
            const fromEmail = $('<div>').text(email.fromEmail || '—').html();

            autoHtml += `
              <tr>
                <td>${subject}</td>
                <td>${toEmail}</td>
                <td>${fromEmail}</td>
                <td>${formattedDate}</td>
                <td>${openedLabel}</td>
                <td>${clickedLabel}</td>
                <td><span class="omnimail-badge omnimail-badge-${status}">${status}</span></td>
              </tr>
            `;
          });
        } else {
          autoHtml = '<tr><td colspan="7" style="text-align: center;"><?php _e('No AI auto-replies sent yet', 'omnimail'); ?></td></tr>';
        }
        $('#auto-replies-tbody').html(autoHtml);
        displayAutoReplyPagination(data.pagination || data.autoReplyPagination || {});
      },
      error: function() {
        $('#auto-replies-loading').hide();
        $('#auto-replies-tbody').html(
          '<tr><td colspan="7" style="text-align: center;"><?php _e('Failed to load AI auto-replies', 'omnimail'); ?></td></tr>'
        );
        $('#auto-replies-pagination').empty();
      }
    });
  }

  function displayAutoReplyPagination(pagination) {
    pagination = pagination || {};
    pagination.page = pagination.page || 1;
    pagination.limit = pagination.limit || autoReplyLimit;
    pagination.total = pagination.total || 0;
    pagination.totalPages = pagination.totalPages || 1;
    pagination.hasPrev = !!pagination.hasPrev;
    pagination.hasNext = !!pagination.hasNext;

    if (pagination.total <= 0) {
      $('#auto-replies-pagination').empty();
      return;
    }

    const from = ((pagination.page - 1) * pagination.limit) + 1;
    const to = Math.min(pagination.page * pagination.limit, pagination.total);
    const html = `
      <div>
        <?php _e('Showing', 'omnimail'); ?> ${from} - ${to}
        <?php _e('of', 'omnimail'); ?> ${pagination.total} <?php _e('auto-replies', 'omnimail'); ?>
      </div>
      <div>
        <button type="button" class="button" id="ar-first-page-btn" ${pagination.page === 1 ? 'disabled' : ''}>
          <?php _e('First', 'omnimail'); ?>
        </button>
        <button type="button" class="button" id="ar-prev-page-btn" ${!pagination.hasPrev ? 'disabled' : ''}>
          <?php _e('Previous', 'omnimail'); ?>
        </button>
        <span style="margin: 0 10px;"><?php _e('Page', 'omnimail'); ?> ${pagination.page} <?php _e('of', 'omnimail'); ?> ${pagination.totalPages}</span>
        <button type="button" class="button" id="ar-next-page-btn" ${!pagination.hasNext ? 'disabled' : ''}>
          <?php _e('Next', 'omnimail'); ?>
        </button>
        <button type="button" class="button" id="ar-last-page-btn" ${pagination.page === pagination.totalPages ? 'disabled' : ''}>
          <?php _e('Last', 'omnimail'); ?>
        </button>
      </div>
    `;

    $('#auto-replies-pagination').html(html);

    $('#ar-first-page-btn').on('click', function() { loadAutoReplies(1); });
    $('#ar-prev-page-btn').on('click', function() {
      if (pagination.hasPrev) loadAutoReplies(pagination.page - 1);
    });
    $('#ar-next-page-btn').on('click', function() {
      if (pagination.hasNext) loadAutoReplies(pagination.page + 1);
    });
    $('#ar-last-page-btn').on('click', function() {
      loadAutoReplies(pagination.totalPages);
    });
  }

  function showError(message) {
    $('#stats-loading').hide();
    $('#stats-error').show();
    $('#stats-error-message').text(message);
  }

  // Refresh button
  $('#refresh-stats-btn').on('click', function() {
    $('#stats-content').hide();
    $('#stats-error').hide();
    $('#stats-loading').show();
    loadEmailStats();
    loadAutoReplies(1);
  });

  // Load stats on page load
  <?php if ($is_configured): ?>
    loadEmailStats();
    loadAutoReplies(1);
  <?php else: ?>
    $('#stats-loading').hide();
  <?php endif; ?>
});
</script>
