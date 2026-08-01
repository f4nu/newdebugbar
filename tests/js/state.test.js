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
    matchMedia: () => ({ matches: true, addEventListener() {} }),
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
  state.$nextTick = (callback) => callback();

  state.runCommand('section:queries');
  await Promise.resolve();
  assert.equal(state.inspectorOpen, true);
  assert.equal(state.selected, 'queries');
  assert.equal(detailsLoaded, 1);
  assert.equal(highlighted, 2);

  state.runCommand('theme:light');
  assert.equal(state.resolvedTheme, 'light');
});
