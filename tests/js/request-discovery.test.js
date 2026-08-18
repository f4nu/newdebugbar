import assert from 'node:assert/strict';
import test from 'node:test';

import { installProfileDiscoveryBridge, installRequestDiscovery } from '../../resources/js/request-discovery.js';

const profileId = '550e8400-e29b-41d4-a716-446655440000';

function runtime() {
  const events = [];
  const listeners = {};

  class CustomEvent {
    constructor(type, options) {
      this.type = type;
      this.detail = options.detail;
    }
  }

  class XMLHttpRequest {
    listeners = {};
    responseURL = 'https://viteclinic.test/ajax';

    open(_method, url) { this.url = url; }
    setRequestHeader() {}
    addEventListener(type, callback) { this.listeners[type] = callback; }
    getResponseHeader(name) { return name === 'X-NewDebugBar-Profile' ? profileId : null; }
    send() { this.listeners.loadend?.(); }
  }

  return {
    events,
    addEventListener: (type, callback) => {
      listeners[type] ??= [];
      listeners[type].push(callback);
    },
    location: { href: 'https://viteclinic.test/dashboard', origin: 'https://viteclinic.test' },
    CustomEvent,
    XMLHttpRequest,
    dispatchEvent: (event) => {
      events.push(event);
      listeners[event.type]?.forEach((callback) => callback(event));
    },
    fetch: async (input) => ({
      input,
      url: new URL(typeof input === 'string' ? input : input.url, 'https://viteclinic.test').href,
      headers: { get: (name) => name === 'X-NewDebugBar-Profile' ? profileId : null },
    }),
  };
}

test('profile discovery always reaches the current toolbar after a morph', () => {
  const browser = runtime();
  const first = { discoveries: [], noticeProfile(id) { this.discoveries.push(id); } };
  const second = { discoveries: [], noticeProfile(id) { this.discoveries.push(id); } };
  let state = first;
  browser.document = { getElementById: () => ({}) };
  browser.Alpine = { $data: () => state };
  installProfileDiscoveryBridge(browser);

  browser.dispatchEvent(new browser.CustomEvent('newdebugbar-profile-discovered', {
    detail: { profileId },
  }));
  state = second;
  browser.dispatchEvent(new browser.CustomEvent('newdebugbar-profile-discovered', {
    detail: { profileId: '660e8400-e29b-41d4-a716-446655440000' },
  }));

  assert.deepEqual(first.discoveries, [profileId]);
  assert.deepEqual(second.discoveries, ['660e8400-e29b-41d4-a716-446655440000']);
});

test('profile discovery safely falls back to Livewire while the toolbar initializes', () => {
  const browser = runtime();
  const discoveries = [];
  browser.document = { getElementById: () => null };
  browser.Livewire = {
    getByName: () => [{ switchProfile: (id) => discoveries.push(id) }],
  };
  installProfileDiscoveryBridge(browser);
  installProfileDiscoveryBridge(browser);

  browser.dispatchEvent(new browser.CustomEvent('newdebugbar-profile-discovered', {
    detail: { profileId },
  }));
  browser.dispatchEvent(new browser.CustomEvent('newdebugbar-profile-discovered', {
    detail: { profileId: 'not-a-profile-id' },
  }));

  assert.deepEqual(discoveries, [profileId]);

  browser.Livewire.getByName = () => { throw new Error('toolbar unavailable'); };
  assert.doesNotThrow(() => browser.dispatchEvent(new browser.CustomEvent('newdebugbar-profile-discovered', {
    detail: { profileId },
  })));
});

test('ignores background fetch and xhr profiles without replacing responses', async () => {
  const browser = runtime();
  installRequestDiscovery(browser);

  const response = await browser.fetch('/api/search');
  const xhr = new browser.XMLHttpRequest();
  xhr.open('GET', '/ajax');
  xhr.send();

  assert.equal(response.input, '/api/search');
  assert.deepEqual(browser.events, []);
});

test('discovers full Inertia visits and ignores partial reloads', async () => {
  const browser = runtime();
  installRequestDiscovery(browser);

  await browser.fetch('/work-orders', { headers: { 'X-Inertia': 'true' } });
  await browser.fetch('/work-orders', {
    headers: {
      'X-Inertia': 'true',
      'X-Inertia-Partial-Component': 'WorkOrders/Index',
    },
  });
  const xhr = new browser.XMLHttpRequest();
  xhr.open('GET', '/work-orders');
  xhr.setRequestHeader('X-Inertia', 'true');
  xhr.setRequestHeader('X-Inertia-Partial-Component', 'WorkOrders/Index');
  xhr.send();
  const visit = new browser.XMLHttpRequest();
  visit.open('GET', '/work-orders/2');
  visit.setRequestHeader('X-Inertia', 'true');
  visit.send();

  assert.deepEqual(browser.events.map((event) => event.detail), [
    { profileId, transport: 'fetch' },
    { profileId, transport: 'xhr' },
  ]);
});

test('ignores external package and Livewire requests', async () => {
  const browser = runtime();
  installRequestDiscovery(browser);

  await browser.fetch('https://example.test/api');
  await browser.fetch('/__newdebugbar/assets/newdebugbar.js');
  await browser.fetch('/livewire/update');
  await browser.fetch('/custom-update', { headers: { 'X-Livewire': 'true' } });
  await browser.fetch('/profiled-next', { headers: { 'X-Livewire-Navigate': 'true' } });
  await browser.fetch({ url: '/array-update', headers: [['X-Livewire', 'true']] });
  const xhr = new browser.XMLHttpRequest();
  xhr.open('POST', '/custom-livewire');
  xhr.setRequestHeader('X-Livewire', 'true');
  xhr.send();
  installRequestDiscovery(browser);

  assert.deepEqual(browser.events, []);
});

test('malformed request metadata and notification failures never affect fetch results', async () => {
  const browser = runtime();
  const response = {
    url: 'https://viteclinic.test/api',
    headers: { get: () => profileId },
  };
  browser.fetch = async () => response;
  browser.dispatchEvent = () => { throw new Error('host listener failed'); };
  installRequestDiscovery(browser);

  const malformed = { get url() { throw new Error('bad request'); } };

  assert.equal(await browser.fetch(malformed), response);
  assert.equal(await browser.fetch('/work-orders', { headers: { 'X-Inertia': 'true' } }), response);
});
