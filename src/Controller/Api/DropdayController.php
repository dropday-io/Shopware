<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration\Controller\Api;

use Dropday\DropdayIntegration\Service\DropdayApiClient;
use Dropday\DropdayIntegration\Service\OrderSyncService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Admin API Controller for Dropday Integration
 *
 * @Route(defaults={"_routeScope"={"api"}})
 */
class DropdayController extends AbstractController
{
    private DropdayApiClient $apiClient;
    private OrderSyncService $orderSyncService;
    private EntityRepository $orderRepository;

    public function __construct(
        DropdayApiClient $apiClient,
        OrderSyncService $orderSyncService,
        EntityRepository $orderRepository
    ) {
        $this->apiClient = $apiClient;
        $this->orderSyncService = $orderSyncService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Test the Dropday API connection
     *
     * @Route("/api/dropday/test-connection", name="api.dropday.test-connection", methods={"POST"})
     */
    public function testConnection(Request $request): JsonResponse
    {
        $salesChannelId = $request->request->get('salesChannelId');
        $result = $this->apiClient->testConnection($salesChannelId);

        return new JsonResponse($result);
    }

    /**
     * Sync a single order to Dropday
     *
     * @Route("/api/dropday/sync-order/{orderId}", name="api.dropday.sync-order", methods={"POST"})
     */
    public function syncOrder(string $orderId, Context $context): JsonResponse
    {
        $result = $this->orderSyncService->syncOrderById($orderId, $context);

        return new JsonResponse($result);
    }

    /**
     * Sync multiple orders to Dropday
     *
     * @Route("/api/dropday/sync-orders", name="api.dropday.sync-orders", methods={"POST"})
     */
    public function syncOrders(Request $request, Context $context): JsonResponse
    {
        $orderIds = $request->request->all('orderIds');

        if (empty($orderIds)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No order IDs provided',
            ], 400);
        }

        $results = $this->orderSyncService->syncOrdersByIds($orderIds, $context);

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $failCount = count($results) - $successCount;

        return new JsonResponse([
            'success' => $failCount === 0,
            'message' => sprintf('%d orders synced successfully, %d failed', $successCount, $failCount),
            'results' => $results,
        ]);
    }

    /**
     * Get sync status for orders
     *
     * @Route("/api/dropday/order-status", name="api.dropday.order-status", methods={"POST"})
     */
    public function getOrderStatus(Request $request, Context $context): JsonResponse
    {
        $orderIds = $request->request->all('orderIds');

        if (empty($orderIds)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No order IDs provided',
            ], 400);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $orderIds));
        $criteria->addAssociation('customFields');

        $orders = $this->orderRepository->search($criteria, $context);

        $status = [];
        foreach ($orders as $order) {
            $customFields = $order->getCustomFields() ?? [];
            $status[$order->getId()] = [
                'orderNumber' => $order->getOrderNumber(),
                'synced' => isset($customFields['dropday_synced']),
                'dropdayReference' => $customFields['dropday_reference'] ?? null,
                'syncedAt' => $customFields['dropday_synced_at'] ?? null,
            ];
        }

        return new JsonResponse([
            'success' => true,
            'status' => $status,
        ]);
    }
}

