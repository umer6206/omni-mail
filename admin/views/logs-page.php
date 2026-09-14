<?php
/**
 * Logs page template
 *
 * @package    OmniMail
 * @subpackage OmniMail/admin/views
 */

if (!defined('WPINC')) {
    die;
}

global $wpdb;
$table_name = $wpdb->prefix . 'omnimail_logs';

// Get filter parameters
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$filter_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';

// Build query
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

$where = '';
if ($filter_type) {
    $where = $wpdb->prepare(" WHERE log_type = %s", $filter_type);
}

$logs = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
$total_pages = ceil($total_logs / $per_page);
$log_types = $wpdb->get_col("SELECT DISTINCT log_type FROM $table_name");
?>

<div class="wrap omnimail-logs">
    <div class="omnimail-page-wrapper">
        <h1><?php _e('OmniMail Logs', 'omnimail'); ?></h1>

        <!-- Filters -->
        <div class="omnimail-card omnimail-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="omnimail-logs">

                <div class="omnimail-filter-row">
                    <div class="omnimail-filter-group">
                        <label for="log_type"><?php _e('Type:', 'omnimail'); ?></label>
                        <select name="log_type" id="log_type">
                            <option value=""><?php _e('All Types', 'omnimail'); ?></option>
                            <?php foreach ($log_types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($filter_type, $type); ?>>
                                    <?php echo esc_html(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="omnimail-filter-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-filter"></span>
                            <?php _e('Filter', 'omnimail'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=omnimail-logs'); ?>" class="button">
                            <?php _e('Reset', 'omnimail'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="omnimail-card">
            <div class="omnimail-logs-header">
                <h2><?php _e('Activity Logs', 'omnimail'); ?></h2>
                <span class="omnimail-logs-count">
                    <?php printf(__('Total: %s', 'omnimail'), number_format($total_logs)); ?>
                </span>
            </div>

            <?php if (!empty($logs)): ?>
                <table class="widefat omnimail-logs-table">
                    <thead>
                        <tr>
                            <th class="omnimail-col-id"><?php _e('ID', 'omnimail'); ?></th>
                            <th class="omnimail-col-date"><?php _e('Date & Time', 'omnimail'); ?></th>
                            <th class="omnimail-col-type"><?php _e('Type', 'omnimail'); ?></th>
                            <th class="omnimail-col-message"><?php _e('Message', 'omnimail'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td>
                                    <?php echo esc_html(date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'),
                                        strtotime($log->created_at)
                                    )); ?>
                                </td>
                                <td>
                                    <span class="omnimail-log-type omnimail-log-type-<?php echo esc_attr($log->log_type); ?>">
                                        <?php echo esc_html(ucfirst($log->log_type)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log->message); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="omnimail-pagination">
                        <?php
                        $base_url = add_query_arg(array(
                            'page' => 'omnimail-logs',
                            'log_type' => $filter_type
                        ), admin_url('admin.php'));

                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%', $base_url),
                            'format' => '',
                            'prev_text' => __('&laquo; Previous', 'omnimail'),
                            'next_text' => __('Next &raquo;', 'omnimail'),
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'list'
                        ));
                        ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="omnimail-no-logs">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php _e('No logs found.', 'omnimail'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
