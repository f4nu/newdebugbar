<?php

namespace NewDebugBar\Tests\Support;

/** Classifies every root Blade component by its intended ownership boundary. */
final class ViewComponentInventory
{
    /** @var list<string> */
    public const SHARED = [
        'icon',
        'icon-button',
        'inspector-action',
        'search-field',
        'select-field',
        'filter-tabs',
        'filter-tab',
        'popover-surface',
        'empty-state',
        'inspector-operation-badge',
        'code-block',
        'inspector-source-link',
        'section-heading',
        'inspector-definition-list',
        'inspector-definition-row',
        'inspector-detail-empty',
        'inspector-evidence',
        'inspector-explanation',
        'inspector-facts',
        'inspector-fact',
        'inspector-source-fact',
        'inspector-source-panel',
        'inspector-stack',
        'inspector-detail-back',
        'inspector-detail-header',
        'inspector-detail-pane',
        'inspector-detail-tabs',
        'inspector-list-controls',
        'inspector-list-panel',
        'inspector-workspace',
    ];

    /** @var array<string, list<string>> */
    public const PRIVATE_BY_OWNER = [
        'authorization' => [
            'authorization-detail',
        ],
        'cache' => [
            'cache-controls',
            'cache-detail',
            'cache-detail-tabs',
            'cache-header',
            'cache-list-item',
            'cache-overview-facts',
            'cache-overview-panel',
            'cache-raw-panel',
            'cache-workspace',
        ],
        'events' => [
            'event-detail',
        ],
        'exceptions' => [
            'exception-detail',
            'exception-list-item',
        ],
        'http-client' => [
            'http-client-controls',
            'http-client-detail',
            'http-client-detail-tabs',
            'http-client-header',
            'http-client-list-item',
            'http-client-no-response',
            'http-client-request-panel',
            'http-client-response-panel',
            'http-client-workspace',
        ],
        'livewire' => [
            'livewire-property-editor',
        ],
        'logs' => [
            'log-entry',
        ],
        'mail' => [
            'mail-actions',
            'mail-header',
            'mail-message-details',
        ],
        'models' => [
            'model-group',
            'model-group-detail',
        ],
        'notifications' => [
            'notification-delivery-panel',
            'notification-detail',
            'notification-header',
            'notification-payload-panel',
        ],
        'queries' => [
            'query-detail',
            'query-section',
        ],
        'shell' => [
            'corner-toolbar',
            'mobile-request-metrics',
            'mobile-toolbar-popover',
            'request-option',
            'request-switcher',
            'theme-menu-item',
            'theme-toggle',
            'toolbar-anchor-preview',
            'toolbar-button',
            'window-controls',
        ],
        'validation' => [
            'validation-entry',
        ],
    ];
}
