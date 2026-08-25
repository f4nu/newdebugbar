<?php

namespace NewDebugBar\Presentation;

/** Lists every reusable Blade component shown in the local Studio. */
final class StudioCatalog
{
    /**
     * @return array<string, array{title: string, description: string, components: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'foundations' => [
                'title' => 'Foundations',
                'description' => 'The small controls, labels, and visual primitives that establish the interface language.',
                'components' => [
                    'icon' => 'Renders a package-owned SVG symbol at an explicit size.',
                    'icon-button' => 'Provides an accessible icon-only action with consistent focus, hover, and disabled states.',
                    'inspector-action' => 'Shows a compact labeled action for contextual work inside a detail pane.',
                    'inspector-operation-badge' => 'Keeps HTTP methods and cache operations neutral, compact, and equal in width.',
                    'search-field' => 'Pairs an accessible search input with the established left-side search icon and spacing.',
                    'select-field' => 'Uses a native select with the shared field height, focus treatment, and chevron.',
                    'filter-tab' => 'Renders one selectable filter in either the quiet tab or segmented-control treatment.',
                    'filter-tabs' => 'Groups related filters with the correct semantics and shared variants.',
                    'empty-state' => 'Explains that a section or filtered result has no items without adding decoration.',
                    'popover-surface' => 'Provides the shared elevated surface for compact menus and supporting evidence.',
                    'theme-menu-item' => 'Provides the contextual light-or-dark theme action inside a menu.',
                    'theme-toggle' => 'Switches between light and dark themes with an accessible icon action.',
                    'section-heading' => 'Pairs a restrained section title with a close, readable description.',
                    'code-block' => 'Displays actual code or data with syntax highlighting and code typography.',
                ],
            ],
            'inspector' => [
                'title' => 'Inspector structure',
                'description' => 'The shared hierarchy for scanning a list, opening one item, and reading its evidence.',
                'components' => [
                    'inspector-definition-list' => 'Stacks labeled definition rows with one calm divider system.',
                    'inspector-definition-row' => 'Pairs a strong term with a readable value, including a danger tone when needed.',
                    'inspector-detail-back' => 'Returns from a mobile detail step to its list with a clear text action.',
                    'inspector-detail-empty' => 'Centers an instruction when no list item has been selected.',
                    'inspector-detail-header' => 'Keeps selected-item identity, optional actions, and quiet metadata in a stable header.',
                    'inspector-detail-pane' => 'Owns detail scrolling and adapts between desktop split view and mobile drill-in.',
                    'inspector-detail-tabs' => 'Places the shared segmented tabs at the center, or left when nearby controls require it.',
                    'inspector-evidence' => 'Adds an optional label above a syntax-highlighted evidence block.',
                    'inspector-explanation' => 'Explains only domain-specific meaning and a conditional next check.',
                    'inspector-fact' => 'Displays one compact labeled fact without turning it into a card or pill.',
                    'inspector-facts' => 'Aligns a small set of facts into stable responsive columns.',
                    'inspector-list-panel' => 'Owns list controls, list scrolling, and the filtered empty state.',
                    'inspector-source-fact' => 'Shows a source location as one labeled fact with optional navigation behavior.',
                    'inspector-source-link' => 'Makes a source location visibly clickable through a simple underline.',
                    'inspector-stack' => 'Presents a bounded application stack with copyable, readable frame labels.',
                    'inspector-workspace' => 'Provides the edge-to-edge split or focused workspace shared across inspectors.',
                ],
            ],
            'shell' => [
                'title' => 'Toolbar and navigation',
                'description' => 'Request-level chrome and responsive controls that stay useful without dominating the host page.',
                'components' => [
                    'corner-toolbar' => 'Composes the compact corner toolbar surface and its request identity.',
                    'mobile-request-metrics' => 'Keeps the most useful request metrics reachable on narrow screens.',
                    'mobile-toolbar-popover' => 'Moves secondary toolbar actions into an accessible mobile popover.',
                    'request-option' => 'Shows one saved request choice with stable identity and outcome facts.',
                    'request-switcher' => 'Opens the request picker while preserving the current request identity.',
                    'toolbar-anchor-preview' => 'Previews a valid toolbar drop target while the toolbar is being moved.',
                    'toolbar-button' => 'Makes a toolbar metric or section summary a consistent inspector action.',
                    'window-controls' => 'Groups expand, shrink, and close actions without shifting the toolbar.',
                ],
            ],
            'http-client' => [
                'title' => 'HTTP Client',
                'description' => 'The complete outbound-request grammar, from list scanning to request, response, and source evidence.',
                'components' => [
                    'http-client-controls' => 'Combines request counts, search, filtering, and ordering in the list header.',
                    'http-client-detail' => 'Coordinates the selected outbound request and its state-aware detail panels.',
                    'http-client-detail-tabs' => 'Orders Response, Request, and Source evidence with a deliberate default.',
                    'http-client-empty' => 'Asks the developer to select an outbound request before showing details.',
                    'http-client-header' => 'Keeps only the method and URL identity in the detail header.',
                    'http-client-list-item' => 'Aligns method, URL, outcome, and runtime on stable scan tracks.',
                    'http-client-no-response' => 'Explains a connection failure when no HTTP response exists.',
                    'http-client-request-panel' => 'Shows request host, headers, and body evidence without repeating response facts.',
                    'http-client-response-panel' => 'Shows status, runtime, response headers, and body evidence.',
                    'http-client-source-panel' => 'Shows the application call site and bounded stack that initiated the request.',
                    'http-client-workspace' => 'Composes the shared list and detail structure for outbound requests.',
                ],
            ],
            'cache' => [
                'title' => 'Cache',
                'description' => 'The cache-operation workspace, using the same scan and detail grammar with cache-specific evidence.',
                'components' => [
                    'cache-controls' => 'Combines cache counts, search, and operation filtering in the list header.',
                    'cache-detail' => 'Coordinates the selected cache operation and its detail panels.',
                    'cache-detail-tabs' => 'Switches between Overview, Raw, and Source with the shared segmented treatment.',
                    'cache-empty' => 'Asks the developer to select a cache operation before showing details.',
                    'cache-header' => 'Keeps the operation badge and key on one aligned identity row.',
                    'cache-list-item' => 'Shows one cache operation with an equal-width badge and compact key identity.',
                    'cache-overview-facts' => 'Displays the outcome, store, duration, and related cache facts.',
                    'cache-overview-panel' => 'Explains the selected cache operation through structured facts.',
                    'cache-raw-panel' => 'Shows retained cache values only when raw evidence adds something unique.',
                    'cache-source-panel' => 'Shows the application source and bounded call stack for the operation.',
                    'cache-workspace' => 'Composes the shared list and detail structure for cache activity.',
                ],
            ],
            'communications' => [
                'title' => 'Mail and notifications',
                'description' => 'Communication details that prioritize destination and outcome, then reveal payload and source evidence.',
                'components' => [
                    'mail-actions' => 'Groups useful mail actions without making the detail header noisy.',
                    'mail-header' => 'Shows message subject and delivery identity in the selected-mail header.',
                    'mail-message-details' => 'Organizes recipients, headers, attachments, and delivery facts.',
                    'mail-source-panel' => 'Shows where a mail message was created and the bounded stack behind it.',
                    'notification-delivery-panel' => 'Shows the actual outcome for each notification channel and destination.',
                    'notification-detail' => 'Coordinates the selected notification across delivery, data, and source views.',
                    'notification-header' => 'Shows notification identity, recipient context, and lifecycle actions without a separate attention badge.',
                    'notification-payload-panel' => 'Shows the application notification data without queue-internal noise.',
                    'notification-source-panel' => 'Shows where the notification began and its bounded application stack.',
                ],
            ],
            'framework-data' => [
                'title' => 'Framework evidence',
                'description' => 'Section-specific components for Laravel activity that still follow the shared evidence hierarchy.',
                'components' => [
                    'authorization-detail' => 'Explains one authorization decision through result, ability, subject, and source evidence.',
                    'event-detail' => 'Separates event overview, payload, and source evidence.',
                    'livewire-property-editor' => 'Edits a supported Livewire property with visible shortcut and validation states.',
                    'livewire-split-view' => 'Shares the stable list and detail geometry used by Livewire inspectors.',
                    'log-entry' => 'Shows one structured log record with level, message, context, source, and occurrences.',
                    'model-group' => 'Shows one model class with retrieved, write, and extra-retrieval counts.',
                    'model-group-detail' => 'Organizes selected-model records, writes, and application sources.',
                    'query-actions' => 'Keeps contextual query actions close to the selected SQL evidence.',
                    'query-execution' => 'Shows one logical query execution with timing, bindings, source, and EXPLAIN evidence.',
                    'query-section' => 'Coordinates query filters, grouped SQL rows, and selected execution details.',
                ],
            ],
        ];
    }
}
