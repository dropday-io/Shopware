<?php declare(strict_types=1);

namespace Dropday\Shopware\Api;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Thin wrapper around the Dropday REST API (https://docs.dropday.io).
 *
 * Mirrors the official dropday-io/laravel package: createOrder(), getOrders() and getOrder(),
 * plus getProducts() from the products endpoint.
 */
class DropdayApiClient
{
    private const CONFIG_DOMAIN = 'DropdayShopware.config.';
    private const DEFAULT_BASE_URL = 'https://dropday.io/api/v1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Submit a new order for fulfilment.
     *
     * If live mode is disabled for the sales channel, "test" is forced to true so Dropday
     * validates the payload without routing it to a real supplier.
     *
     * @throws DropdayApiException
     */
    public function createOrder(array $data, ?string $salesChannelId = null): array
    {
        if (!$this->isLiveMode($salesChannelId)) {
            $data['test'] = true;
        }

        return $this->request('POST', '/orders', $salesChannelId, ['json' => $data]);
    }

    /**
     * @throws DropdayApiException
     */
    public function getOrders(array $filters = [], ?string $salesChannelId = null): array
    {
        return $this->request('GET', '/orders', $salesChannelId, ['query' => $filters]);
    }

    /**
     * @throws DropdayApiException
     */
    public function getOrder(string $reference, ?string $salesChannelId = null): array
    {
        return $this->request('GET', '/orders/' . rawurlencode($reference), $salesChannelId);
    }

    /**
     * @throws DropdayApiException
     */
    public function getProducts(int $page = 1, ?string $salesChannelId = null): array
    {
        return $this->request('GET', '/products', $salesChannelId, ['query' => ['page' => $page]]);
    }

    public function isConfigured(?string $salesChannelId = null): bool
    {
        return (bool) $this->config('apiKey', $salesChannelId) && (bool) $this->config('accountId', $salesChannelId);
    }

    private function isLiveMode(?string $salesChannelId): bool
    {
        return (bool) $this->config('liveMode', $salesChannelId, false);
    }

    private function config(string $key, ?string $salesChannelId, mixed $default = null): mixed
    {
        $value = $this->systemConfigService->get(self::CONFIG_DOMAIN . $key, $salesChannelId);

        return $value ?? $default;
    }

    /**
     * @throws DropdayApiException
     */
    private function request(string $method, string $path, ?string $salesChannelId, array $options = []): array
    {
        $baseUrl = rtrim((string) $this->config('baseUrl', $salesChannelId, self::DEFAULT_BASE_URL), '/');
        $apiKey = (string) $this->config('apiKey', $salesChannelId, '');
        $accountId = (string) $this->config('accountId', $salesChannelId, '');

        if ($apiKey === '' || $accountId === '') {
            throw new DropdayApiException('Dropday API key or account ID is not configured for this sales channel.');
        }

        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'api-key' => $apiKey,
            'account-id' => $accountId,
        ]);

        try {
            $response = $this->httpClient->request($method, $baseUrl . $path, $options);
            $status = $response->getStatusCode();
            $content = $response->toArray(false);
        } catch (HttpClientExceptionInterface $exception) {
            $this->logger->error('[dropday] Transport error: ' . $exception->getMessage());

            throw new DropdayApiException('Could not reach the Dropday API: ' . $exception->getMessage(), 0, $exception);
        }

        if ($status === 422) {
            $this->logger->error('[dropday] Validation error: ' . json_encode($content['errors'] ?? $content, JSON_THROW_ON_ERROR));

            throw new DropdayApiException('Dropday validation error: ' . json_encode($content['errors'] ?? $content, JSON_THROW_ON_ERROR), 422);
        }

        if ($status >= 400) {
            $message = is_array($content) ? ($content['message'] ?? $content['error'] ?? 'Unknown error') : 'Unknown error';
            $this->logger->error(sprintf('[dropday] API error (%d): %s', $status, $message));

            throw new DropdayApiException(sprintf('Dropday API error (%d): %s', $status, $message), $status);
        }

        return $content;
    }
}
