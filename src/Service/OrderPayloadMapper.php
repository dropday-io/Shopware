<?php declare(strict_types=1);

namespace Dropday\Shopware\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Builds a Dropday "create order" payload (https://docs.dropday.io/orders/docs/) from a
 * fully loaded Shopware OrderEntity, mirroring the field mapping used by the Dropday
 * PrestaShop module (getOrderData()).
 */
class OrderPayloadMapper
{
    public function __construct(
        private readonly EntityRepository $productRepository,
    ) {
    }

    public function map(OrderEntity $order, Context $context): array
    {
        $delivery = $order->getDeliveries()?->first();
        $shippingAddress = $delivery?->getShippingOrderAddress();
        $shippingMethod = $delivery?->getShippingMethod();

        $payload = [
            'external_id' => (string) $order->getOrderNumber(),
            'source' => $order->getSalesChannel()?->getName() ?? 'Shopware',
            'total' => round((float) $order->getAmountTotal(), 2),
            'shipping_cost' => round($this->shippingCost($order), 2),
            'shipping' => [
                'name' => $this->translated($shippingMethod, 'getName') ?? '',
                'description' => $this->translated($shippingMethod, 'getDescription') ?? '',
                'cost' => round($this->shippingCost($order), 2),
                'note' => (string) ($order->getCustomerComment() ?? ''),
                'delivery_date' => $delivery?->getShippingDateEarliest()?->format('d-m-Y') ?? '',
            ],
            'email' => $order->getOrderCustomer()?->getEmail() ?? '',
            'shipping_address' => $this->mapAddress($shippingAddress),
            'products' => $this->mapProducts($order, $context),
        ];

        return $payload;
    }

    private function shippingCost(OrderEntity $order): float
    {
        $total = 0.0;

        foreach ($order->getDeliveries() ?? [] as $orderDelivery) {
            $total += (float) $orderDelivery->getShippingCosts()->getTotalPrice();
        }

        return $total;
    }

    private function mapAddress(?OrderAddressEntity $address): array
    {
        if ($address === null) {
            return [];
        }

        $mapped = [
            'first_name' => $address->getFirstName(),
            'last_name' => $address->getLastName(),
            'company_name' => $address->getCompany(),
            'address1' => trim($address->getStreet()),
            'address2' => $address->getAdditionalAddressLine1(),
            'postcode' => $address->getZipcode(),
            'city' => $address->getCity(),
            'country' => $this->translated($address->getCountry(), 'getName') ?? $address->getCountry()?->getIso() ?? '',
            'phone' => $address->getPhoneNumber(),
        ];

        $state = $address->getCountryState();
        if ($state !== null) {
            $mapped['state'] = $this->translated($state, 'getName');
        }

        return array_filter($mapped, static fn ($value) => $value !== null && $value !== '');
    }

    private function mapProducts(OrderEntity $order, Context $context): array
    {
        $lineItems = $order->getLineItems();
        if ($lineItems === null) {
            return [];
        }

        $productLineItems = $lineItems->filter(
            static fn (OrderLineItemEntity $lineItem) => $lineItem->getType() === 'product'
        );

        $productIds = array_values(array_unique(array_filter(
            $productLineItems->map(static fn (OrderLineItemEntity $lineItem) => $lineItem->getProductId())
        )));

        $products = $this->loadProducts($productIds, $context);

        $result = [];
        foreach ($productLineItems as $lineItem) {
            $result[] = $this->mapProductLine($lineItem, $productIds !== [] ? $products->get((string) $lineItem->getProductId()) : null);
        }

        return $result;
    }

    private function mapProductLine(OrderLineItemEntity $lineItem, ?ProductEntity $product): array
    {
        $payload = $lineItem->getPayload() ?? [];

        $line = [
            'external_id' => (string) ($lineItem->getProductId() ?? $lineItem->getId()),
            'name' => (string) $lineItem->getLabel(),
            'reference' => $product?->getProductNumber() ?? (string) ($payload['productNumber'] ?? ''),
            'quantity' => $lineItem->getQuantity(),
            'price' => round((float) ($lineItem->getPrice()?->getUnitPrice() ?? 0.0), 2),
        ];

        if ($product !== null) {
            if (!empty($product->getEan())) {
                $line['ean13'] = $product->getEan();
            }

            if ($product->getStock() !== null) {
                $line['stock_quantity'] = $product->getStock();
            }

            $purchasePrice = $product->getPurchasePrices()?->first()?->getGross();
            if ($purchasePrice !== null) {
                $line['purchase_price'] = round((float) $purchasePrice, 2);
            }

            $cover = $product->getCover()?->getMedia();
            if ($cover !== null && $cover->getUrl()) {
                $line['image_url'] = $cover->getUrl();
            }

            $manufacturerName = $this->translated($product->getManufacturer(), 'getName');
            if ($manufacturerName) {
                $line['brand'] = $manufacturerName;
            }

            $category = $product->getCategories()?->first();
            $categoryName = $this->translated($category, 'getName');
            if ($categoryName) {
                $line['category'] = $categoryName;
            }
        }

        $custom = $this->mapCustomOptions($payload);
        if ($custom !== []) {
            $line['custom'] = $custom;
        }

        return $line;
    }

    private function mapCustomOptions(array $payload): array
    {
        $custom = [];

        foreach ($payload['options'] ?? [] as $option) {
            if (isset($option['group'], $option['option'])) {
                $custom[(string) $option['group']] = (string) $option['option'];
            }
        }

        return $custom;
    }

    private function loadProducts(array $productIds, Context $context): EntityCollection
    {
        if ($productIds === []) {
            return new EntityCollection([]);
        }

        $criteria = new Criteria($productIds);
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('cover.media');
        $criteria->addAssociation('purchasePrices');

        return $this->productRepository->search($criteria, $context)->getEntities();
    }

    /**
     * @param object|null $entity
     */
    private function translated(?object $entity, string $getter): ?string
    {
        if ($entity === null || !method_exists($entity, $getter)) {
            return null;
        }

        $value = $entity->$getter();

        return $value !== null && $value !== '' ? (string) $value : null;
    }
}
