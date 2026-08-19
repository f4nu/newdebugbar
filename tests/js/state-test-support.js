import { createNewDebugBar, STORAGE_KEY } from '../../resources/js/state.js';

export function runtime(saved = null) {
  const values = new Map(saved ? [[STORAGE_KEY, JSON.stringify(saved)]] : []);
  const host = { locks: 0, unlocks: 0 };
  const timers = new Set();

  return {
    values,
    host,
    timers,
    storage: {
      getItem: (key) => values.get(key) ?? null,
      setItem: (key, value) => values.set(key, value),
    },
    matchMedia: () => ({ matches: true, addEventListener() {}, removeEventListener() {} }),
    activeElement: () => null,
    highlight: () => {},
    afterPaint: (callback) => callback(),
    nextFrame: (callback) => callback(),
    schedule: (callback) => {
      timers.add(callback);

      return callback;
    },
    cancelSchedule: (timer) => timers.delete(timer),
    runTimers: () => {
      const callbacks = [...timers];
      timers.clear();
      callbacks.forEach((callback) => callback());
    },
    viewportHeight: () => 900,
    lockHost: () => host.locks++,
    unlockHost: () => host.unlocks++,
  };
}

export const summary = {
  sections: [
    { key: 'overview', label: 'Overview', description: 'Request summary.' },
    { key: 'queries', label: 'Queries', description: 'Query evidence.' },
    { key: 'logs', label: 'Logs', description: 'Log evidence.' },
  ],
};

export function toolbarHarness(saved = null) {
  const browser = runtime(saved);
  browser.toolbarPlacement = (_root, preferred) => preferred;
  browser.watchHostDialogs = () => () => {};
  const state = createNewDebugBar(summary, browser);
  const capture = { pointerId: null, releases: [] };
  const toolbar = {
    setPointerCapture: (pointerId) => { capture.pointerId = pointerId; },
    hasPointerCapture: (pointerId) => capture.pointerId === pointerId,
    releasePointerCapture: (pointerId) => {
      capture.releases.push(pointerId);
      capture.pointerId = null;
    },
    getBoundingClientRect: () => {
      const height = 60;
      const baseTop = state.toolbarPlacement === 'top' ? 12 : 828;

      return { top: baseTop + state.toolbarDragOffsetY, width: 1024, height };
    },
  };
  state.$root = {
    querySelector: (selector) => selector === '[data-ndb-toolbar-shell]' ? toolbar : null,
    querySelectorAll: () => [],
  };
  state.$nextTick = (callback) => callback();
  state.init();

  const pointer = (overrides = {}) => ({
    pointerId: 7,
    pointerType: 'mouse',
    button: 0,
    isPrimary: true,
    clientX: 720,
    clientY: 850,
    currentTarget: toolbar,
    target: { closest: () => null },
    preventDefault() {},
    ...overrides,
  });

  return { browser, capture, pointer, state, toolbar };
}

