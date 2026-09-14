/**
 * OmniMail Admin JavaScript
 * @package OmniMail
 * @version 1.0.0
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // API Key Visibility Toggle
        $('#toggle-api-key').on('click', function () {
            var input = $('#omnimail_api_key');
            var icon = $(this).find('.dashicons');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                input.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });

        // Test Connection
        $('#test-connection-btn').on('click', function (e) {
            e.preventDefault();

            var button = $(this);
            var originalHtml = button.html();
            button.prop('disabled', true);
            button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Testing...');

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'omnimail_test_connection',
                    nonce: omnimailAjax.nonce
                },
                success: function (response) {
                    button.prop('disabled', false);
                    button.html(originalHtml);

                    if (response.success) {
                        showNotification('Connection successful! Test event sent to backend.', 'success');
                    } else {
                        showNotification(response.data.message || 'Connection failed', 'error');
                    }
                },
                error: function () {
                    button.prop('disabled', false);
                    button.html(originalHtml);
                    showNotification('An error occurred while testing connection', 'error');
                }
            });
        });

        var $wcMessage = $('#omnimail-wc-message');
        var $generateKeysBtn = $('#omnimail-generate-wc-keys');
        var $saveWcBtn = $('#omnimail-save-wc-credentials');
        var $consumerSecret = $('#omnimail_wc_consumer_secret');
        var $syncRequired = $('#omnimail-sync-required');

        function showWcMessage(message, ok) {
            $wcMessage
                .css('color', ok ? '#16a34a' : '#ff3b5c')
                .text(message || '');
        }

        $consumerSecret.on('focus', function () {
            if ($(this).val() === $(this).attr('data-saved-mask')) {
                this.select();
            }
        });

        $generateKeysBtn.on('click', function () {
            var $btn = $(this);
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> ' +
                ((omnimailAjax && omnimailAjax.generatingKeysText) || 'Generating keys...')
            );

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'omnimail_generate_wc_keys',
                    nonce: omnimailAjax.nonce
                }
            }).done(function (response) {
                if (!response.success) {
                    showWcMessage((response.data && response.data.message) || 'Failed to generate keys.', false);
                    return;
                }
                $('#omnimail_wc_consumer_key').val(response.data.consumerKey || '');
                $consumerSecret.val(response.data.consumerSecret || '').attr('data-saved-mask', '');
                showWcMessage(response.data.message, true);
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
                showWcMessage(message || 'Failed to generate keys.', false);
            }).always(function () {
                $btn.prop('disabled', false).html(original);
            });
        });

        $saveWcBtn.on('click', function () {
            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }
            var original = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> ' +
                ((omnimailAjax && omnimailAjax.savingWooCredentialsText) || 'Saving & Connecting...')
            );

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'omnimail_save_woocommerce_credentials',
                    nonce: omnimailAjax.nonce,
                    consumerKey: $('#omnimail_wc_consumer_key').val(),
                    consumerSecret: $consumerSecret.val()
                }
            }).done(function (response) {
                if (!response.success) {
                    showWcMessage((response.data && response.data.message) || 'Failed to save credentials.', false);
                    return;
                }

                if (omnimailAjax) {
                    omnimailAjax.hasWooCredentials = '1';
                }
                if (response.data.consumerKey) {
                    $('#omnimail_wc_consumer_key').val(response.data.consumerKey);
                }
                if (response.data.maskedSecret) {
                    $consumerSecret.val(response.data.maskedSecret).attr('data-saved-mask', response.data.maskedSecret);
                }
                $('#sync-products-btn').prop('disabled', false).attr('aria-disabled', 'false');
                $syncRequired.prop('hidden', true);
                showWcMessage(response.data.message, true);
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
                showWcMessage(message || 'Failed to save credentials.', false);
            }).always(function () {
                $btn.prop('disabled', false).html(original);
            });
        });

        // Sync Products (batched, same flow as OmniVoice)
        $('#sync-products-btn').on('click', function () {
            var btn = $(this);
            if (omnimailAjax && omnimailAjax.hasWooCredentials !== '1') {
                if ($syncRequired.length) {
                    $syncRequired.prop('hidden', false);
                }
                showWcMessage('Save WooCommerce REST API credentials before synchronizing products.', false);
                return;
            }
            var progressWrap = $('#product-sync-progress');
            var progressBar = $('#sync-progress-bar');
            var progressText = $('#sync-progress-text');

            btn.prop('disabled', true);
            btn.html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Syncing…');
            progressWrap.slideDown(200);
            progressBar.css({ width: '0%', background: 'linear-gradient(90deg, #22d3ee, #00b8d4)' });
            progressText.text('Preparing…');
            progressWrap.find('.dashicons').first()
                .removeClass('dashicons-yes')
                .addClass('dashicons-update')
                .css({ animation: 'rotation 1s infinite linear', color: '#22d3ee' });

            function runBatch(batch) {
                $.ajax({
                    url: omnimailAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'omnimail_sync_products',
                        nonce: omnimailAjax.nonce,
                        batch: batch
                    },
                    success: function (response) {
                        if (!response.success) {
                            onSyncError(response.data ? response.data.message : 'Unknown error');
                            return;
                        }

                        var data = response.data;
                        var total = data.total || 1;
                        var synced = data.synced || 0;
                        var percent = Math.min(100, Math.round((synced / total) * 100));

                        progressBar.css('width', percent + '%');
                        progressText.text(data.message || (synced + ' of ' + total + ' products synced…'));

                        if (data.done) {
                            onSyncDone(synced, total);
                        } else {
                            setTimeout(function () {
                                runBatch(data.nextBatch);
                            }, 1500);
                        }
                    },
                    error: function (xhr) {
                        var msg = 'Request failed';
                        try {
                            var parsed = JSON.parse(xhr.responseText);
                            msg = parsed.data ? parsed.data.message : msg;
                        } catch (e) { }
                        onSyncError(msg);
                    }
                });
            }

            function onSyncDone(synced) {
                progressBar.css('width', '100%');
                progressText.html(
                    '<span style="color: #22d3ee;">✓ ' +
                    synced + ' products synced successfully.' +
                    '</span>'
                );
                btn.prop('disabled', false);
                btn.html('<span class="dashicons dashicons-yes"></span> Sync Products');

                progressWrap.find('.dashicons-update').css('animation', 'none')
                    .removeClass('dashicons-update').addClass('dashicons-yes')
                    .css('color', '#22d3ee');

                setTimeout(function () {
                    progressWrap.slideUp(300);
                }, 4000);
            }

            function onSyncError(message) {
                progressText.html('<span style="color: #ff3b5c;">✗ ' + message + '</span>');
                progressBar.css({ width: '100%', background: '#ff3b5c' });
                progressWrap.find('.dashicons-update').css('animation', 'none');
                btn.prop('disabled', false);
                btn.html('<span class="dashicons dashicons-update"></span> Sync Products');
            }

            runBatch(0);
        });

        function updateStripeConnectUi(payload) {
            var $btn = $('#stripe-connect-btn');
            var $label = $('#stripe-connect-label');
            var $icon = $('#stripe-connect-icon');
            var $status = $('#stripe-connect-status');
            var i18n = (omnimailAjax && omnimailAjax.stripeI18n) || {};

            if (!$btn.length) {
                return;
            }

            var connected = payload && payload.connected;
            var pending = payload && payload.pending;

            if (connected) {
                $label.text(i18n.connectedLabel || 'Stripe Connected — Manage');
                $icon.removeClass('dashicons-money-alt dashicons-warning').addClass('dashicons-yes-alt');
                $btn.removeClass('button-secondary').addClass('button-primary');
                $status.html(
                    '<span style="color:#16a34a;">✓ ' +
                    (i18n.connectedStatus || 'Stripe is connected and ready for payment links.') +
                    '</span>'
                );
            } else if (pending) {
                $label.text(i18n.pendingLabel || 'Complete Stripe Setup');
                $icon.removeClass('dashicons-yes-alt dashicons-money-alt').addClass('dashicons-warning');
                $btn.removeClass('button-primary').addClass('button-secondary');
                $status.html(
                    '<span style="color:#d97706;">' +
                    (i18n.pendingStatus || 'Stripe account found — finish onboarding to accept payments.') +
                    '</span>'
                );
            } else {
                $label.text(i18n.connectLabel || 'Connect Stripe Account');
                $icon.removeClass('dashicons-yes-alt dashicons-warning').addClass('dashicons-money-alt');
                $btn.removeClass('button-primary').addClass('button-secondary');
                $status.text(
                    i18n.connectStatus ||
                    'Connect Stripe to send product payment links from AI auto-replies.'
                );
            }
        }

        function loadStripeConnectStatus() {
            var configured = omnimailAjax && (
                omnimailAjax.isConfigured === true ||
                omnimailAjax.isConfigured === 1 ||
                omnimailAjax.isConfigured === '1'
            );

            if (!configured || !$('#stripe-connect-btn').length) {
                return;
            }

            var failText = (omnimailAjax.stripeI18n && omnimailAjax.stripeI18n.checkFailed) ||
                'Could not check Stripe status. Open Stripe Connect to verify.';

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                timeout: 20000,
                data: {
                    action: 'omnimail_get_stripe_connect_status',
                    nonce: omnimailAjax.nonce
                },
                success: function (response) {
                    if (response && response.success && response.data) {
                        updateStripeConnectUi(response.data);
                    } else {
                        $('#stripe-connect-status').text(failText);
                    }
                },
                error: function () {
                    $('#stripe-connect-status').text(failText);
                }
            });
        }

        if ($('#stripe-connect-btn').length) {
            loadStripeConnectStatus();
        }

        // Test Email
        $('#test-email-btn').on('click', function (e) {
            e.preventDefault();

            var testEmail = prompt('Enter email address to send test email:');
            if (!testEmail) return;

            var button = $(this);
            var originalHtml = button.html();
            button.prop('disabled', true);
            button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Sending...');

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'omnimail_test_email',
                    nonce: omnimailAjax.nonce,
                    test_email: testEmail
                },
                success: function (response) {
                    button.prop('disabled', false);
                    button.html(originalHtml);

                    if (response.success) {
                        showNotification('Test email sent successfully!', 'success');
                    } else {
                        showNotification(response.data.message || 'Failed to send test email', 'error');
                    }
                },
                error: function () {
                    button.prop('disabled', false);
                    button.html(originalHtml);
                    showNotification('An error occurred while sending test email', 'error');
                }
            });
        });

        // Show Notification
        function showNotification(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            $('.omnimail-page-wrapper').prepend(notice);

            setTimeout(function () {
                notice.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        }

        // Auto-dismiss Notices
        setTimeout(function () {
            $('.notice.is-dismissible').fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);

        // ========================================
        // DASHBOARD EMAIL STATS
        // ========================================
        if ($('#omnimail-dashboard-stats').length) {
            loadDashboardEmailStats();
        }

        function loadDashboardEmailStats() {
            var typeLabels = {
                cart_abandonment: 'Cart Abandonment',
                browse_abandonment: 'Browse Abandonment',
                checkout_abandonment: 'Checkout Abandonment',
                wishlist_reminder: 'Wishlist Reminder',
                post_purchase: 'Post-Purchase',
                re_engagement: 'Re-engagement',
                back_in_stock: 'Back in Stock',
                price_drop: 'Price Drop'
            };

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'omnimail_get_email_stats',
                    nonce: omnimailAjax.nonce
                },
                success: function (response) {
                    if (!(response.success && response.data)) {
                        showDashStatsError((response.data && response.data.message) || 'Failed to load statistics');
                        return;
                    }

                    var data = (response.data.data && response.data.data.data)
                        || response.data.data
                        || response.data;

                    $('#dash-stat-total-sent').text((data.totalSent || 0).toLocaleString());
                    $('#dash-stat-sent-today').text((data.sentToday || 0).toLocaleString());
                    $('#dash-stat-sent-week').text((data.sentThisWeek || 0).toLocaleString());
                    $('#dash-stat-sent-month').text((data.sentThisMonth || 0).toLocaleString());
                    $('#dash-stat-opened').text((data.openedCount || 0).toLocaleString());
                    $('#dash-stat-clicked').text((data.clickedCount || 0).toLocaleString());
                    $('#dash-stat-open-rate').text((data.openRate || 0).toLocaleString());
                    $('#dash-stat-click-rate').text((data.clickRate || 0).toLocaleString());
                    $('#dash-stat-bounced').text((data.bouncedCount || 0).toLocaleString());
                    $('#dash-stat-unsubscribed').text((data.unsubscribedCount || 0).toLocaleString());

                    var recentEmails = data.recentEmails || [];
                    var emailsHtml = '';
                    var maxRows = Math.min(recentEmails.length, 8);

                    if (maxRows > 0) {
                        for (var i = 0; i < maxRows; i++) {
                            var email = recentEmails[i];
                            var rawDate = email.sentAt || email.createdAt;
                            var sentDate = rawDate ? new Date(rawDate) : null;
                            var formattedDate = sentDate && !isNaN(sentDate.getTime())
                                ? sentDate.toLocaleString()
                                : '—';
                            var typeLabel = typeLabels[email.emailType] || email.emailType || '—';
                            var status = email.status || 'unknown';
                            var subject = $('<div/>').text(email.subject || '—').html();
                            var recipient = $('<div/>').text(email.recipientEmail || '—').html();
                            var openedLabel = email.wasOpened
                                ? ('Yes' + (email.openCount > 1 ? ' (' + email.openCount + ')' : ''))
                                : 'No';
                            var clickedLabel = email.wasClicked
                                ? ('Yes' + (email.clickCount > 1 ? ' (' + email.clickCount + ')' : ''))
                                : 'No';
                            var bouncedLabel = email.bounced ? 'Yes' : 'No';
                            var unsubscribedLabel = email.unsubscribed ? 'Yes' : 'No';

                            emailsHtml += '<tr>' +
                                '<td>' + typeLabel + '</td>' +
                                '<td>' + subject + '</td>' +
                                '<td>' + recipient + '</td>' +
                                '<td>' + formattedDate + '</td>' +
                                '<td>' + openedLabel + '</td>' +
                                '<td>' + clickedLabel + '</td>' +
                                '<td>' + bouncedLabel + '</td>' +
                                '<td>' + unsubscribedLabel + '</td>' +
                                '<td><span class="omnimail-badge omnimail-badge-' + status + '">' + status + '</span></td>' +
                                '</tr>';
                        }
                    } else {
                        emailsHtml = '<tr><td colspan="9" style="text-align:center;">No emails sent yet</td></tr>';
                    }

                    $('#dash-recent-emails-tbody').html(emailsHtml);
                    $('#omnimail-dash-stats-loading').hide();
                    $('#omnimail-dash-stats-content').fadeIn();
                },
                error: function (xhr) {
                    var errorMsg = 'Failed to load statistics';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    showDashStatsError(errorMsg);
                }
            });
        }

        function showDashStatsError(message) {
            $('#omnimail-dash-stats-loading').hide();
            $('#omnimail-dash-stats-error').show();
            $('#omnimail-dash-stats-error-message').text(message);
        }

        // ========================================
        // BEHAVIORAL FLOWS PAGE
        // ========================================

        // omnimailFlowsIsPro now drives BOTH the Email panel and the SMS panel.
        // It comes exclusively from the Email Behavioral Flows response
        // (omnimail_get_behavioral_flows -> data.isPro). There is no separate
        // OmniVoice/SMS subscription check anymore — one OmniMail PRO plan
        // unlocks both Email and SMS behavioral flows.
        var omnimailFlowsIsPro = true;
        var omnimailProUpgradeUrl = ($('#behavioral-flows-content').data('pro-url')) ||
            'https://omnimail-app.omninexttech.com/dashboard/mail-blaze/subscription';
        var flowsIgnoreToggleChange = false;
        var omnimailSmsOriginalSettings = {};

        // Load behavioral flows on page load
        if ($('#behavioral-flows-content').length) {
            loadBehavioralFlows();
            // loadOmniVoiceSmsFlows is called inside loadBehavioralFlows once content is visible
        }

        function setOmniVoiceSmsControlsEnabled(enabled) {
            var $checkboxes = $('.omnimail-sms-checkbox');
            var $buttons = $('#omnimail-sms-enable-all, #omnimail-sms-disable-all, #omnimail-sms-save');
            $checkboxes.prop('disabled', !enabled);
            $buttons.prop('disabled', !enabled);
        }

        function readOmniVoiceSmsSettings() {
            var settings = {};
            $('.omnimail-sms-checkbox').each(function () {
                settings[$(this).data('flow-key')] = $(this).is(':checked');
            });
            return settings;
        }

        function applyOmniVoiceSmsSettings(settings) {
            $('.omnimail-sms-checkbox').each(function () {
                var key = $(this).data('flow-key');
                $(this).prop('checked', settings[key] !== false);
            });
        }

        function setOmniVoiceSmsMessage(message, type) {
            var $message = $('#omnimail-sms-message');
            $message
                .removeClass('is-success is-error')
                .addClass(type ? 'is-' + type : '')
                .text(message || '');
        }

        /**
         * Gate the SMS panel using the same isPro flag as the Email panel.
         * No separate OmniVoice subscription check is performed anymore.
         */
        function applySmsProGate(isPro) {
            $('#omnimail-sms-loading').hide();
            $('#omnimail-sms-grid').attr('aria-busy', 'false');
            $('#omnimail-sms-actions').prop('hidden', false);

            if (isPro) {
                $('#omnimail-sms-upgrade').hide();
                setOmniVoiceSmsControlsEnabled(true);
                setOmniVoiceSmsMessage('', '');
            } else {
                $('.omnimail-sms-checkbox').prop('checked', false);
                setOmniVoiceSmsControlsEnabled(false);
                $('#omnimail-sms-upgrade').show();
                setOmniVoiceSmsMessage('', '');
            }
        }

        function loadOmniVoiceSmsFlows() {
            if (!$('#omnimail-sms-grid').length) {
                return;
            }

            // Show spinner, keep grid visible but busy, reveal actions bar
            $('#omnimail-sms-loading').show();
            $('#omnimail-sms-grid').attr('aria-busy', 'true');
            $('#omnimail-sms-actions').prop('hidden', false);
            // Disable controls while loading
            setOmniVoiceSmsControlsEnabled(false);

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                timeout: 10000,
                data: {
                    action: 'omnimail_get_sms_flows',
                    nonce: omnimailAjax.nonce || ''
                },
                success: function (response) {
                    if (response.success) {
                        applyOmniVoiceSmsSettings(response.data || {});
                        omnimailSmsOriginalSettings = readOmniVoiceSmsSettings();
                    }

                    // Gating always follows the OmniMail PRO plan (isPro from
                    // the Email Flows response), regardless of what this
                    // endpoint itself returned.
                    applySmsProGate(omnimailFlowsIsPro);
                },
                error: function () {
                    applySmsProGate(omnimailFlowsIsPro);
                    if (omnimailFlowsIsPro) {
                        setOmniVoiceSmsMessage('Unable to load SMS flow settings.', 'error');
                    }
                }
            });
        }

        function saveSmsFlows(onDone) {
            var settings = readOmniVoiceSmsSettings();
            var requestData = {};

            $.each(settings, function (key, value) {
                requestData[key] = value ? 'true' : 'false';
            });

            setOmniVoiceSmsControlsEnabled(false);
            setOmniVoiceSmsMessage('Saving SMS flow settings...', '');

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: $.extend({
                    action: 'omnimail_update_sms_flows',
                    nonce: omnimailAjax.nonce || ''
                }, requestData),
                success: function (response) {
                    if (!response.success) {
                        applyOmniVoiceSmsSettings(omnimailSmsOriginalSettings);
                        setOmniVoiceSmsMessage(
                            (response.data && response.data.message) || 'Unable to update SMS flows.',
                            'error'
                        );
                        if (typeof onDone === 'function') onDone(false);
                        return;
                    }

                    omnimailSmsOriginalSettings = readOmniVoiceSmsSettings();
                    setOmniVoiceSmsMessage('SMS flow settings saved.', 'success');
                    if (typeof onDone === 'function') onDone(true);
                },
                error: function (xhr) {
                    applyOmniVoiceSmsSettings(omnimailSmsOriginalSettings);
                    var message = 'Unable to update SMS flows.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    setOmniVoiceSmsMessage(message, 'error');
                    if (typeof onDone === 'function') onDone(false);
                },
                complete: function () {
                    setOmniVoiceSmsControlsEnabled(true);
                }
            });
        }

        // Enable All / Disable All for SMS now save immediately — no separate
        // click on "Save Flow Settings" is required.
        $('#omnimail-sms-enable-all').on('click', function () {
            $('.omnimail-sms-checkbox').prop('checked', true);
            saveSmsFlows();
        });

        $('#omnimail-sms-disable-all').on('click', function () {
            $('.omnimail-sms-checkbox').prop('checked', false);
            saveSmsFlows();
        });

        $('#omnimail-sms-save').on('click', function () {
            saveSmsFlows();
        });

        function applyFlowProGate(isPro) {
            omnimailFlowsIsPro = !!isPro;
            var $toggles = $('#behavioral-flows-content input[type="checkbox"][data-flow]');
            var $unlock = $('#behavioral-flows-content .omnimail-pro-unlock');
            var $banner = $('#omnimail-pro-locked-banner');
            var $enableAll = $('#enable-all-flows');
            var $disableAll = $('#disable-all-flows');
            var $saveBtn = $('#save-flows-btn');

            if (omnimailFlowsIsPro) {
                $toggles.prop('disabled', false);
                $unlock.hide();
                $banner.hide();
                $enableAll.prop('disabled', false);
                $disableAll.prop('disabled', false);
                $saveBtn.prop('disabled', false);
                $('#behavioral-flows-content').removeClass('is-pro-locked');
            } else {
                $toggles.prop('checked', false).prop('disabled', true);
                $unlock.show();
                $banner.show();
                $enableAll.prop('disabled', true);
                $disableAll.prop('disabled', true);
                $saveBtn.prop('disabled', true);
                $('#behavioral-flows-content').addClass('is-pro-locked');
            }
        }

        // Load behavioral flows from API
        function loadBehavioralFlows() {
            console.log('Loading behavioral flows...');

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                timeout: 10000, // 10 second timeout
                data: {
                    action: 'omnimail_get_behavioral_flows',
                    nonce: omnimailAjax.nonce
                },
                success: function (response) {
                    console.log('Behavioral flows response:', response);
                    $('#behavioral-flows-loading').hide();

                    if (response.success && response.data && response.data.data) {
                        var payload = response.data.data;
                        var flows = payload.flows || {};
                        var isPro = false;

                        // Unwrap one more nest if present (WP/API double wrap)
                        if (payload.data && payload.data.flows) {
                            if (typeof payload.data.isPro === 'boolean') {
                                isPro = payload.data.isPro;
                            }
                            if (payload.data.upgradeUrl) {
                                omnimailProUpgradeUrl = payload.data.upgradeUrl;
                            }
                            flows = payload.data.flows;
                        } else {
                            // Fail closed: only unlock when API explicitly says isPro === true
                            isPro = payload.isPro === true;
                            if (payload.upgradeUrl) {
                                omnimailProUpgradeUrl = payload.upgradeUrl;
                            }
                        }

                        // Set checkbox states from API (then PRO gate may force them off)
                        flowsIgnoreToggleChange = true;
                        $('#flow_cart_abandonment').prop('checked', !!(flows.cartAbandonment && flows.cartAbandonment.enabled));
                        $('#flow_browse_abandonment').prop('checked', !!(flows.browseAbandonment && flows.browseAbandonment.enabled));
                        $('#flow_checkout_abandonment').prop('checked', !!(flows.checkoutAbandonment && flows.checkoutAbandonment.enabled));
                        $('#flow_wishlist_reminder').prop('checked', !!(flows.wishlistReminder && flows.wishlistReminder.enabled));
                        $('#flow_post_purchase').prop('checked', !!(flows.postPurchase && flows.postPurchase.enabled));
                        $('#flow_re_engagement').prop('checked', !!(flows.reEngagement && flows.reEngagement.enabled));
                        $('#flow_back_in_stock').prop('checked', !!(flows.backInStock && flows.backInStock.enabled));
                        $('#flow_price_drop').prop('checked', !!(flows.priceDrop && flows.priceDrop.enabled));
                        applyFlowProGate(isPro);
                        flowsIgnoreToggleChange = false;
                        $('#behavioral-flows-content').fadeIn(400, function () {
                            // Load SMS flow toggle states; gating uses the same
                            // omnimailFlowsIsPro flag computed above — no
                            // separate OmniVoice PRO check anymore.
                            loadOmniVoiceSmsFlows();
                        });
                    } else {
                        console.error('Invalid response structure:', response);
                        var errorMsg = 'Failed to load flow settings';
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                        showFlowError(errorMsg);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', { xhr: xhr, status: status, error: error });
                    console.error('Response Text:', xhr.responseText);
                    $('#behavioral-flows-loading').hide();

                    var errorMsg = 'An error occurred while loading flow settings';
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. Please check your API connection.';
                    } else if (xhr.status === 0) {
                        errorMsg = 'Cannot connect to server. Please check if the backend is running.';
                    } else if (xhr.status === 404) {
                        errorMsg = 'API endpoint not found (404). Please check your API URL configuration.';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error (500). Please check backend logs.';
                    } else {
                        errorMsg += ': ' + error + ' (Status: ' + xhr.status + ')';
                    }

                    showFlowError(errorMsg);
                }
            });
        }

        // Block toggle when locked — only applies to the EMAIL panel, not the SMS panel
        $(document).on('click', '#behavioral-flows-content.is-pro-locked .omnimail-email-panel .omnimail-toggle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            showNotification('Behavioral flows require a PRO OmniMail plan. Upgrade to unlock.', 'error');
            window.open(omnimailProUpgradeUrl, '_blank');
            return false;
        });

        // Enhance Your Flow prompt (same pattern as OmniMail app)
        var currentFollowupFlowType = null;
        var currentFollowupFlowLabel = '';
        var selectedPrebuiltSequenceId = null;
        var customSequenceSteps = [];
        var currentLoadedFollowups = [];

        function recommendedSequenceId(flowType) {
            var map = {
                cartAbandonment: 'no_engagement_recovery',
                checkoutAbandonment: 'no_engagement_recovery',
                browseAbandonment: 'no_engagement_recovery',
                reEngagement: 'no_engagement_recovery',
                wishlistReminder: 'none',
                priceDrop: 'none',
                backInStock: 'none',
                postPurchase: 'none'
            };
            return map[flowType] || 'none';
        }

        function openEnhanceFlowModal(flowType, flowLabel) {
            currentFollowupFlowType = flowType || null;
            currentFollowupFlowLabel = flowLabel || '';
            $('#omnimail-enhance-flow-modal').css('display', 'flex').hide().fadeIn(150);
        }

        function closeEnhanceFlowModal() {
            $('#omnimail-enhance-flow-modal').fadeOut(120);
        }

        function openModal($el) {
            $el.css('display', 'flex').hide().fadeIn(150);
        }

        function closeModal($el) {
            $el.fadeOut(120);
        }

        function triggerLabel(trigger) {
            return ({ not_opened: 'Not Opened', opened: 'Opened', clicked: 'Clicked' })[trigger] || trigger;
        }

        function actionLabel(actionType) {
            return ({ email: 'Send Email', tag: 'Add Tag', both: 'Email + Tag' })[actionType] || actionType;
        }

        function defaultCustomStep() {
            return {
                trigger: 'not_opened',
                actionType: 'email',
                delayDays: 1,
                followupEmailSubject: '',
                followupEmailContent: '',
                followupTag: '',
                activeDaySchedules: [
                    { day: 1, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
                    { day: 2, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
                    { day: 3, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
                    { day: 4, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
                    { day: 5, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } }
                ]
            };
        }

        function normalizeStepsForEditor(followups) {
            return (followups || []).map(function (item) {
                return {
                    trigger: item.trigger || 'not_opened',
                    actionType: item.actionType || 'email',
                    delayDays: typeof item.delayDays === 'number' ? item.delayDays : 1,
                    followupEmailSubject: item.followupEmailSubject || '',
                    followupEmailContent: item.followupEmailContent || '',
                    followupTag: item.followupTag || '',
                    activeDaySchedules: item.activeDaySchedules || defaultCustomStep().activeDaySchedules
                };
            });
        }

        function applyFollowupsPayload(followups, successMessage) {
            return $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'omnimail_apply_prebuilt_followups',
                    nonce: omnimailAjax.nonce,
                    flow_type: currentFollowupFlowType,
                    followups: JSON.stringify(followups || [])
                }
            }).then(function (response) {
                if (response.success) {
                    showNotification(successMessage || (response.data && response.data.message) || 'Sequence applied!', 'success');
                    loadFlowFollowups(currentFollowupFlowType);
                    return response;
                }
                showNotification((response.data && response.data.message) || 'Failed to apply sequence', 'error');
                return $.Deferred().reject(response).promise();
            });
        }

        function renderFollowupsList(followups) {
            var $list = $('#omnimail-followups-list');
            $list.empty();
            currentLoadedFollowups = followups || [];

            if (!followups || !followups.length) {
                $('#omnimail-followups-banner').hide();
                $('#omnimail-followups-list-wrap').hide();
                $('#omnimail-followups-empty').show();
                return;
            }

            $('#omnimail-followups-empty').hide();
            $('#omnimail-followups-banner').hide();
            $('#omnimail-followups-count').text('Active Follow-ups (' + followups.length + ')');
            $('#omnimail-followups-list-wrap').show();

            followups.forEach(function (item, index) {
                var subject = item.followupEmailSubject || '(No subject)';
                var tagHtml = item.followupTag
                    ? '<div class="omnimail-followup-tag">Auto-tag: ' + $('<div>').text(item.followupTag).html() + '</div>'
                    : '';
                var html =
                    '<div class="omnimail-followup-item" data-id="' + item.id + '">' +
                    '<div class="omnimail-followup-step">' + (index + 1) + '</div>' +
                    '<div class="omnimail-followup-body">' +
                    '<div class="omnimail-followup-meta">' +
                    '<span class="omnimail-chip omnimail-chip-trigger">' + triggerLabel(item.trigger) + '</span>' +
                    '<span class="omnimail-chip-arrow">→</span>' +
                    '<span class="omnimail-chip omnimail-chip-action">' + actionLabel(item.actionType) + '</span>' +
                    '<span class="omnimail-chip">+' + (item.delayDays || 0) + ' day' + ((item.delayDays === 1) ? '' : 's') + '</span>' +
                    '</div>' +
                    '<div class="omnimail-followup-subject">"' + $('<div>').text(subject).html() + '"</div>' +
                    tagHtml +
                    '</div>' +
                    '<button type="button" class="button-link-delete omnimail-delete-followup" title="Delete">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                    '</div>';
                $list.append(html);
            });
        }

        function loadFlowFollowups(flowType) {
            $('#omnimail-followups-loading').show();
            $('#omnimail-followups-banner').hide();
            $('#omnimail-followups-empty').hide();
            $('#omnimail-followups-list-wrap').hide();

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'omnimail_get_flow_followups',
                    nonce: omnimailAjax.nonce,
                    flow_type: flowType
                },
                success: function (response) {
                    $('#omnimail-followups-loading').hide();
                    if (response.success) {
                        var list = (response.data && response.data.data) ? response.data.data : [];
                        if (!Array.isArray(list)) {
                            list = [];
                        }
                        renderFollowupsList(list);
                    } else {
                        showNotification((response.data && response.data.message) || 'Failed to load follow-ups', 'error');
                        $('#omnimail-followups-banner').hide();
                        $('#omnimail-followups-empty').show();
                    }
                },
                error: function () {
                    $('#omnimail-followups-loading').hide();
                    showNotification('Failed to load follow-ups', 'error');
                    $('#omnimail-followups-banner').hide();
                    $('#omnimail-followups-empty').show();
                }
            });
        }

        function openFollowupsModal(flowType, flowLabel) {
            if (!flowType) {
                return;
            }
            currentFollowupFlowType = flowType;
            currentFollowupFlowLabel = flowLabel || flowType;
            $('#omnimail-followups-title-text').text('Follow-up Sequences for ' + currentFollowupFlowLabel);
            openModal($('#omnimail-followups-modal'));
            loadFlowFollowups(flowType);
        }

        function syncCustomStepFromDom(index) {
            var $card = $('#omnimail-custom-steps .omnimail-custom-step[data-index="' + index + '"]');
            if (!$card.length || !customSequenceSteps[index]) {
                return;
            }
            customSequenceSteps[index].trigger = $card.find('.omnimail-custom-trigger').val();
            customSequenceSteps[index].actionType = $card.find('.omnimail-custom-action').val();
            customSequenceSteps[index].delayDays = parseInt($card.find('.omnimail-custom-delay').val(), 10) || 0;
            customSequenceSteps[index].followupEmailSubject = $card.find('.omnimail-custom-subject').val() || '';
            customSequenceSteps[index].followupEmailContent = $card.find('.omnimail-custom-content').val() || '';
            customSequenceSteps[index].followupTag = $card.find('.omnimail-custom-tag').val() || '';
        }

        function syncAllCustomStepsFromDom() {
            $('#omnimail-custom-steps .omnimail-custom-step').each(function () {
                syncCustomStepFromDom(parseInt($(this).data('index'), 10));
            });
        }

        function renderCustomStepsEditor() {
            var $wrap = $('#omnimail-custom-steps').empty();
            if (!customSequenceSteps.length) {
                $wrap.append('<p class="description">No steps yet. Click “Add Step” to create your sequence.</p>');
                return;
            }

            customSequenceSteps.forEach(function (step, index) {
                var needsEmail = step.actionType === 'email' || step.actionType === 'both';
                var needsTag = step.actionType === 'tag' || step.actionType === 'both';
                var html =
                    '<div class="omnimail-custom-step" data-index="' + index + '">' +
                    '<div class="omnimail-custom-step-header">' +
                    '<strong>Step ' + (index + 1) + '</strong>' +
                    '<button type="button" class="button-link-delete omnimail-custom-remove-step" title="Remove step">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                    '</div>' +
                    '<div class="omnimail-custom-grid">' +
                    '<label>Trigger<select class="omnimail-custom-trigger">' +
                    '<option value="not_opened"' + (step.trigger === 'not_opened' ? ' selected' : '') + '>Not Opened</option>' +
                    '<option value="opened"' + (step.trigger === 'opened' ? ' selected' : '') + '>Opened (no click)</option>' +
                    '<option value="clicked"' + (step.trigger === 'clicked' ? ' selected' : '') + '>Clicked</option>' +
                    '</select></label>' +
                    '<label>Action<select class="omnimail-custom-action">' +
                    '<option value="email"' + (step.actionType === 'email' ? ' selected' : '') + '>Send Email</option>' +
                    '<option value="tag"' + (step.actionType === 'tag' ? ' selected' : '') + '>Add Tag</option>' +
                    '<option value="both"' + (step.actionType === 'both' ? ' selected' : '') + '>Email + Tag</option>' +
                    '</select></label>' +
                    '<label>Delay (days)<input type="number" min="0" class="omnimail-custom-delay" value="' + (step.delayDays || 0) + '"></label>' +
                    '</div>' +
                    '<div class="omnimail-custom-email-fields" style="' + (needsEmail ? '' : 'display:none;') + '">' +
                    '<label>Subject<input type="text" class="omnimail-custom-subject widefat" value="' + $('<div>').text(step.followupEmailSubject || '').html() + '"></label>' +
                    '<label>Email HTML<textarea class="omnimail-custom-content widefat" rows="5">' + $('<div>').text(step.followupEmailContent || '').html() + '</textarea></label>' +
                    '</div>' +
                    '<div class="omnimail-custom-tag-fields" style="' + (needsTag ? '' : 'display:none;') + '">' +
                    '<label>Tag<input type="text" class="omnimail-custom-tag widefat" value="' + $('<div>').text(step.followupTag || '').html() + '"></label>' +
                    '</div>' +
                    '</div>';
                $wrap.append(html);
            });
        }

        function openCustomSequenceModal(seedSteps) {
            customSequenceSteps = normalizeStepsForEditor(seedSteps || []);
            if (!customSequenceSteps.length) {
                customSequenceSteps = [defaultCustomStep()];
            }
            renderCustomStepsEditor();
            closeModal($('#omnimail-prebuilt-modal'));
            openModal($('#omnimail-custom-modal'));
        }

        function selectPrebuiltSequence(sequenceId) {
            var sequences = window.OMNIMAIL_PREBUILT_SEQUENCES || {};
            var seq = sequences[sequenceId];
            if (!seq) {
                return;
            }

            selectedPrebuiltSequenceId = sequenceId;
            $('#omnimail-prebuilt-options .omnimail-prebuilt-option').removeClass('is-selected');
            $('#omnimail-prebuilt-options .omnimail-prebuilt-option[data-sequence-id="' + sequenceId + '"]').addClass('is-selected');

            if (seq.isCustom) {
                $('#omnimail-prebuilt-preview-wrap').hide();
                $('#omnimail-prebuilt-apply').prop('disabled', true).hide();
                $('#omnimail-prebuilt-customize').show().prop('disabled', false);
                return;
            }

            $('#omnimail-prebuilt-apply').show();
            $('#omnimail-prebuilt-customize').toggle((seq.followups || []).length > 0);

            $('#omnimail-prebuilt-name').text(seq.name || '');
            $('#omnimail-prebuilt-desc').text(seq.description || '');
            $('#omnimail-prebuilt-preview-wrap').show();

            var $steps = $('#omnimail-prebuilt-steps').empty();
            var followups = seq.followups || [];
            if (!followups.length) {
                $steps.append('<p class="description">No extra follow-up steps. Only the default behavioral email will send.</p>');
                $('#omnimail-prebuilt-apply-label').text('Apply None (Clear Follow-ups)');
                $('#omnimail-prebuilt-customize').hide();
            } else {
                followups.forEach(function (step, idx) {
                    $steps.append(
                        '<div class="omnimail-prebuilt-step">' +
                        '<div class="omnimail-followup-meta">' +
                        '<span class="omnimail-chip omnimail-chip-trigger">' + triggerLabel(step.trigger) + '</span>' +
                        '<span class="omnimail-chip-arrow">→</span>' +
                        '<span class="omnimail-chip omnimail-chip-action">' + actionLabel(step.actionType) + '</span>' +
                        '<span class="omnimail-chip">+' + (step.delayDays || 0) + 'd</span>' +
                        '</div>' +
                        '<div class="omnimail-followup-subject">' + (idx + 1) + '. ' + $('<div>').text(step.followupEmailSubject || '').html() + '</div>' +
                        (step.stepDescription ? '<div class="omnimail-prebuilt-step-desc">' + $('<div>').text(step.stepDescription).html() + '</div>' : '') +
                        '</div>'
                    );
                });
                $('#omnimail-prebuilt-apply-label').text('Use This Sequence');
            }

            $('#omnimail-prebuilt-apply').prop('disabled', false);
        }

        function openPrebuiltModal() {
            var sequences = window.OMNIMAIL_PREBUILT_SEQUENCES;
            if (!sequences) {
                showNotification('Pre-built sequences not loaded', 'error');
                return;
            }

            selectedPrebuiltSequenceId = null;
            $('#omnimail-prebuilt-apply').prop('disabled', true).show();
            $('#omnimail-prebuilt-customize').hide();
            $('#omnimail-prebuilt-preview-wrap').hide();
            $('#omnimail-prebuilt-steps').empty();

            var recommended = recommendedSequenceId(currentFollowupFlowType);
            var recommendedName = (sequences[recommended] && sequences[recommended].name) || recommended;
            $('#omnimail-prebuilt-hint').text(
                'Recommended for ' + (currentFollowupFlowLabel || currentFollowupFlowType) + ': ' + recommendedName
            );

            var order = ['none', 'no_engagement_recovery', 'engaged_lead', 'no_response_recovery', 'custom'];
            var $options = $('#omnimail-prebuilt-options').empty();
            order.forEach(function (id) {
                var seq = sequences[id];
                if (!seq) {
                    return;
                }
                var stepCount = (seq.followups || []).length;
                var isRec = id === recommended;
                var meta = seq.isCustom
                    ? '<span class="omnimail-chip">Build your own</span>'
                    : (isRec
                        ? '<span class="omnimail-chip omnimail-chip-recommended">Recommended</span>'
                        : '<span class="omnimail-chip">' + stepCount + ' step' + (stepCount === 1 ? '' : 's') + '</span>');
                $options.append(
                    '<button type="button" class="omnimail-prebuilt-option" data-sequence-id="' + id + '">' +
                    '<strong>' + $('<div>').text(seq.name).html() + '</strong>' +
                    '<span class="omnimail-prebuilt-option-desc">' + $('<div>').text(seq.description || '').html() + '</span>' +
                    meta +
                    '</button>'
                );
            });

            openModal($('#omnimail-prebuilt-modal'));
            selectPrebuiltSequence(recommended);
        }

        $(document).on('change', '#behavioral-flows-content input[type="checkbox"][data-flow]', function () {
            if (flowsIgnoreToggleChange || !omnimailFlowsIsPro) {
                return;
            }
            if ($(this).is(':checked')) {
                var $card = $(this).closest('.omnimail-flow-card');
                openEnhanceFlowModal(
                    $(this).data('flow-type') || $card.data('flow-type'),
                    $card.data('flow-label') || ''
                );
            }
        });

        $(document).on('click', '#omnimail-enhance-flow-close, #omnimail-enhance-flow-later', function (e) {
            e.preventDefault();
            closeEnhanceFlowModal();
        });

        $(document).on('click', '#omnimail-enhance-flow-yes', function (e) {
            e.preventDefault();
            closeEnhanceFlowModal();
            openFollowupsModal(currentFollowupFlowType, currentFollowupFlowLabel);
        });

        $(document).on('click', '.omnimail-manage-followups-btn', function (e) {
            e.preventDefault();
            if (!omnimailFlowsIsPro) {
                showNotification('Behavioral flows require a PRO OmniMail plan. Upgrade to unlock.', 'error');
                window.open(omnimailProUpgradeUrl, '_blank');
                return;
            }
            var $card = $(this).closest('.omnimail-flow-card');
            openFollowupsModal($card.data('flow-type'), $card.data('flow-label'));
        });

        $(document).on('click', '#omnimail-followups-close', function (e) {
            e.preventDefault();
            closeModal($('#omnimail-followups-modal'));
        });

        $(document).on('click', '#omnimail-browse-prebuilt-btn, #omnimail-followups-get-started, #omnimail-replace-prebuilt-btn', function (e) {
            e.preventDefault();
            openPrebuiltModal();
        });

        $(document).on('click', '#omnimail-followups-build-custom, #omnimail-edit-custom-btn', function (e) {
            e.preventDefault();
            openCustomSequenceModal(currentLoadedFollowups);
        });

        $(document).on('click', '#omnimail-prebuilt-options .omnimail-prebuilt-option', function (e) {
            e.preventDefault();
            selectPrebuiltSequence($(this).data('sequence-id'));
        });

        $(document).on('click', '#omnimail-prebuilt-close, #omnimail-prebuilt-cancel', function (e) {
            e.preventDefault();
            closeModal($('#omnimail-prebuilt-modal'));
        });

        $(document).on('click', '#omnimail-prebuilt-customize', function (e) {
            e.preventDefault();
            var sequences = window.OMNIMAIL_PREBUILT_SEQUENCES || {};
            var seq = sequences[selectedPrebuiltSequenceId];
            if (seq && seq.isCustom) {
                openCustomSequenceModal([]);
                return;
            }
            openCustomSequenceModal(seq ? (seq.followups || []) : []);
        });

        $(document).on('click', '#omnimail-prebuilt-apply', function (e) {
            e.preventDefault();
            var sequences = window.OMNIMAIL_PREBUILT_SEQUENCES || {};
            var seq = sequences[selectedPrebuiltSequenceId];
            if (!seq || seq.isCustom || !currentFollowupFlowType) {
                showNotification('Select a sequence first', 'error');
                return;
            }

            var $btn = $(this);
            var original = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Applying...');

            applyFollowupsPayload(seq.followups || []).always(function () {
                $btn.prop('disabled', false).html(original);
            }).done(function () {
                closeModal($('#omnimail-prebuilt-modal'));
            });
        });

        $(document).on('change', '#omnimail-custom-steps .omnimail-custom-action', function () {
            var $card = $(this).closest('.omnimail-custom-step');
            var action = $(this).val();
            $card.find('.omnimail-custom-email-fields').toggle(action === 'email' || action === 'both');
            $card.find('.omnimail-custom-tag-fields').toggle(action === 'tag' || action === 'both');
            syncCustomStepFromDom(parseInt($card.data('index'), 10));
        });

        $(document).on('change input', '#omnimail-custom-steps .omnimail-custom-trigger, #omnimail-custom-steps .omnimail-custom-delay, #omnimail-custom-steps .omnimail-custom-subject, #omnimail-custom-steps .omnimail-custom-content, #omnimail-custom-steps .omnimail-custom-tag', function () {
            syncCustomStepFromDom(parseInt($(this).closest('.omnimail-custom-step').data('index'), 10));
        });

        $(document).on('click', '#omnimail-custom-add-step', function (e) {
            e.preventDefault();
            syncAllCustomStepsFromDom();
            customSequenceSteps.push(defaultCustomStep());
            renderCustomStepsEditor();
        });

        $(document).on('click', '.omnimail-custom-remove-step', function (e) {
            e.preventDefault();
            syncAllCustomStepsFromDom();
            var index = parseInt($(this).closest('.omnimail-custom-step').data('index'), 10);
            customSequenceSteps.splice(index, 1);
            renderCustomStepsEditor();
        });

        $(document).on('click', '#omnimail-custom-close, #omnimail-custom-cancel', function (e) {
            e.preventDefault();
            closeModal($('#omnimail-custom-modal'));
        });

        $(document).on('click', '#omnimail-custom-save', function (e) {
            e.preventDefault();
            if (!currentFollowupFlowType) {
                showNotification('No flow selected', 'error');
                return;
            }
            syncAllCustomStepsFromDom();

            for (var i = 0; i < customSequenceSteps.length; i++) {
                var step = customSequenceSteps[i];
                if ((step.actionType === 'email' || step.actionType === 'both') &&
                    (!step.followupEmailSubject || !step.followupEmailContent)) {
                    showNotification('Step ' + (i + 1) + ': subject and email content are required', 'error');
                    return;
                }
                if ((step.actionType === 'tag' || step.actionType === 'both') && !step.followupTag) {
                    showNotification('Step ' + (i + 1) + ': tag is required', 'error');
                    return;
                }
            }

            var $btn = $(this);
            var original = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Saving...');

            applyFollowupsPayload(customSequenceSteps, 'Custom sequence saved').always(function () {
                $btn.prop('disabled', false).html(original);
            }).done(function () {
                closeModal($('#omnimail-custom-modal'));
            });
        });

        $(document).on('click', '.omnimail-delete-followup', function (e) {
            e.preventDefault();
            var id = $(this).closest('.omnimail-followup-item').data('id');
            if (!id || !confirm('Delete this follow-up?')) {
                return;
            }

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'omnimail_delete_flow_followup',
                    nonce: omnimailAjax.nonce,
                    followup_id: id
                },
                success: function (response) {
                    if (response.success) {
                        showNotification('Follow-up deleted', 'success');
                        loadFlowFollowups(currentFollowupFlowType);
                    } else {
                        showNotification((response.data && response.data.message) || 'Failed to delete', 'error');
                    }
                },
                error: function () {
                    showNotification('Failed to delete follow-up', 'error');
                }
            });
        });

        $(document).on('click', '#omnimail-enhance-flow-modal, #omnimail-followups-modal, #omnimail-prebuilt-modal, #omnimail-custom-modal', function (e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key !== 'Escape') {
                return;
            }
            if ($('#omnimail-custom-modal').is(':visible')) {
                closeModal($('#omnimail-custom-modal'));
            } else if ($('#omnimail-prebuilt-modal').is(':visible')) {
                closeModal($('#omnimail-prebuilt-modal'));
            } else if ($('#omnimail-followups-modal').is(':visible')) {
                closeModal($('#omnimail-followups-modal'));
            } else if ($('#omnimail-enhance-flow-modal').is(':visible')) {
                closeEnhanceFlowModal();
            }
        });

        // Collect the current Email flow checkbox states into a settings object
        function collectBehavioralFlowSettings() {
            return {
                cartAbandonmentEnabled: $('#flow_cart_abandonment').is(':checked'),
                browseAbandonmentEnabled: $('#flow_browse_abandonment').is(':checked'),
                checkoutAbandonmentEnabled: $('#flow_checkout_abandonment').is(':checked'),
                wishlistReminderEnabled: $('#flow_wishlist_reminder').is(':checked'),
                postPurchaseEnabled: $('#flow_post_purchase').is(':checked'),
                reEngagementEnabled: $('#flow_re_engagement').is(':checked'),
                backInStockEnabled: $('#flow_back_in_stock').is(':checked'),
                priceDropEnabled: $('#flow_price_drop').is(':checked')
            };
        }

        // Shared save routine used by the Save button AND by Enable All /
        // Disable All, so those two buttons persist immediately without
        // requiring an extra click on "Save Flow Settings".
        function saveBehavioralFlows(onDone) {
            var settings = collectBehavioralFlowSettings();

            $.ajax({
                url: omnimailAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'omnimail_update_behavioral_flows',
                    nonce: omnimailAjax.nonce,
                    settings: JSON.stringify(settings)
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof onDone === 'function') onDone(true, response);
                    } else {
                        showNotification((response.data && response.data.message) || 'Failed to save settings', 'error');
                        if (typeof onDone === 'function') onDone(false, response);
                    }
                },
                error: function () {
                    showNotification('An error occurred while saving settings', 'error');
                    if (typeof onDone === 'function') onDone(false, null);
                }
            });
        }

        // Save behavioral flows
        $('#save-flows-btn').on('click', function (e) {
            e.preventDefault();

            if (!omnimailFlowsIsPro) {
                showNotification('Behavioral flows require a PRO OmniMail plan. Upgrade to unlock.', 'error');
                window.open(omnimailProUpgradeUrl, '_blank');
                return;
            }

            var button = $(this);
            var originalHtml = button.html();
            button.prop('disabled', true);
            button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Saving...');

            saveBehavioralFlows(function (success) {
                button.prop('disabled', false);
                button.html(originalHtml);
                if (success) {
                    showNotification('Flow settings saved successfully!', 'success');
                }
            });
        });

        // Enable all flows — checks every toggle then saves immediately
        $('#enable-all-flows').on('click', function (e) {
            e.preventDefault();

            if (!omnimailFlowsIsPro) {
                showNotification('Behavioral flows require a PRO OmniMail plan. Upgrade to unlock.', 'error');
                window.open(omnimailProUpgradeUrl, '_blank');
                return;
            }
            var button = $(this);
            var originalHtml = button.html();
            button.prop('disabled', true);
            button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Enabling...');

            flowsIgnoreToggleChange = true;
            $('#behavioral-flows-content input[type="checkbox"][data-flow]').prop('checked', true);
            flowsIgnoreToggleChange = false;

            saveBehavioralFlows(function (success) {
                button.prop('disabled', false);
                button.html(originalHtml);
                if (success) {
                    showNotification('All flows enabled and saved successfully!', 'success');
                }
            });
        });

        // Disable all flows — unchecks every toggle then saves immediately
        $('#disable-all-flows').on('click', function (e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to disable all behavioral flows? This will stop all automated emails.')) {
                return;
            }

            var button = $(this);
            var originalHtml = button.html();
            button.prop('disabled', true);
            button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Disabling...');

            flowsIgnoreToggleChange = true;
            $('#behavioral-flows-content input[type="checkbox"][data-flow]').prop('checked', false);
            flowsIgnoreToggleChange = false;

            saveBehavioralFlows(function (success) {
                button.prop('disabled', false);
                button.html(originalHtml);
                if (success) {
                    showNotification('All flows disabled and saved successfully!', 'success');
                }
            });
        });

        // Show flow error
        function showFlowError(message) {
            $('#error-message').text(message);
            $('#behavioral-flows-error').fadeIn();
        }

        // ===========================
        // Raise Ticket Functionality
        // ===========================
        const validFileTypes = [
            "image/png", "image/jpeg", "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "text/plain",
        ];
        const maxSize = 10 * 1024 * 1024; // 10 MB
        let selectedFiles = [];
        let baseTicketId = '';

        function generateTicketID() {
            const randomNum = Math.floor(100000 + Math.random() * 900000);
            baseTicketId = randomNum.toString();
            return `TKT-${baseTicketId}-OmniMail`;
        }

        // Open Ticket Modal
        $('#omnimail-raise-ticket-btn').on('click', function () {
            console.log('Opening ticket modal...');

            // Check if omnimailAjax exists
            if (typeof omnimailAjax === 'undefined') {
                console.error('omnimailAjax is not defined');
                alert('Configuration error. Please refresh the page and try again.');
                return;
            }

            const connectionId = omnimailAjax.connectionId || '';
            const modal = $('#omnimail-ticket-modal');
            const errorDiv = $('#ticket-modal-error');
            const form = $('#omnimail-ticket-form');

            if (!connectionId) {
                errorDiv.text('Connection ID is missing. Please configure it in settings first.').show();
                form.hide();
            } else {
                errorDiv.hide();
                form.show();

                // Generate and set subject
                const ticketId = generateTicketID();
                $('#generated-subject-text').text(ticketId);
                $('#ticket-subject').val(ticketId);

                // Reset form
                form[0].reset();
                $('#ticket-subject').val(ticketId);
                $('#ticket-priority').val('Medium');
                $('#ticket-type').val('General Inquiry'); // Set default type
                selectedFiles = [];
                updateFileList();
            }

            modal.fadeIn(200);
        });

        // Close modal (X button always; backdrop only when clicking outside content)
        $(document).on('click', '#close-ticket-modal', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('#omnimail-ticket-modal').fadeOut(200);
        });

        $(document).on('click', '#omnimail-ticket-modal', function (e) {
            if (e.target === this) {
                $('#omnimail-ticket-modal').fadeOut(200);
            }
        });

        // Copy subject
        $('#copy-subject-btn').on('click', function (e) {
            e.preventDefault();
            const text = $('#generated-subject-text').text();
            navigator.clipboard.writeText(text).then(() => {
                const icon = $(this).find('.dashicons');
                icon.removeClass('dashicons-admin-page').addClass('dashicons-yes');
                setTimeout(() => {
                    icon.removeClass('dashicons-yes').addClass('dashicons-admin-page');
                }, 2000);
            }).catch(err => {
                console.error('Copy failed:', err);
            });
        });

        // File upload functionality
        const uploadArea = $('#ticket-upload-area');
        const fileInput = $('#ticket-files');

        uploadArea.on('click', function (e) {
            if (e.target !== fileInput[0]) {
                fileInput.click();
            }
        });

        fileInput.on('change', function (e) {
            handleFiles(e.target.files);
        });

        uploadArea.on('dragover', function (e) {
            e.preventDefault();
            $(this).css('background', 'rgba(0, 255, 255, 0.1)');
            $(this).css('border-color', 'var(--color-cyan)');
        });

        uploadArea.on('dragleave', function (e) {
            e.preventDefault();
            $(this).css('background', 'rgba(0, 255, 255, 0.05)');
            $(this).css('border-color', '#0a738c');
        });

        uploadArea.on('drop', function (e) {
            e.preventDefault();
            $(this).css('background', 'rgba(0, 255, 255, 0.05)');
            $(this).css('border-color', '#0a738c');
            handleFiles(e.originalEvent.dataTransfer.files);
        });

        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!validFileTypes.includes(file.type)) {
                    alert(`File type not allowed: ${file.name}`);
                    continue;
                }
                if (file.size > maxSize) {
                    alert(`File too large: ${file.name} (Max 10MB)`);
                    continue;
                }
                selectedFiles.push(file);
            }
            updateFileList();
        }

        function updateFileList() {
            const list = $('#selected-files-list');
            list.empty();
            selectedFiles.forEach((file, index) => {
                const item = $(`<div style="background: #232b3b; padding: 8px 12px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
      <span style="color: #f6f8fa; font-size: 13px;">${file.name}</span>
      <button type="button" class="remove-file-btn" data-index="${index}" style="background: none; border: none; color: #ff3b5c; cursor: pointer; padding: 0 5px;">×</button>
    </div>`);
                list.append(item);
            });
        }

        $(document).on('click', '.remove-file-btn', function () {
            const index = $(this).data('index');
            selectedFiles.splice(index, 1);
            updateFileList();
        });

        // Prevent default form submission entirely
        $(document).on('submit', '#omnimail-ticket-form', function (e) {
            e.preventDefault();
            return false;
        });

        // Submit ticket
        $(document).on('click', '#submit-ticket-btn', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('=== Ticket Submission Clicked ===');

            // Check if omnimailAjax exists
            if (typeof omnimailAjax === 'undefined') {
                console.error('omnimailAjax is undefined');
                alert('Configuration error: Plugin data is missing. Please refresh the page.');
                return;
            }

            const submitBtn = $(this);
            const originalText = submitBtn.text();
            const connectionId = omnimailAjax.connectionId;

            console.log('Connection ID present:', !!connectionId);

            if (!connectionId) {
                alert('Connection ID is missing. Please configure it in settings.');
                return;
            }

            // Validate required fields
            const subject = $('#ticket-subject').val() ? $('#ticket-subject').val().trim() : '';
            const type = $('#ticket-type').val() || 'General Inquiry'; // Default type
            const priority = $('#ticket-priority').val();
            const description = $('#ticket-description').val() ? $('#ticket-description').val().trim() : '';

            console.log('Validating fields:', { subject, type, priority, description: description.substring(0, 20) + '...' });

            if (!subject || !description) {
                alert('Please fill in all required fields (Subject and Description).');
                return;
            }

            submitBtn.prop('disabled', true).text('Processing...');

            try {
                let attachmentUrls = [];

                // Step 1: Upload files if any
                if (selectedFiles.length > 0) {
                    submitBtn.text('Uploading Files...');
                    console.log('Uploading', selectedFiles.length, 'files...');

                    const uploadData = new FormData();
                    selectedFiles.forEach(file => {
                        uploadData.append('attachments', file);
                    });

                    try {
                        const uploadRes = await $.ajax({
                            url: 'https://backend.omninexttech.com/api/ticketing/upload',
                            type: 'POST',
                            headers: { 'x-connection-id': connectionId },
                            data: uploadData,
                            processData: false,
                            contentType: false
                        });

                        console.log('Upload response:', uploadRes);

                        if (uploadRes.success || uploadRes.responseCode === 2000) {
                            attachmentUrls = uploadRes.data || [];
                            console.log('Files uploaded successfully:', attachmentUrls);
                        } else {
                            throw new Error(uploadRes.message || 'File upload failed');
                        }
                    } catch (uploadErr) {
                        console.error('Upload API Error:', uploadErr);
                        throw new Error('File upload failed: ' + (uploadErr.responseJSON?.message || uploadErr.statusText));
                    }
                }

                // Step 2: Submit ticket
                submitBtn.text('Submitting Ticket...');
                console.log('Submitting ticket JSON payload...');

                const ticketPayload = {
                    subject: subject,
                    type: type,
                    priority: priority,
                    loomLink: $('#ticket-loom').val() ? $('#ticket-loom').val().trim() : '',
                    description: description,
                    category: 'OmniMail',
                    attachments: attachmentUrls
                };

                console.log('Sending Payload:', ticketPayload);

                const response = await $.ajax({
                    url: 'https://backend.omninexttech.com/api/ticketing',
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'x-connection-id': connectionId },
                    data: JSON.stringify(ticketPayload),
                    dataType: 'json'
                });

                console.log('Ticket API Response:', response);

                if (response.success || response.responseCode === 2000) {
                    // Close modal and reset form without showing alert
                    $('#omnimail-ticket-modal').fadeOut(200);
                    $('#omnimail-ticket-form')[0].reset();
                    selectedFiles = [];
                    updateFileList();
                } else {
                    throw new Error(response.message || 'Failed to create ticket');
                }

            } catch (error) {
                console.error('Final Submission Error:', error);
                let errorMsg = 'Failed to raise ticket.';

                if (error.responseJSON && error.responseJSON.message) {
                    errorMsg = error.responseJSON.message;
                } else if (error.message) {
                    errorMsg = error.message;
                }

                alert(errorMsg);
            } finally {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });

    });

})(jQuery);

// Add rotation animation
var style = document.createElement('style');
style.textContent = `
    @keyframes rotation {
        from { transform: rotate(0deg); }
        to { transform: rotate(359deg); }
    }
`;
document.head.appendChild(style);