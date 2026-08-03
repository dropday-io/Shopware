import template from './sw-flow-dropday-send-order-modal.html.twig';

// Flow Builder always dynamically resolves a modal component named after the action
// (see sw-flow-sequence-modal.html.twig: <component :is="modalName">), even for actions
// that need no configuration - without one, clicking the action silently does nothing.
// This action has no config, so the modal is just a confirmation dialog.
export default {
    template,

    emits: [
        'modal-close',
        'process-finish',
    ],

    props: {
        sequence: {
            type: Object,
            required: true,
        },
    },

    methods: {
        onClose() {
            this.$emit('modal-close');
        },

        onAddAction() {
            this.sequence.config = {};
            this.$emit('process-finish', this.sequence);
        },
    },
};
