import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar } from '../../resources/js/state.js';
import { runtime, summary } from './state-test-support.js';

test('malformed startup inputs use safe request defaults', () => {
  const initial = { theme: 'dark' };
  const state = createNewDebugBar(initial, runtime(), null, 0);

  assert.equal(state.theme, 'dark');
  assert.equal(state.profileLimit, 20);
  assert.deepEqual(state.recentProfiles, []);
  assert.deepEqual(state.sectionKeys, []);
  assert.equal(state.selectedSection.key, 'overview');
  assert.equal(state.currentRequestProfile, initial);

  state.currentRequestId = 'current';
  state.recentProfiles = [
    { id: 'current' },
    ...Array.from({ length: 10 }, (_, index) => ({ id: `later-${index}` })),
  ];

  assert.equal(state.requestBadgeCount, '9+');
  assert.equal(state.requestPickerButtonLabel, 'Choose request, 10 later requests');
});

test('a new application profile keeps a matching section and resets stale section state', async () => {
  const state = createNewDebugBar(summary, runtime());
  let detailsLoaded = 0;
  state.$wire = { loadDetails: async () => detailsLoaded++ };
  state.$nextTick = (callback) => callback();
  state.selected = 'logs';
  state.inspectorOpen = true;
  state.detailsRequested = true;
  state.viewSort = 'count';
  state.viewSortDirection = 'desc';
  state.eventSource = 'framework';
  state.eventSearch = 'booted';

  state.switchProfile({ ...summary, id: '550e8400-e29b-41d4-a716-446655440000', path: '/api/jobs' });
  await Promise.resolve();

  assert.equal(state.summary.path, '/api/jobs');
  assert.equal(state.selected, 'logs');
  assert.equal(state.detailsRequested, true);
  assert.equal(state.viewSort, 'name');
  assert.equal(state.viewSortDirection, 'asc');
  assert.equal(state.eventSource, 'application');
  assert.equal(state.eventSearch, '');
  assert.equal(detailsLoaded, 1);

  state.inspectorOpen = false;
  state.selected = 'missing';
  state.switchProfile({ ...summary, id: '6ba7b810-9dad-41d1-80b4-00c04fd430c8' });
  assert.equal(state.selected, 'overview');
});

test('foreground profiles replace the current profile', async () => {
  const activeProfileId = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';
  const visitProfileId = '550e8400-e29b-41d4-a716-446655440000';
  const state = createNewDebugBar({ ...summary, id: activeProfileId }, runtime());
  let switched = null;
  state.$wire = { switchProfile: async (id) => { switched = id; } };

  state.noticeProfile(visitProfileId, true);
  await Promise.resolve();

  assert.equal(switched, visitProfileId);
  assert.equal(state.laterRequestCount, 0);
});

test('background profiles are announced once and stay counted after the picker opens', async () => {
  const activeProfileId = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';
  const ajaxProfileId = '550e8400-e29b-41d4-a716-446655440000';
  const calls = [];
  const state = createNewDebugBar({ ...summary, id: activeProfileId, path: '/dashboard' }, runtime());
  state.$wire = { noticeProfile: async (id) => calls.push(['notice', id]) };
  state.$nextTick = (callback) => callback();
  state.$root = { querySelector: () => ({ querySelectorAll: () => [] }) };

  assert.equal(state.hasOtherRequests, false);
  assert.equal(state.requestPickerButtonLabel, 'No later requests yet');
  state.openRequestPicker('toolbar');
  assert.equal(state.requestPickerScope, null);

  state.noticeProfile(ajaxProfileId);
  state.noticeProfile(ajaxProfileId);
  await Promise.resolve();

  assert.deepEqual(calls, [['notice', ajaxProfileId]]);
  assert.deepEqual(state.pendingProfileIds, [ajaxProfileId]);
  assert.equal(state.laterRequestCount, 0);

  state.receiveProfile({ id: ajaxProfileId, method: 'GET', path: '/metrics' });
  assert.deepEqual(state.pendingProfileIds, []);
  assert.equal(state.recentProfiles[0].id, ajaxProfileId);
  assert.equal(state.currentRequestProfile.id, activeProfileId);
  assert.deepEqual(state.laterRequestProfiles.map((profile) => profile.id), [ajaxProfileId]);
  assert.equal(state.hasOtherRequests, true);
  assert.equal(state.laterRequestCount, 1);
  assert.equal(state.requestBadgeCount, '1');
  assert.equal(state.requestPickerButtonLabel, 'Choose request, 1 later request');

  state.openRequestPicker('toolbar');

  assert.equal(state.requestPickerScope, 'toolbar');
  assert.equal(state.laterRequestCount, 1);
  assert.equal(state.requestBadgeCount, '1');
  assert.equal(state.requestPickerButtonLabel, 'Choose request, 1 later request');
  assert.deepEqual(calls, [['notice', ajaxProfileId]]);
});

test('recent requests stay deduplicated, bounded, and include the selected request', () => {
  const current = { ...summary, id: '6ba7b810-9dad-41d1-80b4-00c04fd430c8', path: '/current' };
  const first = { id: '550e8400-e29b-41d4-a716-446655440000', path: '/first' };
  const second = { id: 'f47ac10b-58cc-4372-a567-0e02b2c3d479', path: '/second' };
  const state = createNewDebugBar(current, runtime(), [first, current, second], 2);

  assert.deepEqual(state.recentProfiles.map((profile) => profile.id), [current.id, first.id]);

  state.rememberProfile(second);

  assert.deepEqual(state.recentProfiles.map((profile) => profile.id), [second.id, current.id]);
});

test('request summaries format useful labels and update existing recent entries', () => {
  const current = { ...summary, id: '6ba7b810-9dad-41d1-80b4-00c04fd430c8', path: '/current' };
  const other = { id: '550e8400-e29b-41d4-a716-446655440000', path: '/other' };
  const state = createNewDebugBar(current, runtime(), [other], 3);
  const originalNow = Date.now;

  state.rememberProfile({ ...current, method: 'POST', path: '/updated' });
  state.receiveProfile({ id: 'not-a-profile' });

  assert.equal(state.recentProfiles.find((profile) => profile.id === current.id).path, '/updated');
  assert.equal(state.requestTitle({ activity: 'Search patients', path: '/patients' }), 'Search patients');
  assert.equal(state.requestTitle({ path: '/patients' }), '/patients');
  assert.equal(state.requestTitle({}), 'Request');
  assert.deepEqual(
    ['ajax', 'cli', 'download', 'full_page', 'json', 'redirect', 'stream', 'unknown']
      .map((type) => state.requestTypeLabel(type)),
    ['Ajax', 'CLI', 'Download', 'Page', 'JSON', 'Redirect', 'Stream', 'Request'],
  );
  assert.deepEqual(
    [200, 302, 422, 500, null].map((status) => state.requestStatusClass(status)),
    [
      'ndb:text-emerald-600 ndb:dark:text-emerald-300',
      'ndb:text-sky-600 ndb:dark:text-sky-300',
      'ndb:text-amber-600 ndb:dark:text-amber-300',
      'ndb:text-red-600 ndb:dark:text-red-300',
      'ndb:text-zinc-500 ndb:dark:text-zinc-400',
    ],
  );

  Date.now = () => Date.parse('2026-08-19T12:00:00Z');
  assert.equal(state.relativeRequestTime({ recorded_time: 'Earlier' }), 'Earlier');
  assert.equal(state.relativeRequestTime({ recorded_at: '2026-08-19T11:59:58Z' }), 'now');
  assert.equal(state.relativeRequestTime({ recorded_at: '2026-08-19T11:59:45Z' }), '15s ago');
  assert.equal(state.relativeRequestTime({ recorded_at: '2026-08-19T11:42:00Z' }), '18m ago');
  assert.equal(
    state.relativeRequestTime({ recorded_at: '2026-08-19T09:00:00Z', recorded_time: '09:00' }),
    '09:00',
  );
  Date.now = originalNow;
});

test('the request picker manages focus, keyboard movement, and profile selection', async () => {
  const requestSummary = {
    ...summary,
    sections: [...summary.sections, { key: 'request', label: 'Requests' }],
  };
  const current = { ...requestSummary, id: '6ba7b810-9dad-41d1-80b4-00c04fd430c8', path: '/current' };
  const other = { ...requestSummary, id: '550e8400-e29b-41d4-a716-446655440000', path: '/other' };
  const browser = runtime();
  let active = null;
  let triggerFocuses = 0;
  const switches = [];
  const option = (profileId) => ({
    dataset: { profileId },
    focus() { active = this; },
  });
  const currentOption = option(current.id);
  const laterOption = option(other.id);
  const switcher = {
    getBoundingClientRect: () => ({ left: 20 }),
    querySelectorAll: () => [currentOption, laterOption],
  };
  const trigger = {
    closest: () => switcher,
    focus: () => triggerFocuses++,
    getBoundingClientRect: () => ({ left: 160, width: 40 }),
  };
  switcher.querySelector = () => trigger;
  const listbox = { querySelectorAll: () => [currentOption, laterOption] };
  const state = createNewDebugBar(current, browser, [other]);
  browser.activeElement = () => active;
  state.$nextTick = (callback) => callback();
  state.$root = { querySelector: () => switcher };
  state.$wire = {
    loadDetails: async () => {},
    switchProfile: async (id) => switches.push(id),
  };

  state.openRequestPicker('unknown', trigger);
  state.openRequestPicker('header', trigger);
  assert.equal(state.requestPickerScope, null);

  state.toggleRequestPicker('toolbar', trigger);
  assert.equal(state.requestPickerScope, 'toolbar');
  assert.equal(state.requestPickerArrowLeft, 152);
  assert.equal(active, currentOption);

  state.moveRequestPicker(-1, listbox);
  assert.equal(active, laterOption);
  state.moveRequestPicker(-1, listbox);
  assert.equal(active, currentOption);
  active = null;
  state.moveRequestPicker(1, listbox);
  assert.equal(active, laterOption);
  state.focusRequestPickerEdge('end', listbox);
  assert.equal(active, laterOption);
  state.focusRequestPickerEdge('start', listbox);
  assert.equal(active, currentOption);
  state.moveRequestPicker(1, { querySelectorAll: () => [] });
  state.focusRequestPickerEdge('start', { querySelectorAll: () => [] });

  state.toggleRequestPicker('toolbar', trigger);
  assert.equal(state.requestPickerScope, null);
  assert.equal(triggerFocuses, 1);

  state.inspectorOpen = true;
  state.openRequestPicker('toolbar', trigger);
  assert.equal(state.requestPickerScope, null);
  state.openRequestPicker('header', trigger);
  assert.equal(state.requestPickerScope, 'header');
  state.selectRequest(current.id);
  assert.deepEqual(switches, []);
  assert.equal(state.inspectorOpen, true);
  assert.equal(state.selected, 'request');

  state.selected = 'logs';
  state.openRequestPicker('header', trigger);
  state.selectRequest(other.id);
  await Promise.resolve();
  assert.deepEqual(switches, [other.id]);
  assert.equal(state.requestSelectionPending, other.id);
  assert.equal(state.selected, 'logs');

  state.switchProfile(other);
  assert.equal(state.requestSelectionPending, null);
  assert.equal(state.inspectorOpen, true);
  assert.equal(state.selected, 'request');
  assert.equal(state.currentRequestProfile.id, current.id);
  assert.deepEqual(state.laterRequestProfiles.map((profile) => profile.id), [other.id]);
  state.closeRequestPicker(false);
});

test('request discovery and selection failures clear their pending state', async () => {
  const current = { ...summary, id: '6ba7b810-9dad-41d1-80b4-00c04fd430c8' };
  const otherId = '550e8400-e29b-41d4-a716-446655440000';
  const state = createNewDebugBar(current, runtime());

  state.$wire = { noticeProfile: async () => { throw new Error('unavailable'); } };
  state.noticeProfile(otherId);
  await Promise.resolve();
  await Promise.resolve();
  assert.deepEqual(state.pendingProfileIds, []);
  assert.equal(state.laterRequestCount, 0);

  state.$wire = {};
  state.noticeProfile(otherId);
  state.noticeProfile('invalid');
  state.noticeProfile(current.id);
  assert.equal(state.laterRequestCount, 0);

  state.$wire = { switchProfile: async () => { throw new Error('expired'); } };
  state.selectRequest(otherId);
  await Promise.resolve();
  await Promise.resolve();
  assert.equal(state.requestSelectionPending, null);

  state.requestSelectionPending = otherId;
  state.selectRequest(otherId);
  state.selectRequest('invalid');
  assert.equal(state.requestSelectionPending, otherId);
});

test('stale detail responses cannot resync panels for a newer profile', async () => {
  const pending = [];
  const state = createNewDebugBar({ ...summary, id: '6ba7b810-9dad-41d1-80b4-00c04fd430c8' }, runtime());
  let synced = 0;
  state.syncSectionPanels = () => synced++;
  state.$wire = { loadDetails: () => new Promise((resolve) => pending.push(resolve)) };
  state.$nextTick = (callback) => callback();
  state.inspectorOpen = true;

  state.openInspector();
  state.switchProfile({ ...summary, id: '550e8400-e29b-41d4-a716-446655440000' });
  const syncsBeforeStaleResponse = synced;
  pending[0]();
  await Promise.resolve();

  assert.equal(synced, syncsBeforeStaleResponse);
  assert.equal(state.summary.id, '550e8400-e29b-41d4-a716-446655440000');
  assert.equal(state.selected, 'overview');
});

test('section selection falls back safely and profile details retry after failure', async () => {
  const state = createNewDebugBar(summary, runtime());
  let attempts = 0;
  state.$wire = {
    loadDetails: () => {
      attempts++;
      return attempts === 1 ? Promise.reject(new Error('expired')) : Promise.resolve();
    },
  };
  state.$nextTick = (callback) => callback();

  state.selectSection('missing');
  assert.equal(state.selected, 'overview');

  state.openInspector('queries');
  await new Promise((resolve) => setImmediate(resolve));
  assert.equal(state.detailsRequested, false);

  state.openInspector('logs');
  await new Promise((resolve) => setImmediate(resolve));
  state.openInspector('queries');
  await new Promise((resolve) => setImmediate(resolve));

  assert.equal(attempts, 2);
  assert.equal(state.detailsRequested, true);
  assert.equal(state.selected, 'queries');
});
