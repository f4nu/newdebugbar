<?php

namespace NewDebugBar\Presentation;

use Illuminate\Support\Str;
use LogicException;

/** Lists every reusable Blade component shown in the local Studio. */
final class StudioCatalog
{
    public const DEFAULT_COMPONENT = 'search-field';

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
                    'http-client-controls' => 'Combines the request count, search, and one status filter in a stable list header.',
                    'http-client-detail' => 'Coordinates the selected outbound request and its state-aware detail panels.',
                    'http-client-detail-tabs' => 'Orders Response, Request, and Source evidence with a deliberate default.',
                    'http-client-empty' => 'Explains that no outbound requests were captured and what activity would appear here.',
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
                    'cache-empty' => 'Explains that no cache operations were captured and what activity would appear here.',
                    'cache-header' => 'Keeps the operation badge and key on one aligned identity row.',
                    'cache-list-item' => 'Shows one cache operation with an equal-width badge and compact key identity.',
                    'cache-overview-facts' => 'Displays the outcome, store, duration, and related cache facts.',
                    'cache-overview-panel' => 'Explains the selected cache operation through structured facts.',
                    'cache-raw-panel' => 'Shows the bounded raw collector fields retained for one cache operation.',
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
                    'notification-header' => 'Shows notification identity, recipient context, destinations, and relevant lifecycle actions.',
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

    /**
     * @return array<string, array{title: string, singular: string, description: string, components: list<string>}>
     */
    public static function kinds(): array
    {
        return [
            'elements' => [
                'title' => 'Elements',
                'singular' => 'Element',
                'description' => 'Small controls and visual units that make sense on their own.',
                'components' => [
                    'icon',
                    'icon-button',
                    'inspector-action',
                    'inspector-operation-badge',
                    'search-field',
                    'select-field',
                    'filter-tab',
                    'empty-state',
                    'popover-surface',
                    'theme-menu-item',
                    'theme-toggle',
                    'code-block',
                    'inspector-definition-row',
                    'inspector-detail-back',
                    'inspector-detail-empty',
                    'inspector-explanation',
                    'inspector-fact',
                    'inspector-source-fact',
                    'inspector-source-link',
                    'toolbar-anchor-preview',
                    'toolbar-button',
                ],
            ],
            'patterns' => [
                'title' => 'Patterns',
                'singular' => 'Pattern',
                'description' => 'Recurring arrangements and interactions built from elements.',
                'components' => [
                    'filter-tabs',
                    'section-heading',
                    'inspector-definition-list',
                    'inspector-detail-header',
                    'inspector-detail-pane',
                    'inspector-detail-tabs',
                    'inspector-evidence',
                    'inspector-facts',
                    'inspector-list-panel',
                    'inspector-stack',
                    'mobile-request-metrics',
                    'mobile-toolbar-popover',
                    'request-option',
                    'window-controls',
                    'http-client-controls',
                    'http-client-detail-tabs',
                    'http-client-empty',
                    'http-client-header',
                    'http-client-list-item',
                    'http-client-no-response',
                    'http-client-request-panel',
                    'http-client-response-panel',
                    'http-client-source-panel',
                    'cache-controls',
                    'cache-detail-tabs',
                    'cache-empty',
                    'cache-header',
                    'cache-list-item',
                    'cache-overview-facts',
                    'cache-overview-panel',
                    'cache-raw-panel',
                    'cache-source-panel',
                    'mail-actions',
                    'mail-header',
                    'mail-message-details',
                    'mail-source-panel',
                    'notification-delivery-panel',
                    'notification-header',
                    'notification-payload-panel',
                    'notification-source-panel',
                    'livewire-property-editor',
                    'livewire-split-view',
                    'log-entry',
                    'model-group',
                    'query-actions',
                    'query-execution',
                ],
            ],
            'compositions' => [
                'title' => 'Compositions',
                'singular' => 'Composition',
                'description' => 'Larger, stateful product slices that coordinate several patterns.',
                'components' => [
                    'inspector-workspace',
                    'corner-toolbar',
                    'request-switcher',
                    'http-client-detail',
                    'http-client-workspace',
                    'cache-detail',
                    'cache-workspace',
                    'notification-detail',
                    'authorization-detail',
                    'event-detail',
                    'model-group-detail',
                    'query-section',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{title: string, description: string, components: list<string>}>
     */
    public static function navigationGroups(): array
    {
        return [
            'controls' => [
                'title' => 'Controls',
                'description' => 'Inputs, actions, menus, and selectors.',
                'components' => [
                    'icon',
                    'icon-button',
                    'inspector-action',
                    'search-field',
                    'select-field',
                    'filter-tab',
                    'filter-tabs',
                    'popover-surface',
                    'theme-menu-item',
                    'theme-toggle',
                    'toolbar-button',
                    'window-controls',
                    'query-actions',
                    'mail-actions',
                ],
            ],
            'content' => [
                'title' => 'Content and states',
                'description' => 'Reusable ways to show outcomes, facts, and evidence.',
                'components' => [
                    'empty-state',
                    'inspector-operation-badge',
                    'code-block',
                    'inspector-definition-list',
                    'inspector-definition-row',
                    'inspector-detail-empty',
                    'inspector-evidence',
                    'inspector-explanation',
                    'inspector-fact',
                    'inspector-facts',
                    'inspector-source-fact',
                    'inspector-source-link',
                    'inspector-stack',
                    'http-client-empty',
                    'http-client-no-response',
                    'cache-empty',
                    'cache-overview-facts',
                    'cache-raw-panel',
                    'notification-payload-panel',
                    'log-entry',
                    'query-execution',
                ],
            ],
            'layout' => [
                'title' => 'Inspector layout',
                'description' => 'Shared structure for lists, details, and section framing.',
                'components' => [
                    'section-heading',
                    'inspector-detail-back',
                    'inspector-detail-header',
                    'inspector-detail-pane',
                    'inspector-detail-tabs',
                    'inspector-list-panel',
                    'inspector-workspace',
                ],
            ],
            'toolbar' => [
                'title' => 'Request toolbar',
                'description' => 'Controls for the current request and debug bar window.',
                'components' => [
                    'corner-toolbar',
                    'mobile-request-metrics',
                    'mobile-toolbar-popover',
                    'request-option',
                    'request-switcher',
                    'toolbar-anchor-preview',
                ],
            ],
            'section-parts' => [
                'title' => 'Section parts',
                'description' => 'Focused pieces used inside diagnostic sections.',
                'components' => [
                    'http-client-controls',
                    'http-client-detail-tabs',
                    'http-client-header',
                    'http-client-list-item',
                    'http-client-request-panel',
                    'http-client-response-panel',
                    'http-client-source-panel',
                    'cache-controls',
                    'cache-detail-tabs',
                    'cache-header',
                    'cache-list-item',
                    'cache-overview-panel',
                    'cache-source-panel',
                    'mail-header',
                    'mail-message-details',
                    'mail-source-panel',
                    'notification-delivery-panel',
                    'notification-header',
                    'notification-source-panel',
                    'model-group',
                ],
            ],
            'laravel-activity' => [
                'title' => 'Laravel activity',
                'description' => 'Components for framework-specific runtime data.',
                'components' => [
                    'authorization-detail',
                    'event-detail',
                    'livewire-property-editor',
                    'livewire-split-view',
                    'model-group-detail',
                    'query-section',
                ],
            ],
            'compositions' => [
                'title' => 'Complete views',
                'description' => 'Large components that combine smaller pieces into a usable inspector view.',
                'components' => [
                    'http-client-detail',
                    'http-client-workspace',
                    'cache-detail',
                    'cache-workspace',
                    'notification-detail',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     slug: string,
     *     title: string,
     *     description: string,
     *     kind: string,
     *     kindTitle: string,
     *     kindSingular: string,
     *     kindDescription: string,
     *     group: string,
     *     groupTitle: string,
     *     groupDescription: string
     * }>
     */
    public static function components(): array
    {
        $origins = [];

        foreach (self::groups() as $group => $metadata) {
            foreach ($metadata['components'] as $component => $description) {
                $origins[$component] = [
                    'description' => $description,
                    'group' => $group,
                    'groupTitle' => $metadata['title'],
                    'groupDescription' => $metadata['description'],
                ];
            }
        }

        $components = [];

        foreach (self::kinds() as $kind => $metadata) {
            foreach ($metadata['components'] as $component) {
                $origin = $origins[$component] ?? throw new LogicException(
                    sprintf('Studio component [%s] has no demo group.', $component),
                );

                $components[$component] = [
                    'slug' => $component,
                    'title' => self::displayTitle($component),
                    'description' => $origin['description'],
                    'kind' => $kind,
                    'kindTitle' => $metadata['title'],
                    'kindSingular' => $metadata['singular'],
                    'kindDescription' => $metadata['description'],
                    ...$origin,
                ];
            }
        }

        return $components;
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     description: string,
     *     kind: string,
     *     kindTitle: string,
     *     kindSingular: string,
     *     kindDescription: string,
     *     group: string,
     *     groupTitle: string,
     *     groupDescription: string
     * }|null
     */
    public static function component(string $slug): ?array
    {
        return self::components()[$slug] ?? null;
    }

    private static function displayTitle(string $component): string
    {
        $titles = [
            'filter-tab' => 'Segmented Option',
            'filter-tabs' => 'Segmented Control',
            'inspector-operation-badge' => 'Operation Badge',
            'inspector-definition-list' => 'Definition List',
            'inspector-definition-row' => 'Definition Row',
            'inspector-detail-back' => 'Detail Back Action',
            'inspector-detail-empty' => 'Detail Empty State',
            'inspector-detail-header' => 'Detail Header',
            'inspector-detail-pane' => 'Detail Pane',
            'inspector-detail-tabs' => 'Detail Tabs',
            'inspector-evidence' => 'Code Evidence',
            'inspector-fact' => 'Summary Fact',
            'inspector-facts' => 'Fact Grid',
            'inspector-list-panel' => 'List Pane',
            'inspector-stack' => 'Application Stack',
            'inspector-workspace' => 'List-Detail Workspace',
            'corner-toolbar' => 'Compact Request Toolbar',
            'request-option' => 'Saved Request Row',
            'toolbar-anchor-preview' => 'Toolbar Drop Preview',
            'model-group' => 'Model Row',
            'model-group-detail' => 'Model Detail',
            'query-section' => 'Queries Workspace',
        ];

        return $titles[$component] ?? str_replace('Http ', 'HTTP ', Str::headline($component));
    }
}
