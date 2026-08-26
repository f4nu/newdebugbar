<div data-ndb-cache-detail-panel="raw" x-show.important="cacheDetailTab === 'raw'" class="ndb:p-4">
    <p class="ndb:mb-2 ndb:max-w-xl ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
        Captured collector fields only. Values are bounded and sensitive fields are redacted.
    </p>
    <x-newdebugbar::inspector-evidence data-ndb-cache-raw-evidence>
        <x-slot:value x-text="formatCachePayload(selectedCacheOperation.raw)"></x-slot:value>
    </x-newdebugbar::inspector-evidence>
</div>
