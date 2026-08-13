<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Order Sync Service
 *
 * Handles synchronization of orders to Dropday.
 */
class OrderSyncService
{
    private DropdayApiClient $apiClient;
    private OrderTransformer $orderTransformer;
    private EntityRepository $orderRepository;
    private LoggerInterface $logger;

    public function __construct(
        DropdayApiClient $apiClient,
        OrderTransformer $orderTransformer,
        EntityRepository $orderRepository,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->orderTransformer = $orderTransformer;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Sync an order to Dropday
     */
    public function syncOrder(OrderEntity $order): array
    {
        $this->logger->info('[Dropday] Syncing order', [
            'order_id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
        ]);

        try {
            $orderData = $this->orderTransformer->transform($order);
            $result = $this->apiClient->createOrder($orderData, $order->getSalesChannelId());

            if ($result['success']) {
                $this->logger->info('[Dropday] Order synced successfully', [
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                    'dropday_reference' => $result['reference'] ?? null,
                ]);
            } else {
                $this->logger->warning('[Dropday] Order sync failed', [
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                    'error' => $result['message'] ?? 'Unknown error',
                    'errors' => $result['errors'] ?? [],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('[Dropday] Order sync exception', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync an order by ID
     */
    public function syncOrderById(string $orderId, Context $context): array
    {
        $order = $this->loadOrder($orderId, $context);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found',
            ];
        }

        return $this->syncOrder($order);
    }

    /**
     * Sync multiple orders by their IDs
     */
    public function syncOrdersByIds(array $orderIds, Context $context): array
    {
        $results = [];

        foreach ($orderIds as $orderId) {
            $results[$orderId] = $this->syncOrderById($orderId, $context);
        }

        return $results;
    }

    /**
     * Load an order with all required associations
     */
    public function loadOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $this->addOrderAssociations($criteria);

        return $this->orderRepository->search($criteria, $context)->first();
    }

    /**
     * Load an order by order number
     */
    public function loadOrderByNumber(string $orderNumber, Context $context): ?OrderEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        $this->addOrderAssociations($criteria);

        return $this->orderRepository->search($criteria, $context)->first();
    }

    /**
     * Add required associations to order criteria
     */
    private function addOrderAssociations(Criteria $criteria): void
    {
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('billingAddress');
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('billingAddress.countryState');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.shippingOrderAddress');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingOrderAddress.countryState');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.cover');
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('transactions.stateMachineState');
    }
}

