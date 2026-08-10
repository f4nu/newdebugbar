const PROFILE_HEADER = 'X-NewDebugBar-Profile';
const TRACE_HEADER = 'X-NewDebugBar-Livewire-Trace';
const PROFILE_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const SOURCE_CONTRACT = 'livewire_action_origin_v1';

const now = (runtime) => runtime.performance?.now?.() ?? Date.now();
const offset = (runtime, trace) => Math.max(0, now(runtime) - (trace.sentAt ?? trace.createdAt));

const valueAtPath = (value, path) => path.split('.').reduce((current, segment) => (
  current !== null && current !== undefined ? current[segment] : undefined
), value);

const valueType = (value) => {
  if (value === undefined) return 'missing';
  if (value === null) return 'null';
  if (Array.isArray(value)) return 'array';
  if (['boolean', 'number', 'string'].includes(typeof value)) return typeof value;
  if (typeof value === 'object') return 'object';
  return 'unknown';
};

const sameValue = (left, right) => {
  try {
    return JSON.stringify(left) === JSON.stringify(right);
  } catch {
    return null;
  }
};

export function safeActionOrigin(action) {
  try {
    const origin = action?.origin;
    const directive = origin?.directive?.rawName ?? origin?.directive?.raw ?? null;
    const element = origin?.el?.tagName?.toLowerCase?.() ?? null;
    const safeDirective = typeof directive === 'string' && /^wire:[a-z0-9_.:-]+$/i.test(directive)
      ? directive
      : null;
    const safeElement = typeof element === 'string' && /^[a-z][a-z0-9-]{0,29}$/.test(element)
      ? element
      : null;

    return {
      status: safeDirective || safeElement ? 'observed' : 'unknown',
      directive: safeDirective,
      element: safeElement,
      contract: SOURCE_CONTRACT,
    };
  } catch {
    return { status: 'unknown', directive: null, element: null, contract: SOURCE_CONTRACT };
  }
}

const validAppendTarget = (runtime, profileId, target) => {
  try {
    const url = new URL(target, runtime.location.href);
    const nonce = url.searchParams.get('nonce');

    return url.origin === runtime.location.origin
      && url.pathname === `/__newdebugbar/livewire-trace/${profileId}`
      && PROFILE_PATTERN.test(profileId)
      && PROFILE_PATTERN.test(nonce ?? '')
      ? { url: url.href, nonce }
      : null;
  } catch {
    return null;
  }
};

const addPhase = (runtime, trace, message, name) => {
  message.phases.push({ name, at_ms: offset(runtime, trace) });
};

const messageTrace = (trace, message) => {
  if (!trace.messages.has(message)) {
    trace.messages.set(message, {
      component_id: String(message?.component?.id ?? ''),
      outcome: 'unknown',
      phases: [],
      state: [],
    });
  }

  return trace.messages.get(message);
};

const compareBrowserState = (message) => Object.keys(message?.updates ?? {}).map((path) => {
  const canonical = valueAtPath(message.component?.canonical, path);
  const browser = valueAtPath(message.component?.reactive, path);

  return {
    path,
    matches_server: sameValue(canonical, browser),
    browser_type: valueType(browser),
  };
});

const requestPayload = (trace) => ({
  schema_version: 1,
  idempotency_key: trace.target.nonce,
  request: {
    outcome: trace.outcome,
    status: trace.status,
    wait_ms: trace.responseAt === null || trace.sentAt === null ? null : trace.responseAt - trace.sentAt,
    parse_ms: trace.parsedAt === null || trace.responseAt === null ? null : trace.parsedAt - trace.responseAt,
    total_ms: trace.finishedAt === null || trace.sentAt === null ? null : trace.finishedAt - trace.sentAt,
  },
  messages: [...trace.messages.values()],
  actions: trace.actions,
  failures: trace.failures,
});

const csrfToken = (runtime) => runtime.document
  ?.querySelector?.('script[data-update-uri][data-csrf]')
  ?.dataset?.csrf ?? '';

export function installLivewireTrace(runtime = window) {
  if (runtime.__newDebugBarLivewireTrace) return runtime.__newDebugBarLivewireTrace;

  const livewire = runtime.Livewire;
  const requests = new WeakMap();
  const pending = new Set();
  const unsubscribers = [];
  const afterRender = (callback) => {
    const schedule = runtime.requestAnimationFrame ?? ((next) => runtime.setTimeout(next, 0));
    schedule(() => schedule(callback));
  };

  const append = async (trace) => {
    if (trace.appended || !trace.target || typeof runtime.fetch !== 'function') return;
    trace.appended = true;
    pending.delete(trace);

    try {
      const token = csrfToken(runtime);
      const response = await runtime.fetch(trace.target.url, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify(requestPayload(trace)),
      });

      if (!response?.ok) return;
      const result = await response.json?.();

      runtime.dispatchEvent?.(new runtime.CustomEvent('newdebugbar-profile-trace-updated', {
        detail: {
          profileId: trace.profileId,
          revision: result?.revision ?? null,
        },
      }));
    } catch {
      // Browser evidence must never change the host request or navigation.
    }
  };

  const scheduleAppend = (trace) => {
    if (trace.appended || trace.scheduled) return;
    trace.scheduled = true;
    afterRender(() => append(trace));
  };

  const navigationCleanup = () => {
    pending.forEach((trace) => append(trace));
  };
  const refreshToolbar = (event) => {
    try {
      const profileId = event.detail?.profileId;
      if (!PROFILE_PATTERN.test(profileId ?? '')) return;

      const toolbar = livewire?.getByName?.('newdebugbar.toolbar')?.[0];
      Promise.resolve(toolbar?.refreshProfileTrace?.(profileId)).catch(() => {});
    } catch {
      // A stale toolbar must not affect browser tracing.
    }
  };

  const state = {
    installed: false,
    destroy() {
      unsubscribers.splice(0).forEach((unsubscribe) => unsubscribe?.());
      runtime.document?.removeEventListener?.('livewire:navigating', navigationCleanup);
      runtime.removeEventListener?.('newdebugbar-profile-trace-updated', refreshToolbar);
      pending.clear();
      delete runtime.__newDebugBarLivewireTrace;
    },
  };
  runtime.__newDebugBarLivewireTrace = state;

  if (typeof livewire?.interceptRequest !== 'function'
    || typeof livewire?.interceptMessage !== 'function'
    || typeof livewire?.interceptAction !== 'function') return state;

  state.installed = true;
  runtime.document?.addEventListener?.('livewire:navigating', navigationCleanup);
  runtime.addEventListener?.('newdebugbar-profile-trace-updated', refreshToolbar);

  unsubscribers.push(livewire.interceptRequest(({
    request,
    onSend,
    onCancel,
    onFailure,
    onResponse,
    onParsed,
    onError,
    onRedirect,
    onSuccess,
    onFinish,
  }) => {
    const trace = {
      request,
      createdAt: now(runtime),
      sentAt: null,
      responseAt: null,
      parsedAt: null,
      finishedAt: null,
      status: null,
      outcome: 'failure',
      profileId: null,
      target: null,
      messages: new Map(),
      actions: [],
      failures: [],
      scheduled: false,
      appended: false,
    };
    requests.set(request, trace);
    pending.add(trace);

    onSend(() => { trace.sentAt = now(runtime); });
    onResponse(({ response }) => {
      trace.responseAt = now(runtime);
      trace.status = Number.isInteger(response?.status) ? response.status : null;
      trace.profileId = response?.headers?.get?.(PROFILE_HEADER) ?? null;
      trace.target = validAppendTarget(
        runtime,
        trace.profileId,
        response?.headers?.get?.(TRACE_HEADER),
      );
    });
    onParsed(() => { trace.parsedAt = now(runtime); });
    onSuccess(({ response }) => {
      trace.outcome = 'success';
      trace.status = Number.isInteger(response?.status) ? response.status : trace.status;
    });
    onRedirect(() => {
      trace.outcome = 'redirected';
      trace.finishedAt = now(runtime);
      append(trace);
    });
    onError(() => {
      trace.outcome = 'error';
      trace.failures.push({ phase: 'request', kind: 'error' });
    });
    onFailure(() => {
      trace.outcome = 'failure';
      trace.failures.push({ phase: 'request', kind: 'failure' });
    });
    onCancel(() => {
      trace.outcome = 'cancelled';
      trace.failures.push({ phase: 'request', kind: 'cancelled' });
    });
    onFinish(() => {
      trace.finishedAt = now(runtime);
      scheduleAppend(trace);
    });
  }));

  unsubscribers.push(livewire.interceptMessage(({
    message,
    onSend,
    onCancel,
    onFailure,
    onError,
    onSuccess,
    onSkipped,
    onFinish,
  }) => {
    const trace = () => requests.get(message.request);
    const record = () => {
      const request = trace();
      return request ? messageTrace(request, message) : null;
    };

    onSend(() => {
      const request = trace();
      const item = record();
      if (request && item) addPhase(runtime, request, item, 'send');
    });
    onSuccess(({ onSync, onEffect, onMorph, onRender }) => {
      const request = trace();
      const item = record();
      if (!request || !item) return;
      item.outcome = 'success';
      addPhase(runtime, request, item, 'success');
      onSync(() => {
        item.state = compareBrowserState(message);
        addPhase(runtime, request, item, 'sync');
      });
      onEffect(() => addPhase(runtime, request, item, 'effect'));
      onMorph(() => addPhase(runtime, request, item, 'morph'));
      onRender(() => addPhase(runtime, request, item, 'render'));
    });
    onSkipped?.(() => {
      const request = trace();
      const item = record();
      if (!request || !item) return;
      item.outcome = 'skipped';
      addPhase(runtime, request, item, 'skipped');
    });
    onError(() => {
      const item = record();
      if (item) item.outcome = 'error';
      trace()?.failures.push({ phase: 'message', kind: 'error' });
    });
    onFailure(() => {
      const item = record();
      if (item) item.outcome = 'failure';
      trace()?.failures.push({ phase: 'message', kind: 'failure' });
    });
    onCancel(() => {
      const item = record();
      if (item) item.outcome = 'cancelled';
      trace()?.failures.push({ phase: 'message', kind: 'cancelled' });
    });
    onFinish(() => {
      const request = trace();
      const item = record();
      if (request && item) addPhase(runtime, request, item, 'finish');
    });
  }));

  unsubscribers.push(livewire.interceptAction(({ action, onSend }) => {
    if (action?.component?.name === 'newdebugbar.toolbar') return;

    onSend(() => {
      const trace = requests.get(action?.message?.request);
      const componentId = action?.component?.id;
      const name = action?.name;
      if (!trace || typeof componentId !== 'string' || typeof name !== 'string') return;

      trace.actions.push({
        component_id: componentId,
        name,
        source: safeActionOrigin(action),
      });
    });
  }));

  return state;
}
