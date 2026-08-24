<div data-ndb-cache-detail-panel="overview" x-show.important="cacheDetailTab === 'overview'">
    <x-newdebugbar::cache-overview-facts />

    <x-newdebugbar::inspector-definition-list
        x-show.important="
            selectedCacheOperation.has_value ||
            ['write', 'write_failed'].includes(selectedCacheOperation.operation) ||
            selectedCacheOperation.duration_scope === 'batch' ||
            selectedCacheOperation.related_count > 1 ||
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
            label="Related uses"
            x-show.important="selectedCacheOperation.related_count > 1"
        >
            <x-slot:value>
                <span x-text="selectedCacheOperation.related_count + ' operations for this key in this store:'"></span>
                <span class="ndb:ml-1 ndb:inline-flex ndb:flex-wrap ndb:gap-x-2">
                    <template x-for="execution in selectedCacheOperation.related_executions" :key="execution">
                        <button
                            type="button"
                            @click="selectRelatedCacheOperation(execution)"
                            :aria-label="'Open cache execution ' + execution"
                            :aria-current="cacheSelected === execution ? 'true' : null"
                            class="ndb:rounded-sm ndb:font-mono ndb:font-semibold ndb:text-indigo-600 ndb:underline ndb:decoration-indigo-300 ndb:underline-offset-2 ndb:hover:text-indigo-800 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:decoration-indigo-700 ndb:dark:hover:text-indigo-200"
                            x-text="'#' + execution"
                        ></button>
                    </template>
                </span>
            </x-slot:value>
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
