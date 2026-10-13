=== OmniNextMail ===
Contributors: omniplugin
Tags: woocommerce, email, automation, smtp
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WooCommerce to OmniNextMail for email automation, SMTP configuration, and store event tracking.

== Description ==

OmniNextMail connects a WooCommerce store to the OmniNextMail service. It can send supported store events, synchronize products when requested by an administrator, and manage email settings through the OmniNextMail platform.

= Features =

* Configure the service with an API key and Connection ID.
* Test the service connection from the plugin settings.
* Send supported WooCommerce order, product, cart, and customer events.
* Synchronize products when requested by an administrator.
* Configure and test email settings through the OmniNextMail service.

== External services ==

This plugin connects to OmniNextMail, an external service provided by OmniNext Tech.

Service name: OmniNextMail
Technical service: OmniNext backend
API endpoint: https://backend.omninexttech.com
Account dashboard: https://omninextmail-app.omninexttech.com
Privacy policy: https://omninexttech.com/privacy-policy
Terms of service: https://omninexttech.com/terms-of-service

The plugin sends requests when an administrator tests the connection, configures or tests email settings, requests product synchronization, or when an enabled integration reports a supported WooCommerce event. Depending on the request and event, data may include the site URL, Connection ID, API key in an authentication header, administrator email, WooCommerce order identifiers and status, order totals and currency, product identifiers and details, and customer name, email, phone, and billing or shipping details. When an administrator configures WooCommerce REST API credentials for product synchronization, those credentials are sent securely to the OmniNext service. SMS contact capture, if enabled, sends the visitor's phone number and the submitted browser/device and page details.

Requests are sent to the OmniNext backend over HTTPS. The service processes the submitted data to provide the configured plugin features. See the service privacy policy and terms for details about its retention and processing.

== Installation ==

1. Upload the `omnimail` folder to the `/wp-content/plugins/` directory, or upload the plugin ZIP in **Plugins > Add New**.
2. Activate OmniNextMail through the **Plugins** screen.
3. Open **OmniNextMail** in the WordPress admin menu.
4. Enter the API key and Connection ID from your OmniNextMail account.
5. Enable the integration and use **Test Connection** to verify the setup.

== Frequently Asked Questions ==

= What is required? =

WordPress 5.8 or later, PHP 7.4 or later, WooCommerce 5.0 or later, and an OmniNextMail account.

= What data is sent to OmniNextMail? =

Depending on enabled features, the plugin sends supported WooCommerce events and associated store, product, order, and customer data to the OmniNext backend. See **External services** above.

== Changelog ==

= 1.0.1 =
* Current release.
* WooCommerce email integration, service settings, and event tracking.

== Upgrade Notice ==

= 1.0.1 =
* Current OmniNextMail release.
