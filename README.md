# Dropday for Shopware

Official Dropday plugin for [Shopware 6](https://www.shopware.com) — [Dropday](https://dropday.io) · [API docs](https://docs.dropday.io)

Send orders to dropshipping suppliers and track fulfilment status from your Shopware store. **Dropday** is an order automation platform that connects your store to dropshipping suppliers: you push an order through the API, Dropday routes it to the right supplier, handles the fulfilment flow, and keeps you updated on status.

This plugin ports the same order-push behaviour as the [official PrestaShop module](https://github.com/dropday-io/PrestaShop) and the [Laravel package](https://github.com/dropday-io/laravel) to Shopware 6, using Shopware's native extension points.

## Requirements

- PHP 8.1+
- Shopware 6.5, 6.6, or 6.7

## Installation

```bash
composer require dropday-io/shopware
bin/console plugin:refresh
bin/console plugin:install --activate DropdayShopware
bin/console cache:clear
```

Or upload/extract the plugin into `custom/plugins/DropdayShopware` and install it from **Settings > System > Plugins** in the administration.

## Configuration

Go to **Settings > System > Plugins > Dropday** and fill in:

| Field | Description |
|---|---|
| Live mode | When disabled, orders are sent flagged as `test: true` — Dropday validates them but does not route them to a real supplier. Mirrors the Laravel package's test mode. |
| Account ID | From your Dropday account settings. |
| API key | From your Dropday account settings. |
| API base URL | Defaults to `https://dropday.io/api/v1`. Only change this to point at a staging environment. |

These settings can also be configured per sales channel, since Shopware's system config supports sales-channel-specific overrides.

## Choosing when orders are sent (Flow Builder)

Unlike the PrestaShop module — which hard-codes a "select which order statuses trigger the API call" dropdown in its config screen — this plugin registers a **custom Flow Builder action**: *"Send order to Dropday"*.

To wire it up:

1. Go to **Settings > Shop > Flow Builder** and create a new flow.
2. Choose a trigger, e.g. `Order transaction state changed` → `Paid` (this is the equivalent of the PrestaShop module's default behaviour), or `Order state changed` → `Done`, or any other trigger/condition combination you need.
3. Add the **"Send order to Dropday"** action.
4. Save and activate the flow.

This is strictly more flexible than the PrestaShop module: you can add conditions (e.g. only for a specific sales channel, minimum order value, shipping country), combine multiple triggers, or attach additional actions (tagging, emails) to the same flow.

## What gets sent

On trigger, the order is mapped to the [Dropday "create order"](https://docs.dropday.io/orders/docs/) payload:

| Dropday field | Shopware source |
|---|---|
| `external_id` | Order number |
| `source` | Sales channel name |
| `total` | Order total |
| `shipping_cost` / `shipping.cost` | Sum of delivery shipping costs |
| `shipping.name` / `shipping.description` | Shipping method name/description |
| `shipping.note` | Customer comment |
| `shipping.delivery_date` | Earliest shipping date of the delivery |
| `email` | Order customer's email |
| `shipping_address.*` | Order delivery's shipping address (name, company, street, zip, city, state, country, phone) |
| `products[].external_id` | Product ID |
| `products[].name` | Line item label |
| `products[].reference` | Product number (SKU) |
| `products[].quantity` / `price` | Line item quantity / unit price |
| `products[].ean13` | Product EAN |
| `products[].stock_quantity` | Product stock |
| `products[].purchase_price` | Product purchase price |
| `products[].image_url` | Product cover image URL |
| `products[].brand` | Manufacturer name |
| `products[].category` | First assigned category name |
| `products[].custom` | Variant option group/option pairs, if any |
| `test` | `true` whenever Live mode is disabled |

After a successful call, the Dropday reference number returned by the API is stored on the order as a custom field (**Dropday > Dropday reference**, visible in the order detail's custom fields tab), so support staff can cross-reference orders without leaving Shopware.

## Error handling

Failures are logged (Dropday validation errors, transport errors, missing credentials) via Shopware's default logger and do not interrupt the rest of the flow sequence — matching the PrestaShop module's behaviour of logging failures without blocking the checkout/order process.

## Differences from the Laravel package

The Laravel package (`dropday-io/laravel`) is a thin, framework-agnostic API client (`createOrder`, `getOrders`, `getOrder`) meant for custom integrations. This plugin's `Dropday\Shopware\Api\DropdayApiClient` mirrors that same interface (plus `getProducts`) using Symfony's HTTP client, but is wired directly into Shopware's Flow Builder so no custom code is required to start sending orders.

## Development notes

- The Flow Builder administration registration in `src/Resources/app/administration/src/main.js` uses the `flowBuilderService` extension API. Verify the exact call signature against the [Shopware Flow Builder extension docs](https://developer.shopware.com/docs/guides/plugins/plugins/framework/flow/add-flow-action.html) for your target version before shipping to production, since this part of the Administration API has evolved across 6.5/6.6/6.7.
- No console command or "test connection" button is included yet; `DropdayApiClient::isConfigured()` is available for building one.

## License

MIT
