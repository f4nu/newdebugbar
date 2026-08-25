<div data-ndb-cache-detail-panel="overview" x-show.important="cacheDetailTab === 'overview'">
    <x-newdebugbar::cache-overview-facts />

    <x-newdebugbar::inspector-definition-list
        x-show.important="
            selectedCacheOperation.has_value ||
            ['write', 'write_failed'].includes(selectedCacheOperation.operation) ||
            selectedCacheOperation.duration_scope === 'batch' ||
            (selectedCacheOperation.failed && selectedCacheOperation.exception_message)
        "
        class="ndb:mt-4"
    >
        <x-newdebugbar::inspector-definition-row label="Value" x-show.important="selectedCacheOperation.has_value">
            <x-slot:value class="ndb:min-w-0">
                <pre
                    class="ndb:whitespace-pre-wrap ndb:break-words ndb:font-mono"
                    x-text="selectedCacheOperation.value_display"
                ></pre>
            </x-slot:value>
        </x-newdebugbar::inspector-definition-row>
        <x-newdebugbar::inspector-definition-row
            label="Lifetime"
            x-show.important="['write', 'write_failed'].includes(selectedCacheOperation.operation)"
        >
            <x-slot:value x-text="selectedCacheOperation.lifetime_label"></x-slot:value>
        </x-newdebugbar::inspector-definition-row>
        <x-newdebugbar::inspector-definition-row
            label="Timing context"
            x-show.important="selectedCacheOperation.duration_scope === 'batch'"
        >
            <x-slot:value x-text="'Shared across a batch of ' + selectedCacheOperation.batch_size + ' operations.'"></x-slot:value>
        </x-newdebugbar::inspector-definition-row>
        <x-newdebugbar::inspector-definition-row
            label="Failure"
            tone="danger"
            x-show.important="selectedCacheOperation.failed && selectedCacheOperation.exception_message"
        >
            <x-slot:value x-text="selectedCacheOperation.exception_message"></x-slot:value>
        </x-newdebugbar::inspector-definition-row>
    </x-newdebugbar::inspector-definition-list>
</div>
