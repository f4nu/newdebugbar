@if ($component === $selected)
    <section data-ndb-studio-demo="{{ $component }}" class="ndb:min-h-screen ndb:min-w-0">
        <div data-ndb-studio-demo-surface class="ndb:grid ndb:min-h-screen ndb:min-w-0 ndb:place-items-center">
            <div @class([
                'ndb:w-full ndb:min-w-0 ndb:[&>*]:mx-auto',
                'ndb:max-w-md ndb:[&>*]:justify-center' => $kind === 'elements',
                'ndb:max-w-5xl' => $kind === 'patterns',
            ])>
                {{ $slot }}
            </div>
        </div>
    </section>
@endif
