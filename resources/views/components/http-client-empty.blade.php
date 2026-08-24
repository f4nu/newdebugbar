<div
    data-ndb-http-client-empty
    class="ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:items-center ndb:lg:justify-center"
>
    <div class="ndb:w-full ndb:max-w-lg">
        <x-newdebugbar::empty-state label="No outbound HTTP requests were captured for this request." />
        <p class="ndb:mt-3 ndb:text-center ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
            Requests made through Laravel's HTTP client will appear here with their response, timing, and source.
        </p>
    </div>
</div>
