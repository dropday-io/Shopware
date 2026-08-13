import template from './sw-order-detail-base.html.twig';

const { Component, Mixin } = Shopware;

/**
 * Order Detail Base Extension
 *
 * Adds Dropday sync button to order detail page.
 */
Component.override('sw-order-detail-base', {
    template,

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            dropdaySyncing: false,
        };
    },

    computed: {
        dropdayApiService() {
            return Shopware.Service('DropdayApiService');
        },
    },

    methods: {
        async syncToDropday() {
            if (!this.order || !this.order.id) {
                return;
            }

            this.dropdaySyncing = true;

            try {
                const response = await this.dropdayApiService.syncOrder(this.order.id);

                if (response.success) {
                    this.createNotificationSuccess({
                        title: this.$tc('dropday-integration.order.syncSuccess'),
                        message: this.$tc('dropday-integration.order.syncSuccessMessage'),
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('dropday-integration.order.syncError'),
                        message: response.message || this.$tc('dropday-integration.order.syncErrorMessage'),
                    });
                }
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('dropday-integration.order.syncError'),
                    message: error.message || this.$tc('dropday-integration.order.syncErrorMessage'),
                });
            } finally {
                this.dropdaySyncing = false;
            }
        },
    },
});

