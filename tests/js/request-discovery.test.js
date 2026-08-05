import assert from 'node:assert/strict';
import test from 'node:test';

import { installRequestDiscovery } from '../../resources/js/request-discovery.js';

const profileId = '550e8400-e29b-41d4-a716-446655440000';

function runtime() {
  const events = [];

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
    getResponseHeader(name) { return name === 'X-New-Debug-Bar-Profile' ? profileId : null; }
    send() { this.listeners.loadend?.(); }
  }

  return {
    events,
    location: { href: 'https://viteclinic.test/dashboard', origin: 'https://viteclinic.test' },
    CustomEvent,
    XMLHttpRequest,
    dispatchEvent: (event) => events.push(event),
    fetch: async (input) => ({
      input,
      url: new URL(typeof input === 'string' ? input : input.url, 'https://viteclinic.test').href,
      headers: { get: (name) => name === 'X-New-Debug-Bar-Profile' ? profileId : null },
    }),
  };
}

test('discovers same origin fetch and xhr profiles without replacing responses', async () => {
  const browser = runtime();
  installRequestDiscovery(browser);

  const response = await browser.fetch('/api/search');
  const xhr = new browser.XMLHttpRequest();
  xhr.open('GET', '/ajax');
  xhr.send();

  assert.equal(response.input, '/api/search');
  assert.deepEqual(browser.events.map((event) => event.detail), [
    { profileId, transport: 'fetch' },
    { profileId, transport: 'xhr' },
  ]);
});

test('ignores external package and Livewire requests', async () => {
  const browser = runtime();
  installRequestDiscovery(browser);

  await browser.fetch('https://example.test/api');
  await browser.fetch('/__new-debug-bar/assets/new-debug-bar.js');
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
  assert.equal(await browser.fetch('/api'), response);
});
