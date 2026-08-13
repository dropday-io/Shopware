import template from './dropday-dashboard.html.twig';
import './dropday-dashboard.scss';

const { Component, Mixin } = Shopware;

/**
 * Dropday Dashboard Component
 */
Component.register('dropday-dashboard', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            isLoading: false,
            isTesting: false,
            connectionStatus: null,
        };
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    methods: {
        async testConnection() {
            this.isTesting = true;
            this.connectionStatus = null;

            try {
                const response = await Shopware.Service('DropdayApiService').testConnection();

                if (response.success) {
                    this.connectionStatus = 'success';
                    this.createNotificationSuccess({
                        title: this.$tc('dropday-integration.dashboard.connectionSuccess'),
                        message: this.$tc('dropday-integration.dashboard.connectionSuccessMessage'),
                    });
                } else {
                    this.connectionStatus = 'error';
                    this.createNotificationError({
                        title: this.$tc('dropday-integration.dashboard.connectionError'),
                        message: response.message || this.$tc('dropday-integration.dashboard.connectionErrorMessage'),
                    });
                }
            } catch (error) {
                this.connectionStatus = 'error';
                this.createNotificationError({
                    title: this.$tc('dropday-integration.dashboard.connectionError'),
                    message: error.message || this.$tc('dropday-integration.dashboard.connectionErrorMessage'),
                });
            } finally {
                this.isTesting = false;
            }
        },

        openDropdayDashboard() {
            window.open('https://dropday.io/dashboard', '_blank');
        },

        openDocumentation() {
            window.open('https://docs.dropday.io', '_blank');
        },
    },
});

