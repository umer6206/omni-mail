<?php
/**
 * All Emails Page
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
    <h1><?php _e('All Emails', 'omnimail'); ?></h1>

    <?php if (!$is_configured): ?>
      <div class="omnimail-card omnimail-empty-state">
        <span class="dashicons dashicons-email-alt"></span>
        <h2><?php _e('Connect OmniMail to view emails', 'omnimail'); ?></h2>
        <p><?php _e('Once connected, sent and failed emails from your behavioral flows will show up here.', 'omnimail'); ?></p>
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

    <div class="omnimail-card">
      <!-- Filters -->
      <div style="margin-bottom: 20px;">
        <select id="email-type-filter" style="min-width: 200px;">
          <option value=""><?php _e('All Types', 'omnimail'); ?></option>
          <option value="cart_abandonment"><?php _e('Cart Abandonment', 'omnimail'); ?></option>
          <option value="browse_abandonment"><?php _e('Browse Abandonment', 'omnimail'); ?></option>
          <option value="checkout_abandonment"><?php _e('Checkout Abandonment', 'omnimail'); ?></option>
          <option value="wishlist_reminder"><?php _e('Wishlist Reminder', 'omnimail'); ?></option>
          <option value="post_purchase"><?php _e('Post-Purchase', 'omnimail'); ?></option>
          <option value="re_engagement"><?php _e('Re-engagement', 'omnimail'); ?></option>
          <option value="back_in_stock"><?php _e('Back in Stock', 'omnimail'); ?></option>
          <option value="price_drop"><?php _e('Price Drop', 'omnimail'); ?></option>
        </select>

        <button type="button" class="button" id="apply-filter-btn"><?php _e('Apply Filter', 'omnimail'); ?></button>
        <button type="button" class="button" id="reset-filter-btn"><?php _e('Reset', 'omnimail'); ?></button>
      </div>

      <!-- Loading State -->
      <div id="emails-loading" style="text-align: center; padding: 40px;">
        <span class="spinner is-active" style="float: none; margin: 0;"></span>
        <p><?php _e('Loading emails...', 'omnimail'); ?></p>
      </div>

      <!-- Error State -->
      <div id="emails-error" style="display: none;">
        <div class="notice notice-error">
          <p id="emails-error-message"></p>
        </div>
      </div>

      <!-- Emails Table -->
      <div id="emails-content" style="display: none;">
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
          <tbody id="emails-tbody">
            <!-- Content loaded via JS -->
          </tbody>
        </table>

        <!-- Pagination -->
        <div id="emails-pagination" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
          <!-- Pagination loaded via JS -->
        </div>
      </div>
    </div>

      <!-- AI Auto-Replies (same data as Email Statistics) -->
      <div class="omnimail-card" id="auto-replies-card" style="margin-top: 20px;">
        <h2>
          <?php _e('AI Auto-Replies', 'omnimail'); ?>
          <span id="auto-reply-total-badge" class="omnimail-badge" style="margin-left:8px;display:none;"></span>
        </h2>
        <p class="description" style="margin-top:0;">
          <?php _e('Outbound replies sent by OmniMail AI from your linked knowledge-base inboxes.', 'omnimail'); ?>
        </p>
        <div id="auto-replies-loading" style="text-align: center; padding: 20px;">
          <span class="spinner is-active" style="float: none; margin: 0;"></span>
          <p><?php _e('Loading AI auto-replies...', 'omnimail'); ?></p>
        </div>
        <div id="auto-replies-content" style="display: none;">
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
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
jQuery(document).ready(function($) {
  let currentPage = 1;
  let currentFilter = '';
  const limit = 20;

  function loadEmails(page, emailType) {
    page = page || 1;
    emailType = emailType || '';

    $.ajax({
      url: omnimailAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'omnimail_get_all_emails',
        nonce: omnimailAjax.nonce,
        page: page,
        limit: limit,
        email_type: emailType
      },
      success: function(response) {
        if (response.success && response.data) {
          // Handle nested data structure - could be response.data.data.data or response.data.data
          let emailsData = response.data.data?.data || response.data.data || response.data;
          displayEmails(emailsData.emails || []);
          displayPagination(emailsData.pagination || {page: 1, limit: 20, total: 0, totalPages: 1, hasPrev: false, hasNext: false});
          $('#emails-loading').hide();
          $('#emails-content').fadeIn();
        } else {
          showError(response.data?.message || 'Failed to load emails');
        }
      },
      error: function(xhr) {
        var errorMsg = 'Failed to load emails';
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          errorMsg = xhr.responseJSON.data.message;
        }
        showError(errorMsg);
      }
    });
  }

  function displayEmails(emails) {
    let html = '';

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

    if (emails && emails.length > 0) {
      emails.forEach(function(email) {
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

        html += `
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
      html = '<tr><td colspan="9" style="text-align: center;"><?php _e('No emails found', 'omnimail'); ?></td></tr>';
    }

    $('#emails-tbody').html(html);
  }

  function displayPagination(pagination) {
    // Ensure pagination has required properties with defaults
    pagination = pagination || {};
    pagination.page = pagination.page || 1;
    pagination.limit = pagination.limit || 20;
    pagination.total = pagination.total || 0;
    pagination.totalPages = pagination.totalPages || 1;
    pagination.hasPrev = pagination.hasPrev || false;
    pagination.hasNext = pagination.hasNext || false;
    
    const html = `
      <div>
        <?php _e('Showing', 'omnimail'); ?> ${((pagination.page - 1) * pagination.limit) + 1} - ${Math.min(pagination.page * pagination.limit, pagination.total)} 
        <?php _e('of', 'omnimail'); ?> ${pagination.total} <?php _e('emails', 'omnimail'); ?>
      </div>
      <div>
        <button type="button" class="button" id="first-page-btn" ${pagination.page === 1 ? 'disabled' : ''}>
          <?php _e('First', 'omnimail'); ?>
        </button>
        <button type="button" class="button" id="prev-page-btn" ${!pagination.hasPrev ? 'disabled' : ''}>
          <?php _e('Previous', 'omnimail'); ?>
        </button>
        <span style="margin: 0 10px;"><?php _e('Page', 'omnimail'); ?> ${pagination.page} <?php _e('of', 'omnimail'); ?> ${pagination.totalPages}</span>
        <button type="button" class="button" id="next-page-btn" ${!pagination.hasNext ? 'disabled' : ''}>
          <?php _e('Next', 'omnimail'); ?>
        </button>
        <button type="button" class="button" id="last-page-btn" ${pagination.page === pagination.totalPages ? 'disabled' : ''}>
          <?php _e('Last', 'omnimail'); ?>
        </button>
      </div>
    `;

    $('#emails-pagination').html(html);

    // Bind pagination events
    $('#first-page-btn').on('click', function() {
      currentPage = 1;
      $('#emails-content').hide();
      $('#emails-loading').show();
      loadEmails(currentPage, currentFilter);
    });

    $('#prev-page-btn').on('click', function() {
      if (pagination.hasPrev) {
        currentPage--;
        $('#emails-content').hide();
        $('#emails-loading').show();
        loadEmails(currentPage, currentFilter);
      }
    });

    $('#next-page-btn').on('click', function() {
      if (pagination.hasNext) {
        currentPage++;
        $('#emails-content').hide();
        $('#emails-loading').show();
        loadEmails(currentPage, currentFilter);
      }
    });

    $('#last-page-btn').on('click', function() {
      currentPage = pagination.totalPages;
      $('#emails-content').hide();
      $('#emails-loading').show();
      loadEmails(currentPage, currentFilter);
    });
  }

  function showError(message) {
    $('#emails-loading').hide();
    $('#emails-error').show();
    $('#emails-error-message').text(message);
  }

  let autoReplyPage = 1;
  const autoReplyLimit = 10;

  function loadAutoReplies(page) {
    page = page || 1;
    autoReplyPage = page;
    $('#auto-replies-loading').show();
    $('#auto-replies-content').hide();

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
        $('#auto-replies-content').show();

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
        $('#auto-replies-content').show();
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

  // Filter events
  $('#apply-filter-btn').on('click', function() {
    currentFilter = $('#email-type-filter').val();
    currentPage = 1;
    $('#emails-content').hide();
    $('#emails-error').hide();
    $('#emails-loading').show();
    loadEmails(currentPage, currentFilter);
  });

  $('#reset-filter-btn').on('click', function() {
    $('#email-type-filter').val('');
    currentFilter = '';
    currentPage = 1;
    $('#emails-content').hide();
    $('#emails-error').hide();
    $('#emails-loading').show();
    loadEmails(currentPage, currentFilter);
  });

  // Load emails on page load
  <?php if ($is_configured): ?>
    loadEmails(currentPage, currentFilter);
    loadAutoReplies(1);
  <?php else: ?>
    $('#emails-loading').hide();
  <?php endif; ?>
});
</script>
