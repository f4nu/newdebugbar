import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar, createViewDataState } from '../../resources/js/state.js';
import { runtime, summary } from './state-test-support.js';

test('Cache filters searches sorts and keeps a visible operation selected', () => {
  const state = createNewDebugBar(summary, runtime());
  const appended = [];
  const scrolled = [];
  let detailResets = 0;
  let contentResets = 0;
  const element = (execution, duration, timed, category, failed, key, search) => ({
    dataset: {
      ndbCacheExecution: String(execution),
      ndbCacheDuration: String(duration),
      ndbCacheTimed: String(timed),
      ndbCacheCategory: category,
      ndbCacheFailed: String(failed),
      ndbCacheKey: key,
      ndbCacheSearchText: search,
    },
    hidden: false,
    scrollIntoView: (options) => scrolled.push([execution, options]),
    style: {
      display: '',
      removeProperty(property) {
        if (property === 'display') this.display = '';
      },
      setProperty(property, value) {
        if (property === 'display') this.display = value;
      },
    },
  });
  const first = element(1, 0.4, true, 'read', false, 'trip:alpha', 'get hit trip alpha array');
  const second = element(2, 2.1, true, 'write', false, 'trip:beta', 'put stored trip beta redis');
  const third = element(3, 0, false, 'delete', true, 'trip:stale', 'forget failed trip stale database');
  state.$refs = {
    cacheList: {
      children: [first, second, third],
      appendChild: (child) => appended.push(child),
    },
    cacheDetail: { scrollTo: () => detailResets++ },
    content: { scrollTo: () => contentResets++ },
  };
  state.$nextTick = (callback) => callback();

  state.initializeCache([
    { execution: 1, key: 'trip:alpha' },
    { execution: 2, key: 'trip:beta' },
    { execution: 3, key: 'trip:stale', failed: true },
  ]);
  assert.equal(state.cacheFilter, 'all');
  assert.equal(state.cacheSelected, 1);
  assert.equal(state.cacheDetailOpen, false);
  assert.equal(state.selectedCacheOperation.key, 'trip:alpha');
  assert.equal(state.visibleCacheCount, 3);

  state.setCacheFilter('failed');
  assert.equal(first.hidden, true);
  assert.equal(second.hidden, true);
  assert.equal(third.hidden, false);
  assert.equal(state.cacheSelected, 3);
  assert.equal(state.visibleCacheCount, 1);

  state.setCacheFilter('writes');
  assert.equal(second.hidden, false);
  assert.equal(state.cacheSelected, 2);

  state.setCacheFilter('all');
  state.cacheSearch = 'alpha';
  state.applyCacheView();
  assert.equal(first.hidden, false);
  assert.equal(second.hidden, true);
  assert.equal(third.hidden, true);
  assert.equal(state.cacheSelected, 1);

  state.cacheSearch = '';
  appended.length = 0;
  state.setCacheSort('duration');
  assert.deepEqual(appended, [second, first, third]);

  appended.length = 0;
  state.setCacheSort('key');
  assert.deepEqual(appended, [first, second, third]);

  state.selectCacheOperation(3);
  assert.equal(state.cacheSelected, 3);
  assert.equal(state.cacheDetailOpen, true);
  assert.equal(state.cacheDetailTab, 'overview');
  assert.equal(detailResets, 1);
  assert.equal(contentResets, 1);

  state.cacheSearch = 'stale';
  state.setCacheFilter('failed');
  state.selectRelatedCacheOperation(2);
  assert.equal(state.cacheFilter, 'all');
  assert.equal(state.cacheSearch, '');
  assert.equal(state.cacheSelected, 2);
  assert.deepEqual(scrolled, [[2, { block: 'nearest' }]]);

  state.setCacheDetailTab('source');
  assert.equal(detailResets, 3);
  assert.equal(contentResets, 3);
  state.setCacheDetailTab('raw');
  state.setCacheDetailTab('invalid');
  state.setCacheFilter('invalid');
  state.setCacheSort('invalid');
  state.selectCacheOperation(99);
  assert.equal(state.cacheDetailTab, 'raw');
  assert.equal(state.cacheFilter, 'all');
  assert.equal(state.cacheSort, 'key');
  assert.equal(state.cacheSelected, 2);
  assert.equal(state.formatCachePayload({ key: 'trip:alpha' }), '{\n  "key": "trip:alpha"\n}');

  state.initializeCache('invalid');
  assert.deepEqual(state.cacheOperations, []);
  assert.equal(state.cacheSelected, null);
  assert.equal(state.cacheDetailOpen, false);
});

test('HTTP client filters failures and slow requests while keeping one selected', () => {
  const browser = runtime();
  const state = createNewDebugBar(summary, browser);
  const appended = [];
  let detailResets = 0;
  const element = (execution, duration, failed, slow, search) => ({
    dataset: {
      ndbExecution: String(execution),
      ndbDuration: String(duration),
      ndbFailed: String(failed),
      ndbSlow: String(slow),
      ndbSearch: search,
    },
    hidden: false,
    style: {
      display: '',
      removeProperty(property) {
        if (property === 'display') this.display = '';
      },
      setProperty(property, value) {
        if (property === 'display') this.display = value;
      },
    },
  });
  const first = element(1, 12, false, false, 'get api.example.test 200');
  const second = element(2, 319.53, false, true, 'get api.slow.test 200');
  const third = element(3, 68.44, true, false, 'delete api.error.test 503');
  state.$refs = {
    httpClientList: {
      children: [first, second, third],
      appendChild: (child) => appended.push(child),
    },
    httpClientDetail: { scrollTo: () => detailResets++ },
  };
  state.$nextTick = (callback) => callback();

  state.initializeHttpClient([
    { execution: 1, failed: false, slow: false, host: 'api.example.test' },
    { execution: 2, failed: false, slow: true, host: 'api.slow.test' },
    { execution: 3, failed: true, slow: false, host: 'api.error.test' },
  ]);
  assert.equal(state.httpClientFilter, 'all');
  assert.equal(state.httpClientSelected, 1);
  assert.equal(state.httpClientDetailOpen, false);
  assert.equal(state.selectedHttpClientRequest.host, 'api.example.test');
  assert.equal(first.hidden, false);
  assert.equal(first.style.display, '');
  assert.equal(second.hidden, false);
  assert.equal(second.style.display, '');
  assert.equal(third.hidden, false);
  assert.equal(state.visibleHttpClientCount, 3);

  state.setHttpClientFilter('failed');
  assert.equal(first.hidden, true);
  assert.equal(first.style.display, 'none');
  assert.equal(second.hidden, true);
  assert.equal(second.style.display, 'none');
  assert.equal(third.hidden, false);
  assert.equal(state.visibleHttpClientCount, 1);
  assert.equal(state.httpClientSelected, 3);

  state.setHttpClientFilter('slow');
  assert.equal(first.hidden, true);
  assert.equal(second.hidden, false);
  assert.equal(third.hidden, true);
  assert.equal(state.visibleHttpClientCount, 1);
  assert.equal(state.httpClientSelected, 2);

  state.setHttpClientFilter('all');
  assert.equal(first.hidden, false);
  assert.equal(state.visibleHttpClientCount, 3);

  state.httpClientSearch = '503';
  state.applyHttpClientView();
  assert.equal(first.hidden, true);
  assert.equal(second.hidden, true);
  assert.equal(third.hidden, false);
  assert.equal(state.httpClientSelected, 3);

  state.httpClientSearch = '';
  appended.length = 0;
  state.setHttpClientSort('duration');
  assert.deepEqual(appended, [second, third, first]);

  state.httpClientDetailTab = 'response';
  state.selectHttpClientRequest(1);
  assert.equal(state.httpClientSelected, 1);
  assert.equal(state.httpClientDetailOpen, true);
  assert.equal(state.httpClientDetailTab, 'overview');

  state.selectHttpClientRequest(2);
  assert.equal(state.httpClientSelected, 2);

  state.setHttpClientDetailTab('request');
  assert.equal(detailResets, 1);
  state.setHttpClientDetailTab('source');
  assert.equal(state.httpClientDetailTab, 'source');
  state.setHttpClientDetailTab('request');
  state.setHttpClientDetailTab('invalid');
  state.setHttpClientFilter('invalid');
  state.setHttpClientSort('invalid');
  state.selectHttpClientRequest(99);
  assert.equal(state.httpClientDetailTab, 'request');
  assert.equal(state.httpClientFilter, 'all');
  assert.equal(state.httpClientSort, 'duration');
  assert.equal(state.httpClientSelected, 2);

  assert.equal(state.formatHttpClientEvidence(null), 'No evidence was captured.');
  assert.equal(state.formatHttpClientEvidence('raw body'), 'raw body');
  assert.equal(state.formatHttpClientEvidence({ ready: true }), '{\n  "ready": true\n}');
});

test('HTTP client defaults to all when no request failed or ran slowly', () => {
  const state = createNewDebugBar(summary, runtime());

  state.initializeHttpClient([{ execution: 4, failed: false, slow: false }]);
  assert.equal(state.httpClientFilter, 'all');
  assert.equal(state.httpClientSelected, 4);

  state.initializeHttpClient('invalid');
  assert.deepEqual(state.httpClientRequests, []);
  assert.equal(state.httpClientSelected, null);
  assert.equal(state.httpClientDetailOpen, false);

  state.$refs = {};
  state.applyHttpClientView();
  assert.equal(state.visibleHttpClientCount, 0);
});

test('mail defaults to all and preview while keeping a visible message selected', () => {
  const browser = runtime();
  const state = createNewDebugBar(summary, browser);
  let detailResets = 0;
  const element = (execution, attachments, search) => ({
    dataset: {
      execution: String(execution),
      attachments: String(attachments),
      search,
    },
    hidden: false,
    style: {
      display: '',
      removeProperty(property) {
        if (property === 'display') this.display = '';
      },
      setProperty(property, value) {
        if (property === 'display') this.display = value;
      },
    },
  });
  const first = element(1, false, 'welcome taylor');
  const second = element(2, true, 'receipt alex invoice');
  const third = element(3, false, 'plain text morgan');
  state.$refs = {
    mailList: { children: [first, second, third] },
    mailDetail: {
      scrollTo: () => detailResets++,
    },
  };
  state.$nextTick = (callback) => callback();

  state.initializeMail([
    {
      execution: 1,
      has_html: true,
      has_text: true,
      html_url: '/1/html',
      text_url: '/1/text',
    },
    {
      execution: 2,
      transport_message_id: null,
      has_html: true,
      has_text: true,
      html_url: '/2/html',
      text_url: '/2/text',
    },
    {
      execution: 3,
      has_html: false,
      has_text: true,
      html_url: null,
      text_url: '/3/text',
    },
  ]);
  assert.equal(state.mailFilter, 'all');
  assert.equal(state.mailSelected, 1);
  assert.equal(state.mailDetailOpen, false);
  assert.equal(state.mailDetailTab, 'preview');
  assert.equal(state.mailPreviewFormat, 'html');
  assert.equal(state.mailPreviewViewport, 'desktop');
  assert.equal(state.mailPreviewUrl(), '/1/html');
  assert.equal(state.visibleMailCount, 3);

  state.setMailFilter('attachments');
  assert.equal(first.hidden, true);
  assert.equal(second.hidden, false);
  assert.equal(third.hidden, true);
  assert.equal(state.mailSelected, 2);
  assert.equal(state.visibleMailCount, 1);

  state.setMailFilter('all');
  state.mailSearch = 'plain text';
  state.applyMailView();
  assert.equal(state.mailSelected, 3);
  assert.equal(state.mailPreviewFormat, 'text');
  assert.equal(state.mailPreviewUrl(), '/3/text');

  state.setMailDetailTab('message');
  assert.ok(detailResets > 0);
  state.setMailPreviewViewport('mobile');
  state.setMailPreviewFormat('html');
  assert.equal(state.mailDetailTab, 'message');
  assert.equal(state.mailPreviewViewport, 'desktop');
  assert.equal(state.mailPreviewFormat, 'text');

  state.selectMailMessage(1);
  assert.equal(state.mailDetailOpen, true);
  assert.equal(state.mailDetailTab, 'preview');
  assert.equal(state.mailPreviewFormat, 'html');
  assert.equal(state.mailPreviewViewport, 'desktop');
  assert.equal(
    state.formatMailAddresses(['one@example.test', 'two@example.test']),
    'one@example.test, two@example.test',
  );
  assert.equal(state.formatMailAddresses([]), '—');

  state.setMailFilter('invalid');
  state.setMailDetailTab('invalid');
  state.setMailPreviewFormat('invalid');
  state.setMailPreviewViewport('invalid');
  state.selectMailMessage(99);
  assert.equal(state.mailFilter, 'all');
  assert.equal(state.mailDetailTab, 'preview');
  assert.equal(state.mailSelected, 1);

  state.initializeMail('invalid');
  assert.deepEqual(state.mailMessages, []);
  assert.equal(state.mailSelected, null);
  assert.equal(state.mailDetailOpen, false);
  assert.equal(state.mailPreviewUrl(), null);
});

test('mail preview keeps desktop and mobile viewport widths inside a narrow canvas', () => {
  const OriginalFrame = globalThis.HTMLIFrameElement;

  class PreviewFrame {}

  globalThis.HTMLIFrameElement = PreviewFrame;

  try {
    const state = createNewDebugBar(summary, runtime());
    const canvas = {
      clientWidth: 320,
      style: {
        setProperty(property, value, priority = '') {
          this[property] = value;
          this[`${property}Priority`] = priority;
        },
      },
    };
    const frame = new PreviewFrame();
    frame.style = {
      height: '640px',
      setProperty(property, value) {
        this[property] = value;
      },
    };
    frame.closest = (selector) => (selector === '[data-ndb-mail-preview-canvas]' ? canvas : null);
    frame.contentDocument = null;
    frame.contentWindow = { postMessage() {} };
    Object.defineProperty(frame, 'offsetHeight', {
      get: () => (frame.style.height === '20rem' ? 320 : Number.parseFloat(frame.style.height)),
    });

    state.mailMessages = [{ execution: 1, has_html: true, has_text: true }];
    state.mailSelected = 1;
    state.$refs = { mailPreviewFrame: frame };
    state.$nextTick = (callback) => callback();

    state.layoutMailPreviewFrame(frame);
    assert.equal(frame.style.width, '1024px');
    assert.equal(frame.style.transform, 'translateX(-50%) scale(0.3125)');
    assert.equal(canvas.style.height, '200px');
    assert.equal(canvas.style.heightPriority, 'important');

    state.setMailPreviewViewport('mobile');
    assert.equal(frame.style.width, '375px');
    assert.equal(frame.style.transform, 'translateX(-50%) scale(0.8533333333333334)');
    assert.equal(canvas.style.height, '274px');

    state.setMailPreviewFormat('text');
    assert.equal(frame.style.width, '320px');
    assert.equal(frame.style.transform, 'translateX(-50%) scale(1)');
    assert.equal(canvas.style.height, '320px');

    canvas.clientWidth = 1200;
    frame.style.height = '640px';
    state.mailPreviewFormat = 'html';
    state.mailPreviewViewport = 'desktop';
    state.layoutMailPreviewFrame(frame);
    assert.equal(frame.style.transform, 'translateX(-50%) scale(1)');
    assert.equal(canvas.style.height, '640px');

    canvas.clientWidth = 0;
    frame.style.width = 'unchanged';
    state.layoutMailPreviewFrame(frame);
    assert.equal(frame.style.width, 'unchanged');

    frame.closest = () => null;
    state.layoutMailPreviewFrame(frame);
    state.layoutMailPreviewFrame({});
  } finally {
    if (OriginalFrame === undefined) delete globalThis.HTMLIFrameElement;
    else globalThis.HTMLIFrameElement = OriginalFrame;
  }
});

test('mail preview follows canvas resizes and cleans up its observer', () => {
  const OriginalFrame = globalThis.HTMLIFrameElement;
  const OriginalObserver = globalThis.ResizeObserver;
  const OriginalWindow = globalThis.window;
  const listeners = new Map();
  const observers = [];

  class PreviewFrame {}
  class PreviewResizeObserver {
    constructor(callback) {
      this.callback = callback;
      this.disconnected = false;
      observers.push(this);
    }

    observe(target) {
      this.target = target;
    }

    disconnect() {
      this.disconnected = true;
    }
  }

  globalThis.HTMLIFrameElement = PreviewFrame;
  globalThis.ResizeObserver = PreviewResizeObserver;
  globalThis.window = {
    addEventListener: (type, listener) => listeners.set(type, listener),
    removeEventListener: (type, listener) => {
      if (listeners.get(type) === listener) listeners.delete(type);
    },
  };

  try {
    const state = createNewDebugBar(summary, runtime());
    const scrolls = [];
    let detailAvailable = true;
    let previousStateCleanup = 0;
    let previousFrameCleanup = 0;
    let bodyObserverCleanup = 0;
    const canvas = {
      clientWidth: 640,
      style: {
        setProperty(property, value, priority = '') {
          this[property] = value;
          this[`${property}Priority`] = priority;
        },
      },
    };
    const detail = {
      clientHeight: 500,
      scrollBy: ({ top }) => scrolls.push(top),
    };
    const frame = new PreviewFrame();
    frame.style = {
      height: '320px',
      setProperty(property, value) {
        this[property] = value;
      },
    };
    frame.closest = (selector) => {
      if (selector === '[data-ndb-mail-preview-canvas]') return canvas;
      if (selector === '[data-ndb-mail-detail]' && detailAvailable) return detail;

      return null;
    };
    frame.contentWindow = {};
    frame.__newDebugBarMailPreviewCleanup = () => previousFrameCleanup++;
    Object.defineProperty(frame, 'offsetHeight', {
      get: () => Number.parseFloat(frame.style.height),
    });
    state.mailPreviewFrameCleanup = () => previousStateCleanup++;

    state.connectMailPreviewFrame({});
    state.connectMailPreviewFrame(frame);
    assert.equal(previousStateCleanup, 1);
    assert.equal(previousFrameCleanup, 1);
    assert.equal(frame.style.width, '1024px');
    assert.equal(frame.style.transform, 'translateX(-50%) scale(0.625)');
    assert.equal(canvas.style.heightPriority, 'important');
    assert.equal(observers[0].target, canvas);

    canvas.clientWidth = 320;
    observers[0].callback();
    assert.equal(frame.style.transform, 'translateX(-50%) scale(0.3125)');

    const handleMessage = listeners.get('message');
    handleMessage({ source: {}, data: { type: 'newdebugbar:mail-preview-height', height: 700 } });
    handleMessage({ source: frame.contentWindow, data: undefined });
    handleMessage({
      source: frame.contentWindow,
      data: { type: 'newdebugbar:mail-preview-height', height: Number.POSITIVE_INFINITY },
    });
    handleMessage({
      source: frame.contentWindow,
      data: { type: 'newdebugbar:mail-preview-scroll', deltaY: 2, deltaMode: 1 },
    });
    handleMessage({
      source: frame.contentWindow,
      data: { type: 'newdebugbar:mail-preview-scroll', deltaY: 0.5, deltaMode: 2 },
    });
    handleMessage({
      source: frame.contentWindow,
      data: { type: 'newdebugbar:mail-preview-scroll', deltaY: 3, deltaMode: 0 },
    });
    assert.deepEqual(scrolls, [32, 250, 3]);

    detailAvailable = false;
    handleMessage({
      source: frame.contentWindow,
      data: { type: 'newdebugbar:mail-preview-scroll', deltaY: 10, deltaMode: 0 },
    });
    assert.deepEqual(scrolls, [32, 250, 3]);

    handleMessage({
      source: frame.contentWindow,
      data: { type: 'newdebugbar:mail-preview-height', height: 480 },
    });
    assert.equal(frame.style.height, '480px');
    assert.equal(canvas.style.height, '150px');

    frame.__newDebugBarMailPreviewObserver = { disconnect: () => bodyObserverCleanup++ };
    state.destroy();
    observers[0].callback();
    assert.equal(bodyObserverCleanup, 1);
    assert.equal(observers[0].disconnected, true);
    assert.equal(listeners.has('message'), false);
  } finally {
    if (OriginalFrame === undefined) delete globalThis.HTMLIFrameElement;
    else globalThis.HTMLIFrameElement = OriginalFrame;
    if (OriginalObserver === undefined) delete globalThis.ResizeObserver;
    else globalThis.ResizeObserver = OriginalObserver;
    if (OriginalWindow === undefined) delete globalThis.window;
    else globalThis.window = OriginalWindow;
  }
});

test('notifications default to all and group channel delivery diagnostics', () => {
  const browser = runtime();
  const state = createNewDebugBar(
    {
      sections: [
        { key: 'overview', label: 'Overview' },
        { key: 'mail', label: 'Mail' },
        { key: 'notifications', label: 'Notifications' },
      ],
    },
    browser,
  );
  let notificationScrolls = 0;
  let mailFocuses = 0;
  const element = (execution, status, search) => ({
    dataset: {
      ndbExecution: String(execution),
      ndbStatus: status,
      ndbSearch: search,
    },
    hidden: false,
    style: {
      display: '',
      removeProperty(property) {
        if (property === 'display') this.display = '';
      },
      setProperty(property, value) {
        if (property === 'display') this.display = value;
      },
    },
  });
  const first = element(1, 'partial', 'journey ready profile sms');
  const second = element(2, 'sent', 'departure push');
  const third = element(3, 'failed', 'payment failure slack');
  state.$root = { querySelectorAll: () => [] };
  state.$refs = {
    notificationList: { children: [first, second, third] },
    notificationDetail: { scrollTo: () => notificationScrolls++ },
    mailDetail: { focus: () => mailFocuses++ },
  };
  state.$nextTick = (callback) => callback();

  state.initializeNotifications([
    {
      execution: 1,
      status: 'partial',
      deliveries: [
        { channel: 'mail', channel_label: 'Mail' },
        { channel: 'profiled-sms', channel_label: 'Profiled Sms' },
      ],
    },
    {
      execution: 2,
      status: 'sent',
      deliveries: [{ channel: 'profiled-push', channel_label: 'Profiled Push' }],
    },
    {
      execution: 3,
      status: 'failed',
      deliveries: [{ channel: 'slack', channel_label: 'Slack' }],
    },
  ]);
  assert.equal(state.notificationFilter, 'all');
  assert.equal(state.notificationSelected, 1);
  assert.equal(state.notificationDetailOpen, false);
  assert.equal(state.notificationDetailTab, 'delivery');
  assert.equal(state.notificationChannel, 'mail');
  assert.equal(state.selectedNotificationDelivery.channel, 'mail');
  assert.equal(state.visibleNotificationCount, 3);

  state.setNotificationFilter('sent');
  assert.equal(first.hidden, true);
  assert.equal(second.hidden, false);
  assert.equal(third.hidden, true);
  assert.equal(state.notificationSelected, 2);
  assert.equal(state.notificationChannel, 'profiled-push');
  assert.equal(state.visibleNotificationCount, 1);

  state.setNotificationFilter('attention');
  assert.equal(first.hidden, false);
  assert.equal(second.hidden, true);
  assert.equal(third.hidden, false);
  assert.equal(state.notificationSelected, 1);

  state.notificationSearch = 'payment';
  state.applyNotificationView();
  assert.equal(first.hidden, true);
  assert.equal(third.hidden, false);
  assert.equal(state.notificationSelected, 3);

  state.notificationSearch = '';
  state.setNotificationFilter('all');
  state.selectNotification(1);
  state.setNotificationDetailTab('payload');
  state.setNotificationChannel('profiled-sms');
  assert.equal(state.notificationDetailOpen, true);
  assert.equal(state.notificationDetailTab, 'payload');
  assert.equal(state.selectedNotificationDelivery.channel, 'profiled-sms');
  assert.ok(notificationScrolls > 0);

  state.setNotificationFilter('invalid');
  state.setNotificationDetailTab('invalid');
  state.setNotificationChannel('invalid');
  state.selectNotification(99);
  assert.equal(state.notificationFilter, 'all');
  assert.equal(state.notificationDetailTab, 'payload');
  assert.equal(state.notificationChannel, 'profiled-sms');
  assert.equal(state.notificationSelected, 1);
  assert.equal(state.formatNotificationEvidence(null), 'No data was captured.');
  assert.equal(state.formatNotificationEvidence({ ready: true }), '{\n  "ready": true\n}');

  state.mailMessages = [{ execution: 7, transport_message_id: 'mail-7', has_html: true }];
  state.openNotificationMail('mail-7');
  assert.equal(state.selected, 'mail');
  assert.equal(state.mailSelected, 7);
  assert.equal(state.mailDetailOpen, true);
  assert.equal(mailFocuses, 1);

  state.mailMessages = [];
  state.openNotificationMail('mail-8');
  assert.equal(state.selected, 'mail');
  assert.equal(state.pendingMailMessageId, 'mail-8');
  state.initializeMail([
    { execution: 8, transport_message_id: 'mail-8', has_html: true },
    { execution: 9, transport_message_id: 'mail-9', has_html: true },
  ]);
  assert.equal(state.pendingMailMessageId, null);
  assert.equal(state.mailSelected, 8);
  assert.equal(state.mailDetailOpen, true);
  assert.equal(mailFocuses, 2);

  state.initializeNotifications('invalid');
  assert.deepEqual(state.notificationGroups, []);
  assert.equal(state.notificationSelected, null);
  assert.equal(state.notificationDetailOpen, false);
});

test('query controls filter search and sort captured evidence', () => {
  const state = createNewDebugBar({ ...summary, query_count: 4 }, runtime());
  const appended = [];
  const groupAppended = [];
  const item = (execution, duration, type, slow, search, repeated = false) => ({
    dataset: {
      execution: String(execution),
      duration: String(duration),
      type,
      slow: String(slow),
      search,
      queryKind: 'item',
      repeated: String(repeated),
      resultCount: '1',
    },
    hidden: false,
  });
  const first = item(1, 4, 'read', false, 'select users 1', true);
  const second = item(2, 6, 'read', false, 'select users 2', true);
  const third = item(3, 20, 'write', true, 'update clinics 42');
  const fourth = item(4, 10, 'read', false, 'select clinics 42');
  const groupedFirst = item(1, 4, 'read', false, 'select users 1');
  const groupedSecond = item(2, 6, 'read', false, 'select users 2');
  const group = {
    dataset: {
      execution: '1',
      duration: '10',
      type: 'read',
      slow: 'false',
      search: 'select repeated users',
      queryKind: 'group',
      resultCount: '2',
    },
    hidden: false,
    querySelector: () => ({
      children: [groupedFirst, groupedSecond],
      appendChild: (child) => groupAppended.push(child),
    }),
  };
  state.$refs = {
    queryResults: {
      children: [first, second, third, fourth, group],
      appendChild: (child) => appended.push(child),
    },
  };

  state.setQueryFilter('read');
  assert.equal(first.hidden, true);
  assert.equal(second.hidden, true);
  assert.equal(third.hidden, true);
  assert.equal(fourth.hidden, false);
  assert.equal(group.hidden, false);
  assert.equal(state.visibleQueryCount, 3);

  state.setQueryFilter('attention');
  assert.equal(first.hidden, true);
  assert.equal(third.hidden, false);
  assert.equal(group.hidden, false);
  assert.equal(state.visibleQueryCount, 3);

  state.setQueryFilter('write');
  assert.equal(third.hidden, false);
  assert.equal(fourth.hidden, true);
  assert.equal(group.hidden, true);

  state.setQueryFilter('read');
  state.querySearch = 'users';
  state.applyQueryView();
  assert.equal(first.hidden, true);
  assert.equal(fourth.hidden, true);
  assert.equal(group.hidden, false);
  assert.equal(state.visibleQueryCount, 2);

  state.querySearch = '';
  state.setQueryFilter('all');
  assert.equal(first.hidden, true);
  assert.equal(group.hidden, false);
  assert.equal(state.visibleQueryCount, 4);

  appended.length = 0;
  groupAppended.length = 0;
  state.setQuerySort('duration');
  assert.deepEqual(appended, [third, group, fourth, second, first]);
  assert.deepEqual(groupAppended, [groupedSecond, groupedFirst]);

  state.setQueryFilter('invalid');
  state.setQuerySort('invalid');
  assert.equal(state.queryFilter, 'all');
  assert.equal(state.querySort, 'duration');
});

test('authorization controls filter search selection detail and overview navigation', () => {
  const browser = runtime();
  const state = createNewDebugBar({
    sections: [
      { key: 'overview', label: 'Overview' },
      { key: 'authorization', label: 'Authorization' },
    ],
  }, browser);
  let headingFocused = 0;
  let selectedFocused = 0;
  let detailScrolled = 0;
  let detailFocusOptions = null;
  const style = () => ({ removeProperty() {}, setProperty() {} });
  const allowed = {
    dataset: {
      ndbAuthorizationExecution: '1',
      ndbAuthorizationResult: 'allowed',
      ndbAuthorizationSearchValue: 'inspect-profile mara trip',
    },
    hidden: false,
    style: style(),
    focus: () => selectedFocused++,
  };
  const denied = {
    dataset: {
      ndbAuthorizationExecution: '2',
      ndbAuthorizationResult: 'denied',
      ndbAuthorizationSearchValue: 'delete-profile guest model',
    },
    hidden: false,
    style: style(),
    focus: () => selectedFocused++,
  };
  state.$root = {
    querySelectorAll: () => [],
    querySelector: (selector) => (selector.includes('="2"') ? denied : allowed),
  };
  state.$refs = {
    authorizationList: { children: [allowed, denied] },
    authorizationDetail: {
      scrollTo: () => detailScrolled++,
      focus: (options) => {
        detailFocusOptions = options;
      },
    },
    content: { scrollTop: 42 },
    sectionHeading: { focus: () => headingFocused++ },
  };
  state.$nextTick = (callback) => callback();

  state.initializeAuthorization([
    { execution: 1, result: 'allowed', ability: 'inspect-profile' },
    { execution: 2, result: 'denied', ability: 'delete-profile' },
  ]);
  assert.equal(state.authorizationSelected, 1);
  assert.equal(state.selectedAuthorizationDecision.ability, 'inspect-profile');

  state.setAuthorizationFilter('allowed');
  assert.equal(allowed.hidden, false);
  assert.equal(denied.hidden, true);
  assert.equal(state.visibleAuthorizationCount, 1);

  state.authorizationSearch = 'guest';
  state.applyAuthorizationView();
  assert.equal(allowed.hidden, true);
  assert.equal(denied.hidden, true);
  assert.equal(state.visibleAuthorizationCount, 0);
  assert.equal(state.authorizationSelected, null);

  state.authorizationSearch = '';
  state.setAuthorizationFilter('all');
  state.selectAuthorizationDecision(2);
  assert.equal(state.authorizationSelected, 2);
  assert.equal(state.authorizationDetailOpen, true);
  assert.equal(state.authorizationDetailTab, 'decision');
  assert.equal(state.$refs.content.scrollTop, 0);
  assert.deepEqual(detailFocusOptions, { preventScroll: true });

  state.setAuthorizationDetailTab('source');
  assert.equal(state.authorizationDetailTab, 'source');
  assert.equal(detailScrolled > 0, true);
  state.closeAuthorizationDetail();
  assert.equal(state.authorizationDetailOpen, false);
  assert.equal(selectedFocused, 1);

  state.navigateToSection('authorization', 'denied');
  assert.equal(state.selected, 'authorization');
  assert.equal(state.authorizationFilter, 'denied');
  assert.equal(allowed.hidden, true);
  assert.equal(denied.hidden, false);
  assert.equal(headingFocused, 1);

  state.setAuthorizationFilter('invalid');
  assert.equal(state.authorizationFilter, 'denied');
});

test('view headers sort names and render counts in both directions', () => {
  const state = createNewDebugBar(summary, runtime());
  const first = { dataset: { order: '0', count: '1', name: 'zeta' } };
  const second = { dataset: { order: '1', count: '3', name: 'alpha' } };
  const third = { dataset: { order: '2', count: '3', name: 'beta' } };
  const children = [first, second, third];
  state.$refs = {
    viewGroups: {
      children,
      appendChild(group) {
        children.splice(children.indexOf(group), 1);
        children.push(group);
      },
    },
  };

  state.applyViewSort();
  assert.deepEqual(children, [second, third, first]);

  state.toggleViewSort('name');
  assert.equal(state.viewSortDirection, 'desc');
  assert.deepEqual(children, [first, third, second]);

  state.toggleViewSort('count');
  assert.equal(state.viewSort, 'count');
  assert.equal(state.viewSortDirection, 'desc');
  assert.deepEqual(children, [second, third, first]);

  state.toggleViewSort('count');
  assert.equal(state.viewSortDirection, 'asc');
  assert.deepEqual(children, [first, second, third]);

  state.toggleViewSort('invalid');
  assert.equal(state.viewSort, 'count');
  assert.equal(state.viewSortDirection, 'asc');
});

test('view data state loads once and reports retryable failures', async () => {
  const calls = [];
  let highlighted = 0;
  const state = createViewDataState({
    loadViewData: async (renderOrder) => {
      calls.push(renderOrder);

      return { label: 'Context view' };
    },
  }, 7, () => highlighted++);

  state.loadViewData();
  assert.equal(state.viewDataLoading, true);
  await Promise.resolve();
  await Promise.resolve();

  assert.deepEqual(calls, [7]);
  assert.equal(state.viewDataLoaded, true);
  assert.equal(state.viewDataIsEmpty, false);
  assert.match(state.formattedViewData, /Context view/);
  assert.equal(highlighted, 1);

  state.loadViewData();
  assert.deepEqual(calls, [7]);

  const failed = createViewDataState({ loadViewData: async () => Promise.reject(new Error('expired')) }, 8);
  failed.loadViewData();
  await new Promise((resolve) => setImmediate(resolve));
  assert.equal(failed.viewDataLoading, false);
  assert.equal(failed.viewDataError, true);
});

test('timeline controls filter sections and search labels', () => {
  const state = createNewDebugBar({
    ...summary,
    sections: [...summary.sections, { key: 'timeline', label: 'Timeline' }, { key: 'events', label: 'Events' }],
  }, runtime());
  const item = (section, search, key = false) => ({ dataset: { section, search, key: String(key) }, hidden: false });
  const query = item('queries', 'select users', true);
  const event = item('events', 'clinic ready');
  state.$refs = { timelineList: { children: [query, event] } };

  state.applyTimelineFilters();
  assert.equal(state.timelineFilter, 'key');
  assert.equal(query.hidden, false);
  assert.equal(event.hidden, true);

  state.setTimelineFilter('queries');
  assert.equal(query.hidden, false);
  assert.equal(event.hidden, true);
  assert.equal(state.visibleTimelineCount, 1);

  state.timelineSearch = 'MISSING';
  state.applyTimelineFilters();
  assert.equal(query.hidden, true);
  assert.equal(state.visibleTimelineCount, 0);

  state.setTimelineFilter('unknown');
  assert.equal(state.timelineFilter, 'queries');

  state.$refs = {};
  state.applyTimelineFilters();
  assert.equal(state.visibleTimelineCount, 0);
});

test('event controls separate framework noise from application events', () => {
  const state = createNewDebugBar(summary, runtime());
  const item = (source, search) => ({ dataset: { source, search }, hidden: false });
  const framework = item('framework', 'illuminate auth login');
  const application = item('application', 'clinic ready');
  state.$refs = { eventList: { children: [framework, application] } };

  state.applyEventFilters();
  assert.equal(state.eventSource, 'application');
  assert.equal(framework.hidden, true);
  assert.equal(application.hidden, false);
  assert.equal(state.visibleEventCount, 1);

  state.eventSearch = 'READY';
  state.applyEventFilters();
  assert.equal(application.hidden, false);

  state.setEventSource('framework');
  assert.equal(state.eventSource, 'framework');
  assert.equal(framework.hidden, true);
  assert.equal(application.hidden, true);

  state.setEventSource('invalid');
  assert.equal(state.eventSource, 'framework');

  state.$refs = {};
  state.applyEventFilters();
  assert.equal(state.visibleEventCount, 0);
});

test('log controls filter available levels and messages', () => {
  const state = createNewDebugBar(summary, runtime());
  const item = (level, search) => ({ dataset: { level, search }, hidden: false });
  const info = item('info', 'request ready');
  const error = item('error', 'database unavailable');
  state.$refs = { logList: { children: [info, error] } };

  state.setLogLevel('error');
  assert.equal(info.hidden, true);
  assert.equal(error.hidden, false);
  assert.equal(state.visibleLogCount, 1);

  state.logSearch = 'UNAVAILABLE';
  state.applyLogFilters();
  assert.equal(error.hidden, false);

  state.setLogLevel('debug');
  assert.equal(state.logLevel, 'error');

  state.$refs = {};
  state.setLogLevel('all');
  assert.equal(state.visibleLogCount, 0);
});
