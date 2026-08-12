<?php declare(strict_types=1);

namespace Dropday\Shopware\Flow;

use Dropday\Shopware\Api\DropdayApiClient;
use Dropday\Shopware\Api\DropdayApiException;
use Dropday\Shopware\Service\OrderPayloadMapper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\OrderAware;

/**
 * Custom Flow Builder action: "Send order to Dropday".
 *
 * This is the Shopware equivalent of the Dropday PrestaShop module's
 * `hookActionOrderStatusUpdate` / order-status trigger configuration. Instead of a fixed
 * config field listing which order statuses trigger the call, merchants attach this action
 * to any Flow Builder flow (e.g. "Order state changed to Done", "Order transaction state
 * changed to Paid", with optional conditions) via Settings > Shop > Flow Builder.
 */
class SendOrderToDropdayAction extends FlowAction
{
    public function __construct(
        private readonly OrderPayloadMapper $payloadMapper,
        private readonly DropdayApiClient $apiClient,
        private readonly EntityRepository $orderRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getName(): string
    {
        return 'action.dropday.send.order';
    }

    public function requirements(): array
    {
        return [OrderAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {
        if (!$flow->hasData(OrderAware::ORDER_ID)) {
            return;
        }

        $orderId = $flow->getData(OrderAware::ORDER_ID);
        $context = $flow->getContext();

        $order = $this->loadOrder($orderId, $context);
        if ($order === null) {
            $this->logger->warning(sprintf('[dropday] Order %s could not be loaded, skipping.', $orderId));

            return;
        }

        try {
            $payload = $this->payloadMapper->map($order, $context);
            $result = $this->apiClient->createOrder($payload, $order->getSalesChannelId());

            $this->logger->info(sprintf(
                '[dropday] Order %s sent to Dropday: %s',
                $order->getOrderNumber(),
                $result['message'] ?? 'ok'
            ));

            if (isset($result['reference'])) {
                $this->storeReference($order, (string) $result['reference'], $context);
            }
        } catch (DropdayApiException $exception) {
            $this->logger->error(sprintf(
                '[dropday] Failed to send order %s to Dropday: %s',
                $order->getOrderNumber(),
                $exception->getMessage()
            ));
        }
    }

    private function loadOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('salesChannel');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingOrderAddress.countryState');

        return $this->orderRepository->search($criteria, $context)->getEntities()->first();
    }

    private function storeReference(OrderEntity $order, string $reference, Context $context): void
    {
        $this->orderRepository->update([[
            'id' => $order->getId(),
            'customFields' => array_merge($order->getCustomFields() ?? [], [
                'dropday_reference' => $reference,
            ]),
        ]], $context);
    }
}
