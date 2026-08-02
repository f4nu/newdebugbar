import '../css/new-debug-bar.css';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import sql from 'highlight.js/lib/languages/sql';
import { createNewDebugBar } from './state.js';

hljs.registerLanguage('json', json);
hljs.registerLanguage('sql', sql);

window.newDebugBarHighlight = (root = document) => {
  root.querySelectorAll('code[data-ndb-language]:not([data-highlighted])').forEach((block) => {
    block.classList.add(`language-${block.dataset.ndbLanguage}`);
    hljs.highlightElement(block);
  });
};

window.newDebugBar = (summary) => createNewDebugBar(summary);
