import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar, STORAGE_KEY } from '../../resources/js/state.js';

function runtime(saved = null) {
  const values = new Map(saved ? [[STORAGE_KEY, JSON.stringify(saved)]] : []);
  const host = { locks: 0, unlocks: 0 };

  return {
    values,
    host,
    storage: {
      getItem: (key) => values.get(key) ?? null,
      setItem: (key, value) => values.set(key, value),
    },
    matchMedia: () => ({ matches: true, addEventListener() {}, removeEventListener() {} }),
    activeElement: () => null,
    highlight: () => {},
    afterPaint: (callback) => callback(),
    lockHost: () => host.locks++,
    unlockHost: () => host.unlocks++,
  };
}

const summary = {
  sections: [
    { key: 'overview', label: 'Overview', description: 'Request summary.' },
    { key: 'queries', label: 'Queries', description: 'Query evidence.' },
    { key: 'logs', label: 'Logs', description: 'Log evidence.' },
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

test('pins overview before alphabetized active sections while keeping selected and favorite quiet sections', () => {
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

  const visibleKeys = () => state.orderedSections
    .filter((section) => state.isSectionVisible(section))
    .map((section) => section.key);

  assert.deepEqual(visibleKeys(), ['overview', 'history', 'queries']);
  assert.equal(state.firstVisibleNonFavoriteKey, 'overview');
  assert.equal(state.isSectionVisible(state.summary.sections[2]), false);

  state.selectSection('logs');
  assert.deepEqual(visibleKeys(), ['overview', 'history', 'logs', 'queries']);

  state.toggleFavorite('cache');
  assert.deepEqual(visibleKeys(), ['cache', 'overview', 'history', 'logs', 'queries']);
  assert.equal(state.firstVisibleNonFavoriteKey, 'overview');
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
  const visibleKeys = () => state.orderedSections
    .filter((section) => state.isSectionVisible(section))
    .map((section) => section.key);

  assert.deepEqual(state.favorites, ['logs', 'queries']);
  assert.deepEqual(visibleKeys(), ['logs', 'queries', 'overview']);
  assert.equal(browser.values.has(STORAGE_KEY), true);

  state.toggleFavorite('logs');
  assert.deepEqual(state.favorites, ['queries']);
  assert.deepEqual(visibleKeys(), ['queries', 'overview', 'logs']);

  state.toggleFavorite('overview');
  assert.deepEqual(state.favorites, ['queries', 'overview']);
  assert.deepEqual(visibleKeys(), ['queries', 'overview', 'logs']);
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
  state.$refs = {
    content: { scrollTop: 60 },
    sectionHeading: { textContent: '' },
    sectionDescription: { textContent: '' },
  };
  state.$nextTick = (callback) => callback();

  state.selectSection('queries');
  state.syncSectionHeading();

  assert.equal(state.selected, 'queries');
  assert.equal(state.$refs.sectionHeading.textContent, 'Queries');
  assert.equal(state.$refs.sectionDescription.textContent, 'Query evidence.');
  assert.equal(panels[0].hidden, true);
  assert.equal(panels[1].hidden, false);
  assert.equal(state.$refs.content.scrollTop, 0);
  assert.equal(highlighted, 1);
});

test('moves the compact toolbar to the edge with less host dialog overlap', () => {
  const browser = runtime();
  let placement = 'top';
  let watcher = null;
  let stopped = 0;
  browser.toolbarPlacement = () => placement;
  browser.watchHostDialogs = (_root, callback) => {
    watcher = callback;

    return () => stopped++;
  };
  const state = createNewDebugBar(summary, browser);
  state.$root = {};
  state.$nextTick = (callback) => callback();

  state.init();
  assert.equal(state.toolbarPlacement, 'top');

  placement = 'bottom';
  watcher();
  assert.equal(state.toolbarPlacement, 'bottom');

  placement = 'invalid';
  watcher();
  assert.equal(state.toolbarPlacement, 'bottom');

  state.destroy();
  assert.equal(stopped, 1);
});

test('query findings reveal and scroll to grouped slow evidence', () => {
  const state = createNewDebugBar(summary, runtime());
  const content = { scrollTop: 60 };
  let requestedSelector = '';
  let scrollOptions = null;
  const target = {
    scrollIntoView: (options) => {
      scrollOptions = options;
    },
  };
  const group = {
    dataset: {
      execution: '1',
      duration: '20',
      type: 'read',
      slow: 'true',
      search: 'select users',
      queryKind: 'group',
      resultCount: '3',
    },
    hidden: false,
    querySelector: () => null,
  };

  state.$root = { querySelectorAll: () => [] };
  state.$refs = {
    content,
    queryResults: {
      children: [group],
      appendChild() {},
      querySelector: (selector) => {
        requestedSelector = selector;

        return target;
      },
    },
  };
  state.$nextTick = (callback) => callback();

  state.selectSection('queries', 'slow');

  assert.equal(state.queryFilter, 'attention');
  assert.equal(content.scrollTop, 0);
  assert.match(requestedSelector, /data-ndb-query-group/);
  assert.deepEqual(scrollOptions, { block: 'start' });
});

test('a new application profile resets stale section state and reloads open details', async () => {
  const state = createNewDebugBar(summary, runtime());
  let detailsLoaded = 0;
  state.$wire = { loadDetails: async () => detailsLoaded++ };
  state.$nextTick = (callback) => callback();
  state.selected = 'logs';
  state.inspectorOpen = true;
  state.detailsRequested = true;
  state.viewSort = 'count';

  state.switchProfile({ ...summary, id: '550e8400-e29b-41d4-a716-446655440000', path: '/api/jobs' });
  await Promise.resolve();

  assert.equal(state.summary.path, '/api/jobs');
  assert.equal(state.selected, 'overview');
  assert.equal(state.detailsRequested, true);
  assert.equal(state.viewSort, 'render');
  assert.equal(detailsLoaded, 1);

  state.selected = 'history';
  state.historyPath = '/profiled';
  state.switchProfile({ ...summary, id: '6ba7b810-9dad-41d1-80b4-00c04fd430c8' });
  await Promise.resolve();

  assert.equal(state.selected, 'history');
  assert.equal(state.historyPath, '/profiled');
  assert.equal(detailsLoaded, 2);

  state.inspectorOpen = false;
  state.selected = 'missing';
  state.switchProfile(summary);
  assert.equal(state.selected, 'overview');
});

test('background profiles refresh loaded history without switching the active profile', async () => {
  const activeProfileId = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';
  const discoveredProfileId = '550e8400-e29b-41d4-a716-446655440000';
  const state = createNewDebugBar({ ...summary, id: activeProfileId }, runtime());
  let discovered = null;
  state.$wire = { discoverProfile: async (id) => { discovered = id; } };
  state.$nextTick = (callback) => callback();

  state.noticeProfile(discoveredProfileId);
  await Promise.resolve();

  assert.equal(discovered, discoveredProfileId);
  assert.equal(state.summary.id, activeProfileId);

  state.noticeProfile('not-a-profile');
  assert.equal(discovered, discoveredProfileId);
});

test('Escape returns from a retained History profile before closing the inspector', async () => {
  const state = createNewDebugBar({ ...summary, is_current_profile: false }, runtime());
  let returned = 0;
  state.inspectorOpen = true;
  state.$wire = { returnToCurrent: async () => returned++ };

  state.handleShortcut({ metaKey: false, ctrlKey: false, shiftKey: false, key: 'Escape', preventDefault() {} });
  await Promise.resolve();

  assert.equal(returned, 1);
  assert.equal(state.inspectorOpen, true);
});

test('foreground profiles replace the current profile instead of entering background history', async () => {
  const activeProfileId = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';
  const visitProfileId = '550e8400-e29b-41d4-a716-446655440000';
  const state = createNewDebugBar({ ...summary, id: activeProfileId }, runtime());
  let switched = null;
  let discovered = null;
  state.$wire = {
    switchProfile: async (id) => { switched = id; },
    discoverProfile: async (id) => { discovered = id; },
  };

  state.noticeProfile(visitProfileId, { foreground: true, purpose: 'inertia_visit' });
  await Promise.resolve();

  assert.equal(switched, visitProfileId);
  assert.equal(discovered, null);
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

test('the inspector moves focus to shrink and returns it when closed', () => {
  let openerFocused = 0;
  let shrinkFocused = 0;
  const opener = { focus: () => openerFocused++ };
  const shrink = { focus: () => shrinkFocused++ };
  const browser = runtime();
  browser.activeElement = () => opener;
  const state = createNewDebugBar(summary, browser);
  state.$root = { querySelector: () => shrink };
  state.$nextTick = (callback) => callback();

  state.openInspector();
  assert.equal(shrinkFocused, 1);
  assert.equal(browser.host.locks, 1);

  state.closeInspector();
  assert.equal(openerFocused, 1);
  assert.equal(state.inspectorReturnFocus, null);
  assert.equal(browser.host.unlocks, 1);
});

test('dismissing the bar lasts for the page lifetime without becoming a preference', () => {
  let blurred = 0;
  let prevented = 0;
  const active = { blur: () => blurred++ };
  const browser = runtime();
  browser.activeElement = () => active;
  const state = createNewDebugBar(summary, browser);
  state.$nextTick = (callback) => callback();
  state.inspectorOpen = true;
  state.paletteOpen = true;
  state.mobileToolbarMenu = 'actions';
  state.mobileToolbarReturnFocus = active;

  state.dismissBar();

  assert.equal(state.barVisible, false);
  assert.equal(state.inspectorOpen, false);
  assert.equal(state.paletteOpen, false);
  assert.equal(state.mobileSectionsOpen, false);
  assert.equal(state.mobileToolbarMenu, null);
  assert.equal(state.mobileToolbarReturnFocus, null);
  assert.equal(browser.host.unlocks, 1);
  assert.equal(blurred, 1);
  assert.equal(browser.values.has(STORAGE_KEY), false);

  state.openInspector();
  state.openPalette();
  state.handleShortcut({
    metaKey: true,
    ctrlKey: false,
    shiftKey: true,
    key: 'P',
    preventDefault: () => prevented++,
  });

  assert.equal(state.inspectorOpen, false);
  assert.equal(state.paletteOpen, false);
  assert.equal(prevented, 0);

  const reloaded = createNewDebugBar(summary, browser);
  reloaded.init();
  assert.equal(reloaded.barVisible, true);
});

test('mobile section navigation manages focus and layered dismissal', () => {
  let active = null;
  let selectedFocused = 0;
  let headingFocused = 0;
  let openerFocused = 0;
  const opener = { focus() { active = opener; openerFocused++; } };
  const selectedButton = { focus() { active = selectedButton; selectedFocused++; } };
  const heading = { focus() { active = heading; headingFocused++; } };
  const browser = runtime();
  browser.activeElement = () => active;
  const state = createNewDebugBar(summary, browser);
  state.$root = { querySelectorAll: () => [] };
  state.$refs = {
    content: { scrollTop: 40 },
    mobileSectionsNav: { querySelector: () => selectedButton },
    sectionHeading: heading,
  };
  state.$nextTick = (callback) => callback();
  state.inspectorOpen = true;

  active = opener;
  state.openMobileSections();
  assert.equal(state.mobileSectionsOpen, true);
  assert.equal(selectedFocused, 1);

  state.toggleMobileSections();
  assert.equal(state.mobileSectionsOpen, false);
  assert.equal(openerFocused, 1);

  active = opener;
  state.toggleMobileSections();
  assert.equal(state.mobileSectionsOpen, true);

  state.selectSection('queries');
  assert.equal(state.selected, 'queries');
  assert.equal(state.mobileSectionsOpen, false);
  assert.equal(state.mobileSectionsReturnFocus, null);
  assert.equal(headingFocused, 1);

  active = opener;
  state.openMobileSections();
  state.handleShortcut({ metaKey: false, ctrlKey: false, shiftKey: false, key: 'Escape', preventDefault() {} });
  assert.equal(state.mobileSectionsOpen, false);
  assert.equal(state.inspectorOpen, true);
  assert.equal(openerFocused, 2);
});

test('mobile toolbar menus manage focus and hand off to overlays', () => {
  let active = null;
  let actionsFocused = 0;
  let menuItemFocused = 0;
  let paletteFocused = 0;
  let shrinkFocused = 0;
  const actionsOpener = { focus() { active = actionsOpener; actionsFocused++; } };
  const metricOpener = { focus() { active = metricOpener; } };
  const menuItem = { focus() { active = menuItem; menuItemFocused++; } };
  const paletteSearch = { focus() { active = paletteSearch; paletteFocused++; } };
  const shrink = { focus() { active = shrink; shrinkFocused++; } };
  const browser = runtime();
  browser.activeElement = () => active;
  const state = createNewDebugBar(summary, browser);
  state.$wire = { loadDetails: async () => {} };
  state.$refs = { paletteSearch };
  state.$root = {
    querySelector: (selector) => selector.includes('data-ndb-mobile-toolbar-menu') ? menuItem : shrink,
    querySelectorAll: () => [],
  };
  state.$nextTick = (callback) => callback();

  state.openMobileToolbarMenu('unknown', actionsOpener);
  assert.equal(state.mobileToolbarMenu, null);

  state.inspectorOpen = true;
  state.openMobileToolbarMenu('actions', actionsOpener);
  assert.equal(state.mobileToolbarMenu, null);

  state.openMobileToolbarMenu('header-actions', actionsOpener);
  assert.equal(state.mobileToolbarMenu, 'header-actions');
  assert.equal(menuItemFocused, 1);
  state.openMobileSectionsFromToolbar();
  assert.equal(state.mobileToolbarMenu, null);
  assert.equal(state.mobileSectionsOpen, true);
  assert.equal(state.mobileSectionsReturnFocus, actionsOpener);
  state.closeMobileSections(false);

  state.inspectorOpen = false;
  state.barVisible = false;
  state.openMobileToolbarMenu('actions', actionsOpener);
  assert.equal(state.mobileToolbarMenu, null);
  state.barVisible = true;

  active = actionsOpener;
  state.toggleMobileToolbarMenu('actions', actionsOpener);
  assert.equal(state.mobileToolbarMenu, 'actions');
  assert.equal(menuItemFocused, 2);

  state.handleShortcut({ metaKey: false, ctrlKey: false, shiftKey: false, key: 'Escape', preventDefault() {} });
  assert.equal(state.mobileToolbarMenu, null);
  assert.equal(actionsFocused, 1);

  state.toggleMobileToolbarMenu('actions', actionsOpener);
  state.toggleMobileToolbarMenu('actions', actionsOpener);
  assert.equal(state.mobileToolbarMenu, null);
  assert.equal(actionsFocused, 2);

  state.openMobileToolbarMenu('actions', actionsOpener);
  state.closeMobileToolbarMenu(false);
  assert.equal(state.mobileToolbarMenu, null);
  assert.equal(actionsFocused, 2);

  state.openMobileToolbarMenu('actions', actionsOpener);
  state.openPalette();
  assert.equal(state.mobileToolbarMenu, null);
  assert.equal(state.paletteReturnFocus, actionsOpener);
  assert.equal(paletteFocused, 1);

  state.closePalette();
  assert.equal(actionsFocused, 3);

  active = metricOpener;
  state.openInspector('queries');
  assert.equal(state.mobileToolbarMenu, null);
  assert.equal(state.inspectorOpen, true);
  assert.equal(state.selected, 'queries');
  assert.equal(state.inspectorReturnFocus, metricOpener);
  assert.equal(shrinkFocused, 1);
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

test('view sorting keeps render order by default and can prioritize render count', () => {
  const state = createNewDebugBar(summary, runtime());
  const first = { dataset: { order: '0', count: '1' } };
  const second = { dataset: { order: '1', count: '3' } };
  const third = { dataset: { order: '2', count: '3' } };
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
  assert.deepEqual(children, [first, second, third]);

  state.setViewSort('count');
  assert.equal(state.viewSort, 'count');
  assert.deepEqual(children, [second, third, first]);

  state.setViewSort('render');
  assert.deepEqual(children, [first, second, third]);

  state.setViewSort('invalid');
  assert.equal(state.viewSort, 'render');
});

test('history controls combine path method status and warning filters', () => {
  const state = createNewDebugBar(summary, runtime());
  const profile = (path, method, status, warning, runtimeProfile = false) => ({
    dataset: { path, method, status: String(status), warning: String(warning), runtime: String(runtimeProfile) },
    hidden: false,
  });
  const current = profile('/profiled', 'GET', 200, false);
  const failed = profile('/profiled', 'POST', 422, true);
  const other = profile('/clinics', 'GET', 200, true);
  const command = profile('artisan:migrate', 'CLI', 0, false, true);
  state.$refs = { historyList: { children: [current, failed, other, command] } };

  state.applyHistoryFilters();
  assert.equal(state.visibleHistoryCount, 3);
  assert.equal(command.hidden, true);

  state.toggleHistoryRuntime();
  assert.equal(state.visibleHistoryCount, 4);
  assert.equal(command.hidden, false);
  state.toggleHistoryRuntime();

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

test('the command palette keeps quiet collectors behind one reveal action', () => {
  const state = createNewDebugBar({
    sections: [
      { key: 'overview', label: 'Overview', active: true },
      { key: 'queries', label: 'Queries', active: true },
      { key: 'redis', label: 'Redis', active: false },
      { key: 'mail', label: 'Mail', active: false },
    ],
  }, runtime());

  assert.deepEqual(state.filteredCommands.filter((command) => command.id.startsWith('section:')).map((command) => command.id), [
    'section:overview',
    'section:queries',
  ]);
  assert.equal(state.filteredCommands.at(-1).id, 'collectors:show');

  state.runCommand('collectors:show');
  assert.deepEqual(state.filteredCommands.filter((command) => command.id.startsWith('section:')).map((command) => command.id), [
    'section:overview',
    'section:queries',
    'section:mail',
    'section:redis',
  ]);

  state.paletteSearch = 'redis';
  state.paletteShowQuiet = false;
  assert.deepEqual(state.filteredCommands.map((command) => command.id), ['section:redis']);
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
