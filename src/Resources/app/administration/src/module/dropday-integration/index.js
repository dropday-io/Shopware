/**
 * Dropday Integration Module
 */
import './page/dropday-dashboard';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('dropday-integration', {
    type: 'plugin',
    name: 'Dropday',
    title: 'dropday-integration.general.mainMenuItemGeneral',
    description: 'dropday-integration.general.descriptionTextModule',
    color: '#ff6b35',
    icon: 'regular-shopping-bag',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        dashboard: {
            component: 'dropday-dashboard',
            path: 'dashboard',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'dropday.integration.dashboard',
            icon: 'regular-shopping-bag',
            name: 'dropday-integration.general.mainMenuItemGeneral',
        },
    ],
});

