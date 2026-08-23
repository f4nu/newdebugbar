import '../css/newdebugbar.css';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import sql from 'highlight.js/lib/languages/sql';
import { installLivewireTrace } from './livewire-trace.js';
import { installProfileDiscoveryBridge, installRequestDiscovery } from './request-discovery.js';
import { createNewDebugBar, createViewDataState } from './state.js';

const php = (language) => ({
  name: 'PHP',
  aliases: ['php'],
  keywords: {
    keyword:
      'abstract and array as break callable case catch class clone const continue declare default do echo else elseif empty enddeclare endfor endforeach endif endswitch endwhile enum eval exit extends final finally fn for foreach from function global goto if implements include include_once instanceof insteadof interface isset list match namespace new or print private protected public readonly require require_once return static switch throw trait try unset use var while xor yield yield from',
    literal: 'true false null',
  },
  contains: [
    language.C_LINE_COMMENT_MODE,
    language.C_BLOCK_COMMENT_MODE,
    language.APOS_STRING_MODE,
    language.QUOTE_STRING_MODE,
    { scope: 'variable', begin: /\$[A-Za-z_][A-Za-z0-9_]*/ },
    { scope: 'number', begin: language.C_NUMBER_RE },
  ],
});

hljs.registerLanguage('json', json);
hljs.registerLanguage('php', php);
hljs.registerLanguage('sql', sql);

window.newDebugBarHighlight = (root = document) => {
  root.querySelectorAll('code[data-ndb-language]:not([data-highlighted])').forEach((block) => {
    block.classList.add(`language-${block.dataset.ndbLanguage}`);
    hljs.highlightElement(block);
  });
};

const livewireTrace = installLivewireTrace();

window.newDebugBar = (summary, profileLimit) => createNewDebugBar(summary, null, [], profileLimit, livewireTrace);
window.newDebugBar.viewData = (wire, renderOrder) =>
  createViewDataState(wire, renderOrder, () => window.newDebugBarHighlight?.(document.getElementById('newdebugbar')));

installProfileDiscoveryBridge();
installRequestDiscovery();
