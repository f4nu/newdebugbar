{{-- Presents one authorization decision through its result and retained source evidence. --}}
<x-newdebugbar::inspector-detail-pane
    detail-open="authorizationDetailOpen"
    detail-ref="authorizationDetail"
    detail-label="Selected authorization decision details"
    back-label="Decisions"
    close-action="closeAuthorizationDetail()"
    data-ndb-authorization-detail
>
    <x-slot:back>
        <x-newdebugbar::inspector-detail-back
            data-ndb-authorization-detail-back
            @click="closeAuthorizationDetail()"
            label="Decisions"
        />
    </x-slot:back>

    <template x-if="selectedAuthorizationDecision">
        <div class="ndb:flex ndb:flex-col">
            <x-newdebugbar::inspector-detail-header data-ndb-authorization-header>
                <x-slot:title>
                    <div class="ndb:min-w-0">
                        <h3
                            data-ndb-authorization-detail-ability
                            class="ndb:block ndb:break-words ndb:text-base ndb:font-bold ndb:leading-6"
                            x-text="selectedAuthorizationDecision.ability"
                        ></h3>
                        <p
                            class="ndb:mt-1 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                            x-text="'Decision #' + selectedAuthorizationDecision.execution"
                        ></p>
                    </div>
                </x-slot:title>
            </x-newdebugbar::inspector-detail-header>

            <x-newdebugbar::inspector-detail-tabs label="Authorization decision detail">
                @foreach (['decision' => 'Decision', 'source' => 'Source'] as $tab => $label)
                    <x-newdebugbar::filter-tab
                        variant="segmented"
                        data-ndb-authorization-detail-tab="{{ $tab }}"
                        @click="setAuthorizationDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                        ::aria-pressed="authorizationDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                    >
                        {{ $label }}
                    </x-newdebugbar::filter-tab>
                @endforeach
            </x-newdebugbar::inspector-detail-tabs>

            <template x-if="authorizationDetailTab === 'decision'">
                <div data-ndb-authorization-detail-panel="decision" class="ndb:space-y-5 ndb:p-4">
                    <x-newdebugbar::inspector-facts columns="2" data-ndb-authorization-metadata>
                        <x-newdebugbar::inspector-fact label="Result">
                            <x-slot:value
                                data-ndb-authorization-detail-result
                                ::class="selectedAuthorizationDecision.result === 'allowed'
                                    ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300'
                                    : 'ndb:text-red-700 ndb:dark:text-red-300'"
                                class="ndb:text-xs ndb:font-bold"
                                x-text="selectedAuthorizationDecision.result_label"
                            ></x-slot:value>
                        </x-newdebugbar::inspector-fact>

                        <x-newdebugbar::inspector-fact label="Actor" data-ndb-authorization-actor-detail>
                            <x-slot:value>
                                <span
                                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold"
                                    x-text="selectedAuthorizationDecision.actor_label"
                                ></span>
                                <code
                                    x-show.important="selectedAuthorizationDecision.actor_type !== null"
                                    class="ndb:mt-0.5 ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                    x-text="selectedAuthorizationDecision.actor_type"
                                ></code>
                                <span
                                    x-show.important="selectedAuthorizationDecision.actor_identifier !== null"
                                    class="ndb:mt-0.5 ndb:flex ndb:min-w-0 ndb:gap-1.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                >
                                    <span x-text="selectedAuthorizationDecision.actor_identifier_name ?? 'Identifier'"></span>
                                    <span
                                        class="ndb:min-w-0 ndb:truncate ndb:font-semibold ndb:tabular-nums"
                                        x-text="selectedAuthorizationDecision.actor_identifier"
                                    ></span>
                                </span>
                            </x-slot:value>
                        </x-newdebugbar::inspector-fact>

                        <x-newdebugbar::inspector-fact
                            label="Response code"
                            x-show.important="selectedAuthorizationDecision.result_code !== null"
                        >
                            <x-slot:value
                                class="ndb:truncate ndb:text-xs ndb:font-semibold ndb:tabular-nums"
                                x-text="selectedAuthorizationDecision.result_code"
                            ></x-slot:value>
                        </x-newdebugbar::inspector-fact>

                        <x-newdebugbar::inspector-fact
                            label="HTTP status"
                            x-show.important="selectedAuthorizationDecision.result_status !== null"
                        >
                            <x-slot:value
                                class="ndb:truncate ndb:text-xs ndb:font-semibold ndb:tabular-nums"
                                x-text="selectedAuthorizationDecision.result_status"
                            ></x-slot:value>
                        </x-newdebugbar::inspector-fact>
                    </x-newdebugbar::inspector-facts>

                    <x-newdebugbar::inspector-definition-list
                        data-ndb-authorization-arguments-detail
                        x-show.important="
                            selectedAuthorizationDecision.arguments.length > 0 ||
                            selectedAuthorizationDecision.result_message !== null
                        "
                    >
                        <template x-for="argument in selectedAuthorizationDecision.arguments" :key="argument.position">
                            <x-newdebugbar::inspector-definition-row>
                                <x-slot:term x-text="argument.role_label"></x-slot:term>
                                <x-slot:value class="ndb:min-w-0">
                                    <span
                                        class="ndb:block ndb:break-words ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                        x-text="argument.label"
                                    ></span>
                                    <code
                                        class="ndb:mt-0.5 ndb:block ndb:break-words ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                        x-text="argument.type"
                                    ></code>
                                    <span
                                        x-show.important="argument.identity_label !== null"
                                        class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                        x-text="argument.identity_label"
                                    ></span>
                                </x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                        </template>

                        <x-newdebugbar::inspector-definition-row
                            label="Explanation"
                            x-show.important="selectedAuthorizationDecision.result_message !== null"
                        >
                            <x-slot:value x-text="selectedAuthorizationDecision.result_message"></x-slot:value>
                        </x-newdebugbar::inspector-definition-row>
                    </x-newdebugbar::inspector-definition-list>

                    <section>
                        <x-newdebugbar::inspector-explanation>
                            <x-slot:heading>What should I inspect if this result looks wrong?</x-slot:heading>
                            <x-slot:body x-text="selectedAuthorizationDecision.check_next"></x-slot:body>
                        </x-newdebugbar::inspector-explanation>
                    </section>
                </div>
            </template>

            <template x-if="authorizationDetailTab === 'source'">
                <div data-ndb-authorization-detail-panel="source">
                    <x-newdebugbar::inspector-source-panel
                        frames="selectedAuthorizationDecision.stack"
                        empty-label="No application stack was captured for this decision."
                    >
                        <x-slot:actions>
                            <x-newdebugbar::inspector-action
                                icon="copy"
                                data-ndb-authorization-copy-evidence
                                @click="copyText(selectedAuthorizationDecision.copy_evidence)"
                            >
                                Copy evidence
                            </x-newdebugbar::inspector-action>
                        </x-slot:actions>

                        <x-newdebugbar::inspector-source-fact label="Handler" :code="true">
                            <x-slot:value x-text="selectedAuthorizationDecision.handler_name"></x-slot:value>
                        </x-newdebugbar::inspector-source-fact>
                        <x-newdebugbar::inspector-source-fact label="Handler source">
                            <x-slot:value>
                                <template x-if="selectedAuthorizationDecision.handler_source_label">
                                    <x-newdebugbar::inspector-source-link
                                        data-ndb-authorization-copy-handler-source
                                        @click="copyText(selectedAuthorizationDecision.handler_source_label)"
                                        ::title="selectedAuthorizationDecision.handler_source_label"
                                    >
                                        <x-slot:value x-text="selectedAuthorizationDecision.handler_source_label"></x-slot:value>
                                    </x-newdebugbar::inspector-source-link>
                                </template>
                                <template x-if="! selectedAuthorizationDecision.handler_source_label">
                                    <span>—</span>
                                </template>
                            </x-slot:value>
                        </x-newdebugbar::inspector-source-fact>
                        <x-newdebugbar::inspector-source-fact label="Evaluation source">
                            <x-slot:value>
                                <template x-if="selectedAuthorizationDecision.callsite_label">
                                    <x-newdebugbar::inspector-source-link
                                        data-ndb-authorization-copy-callsite
                                        @click="copyText(selectedAuthorizationDecision.callsite_label)"
                                        ::title="selectedAuthorizationDecision.callsite_label"
                                    >
                                        <x-slot:value x-text="selectedAuthorizationDecision.callsite_label"></x-slot:value>
                                    </x-newdebugbar::inspector-source-link>
                                </template>
                                <template x-if="! selectedAuthorizationDecision.callsite_label">
                                    <span>—</span>
                                </template>
                            </x-slot:value>
                        </x-newdebugbar::inspector-source-fact>
                    </x-newdebugbar::inspector-source-panel>

                    <p class="ndb:px-4 ndb:pb-4 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        Laravel reports the final result, but it cannot identify whether a Gate before or after hook
                        made the decision.
                    </p>
                </div>
            </template>
        </div>
    </template>

    <x-newdebugbar::inspector-detail-empty
        x-show.important="! selectedAuthorizationDecision"
        label="Choose a decision to inspect its evidence."
    />
</x-newdebugbar::inspector-detail-pane>
