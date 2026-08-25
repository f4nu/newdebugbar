<div data-ndb-mail-detail-panel="source" x-show.important="mailDetailTab === 'source'" class="ndb:p-4">
    <dl class="ndb:grid ndb:gap-2 ndb:sm:grid-cols-2">
        <x-newdebugbar::inspector-source-fact label="Mailable or notification" :code="true">
            <x-slot:value x-text="selectedMailMessage.source || 'No source class was captured.'"></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
        <x-newdebugbar::inspector-source-fact label="Triggered at">
            <x-slot:value x-text="selectedMailMessage.callsite_label"></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
    </dl>

    <x-newdebugbar::inspector-stack frames="selectedMailMessage.stack" />
</div>
