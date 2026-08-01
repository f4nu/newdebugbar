import '../css/new-debug-bar.css';
import { createNewDebugBar } from './state.js';

window.newDebugBar = (summary) => createNewDebugBar(summary);
window.dispatchEvent(new CustomEvent('new-debug-bar:assets-ready'));
