<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Dropday API Client
 *
 * Handles all communication with the Dropday.io REST API.
 */
class DropdayApiClient
{
    private const API_BASE_URL = 'https://dropday.io/api/v1';
    private const CONFIG_PREFIX = 'DropdayIntegration.config.';

    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * Test the API connection with the configured credentials
     */
    public function testConnection(?string $salesChannelId = null): array
    {
        try {
            $response = $this->request('GET', '', [], $salesChannelId);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'response' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create an order in Dropday
     */
    public function createOrder(array $orderData, ?string $salesChannelId = null): array
    {
        $this->logIfEnabled('Creating order in Dropday', ['external_id' => $orderData['external_id'] ?? null], $salesChannelId);

        try {
            $response = $this->request('POST', '/orders', $orderData, $salesChannelId);

            $this->logIfEnabled('Order created successfully', [
                'external_id' => $orderData['external_id'] ?? null,
                'reference' => $response['reference'] ?? null,
            ], $salesChannelId);

            return [
                'success' => true,
                'message' => $response['message'] ?? 'Order created',
                'reference' => $response['reference'] ?? null,
                'response' => $response,
            ];
        } catch (DropdayApiException $e) {
            $this->logger->error('Failed to create order in Dropday', [
                'external_id' => $orderData['external_id'] ?? null,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];
        }
    }

    /**
     * Make an HTTP request to the Dropday API
     *
     * @throws DropdayApiException
     */
    private function request(string $method, string $endpoint, array $data = [], ?string $salesChannelId = null): array
    {
        $apiKey = $this->getConfig('apiKey', $salesChannelId);
        $accountId = $this->getConfig('accountId', $salesChannelId);

        if (empty($apiKey) || empty($accountId)) {
            throw new DropdayApiException('API Key and Account ID must be configured');
        }

        $url = self::API_BASE_URL . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . $apiKey,
            'account-id: ' . $accountId,
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            throw new DropdayApiException('cURL Error: ' . $curlError);
        }

        $responseData = json_decode($response, true) ?? [];

        $this->logIfEnabled('API Response', [
            'method' => $method,
            'endpoint' => $endpoint,
            'http_code' => $httpCode,
            'response' => $responseData,
        ], $salesChannelId);

        if ($httpCode === 422) {
            throw new DropdayApiException(
                $responseData['message'] ?? 'Validation error',
                $responseData['errors'] ?? [],
                $httpCode
            );
        }

        if ($httpCode >= 400) {
            throw new DropdayApiException(
                $responseData['message'] ?? 'API request failed with status ' . $httpCode,
                $responseData['errors'] ?? [],
                $httpCode
            );
        }

        return $responseData;
    }

    /**
     * Get a configuration value
     */
    private function getConfig(string $key, ?string $salesChannelId = null): mixed
    {
        return $this->systemConfigService->get(self::CONFIG_PREFIX . $key, $salesChannelId);
    }

    /**
     * Log message if logging is enabled
     */
    private function logIfEnabled(string $message, array $context = [], ?string $salesChannelId = null): void
    {
        if ($this->getConfig('logApiCalls', $salesChannelId)) {
            $this->logger->info('[Dropday] ' . $message, $context);
        }
    }
}

