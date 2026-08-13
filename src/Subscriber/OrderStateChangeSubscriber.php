<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration\Subscriber;

use Dropday\DropdayIntegration\Service\OrderSyncService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Order State Change Subscriber
 *
 * Listens for order state changes and triggers synchronization to Dropday.
 */
class OrderStateChangeSubscriber implements EventSubscriberInterface
{
    private const CONFIG_PREFIX = 'DropdayIntegration.config.';

    private OrderSyncService $orderSyncService;
    private SystemConfigService $systemConfigService;
    private EntityRepository $orderRepository;
    private LoggerInterface $logger;

    public function __construct(
        OrderSyncService $orderSyncService,
        SystemConfigService $systemConfigService,
        EntityRepository $orderRepository,
        LoggerInterface $logger
    ) {
        $this->orderSyncService = $orderSyncService;
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order.state.open' => 'onOrderStateChange',
            'state_enter.order.state.in_progress' => 'onOrderStateChange',
            'state_enter.order.state.completed' => 'onOrderStateChange',
            'state_enter.order.state.cancelled' => 'onOrderStateChange',
            'state_enter.order_transaction.state.paid' => 'onOrderTransactionPaid',
            'state_enter.order_transaction.state.paid_partially' => 'onOrderTransactionPaid',
        ];
    }

    /**
     * Handle order state changes
     */
    public function onOrderStateChange(OrderStateMachineStateChangeEvent $event): void
    {
        $order = $event->getOrder();
        $salesChannelId = $order->getSalesChannelId();

        // Check if sync is enabled
        if (!$this->isEnabled($salesChannelId)) {
            return;
        }

        // Get the configured trigger state
        $triggerState = $this->getTriggerState($salesChannelId);

        // Get the new state technical name
        $newState = $order->getStateMachineState()?->getTechnicalName();

        $this->logger->debug('[Dropday] Order state changed', [
            'order_id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'new_state' => $newState,
            'trigger_state' => $triggerState,
        ]);

        // Check if this state should trigger sync
        if ($newState !== $triggerState) {
            return;
        }

        $this->syncOrder($order, $event);
    }

    /**
     * Handle payment state changes (for "paid" trigger)
     */
    public function onOrderTransactionPaid($event): void
    {
        $order = $event->getOrder();
        $salesChannelId = $order->getSalesChannelId();

        // Check if sync is enabled and trigger is "paid"
        if (!$this->isEnabled($salesChannelId)) {
            return;
        }

        $triggerState = $this->getTriggerState($salesChannelId);

        if ($triggerState !== 'paid') {
            return;
        }

        $this->logger->debug('[Dropday] Order payment received', [
            'order_id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
        ]);

        $this->syncOrder($order, $event);
    }

    /**
     * Sync order to Dropday
     */
    private function syncOrder(OrderEntity $order, $event): void
    {
        // Load full order with associations
        $fullOrder = $this->orderSyncService->loadOrder($order->getId(), $event->getContext());

        if (!$fullOrder) {
            $this->logger->error('[Dropday] Could not load order for sync', [
                'order_id' => $order->getId(),
            ]);
            return;
        }

        $result = $this->orderSyncService->syncOrder($fullOrder);

        if (!$result['success']) {
            $this->logger->error('[Dropday] Failed to sync order', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'error' => $result['message'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Check if Dropday sync is enabled
     */
    private function isEnabled(?string $salesChannelId = null): bool
    {
        return (bool) $this->systemConfigService->get(self::CONFIG_PREFIX . 'enabled', $salesChannelId);
    }

    /**
     * Get the configured trigger state
     */
    private function getTriggerState(?string $salesChannelId = null): string
    {
        return $this->systemConfigService->get(self::CONFIG_PREFIX . 'triggerState', $salesChannelId) ?: 'paid';
    }
}

