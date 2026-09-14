# OmniMail - WooCommerce Email Integration Plugin

Integrate WooCommerce with OmniMail for intelligent email automation, SMTP configuration, and event tracking.

## Features

- **Easy Setup**: Simple API Key + Connection ID configuration
- **Test Connection**: Verify your connection before going live
- **Event Tracking**: Automatically sends WooCommerce events to OmniMail backend
- **Email Configuration**: Configure SMTP settings via the platform
- **Activity Logs**: Monitor all plugin activity
- **Dashboard**: Quick overview of plugin status

## Installation

1. Upload the plugin files to `/wp-content/plugins/OmniMail-Plugin/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **OmniMail > Settings**
4. Enter your API Key and Connection ID
5. Enable the plugin
6. Click "Test Connection" to verify

## Configuration

### Getting API Credentials

1. Log in to [OmniMail Platform](https://omnimail-app.omninexttech.com)
2. Go to **Integrations > Connections**
3. Create a new connection or select existing one
4. Copy the **API Key** and **Connection ID**
5. Paste them in the plugin settings

### Settings

- **Enable OmniMail**: Toggle to enable/disable the plugin
- **API Key**: Your OmniMail API key
- **Connection ID**: Your connection identifier

## Events Tracked

The plugin automatically sends these WooCommerce events to your backend:

### Order Events
- `order.created` - New order placed
- `order.status_changed` - Order status updated

### Cart Events
- `cart.item_added` - Product added to cart
- Cart abandonment tracking

### Product Events
- `product.out_of_stock` - Product went out of stock
- `product.back_in_stock` - Product restocked
- `product.price_dropped` - Significant price drop detected

### Customer Events
- `customer.created` - New customer registered

## API Endpoints Used

The plugin integrates with these backend endpoints:

- `GET /api/integrations/connections/{connectionId}` - Test connection
- `POST /api/integrations/wordpress/events/{connectionId}` - Send events
- `GET /api/integrations/connections/{connectionId}/email-config` - Get email config
- `POST /api/integrations/connections/{connectionId}/email-config` - Save email config
- `POST /api/integrations/connections/{connectionId}/email-config/test` - Test email

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- OmniMail Platform account

## Support

For support, visit:
- Platform: https://omnimail-app.omninexttech.com
- Email: support@omninexttech.com

## Author

**Naveed**  
Website: https://omninexttech.com

## License

GPL-2.0+

## Version

1.0.0
