import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar } from '../../resources/js/state.js';
import { runtime, summary } from './state-test-support.js';

test('HTTP client defaults to all and keeps one filtered request selected', () => {
  const browser = runtime();
  const state = createNewDebugBar(summary, browser);
  const appended = [];
  let detailScrolls = 0;
  const element = (execution, duration, attention, search) => ({
    dataset: {
      execution: String(execution),
      duration: String(duration),
      attention: String(attention),
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
  const first = element(1, 12, false, 'get api.example.test 200');
  const second = element(2, 319.53, true, 'get api.slow.test 200');
  const third = element(3, 68.44, true, 'delete api.error.test 503');
  state.$refs = {
    httpClientList: {
      children: [first, second, third],
      appendChild: (child) => appended.push(child),
    },
    httpClientDetail: { scrollIntoView: () => detailScrolls++ },
  };
  state.$nextTick = (callback) => callback();

  state.initializeHttpClient([
    { execution: 1, attention: false, host: 'api.example.test' },
    { execution: 2, attention: true, host: 'api.slow.test' },
    { execution: 3, attention: true, host: 'api.error.test' },
  ]);
  assert.equal(state.httpClientFilter, 'all');
  assert.equal(state.httpClientSelected, 1);
  assert.equal(state.selectedHttpClientRequest.host, 'api.example.test');
  assert.equal(first.hidden, false);
  assert.equal(first.style.display, '');
  assert.equal(second.hidden, false);
  assert.equal(second.style.display, '');
  assert.equal(third.hidden, false);
  assert.equal(state.visibleHttpClientCount, 3);

  state.setHttpClientFilter('attention');
  assert.equal(first.hidden, true);
  assert.equal(first.style.display, 'none');
  assert.equal(second.hidden, false);
  assert.equal(second.style.display, '');
  assert.equal(third.hidden, false);
  assert.equal(state.visibleHttpClientCount, 2);
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
  assert.equal(state.httpClientDetailTab, 'overview');

  browser.viewportWidth = () => 390;
  state.selectHttpClientRequest(2, true);
  assert.equal(detailScrolls, 1);

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

test('HTTP client defaults to all when no request needs attention', () => {
  const state = createNewDebugBar(summary, runtime());

  state.initializeHttpClient([{ execution: 4, attention: false }]);
  assert.equal(state.httpClientFilter, 'all');
  assert.equal(state.httpClientSelected, 4);

  state.initializeHttpClient('invalid');
  assert.deepEqual(state.httpClientRequests, []);
  assert.equal(state.httpClientSelected, null);

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

test('authorization controls filter decisions and overview navigation opens denied results', () => {
  const browser = runtime();
  const state = createNewDebugBar({
    sections: [
      { key: 'overview', label: 'Overview' },
      { key: 'authorization', label: 'Authorization' },
    ],
  }, browser);
  let headingFocused = 0;
  const allowed = { dataset: { result: 'allowed' }, hidden: false };
  const denied = { dataset: { result: 'denied' }, hidden: false };
  state.$root = { querySelectorAll: () => [] };
  state.$refs = {
    authorizationItems: { children: [allowed, denied] },
    sectionHeading: { focus: () => headingFocused++ },
  };
  state.$nextTick = (callback) => callback();

  state.setAuthorizationFilter('allowed');
  assert.equal(allowed.hidden, false);
  assert.equal(denied.hidden, true);
  assert.equal(state.visibleAuthorizationCount, 1);

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
