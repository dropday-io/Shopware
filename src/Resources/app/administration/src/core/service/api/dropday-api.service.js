const ApiService = Shopware.Classes.ApiService;

/**
 * Dropday API Service
 *
 * Service class for communication with the Dropday admin API endpoints.
 */
class DropdayApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'dropday') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'DropdayApiService';
    }

    /**
     * Test the connection to Dropday API
     */
    testConnection(salesChannelId = null) {
        const headers = this.getBasicHeaders();
        const data = salesChannelId ? { salesChannelId } : {};

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/test-connection`,
            data,
            { headers }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * Sync a single order to Dropday
     */
    syncOrder(orderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/sync-order/${orderId}`,
            {},
            { headers }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * Sync multiple orders to Dropday
     */
    syncOrders(orderIds) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/sync-orders`,
            { orderIds },
            { headers }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * Get sync status for orders
     */
    getOrderStatus(orderIds) {
        const headers = this.getBasicHeaders();

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/order-status`,
            { orderIds },
            { headers }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default DropdayApiService;

// Register the service
Shopware.Service().register('DropdayApiService', (container) => {
    const initContainer = Shopware.Application.getContainer('init');
    return new DropdayApiService(initContainer.httpClient, container.loginService);
});

