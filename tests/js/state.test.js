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

  state.startFavoriteDrag('queries');
  state.dropFavorite('logs');
  assert.deepEqual(state.favorites, ['queries', 'logs']);

  assert.deepEqual(state.orderedSections.map((section) => section.key), ['queries', 'logs']);
  assert.equal(browser.values.has(STORAGE_KEY), true);
});

test('the command palette jumps to sections and changes settings', () => {
  const state = createNewDebugBar(summary, runtime());
  let detailsLoaded = 0;
  state.$wire = { loadDetails: () => detailsLoaded++ };

  state.runCommand('section:queries');
  assert.equal(state.inspectorOpen, true);
  assert.equal(state.selected, 'queries');
  assert.equal(detailsLoaded, 1);

  state.runCommand('theme:light');
  assert.equal(state.resolvedTheme, 'light');
});
