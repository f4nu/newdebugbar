{{-- Keeps the existing request split button as the complete corner toolbar. --}}
<div x-cloak x-show.important="toolbarIsCorner" data-ndb-corner-toolbar class="ndb:flex ndb:h-full ndb:w-full">
    <x-newdebugbar::request-switcher scope="corner" class="ndb:w-full" />
</div>
