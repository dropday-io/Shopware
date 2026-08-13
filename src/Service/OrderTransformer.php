<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration\Service;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Order Transformer
 *
 * Transforms Shopware order entities into the Dropday API format.
 */
class OrderTransformer
{
    private const CONFIG_PREFIX = 'DropdayIntegration.config.';

    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Transform a Shopware order to Dropday API format
     */
    public function transform(OrderEntity $order): array
    {
        $salesChannelId = $order->getSalesChannelId();

        $dropDayOrder = [
            'external_id' => $order->getOrderNumber(),
            'source' => $this->getConfig('source', $salesChannelId) ?: 'Shopware Store',
            'test' => (bool) $this->getConfig('testMode', $salesChannelId),
            'total' => $order->getAmountTotal(),
            'email' => $this->getCustomerEmail($order),
            'shipping_address' => $this->transformShippingAddress($order),
            'products' => $this->transformLineItems($order),
        ];

        // Add shipping information
        $shippingInfo = $this->transformShippingInfo($order);
        if (!empty($shippingInfo)) {
            $dropDayOrder['shipping'] = $shippingInfo;
        }

        return $dropDayOrder;
    }

    /**
     * Get the customer email from the order
     */
    private function getCustomerEmail(OrderEntity $order): ?string
    {
        $orderCustomer = $order->getOrderCustomer();

        if ($orderCustomer) {
            return $orderCustomer->getEmail();
        }

        return null;
    }

    /**
     * Transform shipping address to Dropday format
     */
    private function transformShippingAddress(OrderEntity $order): array
    {
        $deliveries = $order->getDeliveries();
        $address = null;

        // Get shipping address from delivery
        if ($deliveries && $deliveries->count() > 0) {
            /** @var OrderDeliveryEntity $delivery */
            $delivery = $deliveries->first();
            $shippingAddress = $delivery->getShippingOrderAddress();

            if ($shippingAddress) {
                $address = $shippingAddress;
            }
        }

        // Fallback to billing address
        if (!$address) {
            $address = $order->getBillingAddress();
        }

        if (!$address) {
            return [];
        }

        $shippingData = [
            'first_name' => $address->getFirstName(),
            'last_name' => $address->getLastName(),
            'address1' => $address->getStreet(),
            'postcode' => $address->getZipcode(),
            'city' => $address->getCity(),
            'country' => $this->getCountryIso($address),
        ];

        // Optional fields
        if ($address->getCompany()) {
            $shippingData['company_name'] = $address->getCompany();
        }

        if ($address->getAdditionalAddressLine1()) {
            $shippingData['address2'] = $address->getAdditionalAddressLine1();
        }

        if ($address->getPhoneNumber()) {
            $shippingData['phone'] = $address->getPhoneNumber();
        }

        $state = $address->getCountryState();
        if ($state) {
            $shippingData['state'] = $state->getShortCode() ?: $state->getName();
        }

        return $shippingData;
    }

    /**
     * Get the ISO code for a country
     */
    private function getCountryIso($address): string
    {
        $country = $address->getCountry();

        if ($country) {
            return $country->getIso() ?? $country->getIso3() ?? '';
        }

        return '';
    }

    /**
     * Transform shipping information
     */
    private function transformShippingInfo(OrderEntity $order): array
    {
        $deliveries = $order->getDeliveries();

        if (!$deliveries || $deliveries->count() === 0) {
            return [];
        }

        /** @var OrderDeliveryEntity $delivery */
        $delivery = $deliveries->first();
        $shippingMethod = $delivery->getShippingMethod();

        $shippingInfo = [];

        if ($shippingMethod) {
            $shippingInfo['name'] = $shippingMethod->getName();
            $shippingInfo['description'] = $shippingMethod->getDescription() ?: $shippingMethod->getName();
        }

        // Get shipping costs
        $shippingCosts = $delivery->getShippingCosts();
        if ($shippingCosts) {
            $shippingInfo['cost'] = $shippingCosts->getTotalPrice();
        }

        return $shippingInfo;
    }

    /**
     * Transform order line items to Dropday product format
     */
    private function transformLineItems(OrderEntity $order): array
    {
        $lineItems = $order->getLineItems();

        if (!$lineItems) {
            return [];
        }

        $products = [];
        $includeImages = (bool) $this->getConfig('includeProductImages', $order->getSalesChannelId());

        foreach ($lineItems as $lineItem) {
            // Only process product line items
            if ($lineItem->getType() !== 'product') {
                continue;
            }

            $product = $this->transformLineItem($lineItem, $includeImages);
            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * Transform a single line item to Dropday product format
     */
    private function transformLineItem(OrderLineItemEntity $lineItem, bool $includeImages): ?array
    {
        $payload = $lineItem->getPayload() ?? [];

        $product = [
            'external_id' => $lineItem->getReferencedId() ?: $lineItem->getId(),
            'name' => $lineItem->getLabel(),
            'quantity' => $lineItem->getQuantity(),
            'price' => $lineItem->getUnitPrice(),
        ];

        // Add product number/reference if available
        if (isset($payload['productNumber'])) {
            $product['reference'] = $payload['productNumber'];
        }

        // Add EAN if available
        if (isset($payload['ean']) && !empty($payload['ean'])) {
            $product['ean13'] = $payload['ean'];
        }

        // Add manufacturer/brand if available
        if (isset($payload['manufacturerName']) && !empty($payload['manufacturerName'])) {
            $product['brand'] = $payload['manufacturerName'];
        }

        // Add purchase price if available
        if (isset($payload['purchasePrices']) && !empty($payload['purchasePrices'])) {
            $purchasePrices = json_decode($payload['purchasePrices'], true);
            if (isset($purchasePrices['currencyId']) && isset($purchasePrices['net'])) {
                $product['purchase_price'] = $purchasePrices['gross'] ?? $purchasePrices['net'];
            }
        }

        // Add stock quantity if available
        if (isset($payload['stock'])) {
            $product['stock_quantity'] = (int) $payload['stock'];
        }

        // Add image URL if enabled and available
        if ($includeImages) {
            $imageUrl = $this->getProductImageUrl($lineItem);
            if ($imageUrl) {
                $product['image_url'] = $imageUrl;
            }
        }

        // Add custom fields if available
        if (isset($payload['customFields']) && !empty($payload['customFields'])) {
            $product['custom'] = $payload['customFields'];
        }

        return $product;
    }

    /**
     * Get the product image URL from a line item
     */
    private function getProductImageUrl(OrderLineItemEntity $lineItem): ?string
    {
        // Check cover image first
        $cover = $lineItem->getCover();
        if ($cover && $cover->getUrl()) {
            return $cover->getUrl();
        }

        // Try to get from payload
        $payload = $lineItem->getPayload() ?? [];
        if (isset($payload['cover']['url'])) {
            return $payload['cover']['url'];
        }

        return null;
    }

    /**
     * Get a configuration value
     */
    private function getConfig(string $key, ?string $salesChannelId = null): mixed
    {
        return $this->systemConfigService->get(self::CONFIG_PREFIX . $key, $salesChannelId);
    }
}

