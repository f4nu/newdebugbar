import assert from 'node:assert/strict';
import test from 'node:test';

import { createLivewireSection } from '../../resources/js/livewire-section.js';

const tabs = () => ['overview', 'components', 'events'].map((key) => ({
  dataset: { ndbLivewireTab: key },
  focused: false,
  focus() { this.focused = true; },
}));

const choices = (ids) => ids.map((id) => ({
  dataset: { ndbLivewireChoice: id },
  focused: false,
  focus() { this.focused = true; },
}));

test('starts with the first safe component and event selection', () => {
  const state = createLivewireSection({
    componentIds: ['component-1', '', null],
    eventIds: ['event-1'],
  });

  assert.deepEqual(state.componentIds, ['component-1']);
  assert.equal(state.selectedComponentId, 'component-1');
  assert.equal(state.selectedEventId, 'event-1');

  state.selectLivewireItem('component', 'unknown');
  state.selectLivewireItem('event', 'unknown');
  assert.equal(state.selectedComponentId, 'component-1');
  assert.equal(state.selectedEventId, 'event-1');
});

test('selects only the three known Livewire tabs', () => {
  const state = createLivewireSection();

  state.selectLivewireTab('components');
  assert.equal(state.livewireTab, 'components');

  state.selectLivewireTab('timeline');
  assert.equal(state.livewireTab, 'components');
});

test('moves through Livewire tabs with standard arrow home and end keys', () => {
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
    currentTarget: items[2],
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

test('moves through component and event choices without leaving known IDs', () => {
  const items = choices(['component-1', 'component-2']);
  const parentElement = { querySelectorAll: () => items };
  items.forEach((item) => { item.parentElement = parentElement; });
  const state = createLivewireSection({ componentIds: ['component-1', 'component-2'] });

  state.handleLivewireItemKey({
    key: 'ArrowDown',
    currentTarget: items[0],
    preventDefault() {},
  }, 'component');
  assert.equal(state.selectedComponentId, 'component-2');
  assert.equal(items[1].focused, true);

  state.handleLivewireItemKey({
    key: 'ArrowUp',
    currentTarget: items[1],
    preventDefault() {},
  }, 'component');
  assert.equal(state.selectedComponentId, 'component-1');
});
