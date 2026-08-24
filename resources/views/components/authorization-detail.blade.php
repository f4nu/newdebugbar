<section
    x-ref="authorizationDetail"
    data-ndb-authorization-detail
    aria-live="polite"
    aria-label="Selected authorization decision details"
    tabindex="0"
    :class="authorizationDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
    class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:border-0 ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
>
    <x-newdebugbar::inspector-detail-back
        data-ndb-authorization-detail-back
        @click="closeAuthorizationDetail()"
        label="Decisions"
    />

    <template x-if="selectedAuthorizationDecision">
        <div class="ndb:flex ndb:flex-col">
            <x-newdebugbar::inspector-detail-header data-ndb-authorization-header>
                <x-slot:title>
                    <code
                        data-ndb-authorization-detail-ability
                        class="ndb:break-words ndb:font-mono ndb:text-base ndb:font-bold ndb:leading-6"
                        x-text="selectedAuthorizationDecision.ability"
                    ></code>
                </x-slot:title>

                <x-slot:aside>
                    <span
                        data-ndb-authorization-detail-result
                        :class="selectedAuthorizationDecision.result === 'allowed'
                            ? 'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300'
                            : 'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300'"
                        class="ndb:inline-flex ndb:shrink-0 ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold"
                        x-text="selectedAuthorizationDecision.result_label"
                    ></span>
                </x-slot:aside>

                <x-slot:identity data-ndb-authorization-identity>
                    <dl class="ndb:space-y-2">
                        <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Actor</dt>
                            <dd class="ndb:min-w-0">
                                <span
                                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                    x-text="selectedAuthorizationDecision.actor_label"
                                ></span>
                                <span
                                    class="ndb:mt-0.5 ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                    x-text="selectedAuthorizationDecision.actor_type_short"
                                ></span>
                            </dd>
                        </div>
                        <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Target</dt>
                            <dd
                                class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="selectedAuthorizationDecision.argument_summary"
                            ></dd>
                        </div>
                    </dl>
                </x-slot:identity>

                <x-slot:metadata data-ndb-authorization-metadata>
                    <div class="ndb:min-w-0">
                        <dt class="ndb:sr-only">Configured handler</dt>
                        <dd
                            :title="selectedAuthorizationDecision.handler_name"
                            class="ndb:truncate ndb:font-mono ndb:font-semibold"
                            x-text="selectedAuthorizationDecision.handler_short_name"
                        ></dd>
                    </div>
                    <div class="ndb:min-w-0">
                        <dt class="ndb:sr-only">Evaluation source</dt>
                        <dd
                            :title="selectedAuthorizationDecision.callsite_label"
                            class="ndb:truncate ndb:font-mono ndb:font-medium"
                            x-text="selectedAuthorizationDecision.callsite_short_label"
                        ></dd>
                    </div>
                </x-slot:metadata>
            </x-newdebugbar::inspector-detail-header>

            <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                <x-newdebugbar::filter-tabs label="Authorization decision detail" class="ndb:min-w-0">
                    @foreach (['decision' => ['Decision', 'check'], 'source' => ['Source', 'code']] as $tab => [$label, $icon])
                        <x-newdebugbar::filter-tab
                            data-ndb-authorization-detail-tab="{{ $tab }}"
                            @click="setAuthorizationDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                            ::aria-pressed="authorizationDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                            class="ndb:h-auto"
                        >
                            <x-newdebugbar::icon name="{{ $icon }}" size="3.5" />
                            <span>{{ $label }}</span>
                        </x-newdebugbar::filter-tab>
                    @endforeach
                </x-newdebugbar::filter-tabs>
            </div>

            <div class="ndb:p-4">
                <div
                    data-ndb-authorization-detail-panel="decision"
                    x-show.important="authorizationDetailTab === 'decision'"
                    class="ndb:space-y-5"
                >
                    <div
                        data-ndb-authorization-reason
                        :class="selectedAuthorizationDecision.result === 'allowed'
                            ? 'ndb:border-emerald-200 ndb:bg-emerald-50/65 ndb:dark:border-emerald-950 ndb:dark:bg-emerald-950/20'
                            : 'ndb:border-red-200 ndb:bg-red-50/70 ndb:dark:border-red-950 ndb:dark:bg-red-950/25'"
                        class="ndb:rounded-xl ndb:border ndb:p-3"
                    >
                        <p
                            :class="selectedAuthorizationDecision.result === 'allowed'
                                ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300'
                                : 'ndb:text-red-700 ndb:dark:text-red-300'"
                            class="ndb:text-xs ndb:font-bold"
                            x-text="
                                selectedAuthorizationDecision.result === 'allowed'
                                    ? 'Laravel allowed this ability'
                                    : 'Laravel denied this ability'
                            "
                        ></p>
                        <p
                            class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                            x-text="selectedAuthorizationDecision.result_message ?? 'No reason was returned.'"
                        ></p>
                        <dl
                            x-show.important="
                                selectedAuthorizationDecision.result_code !== null ||
                                selectedAuthorizationDecision.result_status !== null
                            "
                            class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:gap-x-4 ndb:gap-y-1 ndb:text-[11px]"
                        >
                            <div
                                x-show.important="selectedAuthorizationDecision.result_code !== null"
                                class="ndb:flex ndb:gap-1.5"
                            >
                                <dt class="ndb:font-semibold ndb:text-zinc-400">Code</dt>
                                <dd
                                    class="ndb:font-mono ndb:font-semibold"
                                    x-text="selectedAuthorizationDecision.result_code"
                                ></dd>
                            </div>
                            <div
                                x-show.important="selectedAuthorizationDecision.result_status !== null"
                                class="ndb:flex ndb:gap-1.5"
                            >
                                <dt class="ndb:font-semibold ndb:text-zinc-400">HTTP status</dt>
                                <dd
                                    class="ndb:font-semibold ndb:tabular-nums"
                                    x-text="selectedAuthorizationDecision.result_status"
                                ></dd>
                            </div>
                        </dl>
                    </div>

                    <div data-ndb-authorization-actor-detail>
                        <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Actor
                        </p>
                        <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                            <dl class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                <div class="ndb:grid ndb:gap-1 ndb:px-3 ndb:py-2.5 ndb:sm:grid-cols-[7rem_minmax(0,1fr)] ndb:sm:gap-4">
                                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Type</dt>
                                    <dd
                                        class="ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                        x-text="selectedAuthorizationDecision.actor_type ?? 'Guest'"
                                    ></dd>
                                </div>
                                <div
                                    x-show.important="selectedAuthorizationDecision.actor_identifier !== null"
                                    class="ndb:grid ndb:gap-1 ndb:px-3 ndb:py-2.5 ndb:sm:grid-cols-[7rem_minmax(0,1fr)] ndb:sm:gap-4"
                                >
                                    <dt
                                        class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                                        x-text="selectedAuthorizationDecision.actor_identifier_name ?? 'Identifier'"
                                    ></dt>
                                    <dd
                                        class="ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                        x-text="selectedAuthorizationDecision.actor_identifier"
                                    ></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div data-ndb-authorization-arguments-detail>
                        <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Ordered arguments
                        </p>
                        <p
                            x-show.important="selectedAuthorizationDecision.arguments.length === 0"
                            class="ndb:rounded-xl ndb:border ndb:border-dashed ndb:border-zinc-300 ndb:px-3 ndb:py-4 ndb:text-xs ndb:text-zinc-500 ndb:dark:border-zinc-700 ndb:dark:text-zinc-400"
                        >
                            No target or additional arguments were supplied.
                        </p>
                        <div
                            x-show.important="selectedAuthorizationDecision.arguments.length > 0"
                            class="ndb:divide-y ndb:divide-zinc-200/90 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800"
                        >
                            <template
                                x-for="argument in selectedAuthorizationDecision.arguments"
                                :key="argument.position"
                            >
                                <div class="ndb:grid ndb:gap-1 ndb:px-3 ndb:py-2.5 ndb:sm:grid-cols-[7rem_minmax(0,1fr)] ndb:sm:gap-4">
                                    <p
                                        class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                                        x-text="argument.role_label"
                                    ></p>
                                    <div class="ndb:min-w-0">
                                        <p
                                            class="ndb:break-words ndb:text-xs ndb:font-bold"
                                            x-text="argument.label"
                                        ></p>
                                        <code
                                            class="ndb:mt-0.5 ndb:block ndb:break-words ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                            x-text="argument.type"
                                        ></code>
                                        <p
                                            x-show.important="argument.identity_label !== null"
                                            class="ndb:mt-1 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                            x-text="argument.identity_label"
                                        ></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div data-ndb-authorization-handler-detail>
                        <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Configured handler
                        </p>
                        <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:dark:border-zinc-800">
                            <div class="ndb:min-w-0">
                                <code
                                    class="ndb:block ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-bold"
                                    x-text="selectedAuthorizationDecision.handler_name"
                                ></code>
                                <p
                                    class="ndb:mt-1 ndb:text-[11px] ndb:capitalize ndb:text-zinc-400"
                                    x-text="selectedAuthorizationDecision.handler_kind"
                                ></p>
                            </div>
                            <button
                                type="button"
                                data-ndb-authorization-copy-handler
                                @click="copyText(selectedAuthorizationDecision.handler_name)"
                                class="ndb:inline-flex ndb:min-h-9 ndb:shrink-0 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                            >
                                <x-newdebugbar::icon name="copy" size="3.5" />
                                Copy
                            </button>
                        </div>
                    </div>

                    <div class="ndb:rounded-xl ndb:bg-indigo-50/70 ndb:p-3 ndb:dark:bg-indigo-950/25">
                        <p class="ndb:text-xs ndb:font-bold ndb:text-indigo-700 ndb:dark:text-indigo-300">Check next</p>
                        <p
                            class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                            x-text="selectedAuthorizationDecision.check_next"
                        ></p>
                    </div>
                </div>

                <div
                    data-ndb-authorization-detail-panel="source"
                    x-show.important="authorizationDetailTab === 'source'"
                    class="ndb:space-y-5"
                >
                    <div class="ndb:flex ndb:justify-end">
                        <button
                            type="button"
                            data-ndb-authorization-copy-evidence
                            @click="copyText(selectedAuthorizationDecision.copy_evidence)"
                            class="ndb:inline-flex ndb:min-h-9 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                        >
                            <x-newdebugbar::icon name="copy" size="3.5" />
                            Copy decision evidence
                        </button>
                    </div>

                    <div>
                        <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Handler definition
                        </p>
                        <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3 ndb:rounded-xl ndb:bg-zinc-100/75 ndb:p-3 ndb:dark:bg-zinc-900">
                            <div class="ndb:min-w-0">
                                <code
                                    class="ndb:block ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-bold"
                                    x-text="selectedAuthorizationDecision.handler_name"
                                ></code>
                                <code
                                    class="ndb:mt-1 ndb:block ndb:break-words ndb:font-mono ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                    x-text="
                                        selectedAuthorizationDecision.handler_source_label ??
                                        'Definition source unavailable'
                                    "
                                ></code>
                            </div>
                            <button
                                x-show.important="selectedAuthorizationDecision.handler_source_label !== null"
                                type="button"
                                data-ndb-authorization-copy-handler-source
                                @click="copyText(selectedAuthorizationDecision.handler_source_label)"
                                class="ndb:inline-flex ndb:min-h-9 ndb:shrink-0 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                            >
                                <x-newdebugbar::icon name="copy" size="3.5" />
                                Copy
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Evaluation origin
                        </p>
                        <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3 ndb:rounded-xl ndb:bg-zinc-100/75 ndb:p-3 ndb:dark:bg-zinc-900">
                            <code
                                class="ndb:min-w-0 ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-bold"
                                x-text="selectedAuthorizationDecision.callsite_label ?? 'Source unavailable'"
                            ></code>
                            <button
                                x-show.important="selectedAuthorizationDecision.callsite_label !== null"
                                type="button"
                                data-ndb-authorization-copy-callsite
                                @click="copyText(selectedAuthorizationDecision.callsite_label)"
                                class="ndb:inline-flex ndb:min-h-9 ndb:shrink-0 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                            >
                                <x-newdebugbar::icon name="copy" size="3.5" />
                                Copy
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Application stack
                        </p>
                        <p
                            x-show.important="selectedAuthorizationDecision.stack.length === 0"
                            class="ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        >
                            No application stack was captured.
                        </p>
                        <div
                            x-show.important="selectedAuthorizationDecision.stack.length > 0"
                            class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                        >
                            <template x-for="(frame, index) in selectedAuthorizationDecision.stack" :key="index">
                                <div class="ndb:py-3 ndb:first:pt-0">
                                    <code
                                        class="ndb:block ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                                        x-text="frame.file + ':' + frame.line"
                                    ></code>
                                    <p
                                        class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                        x-text="frame.function || 'Application call'"
                                    ></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <p class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:text-zinc-400">
                        Laravel reports the final result. It does not identify whether a Gate before or after hook made
                        the decision.
                    </p>
                </div>
            </div>
        </div>
    </template>

    <div
        x-show.important="! selectedAuthorizationDecision"
        class="ndb:grid ndb:min-h-[32rem] ndb:place-items-center ndb:p-6 ndb:lg:min-h-0"
    >
        <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-400">Choose a decision to inspect its evidence.</p>
    </div>
</section>
