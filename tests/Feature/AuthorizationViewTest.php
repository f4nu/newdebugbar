<?php

it('renders authorization decisions from actor through target with useful context', function () {
    $section = [
        'payload' => [
            'items' => [
                [
                    'result' => 'allowed',
                    'ability' => 'view',
                    'handler' => 'App\\Policies\\StudioJobPolicy@view',
                    'user_type' => 'App\\Models\\User',
                    'argument_types' => ['App\\Models\\StudioJob', 'string'],
                    'callsite' => ['copy' => 'app/Http/Controllers/StudioJobController.php:99'],
                ],
                [
                    'result' => 'denied',
                    'ability' => 'access-studio',
                    'handler' => 'callback',
                    'user_type' => null,
                    'argument_types' => [],
                    'callsite' => ['file' => 'app/Providers/AuthServiceProvider.php', 'line' => 31],
                ],
            ],
        ],
    ];

    $html = view('newdebugbar::livewire.sections.authorization', compact('section'))->render();
    $document = new DOMDocument;
    $previousLibxmlState = libxml_use_internal_errors(true);

    $document->loadHTML('<!doctype html><html><body>'.$html.'</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previousLibxmlState);

    $xpath = new DOMXPath($document);
    $items = $xpath->query('//*[@data-ndb-authorization-item]');
    $first = $items->item(0);
    $second = $items->item(1);
    $text = static fn (DOMNode $context, string $attribute): string => trim((string) $xpath->evaluate(
        "string(.//*[@{$attribute}])",
        $context,
    ));

    expect($items->length)->toBe(2)
        ->and($first)->toBeInstanceOf(DOMElement::class)
        ->and($text($first, 'data-ndb-authorization-source'))->toBe('User')
        ->and($text($first, 'data-ndb-authorization-result'))->toBe('allowed')
        ->and($text($first, 'data-ndb-authorization-ability'))->toBe('view')
        ->and($text($first, 'data-ndb-authorization-target'))->toBe('StudioJob, string')
        ->and($text($first, 'data-ndb-authorization-callsite'))->toBe('app/Http/Controllers/StudioJobController.php:99')
        ->and($text($first, 'data-ndb-authorization-handler'))->toBe('via StudioJobPolicy@view')
        ->and($xpath->evaluate('string(.//*[@data-ndb-authorization-source]/@title)', $first))->toBe('App\\Models\\User')
        ->and($xpath->evaluate('string(.//*[@data-ndb-authorization-target]/@title)', $first))->toBe('App\\Models\\StudioJob, string')
        ->and($xpath->evaluate('string(.//*[@data-ndb-authorization-handler]/@title)', $first))->toBe('App\\Policies\\StudioJobPolicy@view')
        ->and($xpath->query('.//*[@data-ndb-authorization-connector]', $first)->length)->toBe(3)
        ->and($second)->toBeInstanceOf(DOMElement::class)
        ->and($text($second, 'data-ndb-authorization-source'))->toBe('Guest')
        ->and($text($second, 'data-ndb-authorization-result'))->toBe('denied')
        ->and($text($second, 'data-ndb-authorization-ability'))->toBe('access-studio')
        ->and($text($second, 'data-ndb-authorization-callsite'))->toBe('app/Providers/AuthServiceProvider.php:31')
        ->and($xpath->query('.//*[@data-ndb-authorization-connector]', $second)->length)->toBe(2)
        ->and($xpath->query('.//*[@data-ndb-authorization-target]', $second)->length)->toBe(0)
        ->and($xpath->query('.//*[@data-ndb-authorization-handler]', $second)->length)->toBe(0)
        ->and($html)->not->toContain('→');
});
