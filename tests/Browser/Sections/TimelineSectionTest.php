<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('filters the timeline without inventing spans for point events', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="timeline"]');

    DebugBarBrowser::assertSectionSelected($page, 'timeline');

    $page
        ->assertPresent('[data-ndb-timeline-item="request-start"]')
        ->assertVisible('[data-ndb-timeline-waterfall]')
        ->assertScript(<<<'JS'
            (() => {
                const subtitles = Array.from(document.querySelectorAll('[data-ndb-timeline-activity-section]'));

                return subtitles.length > 0
                    && subtitles.every((subtitle) => getComputedStyle(subtitle).textTransform === 'none');
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-timeline-toolbar]');
                const toolbarBounds = toolbar.getBoundingClientRect();
                const overview = document.querySelector('[data-ndb-timeline-overview]').getBoundingClientRect();
                const filter = document.querySelector('[data-ndb-timeline-filter]').getBoundingClientRect();
                const search = document.querySelector('[data-ndb-timeline-search]').getBoundingClientRect();

                return overview.bottom <= toolbarBounds.top
                    && Math.abs(filter.left - toolbarBounds.left) <= 1
                    && Math.abs(search.right - toolbarBounds.right) <= 1
                    && filter.right <= search.left
                    && toolbar.scrollWidth <= toolbar.clientWidth;
            })()
            JS)
        ->assertMissing('[data-ndb-timeline-tabs]')
        ->assertValue('[data-ndb-timeline-filter]', 'key')
        ->assertScript(<<<'JS'
            (() => {
                const values = Array.from(document.querySelector('[data-ndb-timeline-filter]').options)
                    .map((option) => option.value);

                return JSON.stringify(values.slice(0, 3)) === JSON.stringify(['key', 'all', 'request'])
                    && new Set(values).size === values.length
                    && values.includes('queries')
                    && values.includes('events');
            })()
            JS)
        ->select('[data-ndb-timeline-filter]', 'all')
        ->assertValue('[data-ndb-timeline-filter]', 'all')
        ->assertScript('document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").getBoundingClientRect().left > document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").parentElement.parentElement.getBoundingClientRect().left + 4')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length > 2')
        ->assertScript(<<<'JS'
            Number(document.querySelector('[data-ndb-section-panel="timeline"] [x-text="visibleTimelineCount"]').textContent)
                === document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])').length
            JS)
        ->select('[data-ndb-timeline-filter]', 'queries')
        ->assertValue('[data-ndb-timeline-filter]', 'queries')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.section === 'queries')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][hidden]'))
                .every((item) => getComputedStyle(item).display === 'none')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][data-section="queries"]'))
                .every((item) => {
                    const track = item.querySelector('[data-ndb-timeline-track]').getBoundingClientRect();
                    const mark = item.querySelector('[data-ndb-timeline-mark]').getBoundingClientRect();

                    return item.dataset.kind === 'span'
                        && Number(item.dataset.start) < Number(item.dataset.position)
                        && Number(item.dataset.duration) > 0
                        && mark.width >= 3
                        && mark.left >= track.left
                        && mark.right <= track.right + 1;
                })
            JS)
        ->select('[data-ndb-timeline-filter]', 'events')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.kind === 'point'
                    && item.querySelector('[data-ndb-timeline-mark]').getBoundingClientRect().width > 0)
            JS)
        ->select('[data-ndb-timeline-filter]', 'request')
        ->assertValue('[data-ndb-timeline-filter]', 'request')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.section === 'request')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][hidden]'))
                .every((item) => getComputedStyle(item).display === 'none')
            JS)
        ->resize(390, 844)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-timeline-toolbar]');
                const toolbarBounds = toolbar.getBoundingClientRect();
                const filter = document.querySelector('[data-ndb-timeline-filter]').getBoundingClientRect();
                const search = document.querySelector('[data-ndb-timeline-search]').getBoundingClientRect();

                return toolbar.scrollWidth <= toolbar.clientWidth
                    && Math.abs(filter.left - toolbarBounds.left) <= 1
                    && Math.abs(search.right - toolbarBounds.right) <= 1
                    && filter.right <= search.left;
            })()
            JS)
        ->type('[data-ndb-timeline-search]', 'nothing can match this')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length', 0)
        ->assertSee('No timeline events match these filters.')
        ->assertNoJavaScriptErrors();
});
