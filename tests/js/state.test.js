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
    favorites: ['logs', 'unknown', 'logs'],
  });
  const state = createNewDebugBar(summary, browser);

  state.init();

  assert.equal(state.resolvedTheme, 'dark');
  assert.deepEqual(state.favorites, ['logs']);
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
  assert.deepEqual(state.sidebarSections.map((section) => section.key), ['queries', 'overview', 'logs']);
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
