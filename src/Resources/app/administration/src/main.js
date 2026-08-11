import enGB from './snippet/en-GB.json';
import deDE from './snippet/de-DE.json';
import SwFlowDropdaySendOrderModal from './component/sw-flow-dropday-send-order-modal';

Shopware.Locale.extend('en-GB', enGB);
Shopware.Locale.extend('de-DE', deDE);

// Registered under the name Flow Builder derives from the action name:
// "action.dropday.send.order" -> "sw-flow-dropday-send-order-modal"
// (see sw-flow-sequence-action's getActionModalName()).
Shopware.Component.register('sw-flow-dropday-send-order-modal', SwFlowDropdaySendOrderModal);

// The backend already returns "action.dropday.send.order" from /api/_info/flow-actions.json
// (any flow.action-tagged service is picked up automatically), so it appears in the Flow
// Builder action picker without any admin JS at all - just with a generic "?" icon and an
// "unknown action" label, grouped under "General". This registers the proper label, icon
// and group instead, keyed by flowBuilderService's derived key: strip the "action." prefix
// from the action name and camelCase the remaining dot-separated parts (so
// "action.dropday.send.order" -> "dropdaySendOrder").
const flowBuilderService = Shopware.Service('flowBuilderService');

flowBuilderService.addLabels({
    dropdaySendOrder: 'sw-flow.actions.labelDropdaySendOrder',
});

flowBuilderService.addIcons({
    dropdaySendOrder: 'regular-shopping-bag-alt',
});

flowBuilderService.addActionGroupMapping({
    'action.dropday.send.order': 'order',
});
