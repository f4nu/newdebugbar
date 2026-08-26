import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar } from '../../resources/js/state.js';
import { runtime, summary } from './state-test-support.js';

test('Request evidence uses deliberate HTTP and runtime defaults with one scroll reset', () => {
  const browser = runtime();
  let highlights = 0;
  const scrolls = [];
  browser.highlight = () => highlights++;
  const state = createNewDebugBar(summary, browser);
  state.$refs = {
    requestDetailBody: {
      scrollTo: (options) => scrolls.push(options),
    },
  };
  state.$nextTick = (callback) => callback();

  state.initializeRequestDetails('route');
  assert.equal(state.requestDetailTab, 'route');
  assert.deepEqual(scrolls, [{ top: 0, behavior: 'instant' }]);

  state.setRequestDetailTab('headers');
  assert.equal(state.requestDetailTab, 'headers');
  assert.deepEqual(scrolls, [
    { top: 0, behavior: 'instant' },
    { top: 0, behavior: 'instant' },
  ]);

  state.setRequestDetailTab('invalid');
  assert.equal(state.requestDetailTab, 'headers');

  state.initializeRequestDetails('runtime');
  assert.equal(state.requestDetailTab, 'runtime');
  state.setRequestDetailTab('context');
  assert.equal(state.requestDetailTab, 'context');

  state.initializeRequestDetails('invalid');
  assert.equal(state.requestDetailTab, 'route');
  assert.equal(highlights, 5);
});
