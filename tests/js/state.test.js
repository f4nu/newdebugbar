import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar, STORAGE_KEY } from '../../resources/js/state.js';

function runtime(saved = null) {
  const values = new Map(saved ? [[STORAGE_KEY, JSON.stringify(saved)]] : []);

  return {
    values,
    storage: {
      getItem: (key) => values.get(key) ?? null,
      setItem: (key, value) => values.set(key, value),
    },
    matchMedia: () => ({ matches: true, addEventListener() {}, removeEventListener() {} }),
    activeElement: () => null,
    highlight: () => {},
  };
}

const summary = {
  sections: [
    { key: 'overview', label: 'Overview' },
    { key: 'queries', label: 'Queries' },
    { key: 'logs', label: 'Logs' },
  ],
};

test('restores safe local preferences', () => {
  const browser = runtime({
    theme: 'dark',
    sectionMode: 'all',
    favorites: ['logs', 'unknown', 'logs'],
  });
  const state = createNewDebugBar(summary, browser);

  state.init();

  assert.equal(state.resolvedTheme, 'dark');
  assert.deepEqual(state.favorites, ['logs']);
  assert.equal('sectionMode' in state, false);

  state.setTheme('light');
  assert.deepEqual(JSON.parse(browser.values.get(STORAGE_KEY)), {
    theme: 'light',
    favorites: ['logs'],
  });
});

test('shows active sections alphabetically while keeping selected and favorite quiet sections', () => {
  const browser = runtime();
  const state = createNewDebugBar({
    sections: [
      { key: 'overview', label: 'Overview', active: true },
      { key: 'queries', label: 'Queries', count: 3, active: true },
      { key: 'logs', label: 'Logs', count: 0, active: false },
      { key: 'cache', label: 'Cache', count: 0, active: false },
      { key: 'history', label: 'History', active: true },
    ],
  }, browser);

  state.init();

  assert.deepEqual(state.sidebarSections.map((section) => section.key), ['history', 'overview', 'queries']);
  assert.equal(state.firstVisibleNonFavoriteKey, 'history');
  assert.equal(state.isSectionVisible(state.summary.sections[2]), false);

  state.selectSection('logs');
  assert.deepEqual(state.sidebarSections.map((section) => section.key), ['history', 'logs', 'overview', 'queries']);

  state.toggleFavorite('cache');
  assert.deepEqual(state.sidebarSections.map((section) => section.key), ['cache', 'history', 'logs', 'overview', 'queries']);
  assert.equal(state.firstVisibleNonFavoriteKey, 'history');
  assert.deepEqual(JSON.parse(browser.values.get(STORAGE_KEY)), {
    theme: 'system',
    favorites: ['cache'],
  });
});

test('favorites can be pinned and reordered', () => {
  const browser = runtime();
  const state = createNewDebugBar(summary, browser);

  state.toggleFavorite('queries');
  state.toggleFavorite('logs');
  state.moveFavorite('logs', -1);

  assert.deepEqual(state.favorites, ['logs', 'queries']);
  assert.deepEqual(state.sidebarSections.map((section) => section.key), ['logs', 'queries', 'overview']);
  assert.equal(browser.values.has(STORAGE_KEY), true);

  state.toggleFavorite('logs');
  assert.deepEqual(state.favorites, ['queries']);
  assert.deepEqual(state.sidebarSections.map((section) => section.key), ['queries', 'logs', 'overview']);
});

test('favorites can be reordered by dragging', () => {
  const state = createNewDebugBar(summary, runtime({ favorites: ['overview', 'queries', 'logs'] }));
  const transfer = {
    effectAllowed: null,
    value: null,
    setData: (_type, value) => { transfer.value = value; },
  };

  state.init();
  state.startFavoriteDrag('overview', { dataTransfer: transfer });
  state.hoverFavorite('logs', true);
  state.dropFavorite('logs', true);

  assert.equal(transfer.value, 'overview');
  assert.equal(transfer.effectAllowed, 'move');
  assert.deepEqual(state.favorites, ['queries', 'logs', 'overview']);
  assert.equal(state.favoriteDrag, null);
  assert.equal(state.favoriteDrop, null);
  assert.equal(state.favoriteDropAfter, false);
});

test('selecting a section resets content and highlights its code', async () => {
  let highlighted = 0;
  const browser = runtime();
  browser.highlight = () => highlighted++;
  const state = createNewDebugBar(summary, browser);
  const panels = [
    { dataset: { ndbSectionPanel: 'overview' }, hidden: false },
    { dataset: { ndbSectionPanel: 'queries' }, hidden: true },
  ];
  state.$root = { querySelectorAll: () => panels };
  state.$refs = { content: { scrollTop: 60 } };
  state.$nextTick = (callback) => callback();

  state.selectSection('queries');

  assert.equal(state.selected, 'queries');
  assert.equal(panels[0].hidden, true);
  assert.equal(panels[1].hidden, false);
  assert.equal(state.$refs.content.scrollTop, 0);
  assert.equal(highlighted, 1);
});

test('a new application profile keeps a valid section and reloads open details', async () => {
  const state = createNewDebugBar(summary, runtime());
  let detailsLoaded = 0;
  state.$wire = { loadDetails: async () => detailsLoaded++ };
  state.$nextTick = (callback) => callback();
  state.$refs = { inspectorClose: { focus() {} } };
  state.selected = 'logs';
  state.inspectorOpen = true;
  state.detailsRequested = true;

  state.switchProfile({ ...summary, path: '/livewire/update' });
  await Promise.resolve();

  assert.equal(state.summary.path, '/livewire/update');
  assert.equal(state.selected, 'logs');
  assert.equal(state.detailsRequested, true);
  assert.equal(detailsLoaded, 1);

  state.inspectorOpen = false;
  state.selected = 'missing';
  state.switchProfile(summary);
  assert.equal(state.selected, 'overview');
});

test('background profiles refresh loaded history without switching the active profile', async () => {
  const activeProfileId = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';
  const discoveredProfileId = '550e8400-e29b-41d4-a716-446655440000';
  const state = createNewDebugBar({ ...summary, profile_id: activeProfileId }, runtime());
  let discovered = null;
  state.$wire = { discoverProfile: async (id) => { discovered = id; } };
  state.$nextTick = (callback) => callback();

  state.noticeProfile(discoveredProfileId);
  await Promise.resolve();

  assert.equal(discovered, discoveredProfileId);
  assert.equal(state.discoveredProfileId, discoveredProfileId);
  assert.equal(state.summary.profile_id, activeProfileId);

  state.noticeProfile('not-a-profile');
  assert.equal(discovered, discoveredProfileId);
});

test('the inspector moves focus inside and returns it when closed', () => {
  let openerFocused = 0;
  let closeFocused = 0;
  const opener = { focus: () => openerFocused++ };
  const browser = runtime();
  browser.activeElement = () => opener;
  const state = createNewDebugBar(summary, browser);
  state.$refs = { inspectorClose: { focus: () => closeFocused++ } };
  state.$nextTick = (callback) => callback();

  state.openInspector();
  assert.equal(closeFocused, 1);

  state.closeInspector();
  assert.equal(openerFocused, 1);
  assert.equal(state.inspectorReturnFocus, null);
});

test('modal focus wraps at both edges', () => {
  let active = null;
  let prevented = 0;
  const browser = runtime();
  browser.activeElement = () => active;
  const state = createNewDebugBar(summary, browser);
  const first = { hidden: false, getClientRects: () => [{}], focus() { active = first; } };
  const last = { hidden: false, getClientRects: () => [{}], focus() { active = last; } };
  const container = {
    contains: (element) => [first, last].includes(element),
    querySelectorAll: () => [first, last],
  };

  active = first;
  state.keepFocusWithin({ key: 'Tab', shiftKey: true, preventDefault: () => prevented++ }, container);
  assert.equal(active, last);

  state.keepFocusWithin({ key: 'Tab', shiftKey: false, preventDefault: () => prevented++ }, container);
  assert.equal(active, first);
  assert.equal(prevented, 2);
});

test('clipboard failures stay inside the debug bar', async () => {
  let copied = null;
  const browser = runtime();
  browser.writeClipboard = async (value) => {
    copied = value;
    throw new Error('Clipboard permission denied');
  };
  const state = createNewDebugBar(summary, browser);

  state.copyText('select 1');

  await new Promise((resolve) => setTimeout(resolve));
  assert.equal(copied, 'select 1');

  browser.writeClipboard = () => {
    throw new Error('Clipboard is unavailable');
  };
  state.copyText('select 2');
});

test('the theme toggle shows the opposite resolved theme', () => {
  const state = createNewDebugBar(summary, runtime());
  state.init();

  assert.equal(state.resolvedTheme, 'dark');
  state.toggleTheme();
  assert.equal(state.resolvedTheme, 'light');
  state.toggleTheme();
  assert.equal(state.resolvedTheme, 'dark');
});

test('query controls filter search and sort captured evidence', () => {
  const state = createNewDebugBar({ ...summary, query_count: 3 }, runtime());
  const appended = [];
  const item = (execution, duration, type, slow, search) => ({
    dataset: {
      execution: String(execution),
      duration: String(duration),
      type,
      slow: String(slow),
      search,
    },
    hidden: false,
  });
  const first = item(1, 4, 'read', false, 'select users [string]');
  const second = item(2, 20, 'write', true, 'update clinics 42');
  const third = item(3, 10, 'read', false, 'select clinics 42');
  const group = item(1, 34, 'read', false, 'select repeated users');
  state.$refs = {
    queryItems: {
      children: [first, second, third],
      appendChild: (child) => appended.push(child),
    },
    queryGroups: {
      children: [group],
      appendChild: (child) => appended.push(child),
    },
  };

  state.setQueryFilter('read');
  assert.equal(first.hidden, false);
  assert.equal(second.hidden, true);
  assert.equal(third.hidden, false);
  assert.equal(group.hidden, true);
  assert.equal(state.visibleQueryCount, 2);

  state.setQueryFilter('slow');
  assert.equal(first.hidden, true);
  assert.equal(second.hidden, false);
  assert.equal(state.visibleQueryCount, 1);

  state.setQueryFilter('write');
  assert.equal(second.hidden, false);
  assert.equal(third.hidden, true);

  state.setQueryFilter('read');
  state.querySearch = 'users';
  state.applyQueryView();
  assert.equal(first.hidden, false);
  assert.equal(third.hidden, true);
  assert.equal(state.visibleQueryCount, 1);

  state.querySearch = '';
  state.setQueryFilter('repeated');
  assert.equal(first.hidden, true);
  assert.equal(group.hidden, false);
  assert.equal(state.visibleQueryCount, 1);

  appended.length = 0;
  state.queryFilter = 'all';
  state.setQuerySort('duration');
  assert.deepEqual(appended.slice(0, 3), [second, third, first]);

  state.setQueryFilter('invalid');
  state.setQuerySort('invalid');
  assert.equal(state.queryFilter, 'all');
  assert.equal(state.querySort, 'duration');
});

test('query finding actions reveal and focus the relevant evidence', () => {
  const state = createNewDebugBar(summary, runtime());
  let scrolled = null;
  const repeated = { scrollIntoView: (options) => { scrolled = ['repeated', options]; } };
  const slow = { scrollIntoView: (options) => { scrolled = ['slow', options]; } };
  state.$refs = {
    queryItems: {
      children: [],
      querySelector: () => slow,
    },
    queryGroups: {
      children: [],
      querySelector: () => repeated,
    },
  };
  state.$nextTick = (callback) => callback();

  state.reviewQueryEvidence('repeated');
  assert.equal(state.queryFilter, 'repeated');
  assert.deepEqual(scrolled, ['repeated', { block: 'start' }]);

  state.reviewQueryEvidence('slow');
  assert.equal(state.queryFilter, 'slow');
  assert.deepEqual(scrolled, ['slow', { block: 'start' }]);

  state.reviewQueryEvidence('invalid');
  assert.equal(state.queryFilter, 'slow');
});

test('history controls combine path method status and warning filters', () => {
  const state = createNewDebugBar(summary, runtime());
  const profile = (path, method, status, warning) => ({
    dataset: { path, method, status: String(status), warning: String(warning) },
    hidden: false,
  });
  const current = profile('/profiled', 'GET', 200, false);
  const failed = profile('/profiled', 'POST', 422, true);
  const other = profile('/clinics', 'GET', 200, true);
  state.$refs = { historyList: { children: [current, failed, other] } };

  state.applyHistoryFilters();
  assert.equal(state.visibleHistoryCount, 3);

  state.historyPath = 'PROFILED';
  state.historyMethod = 'post';
  state.historyStatus = '422';
  state.setHistoryWarning('warning');
  assert.equal(current.hidden, true);
  assert.equal(failed.hidden, false);
  assert.equal(other.hidden, true);
  assert.equal(state.visibleHistoryCount, 1);

  state.historyPath = '';
  state.historyMethod = '';
  state.historyStatus = '';
  state.setHistoryWarning('clean');
  assert.equal(current.hidden, false);
  assert.equal(failed.hidden, true);
  assert.equal(state.visibleHistoryCount, 1);

  state.setHistoryWarning('invalid');
  assert.equal(state.historyWarning, 'clean');

  state.$refs = {};
  state.applyHistoryFilters();
  assert.equal(state.visibleHistoryCount, 0);
});

test('timeline controls filter sections and search labels', () => {
  const state = createNewDebugBar({
    ...summary,
    sections: [...summary.sections, { key: 'timeline', label: 'Timeline' }, { key: 'events', label: 'Events' }],
  }, runtime());
  const item = (section, search) => ({ dataset: { section, search }, hidden: false });
  const query = item('queries', 'select users');
  const event = item('events', 'clinic ready');
  state.$refs = { timelineList: { children: [query, event] } };

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

  state.setEventSource('application');
  assert.equal(framework.hidden, true);
  assert.equal(application.hidden, false);
  assert.equal(state.visibleEventCount, 1);

  state.eventSearch = 'READY';
  state.applyEventFilters();
  assert.equal(application.hidden, false);

  state.setEventSource('invalid');
  assert.equal(state.eventSource, 'application');

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

test('the command palette jumps to sections and changes settings', async () => {
  let highlighted = 0;
  const browser = runtime();
  browser.highlight = () => highlighted++;
  const state = createNewDebugBar(summary, browser);
  let detailsLoaded = 0;
  state.$wire = { loadDetails: async () => detailsLoaded++ };
  state.$refs = { inspectorClose: { focus() {} } };
  state.$nextTick = (callback) => callback();
  const opener = { focus() {} };
  state.paletteOpen = true;
  state.paletteReturnFocus = opener;

  state.runCommand('section:queries');
  await Promise.resolve();
  assert.equal(state.inspectorOpen, true);
  assert.equal(state.selected, 'queries');
  assert.equal(detailsLoaded, 1);
  assert.equal(highlighted, 2);
  assert.equal(state.inspectorReturnFocus, opener);
  assert.equal(state.paletteReturnFocus, null);

  state.runCommand('theme:light');
  assert.equal(state.resolvedTheme, 'light');
});

test('broken browser preferences never break initialization or persistence', () => {
  const browser = runtime();
  browser.storage = {
    getItem: () => { throw new Error('blocked'); },
    setItem: () => { throw new Error('blocked'); },
  };
  const state = createNewDebugBar(summary, browser);

  assert.doesNotThrow(() => state.init());
  assert.doesNotThrow(() => state.toggleFavorite('queries'));
  assert.deepEqual(state.favorites, ['queries']);
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

test('favorite guards and drop positions preserve a valid order', () => {
  const state = createNewDebugBar(summary, runtime({ favorites: ['overview', 'queries', 'logs'] }));
  state.init();

  state.toggleFavorite('missing');
  state.moveFavorite('overview', -1);
  state.startFavoriteDrag('missing');
  state.hoverFavorite('missing');
  assert.deepEqual(state.favorites, ['overview', 'queries', 'logs']);
  assert.equal(state.favoriteDrag, null);

  state.startFavoriteDrag('logs');
  state.hoverFavorite('overview');
  assert.equal(state.favoriteDrop, 'overview');
  state.leaveFavorite('queries');
  assert.equal(state.favoriteDrop, 'overview');
  state.dropFavorite('overview');
  assert.deepEqual(state.favorites, ['logs', 'overview', 'queries']);

  state.startFavoriteDrag('logs');
  state.dropFavorite('logs');
  assert.deepEqual(state.favorites, ['logs', 'overview', 'queries']);
});

test('system theme changes only update a system preference', () => {
  let dark = false;
  let listener = null;
  let removed = null;
  const browser = runtime();
  browser.matchMedia = () => ({
    get matches() { return dark; },
    addEventListener: (_name, callback) => { listener = callback; },
    removeEventListener: (_name, callback) => { removed = callback; },
  });
  const state = createNewDebugBar(summary, browser);

  state.init();
  assert.equal(state.resolvedTheme, 'light');

  dark = true;
  listener();
  assert.equal(state.resolvedTheme, 'dark');

  state.setTheme('light');
  dark = false;
  listener();
  assert.equal(state.resolvedTheme, 'light');

  state.setTheme('invalid');
  assert.equal(state.theme, 'light');

  state.destroy();
  assert.equal(removed, listener);
  assert.equal(state.colorScheme, null);
  assert.equal(state.colorSchemeListener, null);
});

test('the palette filters, wraps, restores focus, and handles layered shortcuts', () => {
  let focused = 0;
  const returnTarget = { focus: () => focused++ };
  const browser = runtime();
  browser.activeElement = () => returnTarget;
  const state = createNewDebugBar(summary, browser);
  state.$refs = { paletteSearch: { focus: () => focused++ } };
  state.$nextTick = (callback) => callback();

  state.openPalette();
  assert.equal(state.paletteOpen, true);
  assert.equal(focused, 1);

  state.paletteSearch = 'dark theme';
  assert.deepEqual(state.filteredCommands.map((command) => command.id), ['theme:dark']);
  state.paletteIndex = 0;
  state.movePalette(-1);
  assert.equal(state.paletteIndex, 0);

  state.paletteSearch = '';
  state.paletteIndex = 0;
  state.movePalette(-1);
  assert.equal(state.paletteIndex, state.allCommands.length - 1);

  let prevented = 0;
  state.handleShortcut({ metaKey: true, ctrlKey: false, shiftKey: true, key: 'P', preventDefault: () => prevented++ });
  assert.equal(state.paletteOpen, false);
  assert.equal(prevented, 1);
  assert.equal(focused, 2);

  state.openInspector();
  state.handleShortcut({ metaKey: false, ctrlKey: false, shiftKey: false, key: 'Escape', preventDefault() {} });
  assert.equal(state.inspectorOpen, false);
});
