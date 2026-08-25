<div data-ndb-cache-detail-panel="source" x-show.important="cacheDetailTab === 'source'">
    <dl class="ndb:grid ndb:gap-2">
        <x-newdebugbar::inspector-source-fact label="Triggered at">
            <x-slot:value x-text="selectedCacheOperation.source_label"></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
    </dl>
    <x-newdebugbar::inspector-stack frames="selectedCacheOperation.stack ?? []" />
</div>
