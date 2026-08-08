import '../css/newdebugbar.css';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import sql from 'highlight.js/lib/languages/sql';
import { installProfileDiscoveryBridge, installRequestDiscovery } from './request-discovery.js';
import { createNewDebugBar } from './state.js';

const php = (language) => ({
  name: 'PHP',
  aliases: ['php'],
  keywords: {
    keyword: 'abstract and array as break callable case catch class clone const continue declare default do echo else elseif empty enddeclare endfor endforeach endif endswitch endwhile enum eval exit extends final finally fn for foreach from function global goto if implements include include_once instanceof insteadof interface isset list match namespace new or print private protected public readonly require require_once return static switch throw trait try unset use var while xor yield yield from',
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

window.newDebugBar = (summary) => createNewDebugBar(summary);

const registerLivewireProfileSwitching = () => {
  if (window.__newDebugBarRequestInterceptor || !window.Livewire?.interceptRequest) return;

  window.__newDebugBarRequestInterceptor = true;
  window.Livewire.interceptRequest(({ onResponse, onFinish }) => {
    let profileId = null;

    onResponse(({ response }) => {
      profileId = response.headers.get('X-NewDebugBar-Profile');
    });

    onFinish(() => {
      if (!profileId) return;

      const debugBar = window.Livewire.getByName('newdebugbar.toolbar')[0];
      Promise.resolve(debugBar?.switchProfile?.(profileId)).catch(() => {});
    });
  });
};

registerLivewireProfileSwitching();
document.addEventListener('livewire:init', registerLivewireProfileSwitching, { once: true });
installProfileDiscoveryBridge();
installRequestDiscovery();
