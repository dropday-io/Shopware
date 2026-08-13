# Shopware 6 Plugin for Dropday

[![Shopware Version](https://img.shields.io/badge/Shopware-6.5%20%7C%206.6-189eff.svg)](https://www.shopware.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Seamlessly integrate your Shopware 6 store with [Dropday.io](https://dropday.io) for automated order fulfillment. This plugin automatically sends orders to Dropday when they reach a configurable status, streamlining your dropshipping workflow.

## Features

- 🚀 **Automatic Order Sync** - Orders are automatically sent to Dropday when they reach the configured status (paid, open, or in progress)
- ⚙️ **Flexible Configuration** - Configure API credentials, trigger states, and more per sales channel
- 🔄 **Manual Sync** - Manually sync individual orders from the order detail page
- 🌐 **Multi-language Support** - Full support for English and German
- 📊 **Connection Testing** - Test your API connection directly from the admin dashboard
- 🏪 **Multi Sales Channel** - Different configurations per sales channel
- 📝 **Detailed Logging** - Optional API call logging for debugging

## Requirements

- Shopware 6.5.x or 6.6.x
- PHP 8.1 or higher
- A [Dropday.io](https://dropday.io) account with API credentials

## Installation

### Via Composer (Recommended)

```bash
composer require dropday/shopware-integration
bin/console plugin:refresh
bin/console plugin:install --activate DropdayIntegration
bin/console cache:clear
```

### Manual Installation

1. Download the latest release from the [Releases](https://github.com/dropday-io/Shopware/releases) page
2. Extract the ZIP file to `custom/plugins/DropdayIntegration`
3. Run the following commands:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate DropdayIntegration
bin/console cache:clear
```

### Via Shopware Store

1. Log in to your Shopware admin panel
2. Go to **Extensions** → **My Extensions**
3. Search for "Dropday"
4. Click **Install** and then **Activate**

## Configuration

After installation, configure the plugin:

1. Go to **Settings** → **Plugins** → **Dropday Integration**
2. Or navigate to **Settings** → **Extensions** → **Dropday Integration**

### API Configuration

| Setting | Description |
|---------|-------------|
| **API Key** | Your Dropday API key (find it in your [Dropday Dashboard](https://dropday.io/dashboard) under Settings → API) |
| **Account ID** | Your Dropday Account ID |

### Order Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Order Sync** | Toggle automatic order synchronization | Enabled |
| **Trigger Order State** | When to send orders to Dropday (Open, Paid, In Progress) | Paid |
| **Order Source Name** | Name to identify this store in Dropday | "Shopware Store" |
| **Test Mode** | Mark all orders as test orders in Dropday | Disabled |

### Advanced Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Include Product Images** | Send product image URLs with orders | Enabled |
| **Enable API Logging** | Log all API calls for debugging | Disabled |

## Usage

### Automatic Order Sync

Once configured, orders will automatically be sent to Dropday when they enter the configured state. For example, if you set the trigger to "Paid", orders will be synced as soon as payment is confirmed.

### Manual Order Sync

You can manually sync individual orders:

1. Go to **Orders** → select an order
2. In the order detail view, find the **Dropday** card
3. Click **Send to Dropday**

### Testing the Connection

1. Go to **Settings** → **Dropday Integration**
2. Click **Test Connection**
3. A success or error message will be displayed

## Order Data Mapping

The following data is sent to Dropday for each order:

| Dropday Field | Shopware Source |
|---------------|-----------------|
| `external_id` | Order Number |
| `source` | Configured source name |
| `total` | Order Total |
| `email` | Customer Email |
| `shipping_address.first_name` | Shipping Address First Name |
| `shipping_address.last_name` | Shipping Address Last Name |
| `shipping_address.company_name` | Shipping Address Company |
| `shipping_address.address1` | Shipping Address Street |
| `shipping_address.address2` | Additional Address Line |
| `shipping_address.postcode` | Shipping Address ZIP |
| `shipping_address.city` | Shipping Address City |
| `shipping_address.state` | Shipping Address State |
| `shipping_address.country` | Shipping Address Country (ISO) |
| `shipping_address.phone` | Shipping Address Phone |
| `shipping.name` | Shipping Method Name |
| `shipping.cost` | Shipping Costs |
| `products[].external_id` | Product ID |
| `products[].name` | Product Name |
| `products[].reference` | Product Number (SKU) |
| `products[].ean13` | EAN |
| `products[].quantity` | Ordered Quantity |
| `products[].price` | Unit Price |
| `products[].brand` | Manufacturer |
| `products[].image_url` | Product Image URL |

## Troubleshooting

### Orders not syncing automatically

1. Verify the plugin is activated
2. Check that "Enable Order Sync" is turned on
3. Ensure the order reaches the configured trigger state
4. Enable API logging and check the logs at `var/log/`

### Connection test fails

1. Verify your API Key and Account ID are correct
2. Check that your server can reach `https://dropday.io`
3. Ensure there are no firewall rules blocking outbound HTTPS connections

### API Errors

Enable "API Logging" in the advanced settings to see detailed request/response information in your Shopware logs.

## Development

### Building Administration Assets

```bash
cd custom/plugins/DropdayIntegration
bin/build-administration.sh
```

### Running Tests

```bash
composer test
```

### Docker Development Environment

For local development, you can use Docker:

```bash
docker compose up -d
```

## API Reference

The plugin provides the following admin API endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/dropday/test-connection` | POST | Test API connection |
| `/api/dropday/sync-order/{orderId}` | POST | Sync a single order |
| `/api/dropday/sync-orders` | POST | Sync multiple orders |
| `/api/dropday/order-status` | POST | Get sync status for orders |

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- 📧 Email: [support@dropday.io](mailto:support@dropday.io)
- 📖 Documentation: [docs.dropday.io](https://docs.dropday.io)
- 🐛 Issues: [GitHub Issues](https://github.com/dropday-io/Shopware/issues)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## About Dropday

[Dropday](https://dropday.io) is a fulfillment automation platform that connects e-commerce stores with suppliers and fulfillment centers. Automate your dropshipping workflow, reduce manual work, and scale your business efficiently.

---

Made with ❤️ by [Dropday](https://dropday.io)
