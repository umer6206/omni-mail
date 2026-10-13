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
$omnimail_logs_table_name = esc_sql($wpdb->prefix . 'omnimail_logs');

if (isset($_GET['paged']) || isset($_GET['log_type'])) {
    check_admin_referer('omnimail_logs_filter');
}

$omnimail_logs_current_page = isset($_GET['paged'])
    ? max(1, absint(wp_unslash($_GET['paged'])))
    : 1;
$omnimail_logs_filter_type = isset($_GET['log_type'])
    ? sanitize_text_field(wp_unslash($_GET['log_type']))
    : '';
$omnimail_logs_per_page = 20;
$omnimail_logs_offset = ($omnimail_logs_current_page - 1) * $omnimail_logs_per_page;
$omnimail_logs_cache_group = 'omnimail_logs';
$omnimail_logs_cache_key = md5(serialize(array(
    $omnimail_logs_filter_type,
    $omnimail_logs_current_page,
    $omnimail_logs_per_page,
)));
$omnimail_logs = wp_cache_get($omnimail_logs_cache_key, $omnimail_logs_cache_group);

if (false === $omnimail_logs) {
    if ($omnimail_logs_filter_type !== '') {
        $omnimail_logs_query = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is built from the trusted WordPress prefix and escaped above.
            "SELECT * FROM {$omnimail_logs_table_name} WHERE log_type = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $omnimail_logs_filter_type,
            $omnimail_logs_per_page,
            $omnimail_logs_offset
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- This plugin-owned table has no query API; the result is cached immediately below and the query is prepared above.
        $omnimail_logs = $wpdb->get_results($omnimail_logs_query);
    } else {
        $omnimail_logs_query = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is built from the trusted WordPress prefix and escaped above.
            "SELECT * FROM {$omnimail_logs_table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $omnimail_logs_per_page,
            $omnimail_logs_offset
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- This plugin-owned table has no query API; the result is cached immediately below and the query is prepared above.
        $omnimail_logs = $wpdb->get_results($omnimail_logs_query);
    }
    wp_cache_set($omnimail_logs_cache_key, $omnimail_logs, $omnimail_logs_cache_group, MINUTE_IN_SECONDS);
}

$omnimail_logs_count_cache_key = 'count_' . md5($omnimail_logs_filter_type);
$omnimail_logs_total = wp_cache_get($omnimail_logs_count_cache_key, $omnimail_logs_cache_group);
if (false === $omnimail_logs_total) {
    if ($omnimail_logs_filter_type !== '') {
        $omnimail_logs_count_query = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is built from the trusted WordPress prefix and escaped above.
            "SELECT COUNT(*) FROM {$omnimail_logs_table_name} WHERE log_type = %s",
            $omnimail_logs_filter_type
        );
    } else {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is built from the trusted WordPress prefix and escaped above.
        $omnimail_logs_count_query = "SELECT COUNT(*) FROM {$omnimail_logs_table_name}";
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- This plugin-owned table has no query API; the result is cached immediately below and the query is prepared above where applicable.
    $omnimail_logs_total = (int) $wpdb->get_var($omnimail_logs_count_query);
    wp_cache_set($omnimail_logs_count_cache_key, $omnimail_logs_total, $omnimail_logs_cache_group, MINUTE_IN_SECONDS);
}

$omnimail_logs_total_pages = (int) ceil($omnimail_logs_total / $omnimail_logs_per_page);
$omnimail_logs_types = wp_cache_get('types', $omnimail_logs_cache_group);
if (false === $omnimail_logs_types) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The table name is built from the trusted WordPress prefix; this plugin-owned table has no WordPress query API and the result is cached immediately below.
    $omnimail_logs_types = $wpdb->get_col("SELECT DISTINCT log_type FROM {$omnimail_logs_table_name}");
    wp_cache_set('types', $omnimail_logs_types, $omnimail_logs_cache_group, MINUTE_IN_SECONDS);
}
?>

<div class="wrap omnimail-logs">
    <div class="omnimail-page-wrapper">
        <h1><?php esc_html_e('OmniMail Logs', 'omnimail'); ?></h1>

        <!-- Filters -->
        <div class="omnimail-card omnimail-filters">
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="page" value="omnimail-logs">
                <?php wp_nonce_field('omnimail_logs_filter'); ?>

                <div class="omnimail-filter-row">
                    <div class="omnimail-filter-group">
                        <label for="log_type"><?php esc_html_e('Type:', 'omnimail'); ?></label>
                        <select name="log_type" id="log_type">
                            <option value=""><?php esc_html_e('All Types', 'omnimail'); ?></option>
                            <?php foreach ($omnimail_logs_types as $omnimail_logs_type): ?>
                                <option value="<?php echo esc_attr($omnimail_logs_type); ?>" <?php selected($omnimail_logs_filter_type, $omnimail_logs_type); ?>>
                                    <?php echo esc_html(ucfirst($omnimail_logs_type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="omnimail-filter-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-filter"></span>
                            <?php esc_html_e('Filter', 'omnimail'); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=omnimail-logs')); ?>" class="button">
                            <?php esc_html_e('Reset', 'omnimail'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="omnimail-card">
            <div class="omnimail-logs-header">
                <h2><?php esc_html_e('Activity Logs', 'omnimail'); ?></h2>
                <span class="omnimail-logs-count">
                    <?php
                    /* translators: %s: total number of logs. */
                    echo esc_html(sprintf(__('Total: %s', 'omnimail'), number_format($omnimail_logs_total)));
                    ?>
                </span>
            </div>

            <?php if (!empty($omnimail_logs)): ?>
                <table class="widefat omnimail-logs-table">
                    <thead>
                        <tr>
                            <th class="omnimail-col-id"><?php esc_html_e('ID', 'omnimail'); ?></th>
                            <th class="omnimail-col-date"><?php esc_html_e('Date & Time', 'omnimail'); ?></th>
                            <th class="omnimail-col-type"><?php esc_html_e('Type', 'omnimail'); ?></th>
                            <th class="omnimail-col-message"><?php esc_html_e('Message', 'omnimail'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($omnimail_logs as $omnimail_logs_log): ?>
                            <tr>
                                <td><?php echo esc_html($omnimail_logs_log->id); ?></td>
                                <td>
                                    <?php echo esc_html(date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'),
                                        strtotime($omnimail_logs_log->created_at)
                                    )); ?>
                                </td>
                                <td>
                                    <span class="omnimail-log-type omnimail-log-type-<?php echo esc_attr($omnimail_logs_log->log_type); ?>">
                                        <?php echo esc_html(ucfirst($omnimail_logs_log->log_type)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($omnimail_logs_log->message); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($omnimail_logs_total_pages > 1): ?>
                    <div class="omnimail-pagination">
                        <?php
                        $omnimail_logs_base_url = add_query_arg(array(
                            'page' => 'omnimail-logs',
                            'log_type' => $omnimail_logs_filter_type,
                            '_wpnonce' => wp_create_nonce('omnimail_logs_filter'),
                        ), admin_url('admin.php'));

                        echo wp_kses_post(paginate_links(array(
                            'base' => add_query_arg('paged', '%#%', $omnimail_logs_base_url),
                            'format' => '',
                            'prev_text' => esc_html__('&laquo; Previous', 'omnimail'),
                            'next_text' => esc_html__('Next &raquo;', 'omnimail'),
                            'total' => $omnimail_logs_total_pages,
                            'current' => $omnimail_logs_current_page,
                            'type' => 'list'
                        )));
                        ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="omnimail-no-logs">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php esc_html_e('No logs found.', 'omnimail'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
