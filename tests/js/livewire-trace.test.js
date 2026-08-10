import assert from 'node:assert/strict';
import test from 'node:test';

import { installLivewireTrace, safeActionOrigin } from '../../resources/js/livewire-trace.js';

const profileId = '550e8400-e29b-41d4-a716-446655440000';
const nonce = '660e8400-e29b-41d4-a716-446655440000';
const traceUrl = `https://viteclinic.test/__newdebugbar/livewire-trace/${profileId}?revision=1&nonce=${nonce}&expires=100&signature=signed`;

function callbackHooks(names, extra = {}) {
  const hooks = {};
  const context = { ...extra };

  names.forEach((name) => {
    context[`on${name}`] = (callback) => { hooks[name] = callback; };
  });

  return { context, hooks };
}

function browserRuntime() {
  const interceptors = { request: [], message: [], action: [] };
  const events = [];
  const listeners = {};
  const documentListeners = {};
  const fetches = [];
  const refreshed = [];
  let time = 100;
  let unsubscribed = 0;

  class CustomEvent {
    constructor(type, options) {
      this.type = type;
      this.detail = options.detail;
    }
  }

  const subscribe = (type) => (callback) => {
    interceptors[type].push(callback);
    return () => { unsubscribed += 1; };
  };

  const runtime = {
    interceptors,
    events,
    fetches,
    refreshed,
    get unsubscribed() { return unsubscribed; },
    location: { href: 'https://viteclinic.test/dashboard', origin: 'https://viteclinic.test' },
    performance: { now: () => { time += 1; return time; } },
    requestAnimationFrame: (callback) => callback(),
    setTimeout: (callback) => callback(),
    CustomEvent,
    Livewire: {
      interceptRequest: subscribe('request'),
      interceptMessage: subscribe('message'),
      interceptAction: subscribe('action'),
      getByName: () => [{ refreshProfileTrace: (id) => refreshed.push(id) }],
    },
    document: {
      querySelector: () => ({ dataset: { csrf: 'csrf-token' } }),
      addEventListener: (type, callback) => { documentListeners[type] = callback; },
      removeEventListener: (type) => { delete documentListeners[type]; },
    },
    addEventListener: (type, callback) => { listeners[type] = callback; },
    removeEventListener: (type) => { delete listeners[type]; },
    dispatchEvent: (event) => {
      events.push(event);
      listeners[event.type]?.(event);
    },
    dispatchDocumentEvent: (type) => documentListeners[type]?.({ type }),
    fetch: async (url, options) => {
      fetches.push({ url, options });
      return { ok: true, json: async () => ({ status: 'accepted', revision: 2 }) };
    },
  };

  return runtime;
}

async function tick() {
  await Promise.resolve();
  await Promise.resolve();
}

test('captures value-free public Livewire browser phases and appends once', async () => {
  const browser = browserRuntime();
  const state = installLivewireTrace(browser);
  const request = {};
  const canonicalCycle = {};
  canonicalCycle.self = canonicalCycle;
  const browserCycle = {};
  browserCycle.self = browserCycle;
  const component = {
    id: 'trace-component-1',
    name: 'diagnostics-fixture',
    canonical: {
      search: 'northline',
      password: 'server-secret',
      nothing: null,
      list: [1],
      count: 1,
      ready: true,
      object: { safe: true },
      cycle: canonicalCycle,
      unusual: Symbol('server'),
    },
    reactive: {
      search: 'northline',
      password: 'browser-secret',
      nothing: null,
      list: [1],
      count: 1,
      ready: true,
      object: { safe: true },
      cycle: browserCycle,
      unusual: Symbol('browser'),
    },
  };
  const message = {
    request,
    component,
    updates: {
      search: 'private submitted value',
      nothing: null,
      list: ['private'],
      count: 1,
      ready: true,
      object: { private: true },
      cycle: 'private cycle',
      unusual: 'private unusual',
      missing: 'private missing',
    },
  };
  const action = {
    component,
    message,
    name: 'saveReview',
    params: ['private action value'],
    origin: {
      el: { tagName: 'BUTTON', textContent: 'Private button text' },
      directive: { rawName: 'wire:click', expression: 'saveReview(private)' },
    },
  };
  const requestCallbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
  ], { request });
  const messageCallbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Error', 'Success', 'Skipped', 'Finish',
  ], { message });
  const actionCallbacks = callbackHooks(['Send'], { action });

  browser.interceptors.request[0](requestCallbacks.context);
  browser.interceptors.message[0](messageCallbacks.context);
  browser.interceptors.action[0](actionCallbacks.context);
  requestCallbacks.hooks.Send();
  actionCallbacks.hooks.Send();
  messageCallbacks.hooks.Send();
  requestCallbacks.hooks.Response({
    response: {
      status: 200,
      headers: { get: (name) => ({
        'X-NewDebugBar-Profile': profileId,
        'X-NewDebugBar-Livewire-Trace': traceUrl,
      })[name] ?? null },
    },
  });
  requestCallbacks.hooks.Parsed();
  requestCallbacks.hooks.Success({ response: { status: 200 } });

  const phaseCallbacks = {};
  messageCallbacks.hooks.Success({
    onSync: (callback) => { phaseCallbacks.sync = callback; },
    onEffect: (callback) => { phaseCallbacks.effect = callback; },
    onMorph: (callback) => { phaseCallbacks.morph = callback; },
    onRender: (callback) => { phaseCallbacks.render = callback; },
  });
  phaseCallbacks.sync();
  phaseCallbacks.effect();
  phaseCallbacks.morph();
  phaseCallbacks.render();
  messageCallbacks.hooks.Finish();
  requestCallbacks.hooks.Finish();
  await tick();

  assert.equal(state.installed, true);
  assert.equal(installLivewireTrace(browser), state);
  assert.equal(browser.fetches.length, 1);
  assert.equal(browser.fetches[0].url, traceUrl);
  assert.equal(browser.fetches[0].options.keepalive, true);
  assert.equal(browser.fetches[0].options.headers['X-CSRF-TOKEN'], 'csrf-token');

  const payload = JSON.parse(browser.fetches[0].options.body);
  assert.equal(payload.idempotency_key, nonce);
  assert.equal(payload.request.outcome, 'success');
  assert.equal(payload.request.status, 200);
  assert.deepEqual(payload.messages[0].state[0], {
    path: 'search',
    matches_server: true,
    browser_type: 'string',
  });
  assert.deepEqual(payload.messages[0].state.map((layer) => layer.browser_type), [
    'string', 'null', 'array', 'number', 'boolean', 'object', 'object', 'unknown', 'missing',
  ]);
  assert.equal(payload.messages[0].state.find((layer) => layer.path === 'cycle').matches_server, null);
  assert.deepEqual(payload.messages[0].phases.map((phase) => phase.name), [
    'send', 'success', 'sync', 'effect', 'morph', 'render', 'finish',
  ]);
  assert.deepEqual(payload.actions, [{
    component_id: 'trace-component-1',
    name: 'saveReview',
    source: {
      status: 'observed',
      directive: 'wire:click',
      element: 'button',
      contract: 'livewire_action_origin_v1',
    },
  }]);
  assert.doesNotMatch(JSON.stringify(payload), /private|secret|expression|params/i);
  assert.deepEqual(browser.refreshed, [profileId]);
  assert.equal(browser.events.at(-1).type, 'newdebugbar-profile-trace-updated');

  state.destroy();
  assert.equal(browser.unsubscribed, 3);
});

test('treats undocumented action origin as optional and never reads element content', () => {
  assert.deepEqual(safeActionOrigin({}), {
    status: 'unknown',
    directive: null,
    element: null,
    contract: 'livewire_action_origin_v1',
  });
  assert.deepEqual(safeActionOrigin({
    origin: {
      el: { tagName: 'INPUT', value: 'private input value' },
      directive: { raw: 'wire:model.live', expression: 'privateProperty' },
    },
  }), {
    status: 'observed',
    directive: 'wire:model.live',
    element: 'input',
    contract: 'livewire_action_origin_v1',
  });
  assert.deepEqual(safeActionOrigin({
    origin: {
      el: { tagName: 'INVALID ELEMENT' },
      directive: { rawName: 'x-on:click' },
    },
  }), {
    status: 'unknown',
    directive: null,
    element: null,
    contract: 'livewire_action_origin_v1',
  });
  assert.deepEqual(safeActionOrigin({
    get origin() { throw new Error('unsupported contract'); },
  }), {
    status: 'unknown',
    directive: null,
    element: null,
    contract: 'livewire_action_origin_v1',
  });
});

test('records skipped and failed callbacks as bounded outcomes', async () => {
  const browser = browserRuntime();
  installLivewireTrace(browser);
  const request = {};
  const component = { id: 'trace-component-1', name: 'diagnostics-fixture', canonical: {}, reactive: {} };
  const message = { request, component, updates: {} };
  const requestCallbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
  ], { request });
  const messageCallbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Error', 'Success', 'Skipped', 'Finish',
  ], { message });

  browser.interceptors.request[0](requestCallbacks.context);
  browser.interceptors.message[0](messageCallbacks.context);
  requestCallbacks.hooks.Send();
  messageCallbacks.hooks.Send();
  requestCallbacks.hooks.Response({
    response: {
      status: 503,
      headers: { get: (name) => ({
        'X-NewDebugBar-Profile': profileId,
        'X-NewDebugBar-Livewire-Trace': traceUrl,
      })[name] ?? null },
    },
  });
  messageCallbacks.hooks.Skipped();
  messageCallbacks.hooks.Error();
  messageCallbacks.hooks.Failure();
  messageCallbacks.hooks.Cancel();
  requestCallbacks.hooks.Failure();
  requestCallbacks.hooks.Cancel();
  messageCallbacks.hooks.Finish();
  requestCallbacks.hooks.Finish();
  await tick();

  const payload = JSON.parse(browser.fetches[0].options.body);
  assert.equal(payload.request.outcome, 'cancelled');
  assert.equal(payload.messages[0].outcome, 'cancelled');
  assert.equal(payload.failures.length, 5);
  assert.equal(payload.messages[0].phases.some((phase) => phase.name === 'skipped'), true);
});

test('flushes a redirect as soon as Livewire announces navigation', async () => {
  const browser = browserRuntime();
  installLivewireTrace(browser);
  const request = {};
  const callbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
  ], { request });

  browser.interceptors.request[0](callbacks.context);
  callbacks.hooks.Send();
  callbacks.hooks.Response({
    response: {
      status: 200,
      headers: { get: (name) => ({
        'X-NewDebugBar-Profile': profileId,
        'X-NewDebugBar-Livewire-Trace': traceUrl,
      })[name] ?? null },
    },
  });
  callbacks.hooks.Redirect();
  await tick();

  assert.equal(JSON.parse(browser.fetches[0].options.body).request.outcome, 'redirected');
});

test('flushes pending requests before wire navigation without duplicate appends', async () => {
  const browser = browserRuntime();
  installLivewireTrace(browser);
  const request = {};
  const callbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
  ], { request });

  browser.interceptors.request[0](callbacks.context);
  callbacks.hooks.Send();
  callbacks.hooks.Response({
    response: {
      status: 200,
      headers: { get: (name) => ({
        'X-NewDebugBar-Profile': profileId,
        'X-NewDebugBar-Livewire-Trace': traceUrl,
      })[name] ?? null },
    },
  });
  callbacks.hooks.Parsed();
  callbacks.hooks.Success({ response: { status: 200 } });
  browser.dispatchDocumentEvent('livewire:navigating');
  callbacks.hooks.Finish();
  await tick();

  assert.equal(browser.fetches.length, 1);
});

test('keeps out-of-order Livewire requests correlated to their own profile', async () => {
  const browser = browserRuntime();
  installLivewireTrace(browser);
  const secondProfileId = '770e8400-e29b-41d4-a716-446655440000';
  const secondNonce = '880e8400-e29b-41d4-a716-446655440000';
  const secondTraceUrl = `https://viteclinic.test/__newdebugbar/livewire-trace/${secondProfileId}?revision=1&nonce=${secondNonce}&expires=100&signature=signed`;

  const exchanges = [
    {
      request: {},
      component: { id: 'component-first', name: 'first', canonical: {}, reactive: {} },
      profileId,
      traceUrl,
    },
    {
      request: {},
      component: { id: 'component-second', name: 'second', canonical: {}, reactive: {} },
      profileId: secondProfileId,
      traceUrl: secondTraceUrl,
    },
  ].map((exchange) => {
    const message = { request: exchange.request, component: exchange.component, updates: {} };
    const requestCallbacks = callbackHooks([
      'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
    ], { request: exchange.request });
    const messageCallbacks = callbackHooks([
      'Send', 'Cancel', 'Failure', 'Error', 'Success', 'Skipped', 'Finish',
    ], { message });
    browser.interceptors.request[0](requestCallbacks.context);
    browser.interceptors.message[0](messageCallbacks.context);
    requestCallbacks.hooks.Send();
    messageCallbacks.hooks.Send();

    return { ...exchange, requestCallbacks, messageCallbacks };
  });

  for (const exchange of [...exchanges].reverse()) {
    exchange.requestCallbacks.hooks.Response({
      response: {
        status: 200,
        headers: { get: (name) => ({
          'X-NewDebugBar-Profile': exchange.profileId,
          'X-NewDebugBar-Livewire-Trace': exchange.traceUrl,
        })[name] ?? null },
      },
    });
    exchange.requestCallbacks.hooks.Success({ response: { status: 200 } });
    exchange.messageCallbacks.hooks.Finish();
    exchange.requestCallbacks.hooks.Finish();
  }
  await tick();

  assert.deepEqual(browser.fetches.map((fetch) => fetch.url), [secondTraceUrl, traceUrl]);
  assert.deepEqual(browser.fetches.map((fetch) => (
    JSON.parse(fetch.options.body).messages[0].component_id
  )), ['component-second', 'component-first']);
});

test('missing Livewire contracts or trace headers fail without host effects', async () => {
  const unavailable = browserRuntime();
  unavailable.Livewire = {};
  const state = installLivewireTrace(unavailable);
  assert.equal(state.installed, false);

  const browser = browserRuntime();
  installLivewireTrace(browser);
  const request = {};
  const callbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
  ], { request });
  browser.interceptors.request[0](callbacks.context);
  callbacks.hooks.Send();
  callbacks.hooks.Response({ response: { status: 500, headers: { get: () => null } } });
  callbacks.hooks.Error();
  callbacks.hooks.Finish();
  await tick();

  assert.deepEqual(browser.fetches, []);
});

test('invalid targets append failures and stale toolbar failures stay isolated', async () => {
  const invalid = browserRuntime();
  installLivewireTrace(invalid);
  const invalidRequest = {};
  const invalidCallbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
  ], { request: invalidRequest });
  invalid.interceptors.request[0](invalidCallbacks.context);
  invalidCallbacks.hooks.Send();
  invalidCallbacks.hooks.Response({
    response: {
      status: 200,
      headers: { get: (name) => name === 'X-NewDebugBar-Profile' ? profileId : {
        toString() { throw new Error('invalid trace target'); },
      } },
    },
  });
  invalidCallbacks.hooks.Finish();
  assert.deepEqual(invalid.fetches, []);

  const failing = browserRuntime();
  let attempted = false;
  failing.fetch = async () => { attempted = true; throw new Error('append failed'); };
  failing.Livewire.getByName = () => { throw new Error('toolbar replaced'); };
  installLivewireTrace(failing);
  const request = {};
  const callbacks = callbackHooks([
    'Send', 'Cancel', 'Failure', 'Response', 'Parsed', 'Error', 'Redirect', 'Success', 'Finish',
  ], { request });
  failing.interceptors.request[0](callbacks.context);
  callbacks.hooks.Send();
  callbacks.hooks.Response({
    response: {
      status: 200,
      headers: { get: (name) => ({
        'X-NewDebugBar-Profile': profileId,
        'X-NewDebugBar-Livewire-Trace': traceUrl,
      })[name] ?? null },
    },
  });
  callbacks.hooks.Success({ response: { status: 200 } });
  callbacks.hooks.Finish();
  await tick();

  assert.equal(attempted, true);
  assert.doesNotThrow(() => failing.dispatchEvent(new failing.CustomEvent(
    'newdebugbar-profile-trace-updated',
    { detail: { profileId } },
  )));
});
