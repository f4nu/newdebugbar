{{-- Shows only the selected query execution and the active evidence tab. --}}
<x-newdebugbar::inspector-detail-pane
    detail-open="queryDetailOpen"
    detail-ref="queryDetail"
    detail-label="Selected query details"
    back-label="Queries"
    close-action="closeQueryDetail()"
    data-ndb-query-detail
    class="ndb:border-x-0"
>
    <x-slot:back>
        <x-newdebugbar::inspector-detail-back data-ndb-query-detail-back @click="closeQueryDetail()" label="Queries" />
    </x-slot:back>

    <template x-if="selectedQueryRecord && selectedQuery">
        <div data-ndb-query-active-detail class="ndb:flex ndb:flex-col">
            <x-newdebugbar::inspector-detail-header layout="wrap" data-ndb-query-detail-header>
                <x-slot:title>
                    <h3
                        data-ndb-query-detail-title
                        class="ndb:text-sm ndb:font-bold ndb:leading-5"
                        x-text="
                            selectedQueryRecord.repeated
                                ? 'Repeated query pattern'
                                : `Query #${selectedQuery.execution}`
                        "
                    ></h3>
                </x-slot:title>
                <x-slot:aside></x-slot:aside>
                <x-slot:metadata>
                    <div x-show.important="selectedQueryRecord.repeated" class="ndb:flex ndb:items-baseline ndb:gap-1">
                        <dt class="ndb:font-semibold">Executions</dt>
                        <dd class="ndb:font-semibold ndb:tabular-nums" x-text="selectedQueryRecord.count"></dd>
                    </div>
                    <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1">
                        <dt class="ndb:font-semibold">Connection</dt>
                        <dd class="ndb:max-w-40 ndb:truncate ndb:font-semibold" x-text="selectedQuery.connection"></dd>
                    </div>
                    <div
                        x-show.important="selectedQuery.source_available"
                        class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1"
                    >
                        <dt class="ndb:font-semibold">Source</dt>
                        <dd class="ndb:min-w-0">
                            <x-newdebugbar::inspector-source-link
                                ::title="selectedQuery.source_label"
                                @click="setQueryDetailTab('source')"
                            >
                                <x-slot:value x-text="selectedQuery.source_short_label"></x-slot:value>
                            </x-newdebugbar::inspector-source-link>
                        </dd>
                    </div>
                </x-slot:metadata>
            </x-newdebugbar::inspector-detail-header>

            <x-newdebugbar::inspector-detail-tabs label="Query evidence">
                @foreach (['query' => 'Query', 'bindings' => 'Bindings', 'source' => 'Source', 'explain' => 'EXPLAIN'] as $tab => $label)
                    <x-newdebugbar::filter-tab
                        variant="segmented"
                        data-ndb-query-detail-tab="{{ $tab }}"
                        @click="setQueryDetailTab('{{ $tab }}')"
                        ::aria-pressed="queryDetailTab === '{{ $tab }}'"
                        class="ndb:h-auto ndb:min-h-8"
                    >
                        {{ $label }}
                        @if ($tab === 'bindings')
                            <span
                                data-ndb-query-bindings-count
                                class="ndb:tabular-nums ndb:opacity-60"
                                x-text="selectedQuery.bindings_count"
                            ></span>
                        @endif
                    </x-newdebugbar::filter-tab>
                @endforeach
            </x-newdebugbar::inspector-detail-tabs>

            <template x-if="queryDetailTab === 'query'">
                <section data-ndb-query-detail-panel="query" class="ndb:space-y-4 ndb:p-4">
                    <template x-if="selectedQueryRecord.repeated">
                        <div class="ndb:max-w-sm">
                            <p class="ndb:mb-1.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                Execution
                            </p>
                            <x-newdebugbar::select-field
                                label="Choose repeated query execution"
                                data-ndb-query-execution-select
                                x-model.number="querySelectedExecution"
                                @change="selectQueryExecution(Number($event.target.value))"
                            >
                                <template
                                    x-for="execution in selectedQueryRecord.executions"
                                    :key="execution.execution"
                                >
                                    <option
                                        :value="execution.execution"
                                        x-text="
                                            `#${execution.execution} — ${Number(execution.duration_ms).toFixed(2)} ms`
                                        "
                                    ></option>
                                </template>
                            </x-newdebugbar::select-field>
                        </div>
                    </template>

                    <x-newdebugbar::inspector-facts :bordered="false">
                        <x-newdebugbar::inspector-fact label="Duration">
                            <x-slot:value
                                class="ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                x-text="`${Number(selectedQuery.duration_ms).toFixed(2)} ms`"
                            ></x-slot:value>
                        </x-newdebugbar::inspector-fact>
                        <x-newdebugbar::inspector-fact label="Query time">
                            <x-slot:value
                                class="ndb:text-xs ndb:font-semibold ndb:tabular-nums"
                                x-text="`${Number(selectedQuery.query_time_percent).toFixed(1)}%`"
                            ></x-slot:value>
                        </x-newdebugbar::inspector-fact>
                        <x-newdebugbar::inspector-fact label="Type">
                            <x-slot:value
                                class="ndb:text-xs ndb:font-semibold"
                                x-text="formatQueryType(selectedQuery.query_type)"
                            ></x-slot:value>
                        </x-newdebugbar::inspector-fact>
                        <x-newdebugbar::inspector-fact label="Connection">
                            <x-slot:value
                                ::title="selectedQuery.connection"
                                class="ndb:truncate ndb:text-xs ndb:font-semibold"
                                x-text="selectedQuery.connection"
                            ></x-slot:value>
                        </x-newdebugbar::inspector-fact>
                    </x-newdebugbar::inspector-facts>

                    <x-newdebugbar::inspector-evidence label="SQL" language="sql">
                        <x-slot:aside>
                            <x-newdebugbar::inspector-action
                                icon="copy"
                                data-ndb-query-copy-sql
                                @click="copyText(selectedQuery.sql)"
                            >
                                Copy SQL
                            </x-newdebugbar::inspector-action>
                        </x-slot:aside>
                        <x-slot:value
                            data-ndb-query-sql
                            x-text="selectedQuery.sql"
                            x-effect="
                                selectedQuery?.execution;
                                $nextTick(() => highlightQueryCode($el));
                            "
                        ></x-slot:value>
                    </x-newdebugbar::inspector-evidence>

                    <x-newdebugbar::inspector-explanation
                        x-show.important="selectedQueryRecord.likely_n_plus_one"
                        title="Why this may be an N+1 query"
                        description="The same application call site ran this query at least three times with different bindings. If that work is unexpected, inspect Source for the loop and consider eager loading or batching."
                    />
                    <x-newdebugbar::inspector-explanation
                        x-show.important="selectedQueryRecord.repeated && ! selectedQueryRecord.likely_n_plus_one"
                        title="Why these executions are grouped"
                        description="They share the same normalized SQL and connection. Compare their bindings and Source; if each run is unnecessary, batch, cache, or move the shared work."
                    />
                    <x-newdebugbar::inspector-explanation
                        x-show.important="! selectedQueryRecord.repeated && selectedQuery.slow"
                        title="Why this query needs attention"
                        description="It crossed the configured slow-query threshold. If the delay is unexpected, inspect EXPLAIN for scans and indexes, then use Source to find the calling code."
                    />
                </section>
            </template>

            <template x-if="queryDetailTab === 'bindings'">
                <section data-ndb-query-detail-panel="bindings" class="ndb:space-y-4 ndb:p-4">
                    <template x-if="selectedQuery.bindings_count > 0">
                        <x-newdebugbar::inspector-evidence label="Bindings" language="json">
                            <x-slot:value
                                data-ndb-query-bindings
                                x-text="formatQueryEvidence(selectedQuery.bindings)"
                                x-effect="
                                    selectedQuery?.execution;
                                    $nextTick(() => highlightQueryCode($el));
                                "
                            ></x-slot:value>
                        </x-newdebugbar::inspector-evidence>
                    </template>

                    <x-newdebugbar::empty-state
                        x-show.important="selectedQuery.bindings_count === 0"
                        label="This query has no bindings."
                    />

                    <div x-show.important="selectedQuery.runnable_available" class="ndb:flex ndb:justify-end">
                        <x-newdebugbar::inspector-action
                            icon="copy"
                            data-ndb-query-copy-runnable
                            @click="copyText(selectedQuery.runnable_sql)"
                        >
                            Copy runnable SQL
                        </x-newdebugbar::inspector-action>
                    </div>

                    <p
                        x-show.important="selectedQuery.bindings_complete === false"
                        class="ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400"
                    >
                        Some bindings were omitted, so runnable SQL is unavailable.
                    </p>
                </section>
            </template>

            <template x-if="queryDetailTab === 'source'">
                <div data-ndb-query-detail-panel="source">
                    <x-newdebugbar::inspector-source-panel
                        frames="selectedQuery.stack ?? []"
                        columns="1"
                        empty-label="No application stack was captured for this query."
                    >
                        <x-newdebugbar::inspector-source-fact label="Application call site">
                            <x-slot:value>
                                <x-newdebugbar::inspector-source-link
                                    ::title="selectedQuery.source_available ? 'Copy ' + selectedQuery.source_label : null"
                                    ::disabled="! selectedQuery.source_available"
                                    @click="copyText(selectedQuery.source_label)"
                                >
                                    <x-slot:value x-text="selectedQuery.source_label"></x-slot:value>
                                </x-newdebugbar::inspector-source-link>
                            </x-slot:value>
                        </x-newdebugbar::inspector-source-fact>
                    </x-newdebugbar::inspector-source-panel>
                </div>
            </template>

            <template x-if="queryDetailTab === 'explain'">
                <section data-ndb-query-detail-panel="explain" class="ndb:space-y-4 ndb:p-4">
                    <p
                        x-show.important="! selectedQuery.explain_available"
                        class="ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        x-text="selectedQuery.explain_unavailable_reason"
                    ></p>

                    <x-newdebugbar::inspector-action
                        icon="search"
                        data-ndb-query-explain-action
                        x-show.important="selectedQuery.explain_available && ! queryExplainLoading"
                        @click="$wire.explainQuery(beginQueryExplain()).catch(() => failQueryExplain())"
                    >
                        <span
                            x-text="
                                queryExplain === null && queryExplainError === null
                                    ? 'Run EXPLAIN'
                                    : 'Run EXPLAIN again'
                            "
                        ></span>
                    </x-newdebugbar::inspector-action>

                    <p
                        x-show.important="queryExplainLoading"
                        data-ndb-query-explain-loading
                        role="status"
                        class="ndb:flex ndb:items-center ndb:gap-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                    >
                        <span class="ndb:size-1.5 ndb:shrink-0 ndb:animate-pulse ndb:rounded-full ndb:bg-indigo-500 ndb:motion-reduce:animate-none"></span>
                        <span>Running EXPLAIN…</span>
                    </p>

                    <template x-if="queryExplain !== null">
                        <div data-ndb-query-explain-result class="ndb:space-y-4">
                            <x-newdebugbar::inspector-facts :columns="2" :bordered="false">
                                <x-newdebugbar::inspector-fact label="Mode">
                                    <x-slot:value
                                        class="ndb:text-xs ndb:font-semibold"
                                        x-text="queryExplain.mode"
                                    ></x-slot:value>
                                </x-newdebugbar::inspector-fact>
                                <x-newdebugbar::inspector-fact label="Driver">
                                    <x-slot:value
                                        class="ndb:text-xs ndb:font-semibold"
                                        x-text="queryExplain.driver"
                                    ></x-slot:value>
                                </x-newdebugbar::inspector-fact>
                            </x-newdebugbar::inspector-facts>
                            <x-newdebugbar::inspector-evidence label="Plan" language="json">
                                <x-slot:value
                                    data-ndb-query-explain-plan
                                    x-text="formatQueryEvidence(queryExplain.rows)"
                                    x-effect="
                                        queryExplain;
                                        $nextTick(() => highlightQueryCode($el));
                                    "
                                ></x-slot:value>
                            </x-newdebugbar::inspector-evidence>
                            <x-newdebugbar::inspector-explanation
                                title="What to check in this plan"
                                description="If the plan scans far more rows than expected, check the query filters, indexes, and join order."
                            />
                        </div>
                    </template>

                    <div
                        x-show.important="queryExplainError !== null"
                        class="ndb:rounded-lg ndb:border ndb:border-red-200 ndb:bg-red-50/60 ndb:p-3 ndb:dark:border-red-950 ndb:dark:bg-red-950/20"
                    >
                        <p
                            data-ndb-query-explain-error
                            class="ndb:text-xs ndb:font-semibold ndb:leading-5 ndb:text-red-700 ndb:dark:text-red-300"
                            x-text="queryExplainError"
                        ></p>
                        <p class="ndb:mt-1 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Check the error, then copy runnable SQL from Bindings if you need to inspect it in a
                            database client.
                        </p>
                    </div>
                </section>
            </template>
        </div>
    </template>

    <x-newdebugbar::inspector-detail-empty
        data-ndb-query-detail-empty
        label="Choose a query to inspect its evidence."
        x-show.important="! selectedQuery"
        class="ndb:flex-1"
    />
</x-newdebugbar::inspector-detail-pane>
