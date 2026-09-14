(function () {
    'use strict';

    if (window.omnimailSmsContactInitialized) {
        return;
    }

    var config = window.omnimailSmsContact;
    if (!config) {
        return;
    }

    window.omnimailSmsContactInitialized = true;

    function wasDismissedThisSession() {
        try {
            return window.sessionStorage.getItem(config.dismissalKey) === '1';
        } catch (e) {
            return false;
        }
    }

    function markDismissedThisSession() {
        try {
            window.sessionStorage.setItem(config.dismissalKey, '1');
        } catch (e) {}
    }

    function getBrowserDetails() {
        return {
            userAgent: navigator.userAgent,
            language: navigator.language || '',
            languages: navigator.languages ? Array.prototype.slice.call(navigator.languages) : [],
            platform: navigator.platform || '',
            screen: {
                width: window.screen.width,
                height: window.screen.height,
                pixelRatio: window.devicePixelRatio || 1
            },
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
            url: window.location.href,
            referrer: document.referrer || ''
        };
    }

    function setCookie(name, value, days) {
        var expires = new Date(Date.now() + days * 86400000).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(JSON.stringify(value)) +
            '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    function removeCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
    }

    function getContactId() {
        var match = document.cookie.match(
            new RegExp('(?:^|; )' + config.cookieName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)')
        );
        if (!match) return '';
        try {
            var value = JSON.parse(decodeURIComponent(match[1]));
            return typeof value === 'string' ? value : (value && value.id ? String(value.id) : '');
        } catch (e) {
            return '';
        }
    }

    function createUuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function createModal() {
        var overlay = document.createElement('div');
        overlay.className = 'omnimail-sms-modal-overlay';
        overlay.innerHTML =
            '<div class="omnimail-sms-modal" role="dialog" aria-modal="true" aria-labelledby="omnimail-sms-modal-title">' +
            '<button type="button" class="omnimail-sms-modal-close" aria-label="Close">&times;</button>' +
            '<div class="omnimail-sms-modal-icon">&#9993;</div>' +
            '<p class="omnimail-sms-modal-eyebrow">EXCLUSIVE UPDATES</p>' +
            '<h2 id="omnimail-sms-modal-title">Stay in the loop</h2>' +
            '<p class="omnimail-sms-modal-copy">Get first access to restocks, special offers, and updates by SMS.</p>' +
            '<form class="omnimail-sms-form">' +
            '<label for="omnimail-sms-phone">Mobile number</label>' +
            '<input id="omnimail-sms-phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+1 415 555 2671" required>' +
            '<p class="omnimail-sms-form-hint">Include your country code.</p>' +
            '<label class="omnimail-sms-consent" for="omnimail-sms-consent">' +
            '<input id="omnimail-sms-consent" type="checkbox" name="omnimail_sms_consent">' +
            '<span>I agree to receive marketing text messages. Message and data rates may apply.</span>' +
            '</label>' +
            '<button type="submit" class="omnimail-sms-submit" disabled>Notify me <span>&rarr;</span></button>' +
            '<p class="omnimail-sms-error" role="alert"></p>' +
            '</form>' +
            '</div>';
        document.body.appendChild(overlay);
        return overlay;
    }

    function showModal() {
        var modal = createModal();
        var form = modal.querySelector('.omnimail-sms-form');
        var input = modal.querySelector('#omnimail-sms-phone');
        var consent = modal.querySelector('#omnimail-sms-consent');
        var error = modal.querySelector('.omnimail-sms-error');
        var submit = modal.querySelector('.omnimail-sms-submit');

        function close() {
            markDismissedThisSession();
            modal.classList.remove('is-visible');
            setTimeout(function () { modal.remove(); }, 200);
        }

        function updateSubmitState() {
            submit.disabled = !consent.checked;
            if (!consent.checked && submit.classList.contains('is-loading')) {
                submit.classList.remove('is-loading');
                submit.innerHTML = 'Notify me <span>&rarr;</span>';
            }
        }

        modal.querySelector('.omnimail-sms-modal-close').addEventListener('click', close);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) close();
        });
        consent.addEventListener('change', updateSubmitState);
        updateSubmitState();

        window.setTimeout(function () {
            modal.classList.add('is-visible');
            input.focus();
        }, 20);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var phone = input.value.trim().replace(/[\s().-]/g, '');
            if (!/^\+[1-9][0-9]{7,14}$/.test(phone)) {
                error.textContent = 'Enter a valid number with country code, for example +14155552671.';
                input.focus();
                return;
            }

            if (!consent.checked) {
                error.textContent = 'Please confirm that you agree to receive marketing text messages before continuing.';
                consent.focus();
                return;
            }

            error.textContent = '';
            var contactUuid = createUuid();
            submit.disabled = true;
            submit.classList.add('is-loading');
            submit.innerHTML = 'Saving...';

            var data = new FormData();
            data.append('action', 'omnimail_save_sms_contact');
            data.append('nonce', config.nonce);
            data.append('uuid', contactUuid);
            data.append('phoneNumber', phone);
            data.append('browserDetails', JSON.stringify(getBrowserDetails()));

            fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
                .then(function (r) { return r.json(); })
                .then(function (response) {
                    if (!response.success) {
                        throw new Error(
                            (response.data && response.data.message) ||
                            'Unable to save your number.'
                        );
                    }
                    var savedId = response.data && response.data.id
                        ? String(response.data.id) : '';
                    if (!savedId) {
                        throw new Error('The SMS service did not return a contact ID.');
                    }
                    setCookie(config.cookieName, savedId, 730);
                    close();
                })
                .catch(function (err) {
                    removeCookie(config.cookieName);
                    error.textContent = err.message;
                    submit.disabled = false;
                    submit.classList.remove('is-loading');
                    submit.innerHTML = 'Notify me <span>&rarr;</span>';
                });
        });
    }

    if (!getContactId() && !wasDismissedThisSession()) {
        window.setTimeout(showModal, Number(config.delay) || 30000);
    }
}());
