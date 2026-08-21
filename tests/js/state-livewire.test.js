import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar } from '../../resources/js/state.js';
import { runtime } from './state-test-support.js';

const livewireSummary = {
  id: '550e8400-e29b-41d4-a716-446655440000',
  sections: [
    { key: 'overview', label: 'Overview' },
    { key: 'livewire', label: 'Livewire' },
  ],
};

const browserComponents = [
  {
    id: 'root-1',
    name: 'benchmark.control-panel',
    title: 'Control Panel',
    parentId: null,
    sequence: 1,
    mounted: true,
    status: 'idle',
    latestActivityId: 'activity-2',
    properties: [
      { path: 'count', type: 'Integer', value: 1 },
      {
        path: 'settings',
        type: 'Array',
        value: { enabled: true, nested: { label: 'Primary' } },
      },
      { path: 'fixedLabel', type: 'String', value: 'Fixed' },
      { path: 'optional', type: 'Null', value: null },
    ],
  },
  {
    id: 'root-2',
    name: 'benchmark.event-console',
    title: 'Event Console',
    parentId: null,
    sequence: 2,
    mounted: true,
    status: 'idle',
    latestActivityId: null,
    properties: [],
  },
  {
    id: 'child-1',
    name: 'benchmark.metric-card',
    title: 'Metric Card',
    parentId: 'root-1',
    sequence: 3,
    mounted: true,
    status: 'updating',
    latestActivityId: 'activity-3',
    properties: [{ path: 'label', type: 'String', value: 'Revenue' }],
  },
];

const activity = [
  {
    id: 'activity-1',
    sequence: 1,
    componentId: 'root-1',
    componentTitle: 'Control Panel',
    componentName: 'benchmark.control-panel',
    title: 'Control Panel mounted',
    kind: 'mount',
    status: 'complete',
    occurredAt: 0,
    durationMs: 1.2,
    profileIds: [],
    actions: [],
    changes: [],
    events: [],
    phases: [],
    error: null,
  },
  {
    id: 'activity-2',
    sequence: 2,
    componentId: 'root-1',
    componentTitle: 'Control Panel',
    componentName: 'benchmark.control-panel',
    title: 'Count changed',
    kind: 'mutation',
    status: 'complete',
    occurredAt: 1_000,
    durationMs: 8.25,
    profileIds: ['profile-1'],
    actions: [{ name: '$set' }],
    changes: [{ path: 'count', before: 0, submitted: 1, server: 1 }],
    events: [],
    phases: [],
    error: null,
  },
  {
    id: 'activity-3',
    sequence: 3,
    componentId: 'child-1',
    componentTitle: 'Metric Card',
    componentName: 'benchmark.metric-card',
    title: 'Refresh ran',
    kind: 'action',
    status: 'updating',
    occurredAt: 12_500,
    durationMs: null,
    profileIds: [],
    actions: [{ name: 'refresh' }],
    changes: [],
    events: [],
    phases: [],
    error: null,
  },
];

function traceHarness(
  data = {
    ready: true,
    components: browserComponents,
    activity,
    dropped: { components: 0, activity: 0 },
  },
) {
  const calls = [];
  let subscriber;

  return {
    calls,
    subscribe(callback) {
      subscriber = callback;
      callback(data);
      return () => calls.push(['unsubscribe']);
    },
    emit(snapshot) {
      subscriber(snapshot);
    },
    mergeServerComponents(components) {
      calls.push(['merge', components]);
    },
    async applyMutation(options) {
      calls.push(['apply', options]);
      return options.value;
    },
  };
}

function stateHarness(trace = traceHarness()) {
  const browser = runtime();
  const state = createNewDebugBar(livewireSummary, browser, [], 20, trace);
  state.$root = { querySelectorAll: () => [], querySelector: () => null };
  state.$nextTick = (callback) => callback();
  state.init();

  state.mergeLivewireServer({
    components: [
      {
        id: 'root-1',
        class: 'App\\Livewire\\Benchmark\\ControlPanel',
        source: { file: 'app/Livewire/Benchmark/ControlPanel.php' },
        properties: [
          {
            path: 'count',
            type: 'Integer',
            php_type: 'int',
            server_value: 1,
            writable: true,
            array_leaf_writable: false,
            write_allowed: true,
            write_reason: null,
          },
          {
            path: 'settings',
            type: 'Array',
            php_type: 'array',
            server_value: null,
            writable: false,
            array_leaf_writable: true,
            write_allowed: true,
            write_reason: null,
          },
          {
            path: 'fixedLabel',
            type: 'String',
            php_type: 'string',
            server_value: 'Fixed',
            writable: false,
            array_leaf_writable: false,
            write_allowed: false,
            write_reason: 'locked',
          },
          {
            path: 'optional',
            type: 'Null',
            php_type: '?string',
            server_value: null,
            writable: true,
            array_leaf_writable: false,
            write_allowed: true,
            write_reason: null,
          },
        ],
      },
    ],
    activity: [],
  });

  return { browser, state, trace };
}

test('orders several roots and nested instances while preserving stable instance identity', () => {
  const { state } = stateHarness();

  assert.deepEqual(
    state.livewireComponents.map(({ id, depth }) => [id, depth]),
    [
      ['root-1', 0],
      ['root-2', 0],
      ['child-1', 1],
    ],
  );
  assert.equal(state.livewireSelectedComponentId, 'root-1');
  state.livewireSearch = 'metric';
  assert.deepEqual(
    state.filteredLivewireComponents.map(({ id }) => id),
    ['child-1'],
  );
  state.livewireSearch = 'ControlPanel.php';
  assert.deepEqual(
    state.filteredLivewireComponents.map(({ id }) => id),
    ['root-1'],
  );
  assert.equal(state.livewireComponentTitle('root-2'), 'Event Console');
  assert.equal(state.livewireComponentTitle('missing'), 'missing');
  assert.equal(state.livewireComponentLatestActivity(state.livewireComponents[0]).id, 'activity-2');
  assert.deepEqual(
    state.livewireComponentActivity('root-1').map(({ id }) => id),
    ['activity-2', 'activity-1'],
  );
});

test('filters activity and moves between activity and component details', () => {
  const { state } = stateHarness();

  assert.equal(state.livewireSelectedActivityId, 'activity-3');
  assert.deepEqual(
    state.filteredLivewireActivity.map(({ id }) => id),
    ['activity-3', 'activity-2', 'activity-1'],
  );
  state.setLivewireActivityOrder('oldest');
  assert.deepEqual(
    state.filteredLivewireActivity.map(({ id }) => id),
    ['activity-1', 'activity-2', 'activity-3'],
  );
  state.setLivewireActivityOrder('newest');
  state.setLivewireActivityOrder('missing');
  assert.equal(state.livewireActivityOrder, 'newest');
  state.setLivewireActivityType('mutation');
  assert.deepEqual(
    state.filteredLivewireActivity.map(({ id }) => id),
    ['activity-2'],
  );
  assert.equal(state.livewireSelectedActivityId, 'activity-2');
  state.livewireSearch = 'count';
  assert.equal(state.filteredLivewireActivity.length, 1);
  state.setLivewireActivityType('missing');
  assert.equal(state.livewireActivityType, 'mutation');
  assert.deepEqual(state.livewireActivityTypes, ['action', 'mount', 'mutation']);
  assert.equal(state.livewireActivityFactCount(state.selectedLivewireActivity), 2);
  assert.equal(state.livewireDuration(state.selectedLivewireActivity), '8.3 ms');
  assert.equal(state.livewireDuration(activity[2]), 'In progress');
  assert.equal(state.livewireDuration({ status: 'complete', durationMs: null }), '—');
  assert.equal(state.livewireActivityAge(activity[0]), 'Current request');
  assert.equal(state.livewireActivityAge(activity[1]), '12 sec ago');
  assert.equal(state.livewireActivityAge(activity[2]), 'Now');
  state.livewireClock = 73_000;
  assert.equal(state.livewireActivityAge(activity[1]), '1 min ago');
  state.livewireClock = 7_213_000;
  assert.equal(state.livewireActivityAge(activity[1]), '2 hr ago');
  state.livewireClock = 180_001_000;
  assert.equal(state.livewireActivityAge(activity[1]), '2 days ago');

  state.inspectLivewireActivityComponent();
  assert.equal(state.livewireTab, 'components');
  assert.equal(state.livewireSelectedComponentId, 'root-1');
  state.inspectLivewireComponentActivity();
  assert.equal(state.livewireTab, 'activity');
  assert.equal(state.livewireSelectedActivityId, 'activity-2');
  state.setLivewireTab('components');
  assert.equal(state.livewireTab, 'components');
  assert.equal(state.livewireDetailOpen, false);
  state.selectLivewireComponent('child-1');
  assert.equal(state.livewireSelectedComponentId, 'child-1');
  assert.equal(state.livewireDetailOpen, true);
  state.selectLivewireComponent('missing');
  assert.equal(state.livewireSelectedComponentId, 'child-1');
  state.selectLivewireActivity('activity-1');
  assert.equal(state.livewireSelectedActivityId, 'activity-1');
  state.selectLivewireActivity('missing');
  assert.equal(state.livewireSelectedActivityId, 'activity-1');
  state.setLivewireTab('invalid');
  assert.equal(state.livewireTab, 'components');
});

test('builds an expandable typed property tree with proven edit eligibility', () => {
  const { state } = stateHarness();
  let rows = state.livewirePropertyRows;

  assert.deepEqual(
    rows.map(({ path }) => path),
    ['count', 'settings', 'fixedLabel', 'optional'],
  );
  assert.equal(rows.find(({ path }) => path === 'count').state, 'Synced');
  assert.equal(rows.find(({ path }) => path === 'count').editable, true);
  assert.equal(rows.find(({ path }) => path === 'fixedLabel').state, 'Locked');
  assert.equal(rows.find(({ path }) => path === 'fixedLabel').editable, false);
  assert.equal(rows.find(({ path }) => path === 'optional').state, 'Synced');
  assert.equal(rows.find(({ path }) => path === 'optional').serverSummary, 'null');

  const settings = rows.find(({ path }) => path === 'settings');
  state.toggleLivewireProperty(settings);
  rows = state.livewirePropertyRows;
  assert.deepEqual(
    rows.map(({ path }) => path),
    ['count', 'settings', 'settings.enabled', 'settings.nested', 'fixedLabel', 'optional'],
  );
  assert.equal(rows.find(({ path }) => path === 'settings.enabled').editable, true);
  state.toggleLivewireProperty(rows.find(({ path }) => path === 'settings.nested'));
  assert.ok(state.livewirePropertyRows.some(({ path }) => path === 'settings.nested.label'));
  state.toggleLivewireProperty(settings);
  assert.equal(
    state.livewirePropertyRows.some(({ path }) => path === 'settings.enabled'),
    false,
  );
  state.toggleLivewireProperty({ hasChildren: false });
});

test('summarizes arrays, empty strings, booleans, and unknown browser values', () => {
  const { state } = stateHarness();
  const selected = state.livewireTrace.components.find(({ id }) => id === 'root-1');
  selected.properties.push(
    { path: 'items', type: 'Array', value: ['Only item'] },
    { path: 'empty', type: 'String', value: '' },
    { path: 'disabled', type: 'Boolean', value: false },
    { path: 'missing', type: 'Unknown', value: undefined },
  );
  const server = state.livewireServerComponents.find(({ id }) => id === 'root-1');
  server.properties.push(
    {
      path: 'items',
      type: 'Array',
      server_value: null,
      writable: false,
      array_leaf_writable: true,
      write_allowed: true,
    },
    {
      path: 'empty',
      type: 'String',
      server_value: '',
      writable: true,
      array_leaf_writable: false,
      write_allowed: true,
    },
    {
      path: 'disabled',
      type: 'Boolean',
      server_value: false,
      writable: true,
      array_leaf_writable: false,
      write_allowed: true,
    },
    {
      path: 'missing',
      type: 'Unknown',
      server_value: null,
      writable: false,
      array_leaf_writable: false,
      write_allowed: false,
    },
  );

  let rows = state.livewirePropertyRows;
  assert.equal(rows.find(({ path }) => path === 'items').valueSummary, '1 item');
  assert.equal(rows.find(({ path }) => path === 'empty').valueSummary, 'Empty string');
  assert.equal(rows.find(({ path }) => path === 'disabled').valueSummary, 'false');
  assert.equal(rows.find(({ path }) => path === 'missing').valueSummary, 'undefined');
  state.toggleLivewireProperty(rows.find(({ path }) => path === 'items'));
  rows = state.livewirePropertyRows;
  assert.equal(rows.find(({ path }) => path === 'items.0').valueSummary, 'Only item');
});

test('keeps property edits as drafts until an explicit successful apply', async () => {
  const { state, trace } = stateHarness();
  const count = state.livewirePropertyRows.find(({ path }) => path === 'count');
  state.editLivewireProperty(count);
  const key = state.livewireDraftKey(count);
  state.livewireDrafts[key].value = '5';

  const optional = state.livewirePropertyRows.find(({ path }) => path === 'optional');
  state.editLivewireProperty(optional);
  assert.deepEqual(Object.keys(state.livewireDrafts), [state.livewireDraftKey(optional)]);
  state.editLivewireProperty(count);
  state.livewireDrafts[key].value = '5';
  const focused = [];
  const trigger = {
    dataset: { ndbLivewireEditKey: key },
    focus: () => focused.push('count'),
  };

  assert.equal(browserComponents[0].properties[0].value, 1);
  assert.equal(await state.applyLivewireDraft(count, trigger), true);
  assert.deepEqual(trace.calls.at(-1), [
    'apply',
    {
      componentId: 'root-1',
      path: 'count',
      baseline: 1,
      value: 5,
    },
  ]);
  assert.equal(state.livewireDrafts[key], undefined);
  assert.deepEqual(focused, ['count']);

  state.editLivewireProperty(count);
  state.livewireDrafts[key].type = 'Integer';
  state.livewireDrafts[key].value = 'five';
  assert.equal(await state.applyLivewireDraft(count), false);
  assert.match(state.livewireDrafts[key].error, /whole number/i);
  state.cancelLivewireDraft(count);
  assert.equal(state.livewireDrafts[key], undefined);
  state.editLivewireProperty({ ...count, editable: false });
  assert.equal(state.livewireDrafts[key], undefined);
});

test('positions property popovers inside the visible viewport', () => {
  const { state } = stateHarness();
  const styles = {};
  const popover = {
    getBoundingClientRect: () => ({ width: 336, height: 280 }),
    style: {
      setProperty(property, value, priority = '') {
        styles[property] = value;
        styles[`${property}:priority`] = priority;
        this[property] = value;
      },
    },
  };
  const trigger = {
    getBoundingClientRect: () => ({
      bottom: 860,
      left: 1370,
      right: 1420,
      width: 50,
    }),
  };

  state.positionLivewirePropertyPopover(trigger, popover);

  assert.equal(popover.style.position, 'fixed');
  assert.equal(popover.style.left, '1084px');
  assert.equal(popover.style.top, '604px');
  assert.equal(popover.style.visibility, 'visible');
  assert.equal(styles['position:priority'], 'important');
  assert.equal(styles['--ndb-livewire-popover-arrow-left'], '303px');
});

test('returns focus to the stable property editor after applying', () => {
  const { browser, state } = stateHarness();
  const count = state.livewirePropertyRows.find(({ path }) => path === 'count');
  const focused = [];
  const trigger = {
    dataset: { ndbLivewireEditKey: state.livewireDraftKey(count) },
    isConnected: true,
    focus: () => focused.push('trigger'),
  };

  state.focusLivewirePropertyEditor(count, trigger);

  assert.deepEqual(focused, ['trigger']);

  state.$root.querySelectorAll = () => [
    {
      dataset: { ndbLivewireEditKey: 'other:key' },
      focus: () => focused.push('other'),
    },
    {
      dataset: { ndbLivewireEditKey: state.livewireDraftKey(count) },
      focus: () => focused.push('count'),
    },
  ];
  browser.queryAll = state.$root.querySelectorAll;

  state.focusLivewirePropertyEditor(count, { ...trigger, isConnected: false });

  assert.deepEqual(focused, ['trigger', 'count']);

  state.$root.querySelectorAll = () => [];
  browser.queryAll = state.$root.querySelectorAll;
  state.focusLivewirePropertyEditor(count);
  assert.deepEqual(focused, ['trigger', 'count']);
});

test('supports boolean, float, string, and null replacement controls', () => {
  const { state } = stateHarness();
  const optional = state.livewirePropertyRows.find(({ path }) => path === 'optional');
  state.editLivewireProperty(optional);
  const draft = state.livewireDrafts[state.livewireDraftKey(optional)];

  draft.type = 'Boolean';
  state.toggleLivewireBoolean(optional);
  assert.equal(state.livewireMutationValue(draft), true);
  draft.type = 'Float';
  draft.value = '2.5';
  assert.equal(state.livewireMutationValue(draft), 2.5);
  draft.value = '';
  assert.throws(() => state.livewireMutationValue(draft), /valid number/i);
  draft.type = 'String';
  draft.value = 42;
  assert.equal(state.livewireMutationValue(draft), '42');
});

test('falls back to stored server evidence when browser evidence is unavailable', () => {
  const trace = traceHarness({
    ready: false,
    components: [],
    activity: [],
    dropped: { components: 0, activity: 0 },
  });
  const state = createNewDebugBar(livewireSummary, runtime(), [], 20, trace);
  state.$root = { querySelectorAll: () => [], querySelector: () => null };
  state.$nextTick = (callback) => callback();
  state.init();
  state.mergeLivewireServer({
    components: [
      {
        id: 'stored-1',
        name: 'benchmark.stored',
        title: 'Stored',
        parent_id: null,
        properties: [
          {
            path: 'label',
            type: 'String',
            server_value: 'Stored value',
            writable: true,
          },
        ],
      },
    ],
    activity: [
      {
        id: 'stored-activity',
        component_id: 'stored-1',
        component_name: 'benchmark.stored',
        component_title: 'Stored',
        name: 'Save ran',
        type: 'action',
        status: 'complete',
        method: 'save',
        params: [],
        at_ms: 2,
        property: 'label',
        before: 'Old',
        submitted: 'Stored value',
        server: 'Stored value',
        event: 'stored-updated',
        mode: 'self',
        declared_target: 'stored-1',
        effect: 'redirect',
      },
    ],
  });

  assert.equal(state.livewireComponents[0].status, 'stale');
  assert.equal(state.livewireComponents[0].properties[0].value, 'Stored value');
  assert.equal(state.livewireActivity[0].title, 'Save ran');
  assert.equal(state.livewireActivity[0].actions[0].name, 'save');
  assert.equal(state.livewireActivity[0].changes[0].path, 'label');
  assert.equal(state.livewireActivity[0].events[0].name, 'stored-updated');
  assert.deepEqual(state.livewireActivity[0].effects, { redirect: true });
  state.destroy();
  assert.deepEqual(trace.calls.at(-1), ['unsubscribe']);
});
