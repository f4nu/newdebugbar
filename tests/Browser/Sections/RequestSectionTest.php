<?php

it('shows an aligned request trace and switches request detail groups', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="request"]')
        ->assertVisible('[data-ndb-request-trace]')
        ->assertScript(<<<'JS'
            (() => {
                const description = document.querySelector('[data-ndb-section-description]').getBoundingClientRect();
                const trace = document.querySelector('[data-ndb-request-trace]').getBoundingClientRect();

                return trace.top - description.bottom <= 32;
            })()
            JS)
        ->assertScript('document.querySelector("[data-ndb-request-status]").textContent.trim() === "200"')
        ->assertScript('/^Completed in \\d+(?:\\.\\d+)? ms$/.test(document.querySelector("[data-ndb-request-completion]").textContent.replace(/\\s+/g, " ").trim())')
        ->assertScript('!["Success", "Failed", "Completed successfully", "Completed with an error"].some((meaning) => document.querySelector("[data-ndb-request-trace]").textContent.includes(meaning))')
        ->assertVisible('[data-ndb-request-details]')
        ->assertScript('document.querySelector("[data-ndb-request-details]").open === false')
        ->click('[data-ndb-request-details] > summary')
        ->assertScript('document.querySelector("[data-ndb-request-details]").open === true')
        ->assertScript('document.querySelectorAll("[data-ndb-request-step]").length', 3)
        ->assertScript('document.querySelectorAll("[data-ndb-request-line]").length', 2)
        ->assertScript(<<<'JS'
            (() => {
                const summary = getComputedStyle(document.querySelector('[data-ndb-request-summary]'));
                const timeline = getComputedStyle(document.querySelector('[data-ndb-request-timeline]'));

                return summary.borderTopWidth === '1px'
                    && summary.borderRightWidth === '0px'
                    && summary.borderBottomWidth === '1px'
                    && summary.borderLeftWidth === '0px'
                    && summary.borderRadius === '0px'
                    && timeline.paddingLeft === (innerWidth >= 640 ? '24px' : '16px')
                    && timeline.paddingRight === (innerWidth >= 640 ? '24px' : '16px');
            })()
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-step]')).every((step) => {
                const dot = step.querySelector('[data-ndb-request-dot]').getBoundingClientRect();
                const heading = step.querySelector('h3').getBoundingClientRect();

                return Math.abs((dot.top + dot.height / 2) - (heading.top + heading.height / 2)) < 1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-line]')).every((line, index) => {
                const nextDot = document.querySelectorAll('[data-ndb-request-dot]')[index + 1].getBoundingClientRect();
                const bounds = line.getBoundingClientRect();

                return Math.abs(bounds.bottom - nextDot.top) < 1
                    && Math.abs(bounds.width - 2) < 0.1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-detail]')).every((button) => {
                const parent = button.parentElement;
                const styles = getComputedStyle(parent);
                const availableWidth = parent.clientWidth
                    - parseFloat(styles.paddingLeft)
                    - parseFloat(styles.paddingRight);

                return Math.abs(button.getBoundingClientRect().width - availableWidth) < 1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-detail-count], [data-ndb-request-detail-panel-count]'))
                .every((count) => /^\d+$/.test(count.textContent.trim()))
            JS)
        ->assertAttribute('[data-ndb-request-detail="headers"]', 'aria-pressed', 'true')
        ->click('[data-ndb-request-detail="session"]')
        ->assertAttribute('[data-ndb-request-detail="session"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-detail-panel="session"]')
        ->assertNoJavaScriptErrors();
});
