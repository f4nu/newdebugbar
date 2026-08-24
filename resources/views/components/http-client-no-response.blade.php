<template x-if="! selectedHttpClientRequest.response">
    <div class="ndb:mt-5">
        <p class="ndb:text-xs ndb:font-semibold">No HTTP response was received.</p>
        <x-newdebugbar::inspector-definition-list
            x-show.important="selectedHttpClientRequest.exception_class || selectedHttpClientRequest.exception_message"
            class="ndb:mt-4"
        >
            <x-newdebugbar::inspector-definition-row
                label="Exception"
                x-show.important="selectedHttpClientRequest.exception_class"
            >
                <x-slot:value
                    class="ndb:break-all ndb:font-mono ndb:text-[11px]"
                    x-text="selectedHttpClientRequest.exception_class"
                ></x-slot:value>
            </x-newdebugbar::inspector-definition-row>
            <x-newdebugbar::inspector-definition-row
                label="Message"
                tone="danger"
                x-show.important="selectedHttpClientRequest.exception_message"
            >
                <x-slot:value x-text="selectedHttpClientRequest.exception_message"></x-slot:value>
            </x-newdebugbar::inspector-definition-row>
        </x-newdebugbar::inspector-definition-list>
    </div>
</template>
