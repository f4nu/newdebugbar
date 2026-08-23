import assert from 'node:assert/strict';
import test from 'node:test';

import { createNewDebugBar, STORAGE_KEY } from '../../resources/js/state.js';
import { runtime, summary } from './state-test-support.js';

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

test('consumed escape events do not close the inspector', () => {
  const state = createNewDebugBar(summary, runtime());
  state.inspectorOpen = true;

  state.handleShortcut({
    defaultPrevented: true,
    metaKey: false,
    ctrlKey: false,
    shiftKey: false,
    key: 'Escape',
  });

  assert.equal(state.inspectorOpen, true);

  state.handleShortcut({
    defaultPrevented: false,
    metaKey: false,
    ctrlKey: false,
    shiftKey: false,
    key: 'Escape',
    target: {
      closest: () => ({ querySelector: () => ({}) }),
    },
  });

  assert.equal(state.inspectorOpen, true);
});

test('the request action reuses an open inspector and opens a closed one', () => {
  const browser = runtime();
  const opener = {};
  const state = createNewDebugBar({
    sections: [
      { key: 'overview', label: 'Overview' },
      { key: 'request', label: 'Request' },
    ],
  }, browser);
  state.$root = { querySelectorAll: () => [] };
  state.$wire = { loadSection: async () => {} };
  state.$nextTick = (callback) => callback();
  state.loadedSection = 'overview';
  state.inspectorOpen = true;

  state.openRequestSection();

  assert.equal(state.selected, 'request');
  assert.equal(browser.host.locks, 0);

  state.inspectorOpen = false;
  state.selectSection('overview');
  state.openRequestSection(opener);

  assert.equal(state.inspectorOpen, true);
  assert.equal(state.selected, 'request');
  assert.equal(state.inspectorReturnFocus, opener);
  assert.equal(browser.host.locks, 1);
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
  state.$wire = { loadSection: async () => {} };
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
