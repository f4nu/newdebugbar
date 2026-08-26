<?php

namespace NewDebugBar\Presentation;

use LogicException;

/** Lists the canonical reusable component families shown in the local Studio. */
final class StudioCatalog
{
    public const DEFAULT_COMPONENT = 'search-field';

    private const DEMO_GROUPS = [
        'foundations' => [
            'title' => 'Foundations',
            'description' => 'The controls, labels, and visual primitives that establish the interface language.',
        ],
        'inspector' => [
            'title' => 'Inspector structure',
            'description' => 'The shared hierarchy for scanning a list, opening one item, and reading its evidence.',
        ],
    ];

    private const KINDS = [
        'elements' => [
            'title' => 'Elements',
            'singular' => 'Element',
            'description' => 'Small controls and visual units that make sense on their own.',
        ],
        'patterns' => [
            'title' => 'Patterns',
            'singular' => 'Pattern',
            'description' => 'Recurring arrangements and interactions built from elements.',
        ],
    ];

    private const NAVIGATION_GROUPS = [
        'controls' => [
            'title' => 'Controls',
            'description' => 'Inputs, actions, menus, and selectors.',
        ],
        'content' => [
            'title' => 'Content and evidence',
            'description' => 'Reusable ways to show outcomes, facts, sources, and code.',
        ],
        'layout' => [
            'title' => 'Inspector layout',
            'description' => 'Shared structure for lists, details, and section framing.',
        ],
    ];

    /**
     * Private components remain outside Studio, but each one must have exactly
     * one product owner until it can move beside that section's view.
     *
     * @var array<string, list<string>>
     */
    private const PRIVATE_COMPONENTS = [
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

    /**
     * The explicit public component catalog. Files outside this list are private
     * implementation details, even while older files still live in components/.
     *
     * Compound families list every public Blade file they own in `members`, but
     * receive one focused Studio page because the child has no useful standalone state.
     *
     * @var array<string, array{
     *     title: string,
     *     description: string,
     *     demo: string,
     *     kind: string,
     *     navigation: string,
     *     members?: list<string>
     * }>
     */
    private const CATALOG = [
        'icon' => [
            'title' => 'Icon',
            'description' => 'Renders a package-owned SVG symbol at an explicit size.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'controls',
        ],
        'icon-button' => [
            'title' => 'Icon Button',
            'description' => 'Provides an accessible icon-only action with consistent focus, hover, and disabled states.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'controls',
        ],
        'inspector-action' => [
            'title' => 'Inspector Action',
            'description' => 'Shows a compact labeled action for contextual work inside a detail pane.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'controls',
        ],
        'search-field' => [
            'title' => 'Search Field',
            'description' => 'Pairs an accessible search input with the established left-side search icon and spacing.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'controls',
        ],
        'select-field' => [
            'title' => 'Select Field',
            'description' => 'Uses a native select with the shared field height, focus treatment, and chevron.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'controls',
        ],
        'filter-tabs' => [
            'title' => 'Segmented Control',
            'description' => 'Groups a small set of mutually exclusive options with shared tab or segmented semantics.',
            'demo' => 'foundations',
            'kind' => 'patterns',
            'navigation' => 'controls',
            'members' => ['filter-tabs', 'filter-tab'],
        ],
        'popover-surface' => [
            'title' => 'Popover Surface',
            'description' => 'Provides the shared elevated surface for compact menus and supporting evidence.',
            'demo' => 'foundations',
            'kind' => 'patterns',
            'navigation' => 'controls',
        ],
        'empty-state' => [
            'title' => 'Empty State',
            'description' => 'Explains that a section or filtered result has no items without adding decoration.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'content',
        ],
        'inspector-operation-badge' => [
            'title' => 'Operation Badge',
            'description' => 'Keeps HTTP methods and cache operations neutral, compact, and equal in width.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'content',
        ],
        'code-block' => [
            'title' => 'Code Block',
            'description' => 'Displays actual code or data with syntax highlighting and code typography.',
            'demo' => 'foundations',
            'kind' => 'elements',
            'navigation' => 'content',
        ],
        'inspector-source-link' => [
            'title' => 'Inspector Source Link',
            'description' => 'Makes a source location visibly clickable through a simple underline.',
            'demo' => 'inspector',
            'kind' => 'elements',
            'navigation' => 'content',
        ],
        'section-heading' => [
            'title' => 'Section Heading',
            'description' => 'Pairs a restrained section title with a close, readable description.',
            'demo' => 'foundations',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
        'inspector-definition-list' => [
            'title' => 'Definition List',
            'description' => 'Stacks labeled definition rows with one calm divider system.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
            'members' => ['inspector-definition-list', 'inspector-definition-row'],
        ],
        'inspector-detail-empty' => [
            'title' => 'Detail Empty State',
            'description' => 'Centers an instruction when no list item has been selected.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
        ],
        'inspector-evidence' => [
            'title' => 'Code Evidence',
            'description' => 'Adds an optional label above a syntax-highlighted evidence block.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
        ],
        'inspector-explanation' => [
            'title' => 'Inspector Explanation',
            'description' => 'Explains only domain-specific meaning and a conditional next check.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
        ],
        'inspector-facts' => [
            'title' => 'Fact Grid',
            'description' => 'Aligns a small set of compact labeled facts into stable responsive columns.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
            'members' => ['inspector-facts', 'inspector-fact'],
        ],
        'inspector-source-fact' => [
            'title' => 'Inspector Source Fact',
            'description' => 'Shows one source-like fact while preserving interface typography for locations.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
        ],
        'inspector-source-panel' => [
            'title' => 'Inspector Source Panel',
            'description' => 'Combines source facts and the retained application stack with one spacing system.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
        ],
        'inspector-stack' => [
            'title' => 'Application Stack',
            'description' => 'Presents a bounded application stack with copyable, readable frame labels.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'content',
        ],
        'inspector-detail-back' => [
            'title' => 'Detail Back Action',
            'description' => 'Returns from a mobile detail step to its list with a clear text action.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
        'inspector-detail-header' => [
            'title' => 'Detail Header',
            'description' => 'Keeps selected-item identity and optional actions in a stable header.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
        'inspector-detail-pane' => [
            'title' => 'Detail Pane',
            'description' => 'Owns detail scrolling and adapts between desktop split view and mobile drill-in.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
        'inspector-detail-tabs' => [
            'title' => 'Detail Tabs',
            'description' => 'Places shared segmented tabs at the center, or left when nearby controls require it.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
        'inspector-list-controls' => [
            'title' => 'List Controls',
            'description' => 'Aligns an optional list summary, search field, and optional trailing filter without section-specific markup.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
        'inspector-list-panel' => [
            'title' => 'List Pane',
            'description' => 'Owns list controls, list scrolling, and the filtered empty state.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
        'inspector-workspace' => [
            'title' => 'List-Detail Workspace',
            'description' => 'Provides the edge-to-edge split or focused workspace shared across inspectors.',
            'demo' => 'inspector',
            'kind' => 'patterns',
            'navigation' => 'layout',
        ],
    ];

    /**
     * @return array<string, array{title: string, description: string, components: array<string, string>}>
     */
    public static function groups(): array
    {
        $groups = array_map(
            fn (array $metadata): array => [...$metadata, 'components' => []],
            self::DEMO_GROUPS,
        );

        foreach (self::CATALOG as $component => $metadata) {
            $groups[$metadata['demo']]['components'][$component] = $metadata['description'];
        }

        return $groups;
    }

    /**
     * @return array<string, array{title: string, singular: string, description: string, components: list<string>}>
     */
    public static function kinds(): array
    {
        $kinds = array_map(
            fn (array $metadata): array => [...$metadata, 'components' => []],
            self::KINDS,
        );

        foreach (self::CATALOG as $component => $metadata) {
            $kinds[$metadata['kind']]['components'][] = $component;
        }

        return $kinds;
    }

    /**
     * @return array<string, array{title: string, description: string, components: list<string>}>
     */
    public static function navigationGroups(): array
    {
        $groups = array_map(
            fn (array $metadata): array => [...$metadata, 'components' => []],
            self::NAVIGATION_GROUPS,
        );

        foreach (self::CATALOG as $component => $metadata) {
            $groups[$metadata['navigation']]['components'][] = $component;
        }

        return $groups;
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
     *     groupDescription: string,
     *     members: list<string>
     * }>
     */
    public static function components(): array
    {
        $components = [];

        foreach (self::CATALOG as $component => $metadata) {
            $group = self::DEMO_GROUPS[$metadata['demo']] ?? throw new LogicException(
                sprintf('Studio component [%s] has an unknown demo group.', $component),
            );
            $kind = self::KINDS[$metadata['kind']] ?? throw new LogicException(
                sprintf('Studio component [%s] has an unknown kind.', $component),
            );

            if (! array_key_exists($metadata['navigation'], self::NAVIGATION_GROUPS)) {
                throw new LogicException(sprintf('Studio component [%s] has an unknown navigation group.', $component));
            }

            $components[$component] = [
                'slug' => $component,
                'title' => $metadata['title'],
                'description' => $metadata['description'],
                'kind' => $metadata['kind'],
                'kindTitle' => $kind['title'],
                'kindSingular' => $kind['singular'],
                'kindDescription' => $kind['description'],
                'group' => $metadata['demo'],
                'groupTitle' => $group['title'],
                'groupDescription' => $group['description'],
                'members' => $metadata['members'] ?? [$component],
            ];
        }

        return $components;
    }

    /** @return list<string> */
    public static function publicComponents(): array
    {
        return array_values(array_merge(...array_column(self::components(), 'members')));
    }

    /** @return array<string, list<string>> */
    public static function privateComponents(): array
    {
        return self::PRIVATE_COMPONENTS;
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
     *     groupDescription: string,
     *     members: list<string>
     * }|null
     */
    public static function component(string $slug): ?array
    {
        return self::components()[$slug] ?? null;
    }
}
