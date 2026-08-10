import assert from 'node:assert/strict';
import test from 'node:test';

import { createLivewireSection } from '../../resources/js/livewire-section.js';

const tabs = () => ['overview', 'components', 'timeline', 'events'].map((key) => ({
  dataset: { ndbLivewireTab: key },
  focused: false,
  focus() { this.focused = true; },
}));

test('selects only known Livewire detail tabs', () => {
  const state = createLivewireSection();

  state.selectLivewireTab('components');
  assert.equal(state.livewireTab, 'components');

  state.selectLivewireTab('unknown');
  assert.equal(state.livewireTab, 'components');
});

test('moves and selects Livewire tabs with standard arrow home and end keys', () => {
  const items = tabs();
  const parentElement = { querySelectorAll: () => items };
  items.forEach((item) => { item.parentElement = parentElement; });
  const state = createLivewireSection();
  let prevented = 0;

  state.handleLivewireTabKey({
    key: 'ArrowRight',
    currentTarget: items[0],
    preventDefault: () => prevented++,
  });
  assert.equal(state.livewireTab, 'components');
  assert.equal(items[1].focused, true);

  state.handleLivewireTabKey({
    key: 'End',
    currentTarget: items[1],
    preventDefault: () => prevented++,
  });
  assert.equal(state.livewireTab, 'events');

  state.handleLivewireTabKey({
    key: 'ArrowRight',
    currentTarget: items[3],
    preventDefault: () => prevented++,
  });
  assert.equal(state.livewireTab, 'overview');

  state.handleLivewireTabKey({
    key: 'Home',
    currentTarget: items[2],
    preventDefault: () => prevented++,
  });
  assert.equal(state.livewireTab, 'overview');
  assert.equal(prevented, 4);
});
