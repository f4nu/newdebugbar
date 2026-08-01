const STORAGE_KEY = 'new-debug-bar.preferences.v1';

const defaultRuntime = () => ({
  storage: window.localStorage,
  viewport: () => ({ width: window.innerWidth, height: window.innerHeight }),
  matchMedia: (query) => window.matchMedia(query),
  addWindowListener: (event, callback) => window.addEventListener(event, callback),
  activeElement: () => document.activeElement,
});

export function createNewDebugBar(summary = {}, runtime = null) {
  const browser = runtime ?? defaultRuntime();

  return {
    mode: ['bar', 'floating'].includes(summary.default_mode) ? summary.default_mode : 'bar',
    inspectorOpen: false,
    detailsRequested: false,
    selected: 'overview',
    theme: ['system', 'light', 'dark'].includes(summary.theme) ? summary.theme : 'system',
    resolvedTheme: 'light',
    favorites: [],
    favoriteDrag: null,
    paletteOpen: false,
    paletteSearch: '',
    paletteIndex: 0,
    paletteReturnFocus: null,
    bubble: { x: null, y: null },
    dragOrigin: null,
    dragging: false,
    moved: false,
    summary,

    init() {
      this.restore();
      this.applyTheme();
      this.clampBubble();

      browser.addWindowListener?.('resize', () => this.clampBubble());

      const scheme = browser.matchMedia?.('(prefers-color-scheme: dark)');
      scheme?.addEventListener?.('change', () => {
        if (this.theme === 'system') this.applyTheme();
      });
    },

    restore() {
      try {
        const saved = JSON.parse(browser.storage?.getItem(STORAGE_KEY) ?? '{}');

        if (['bar', 'floating'].includes(saved.mode)) this.mode = saved.mode;
        if (['system', 'light', 'dark'].includes(saved.theme)) this.theme = saved.theme;
        if (Array.isArray(saved.favorites)) {
          const allowed = this.sectionKeys;
          this.favorites = [...new Set(saved.favorites)].filter((key) => allowed.includes(key));
        }
        if (Number.isFinite(saved.bubble?.x) && Number.isFinite(saved.bubble?.y)) {
          this.bubble = { x: saved.bubble.x, y: saved.bubble.y };
        }
      } catch {
        // A broken preference must never break the host page.
      }
    },

    persist() {
      try {
        browser.storage?.setItem(STORAGE_KEY, JSON.stringify({
          mode: this.mode,
          theme: this.theme,
          favorites: this.favorites,
          bubble: this.bubble,
        }));
      } catch {
        // Private browsing and strict storage policies are allowed.
      }
    },

    get sectionKeys() {
      return (this.summary.sections ?? []).map((section) => section.key);
    },

    get allCommands() {
      const sections = (this.summary.sections ?? []).map((section) => ({
        id: `section:${section.key}`,
        label: `Go to ${section.label}`,
        hint: 'Section',
      }));

      return [
        ...sections,
        { id: 'mode:bar', label: 'Use bottom bar', hint: 'Mode' },
        { id: 'mode:floating', label: 'Use floating bubble', hint: 'Mode' },
        { id: 'theme:system', label: 'Use system theme', hint: 'Theme' },
        { id: 'theme:light', label: 'Use light theme', hint: 'Theme' },
        { id: 'theme:dark', label: 'Use dark theme', hint: 'Theme' },
      ];
    },

    get filteredCommands() {
      const words = this.paletteSearch.toLowerCase().trim().split(/\s+/).filter(Boolean);

      if (words.length === 0) return this.allCommands;

      return this.allCommands.filter((command) => {
        const value = `${command.label} ${command.hint}`.toLowerCase();
        return words.every((word) => value.includes(word));
      });
    },

    get orderedSections() {
      const byKey = new Map((this.summary.sections ?? []).map((section) => [section.key, section]));
      return this.favorites.map((key) => byKey.get(key)).filter(Boolean);
    },

    get selectedSection() {
      return (this.summary.sections ?? []).find((section) => section.key === this.selected)
        ?? { key: 'overview', label: 'Overview', count: null };
    },

    get remainingSections() {
      return (this.summary.sections ?? []).filter((section) => !this.favorites.includes(section.key));
    },

    get bubbleStyle() {
      return `transform: translate3d(${Math.round(this.bubble.x ?? 0)}px, ${Math.round(this.bubble.y ?? 0)}px, 0)`;
    },

    openInspector(section = this.selected) {
      this.selected = this.sectionKeys.includes(section) ? section : 'overview';
      this.inspectorOpen = true;

      if (!this.detailsRequested) {
        this.detailsRequested = true;
        this.$wire?.loadDetails();
      }
    },

    closeInspector() {
      this.inspectorOpen = false;
      this.mode = 'floating';
      this.persist();
    },

    useMode(mode) {
      if (!['bar', 'floating'].includes(mode)) return;

      this.mode = mode;
      this.inspectorOpen = false;
      this.persist();
    },

    toggleFavorite(key) {
      if (!this.sectionKeys.includes(key)) return;

      this.favorites = this.favorites.includes(key)
        ? this.favorites.filter((favorite) => favorite !== key)
        : [...this.favorites, key];
      this.persist();
    },

    moveFavorite(key, direction) {
      const index = this.favorites.indexOf(key);
      const target = index + direction;

      if (index < 0 || target < 0 || target >= this.favorites.length) return;

      const reordered = [...this.favorites];
      [reordered[index], reordered[target]] = [reordered[target], reordered[index]];
      this.favorites = reordered;
      this.persist();
    },

    startFavoriteDrag(key) {
      if (this.favorites.includes(key)) this.favoriteDrag = key;
    },

    dropFavorite(target) {
      const source = this.favoriteDrag;
      this.favoriteDrag = null;

      if (!source || source === target || !this.favorites.includes(target)) return;

      const reordered = this.favorites.filter((key) => key !== source);
      reordered.splice(reordered.indexOf(target), 0, source);
      this.favorites = reordered;
      this.persist();
    },

    cycleTheme() {
      const themes = ['system', 'light', 'dark'];
      this.setTheme(themes[(themes.indexOf(this.theme) + 1) % themes.length]);
    },

    setTheme(theme) {
      if (!['system', 'light', 'dark'].includes(theme)) return;

      this.theme = theme;
      this.applyTheme();
      this.persist();
    },

    applyTheme() {
      this.resolvedTheme = this.theme === 'system'
        ? (browser.matchMedia?.('(prefers-color-scheme: dark)')?.matches ? 'dark' : 'light')
        : this.theme;
    },

    togglePalette() {
      this.paletteOpen ? this.closePalette() : this.openPalette();
    },

    openPalette() {
      this.paletteReturnFocus = browser.activeElement?.();
      this.paletteOpen = true;
      this.paletteSearch = '';
      this.paletteIndex = 0;
      this.$nextTick?.(() => this.$refs?.paletteSearch?.focus());
    },

    closePalette() {
      this.paletteOpen = false;
      this.paletteSearch = '';
      this.paletteIndex = 0;
      this.$nextTick?.(() => this.paletteReturnFocus?.focus?.());
    },

    movePalette(direction) {
      const count = this.filteredCommands.length;
      if (count === 0) return;

      this.paletteIndex = (this.paletteIndex + direction + count) % count;
    },

    runActiveCommand() {
      const command = this.filteredCommands[this.paletteIndex];
      if (command) this.runCommand(command.id);
    },

    runCommand(id) {
      const [kind, value] = id.split(':');

      if (kind === 'section') this.openInspector(value);
      if (kind === 'mode') this.useMode(value);
      if (kind === 'theme') this.setTheme(value);

      this.closePalette();
    },

    handleShortcut(event) {
      if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key.toLowerCase() === 'p') {
        event.preventDefault();
        this.togglePalette();
      }

      if (event.key === 'Escape') {
        if (this.paletteOpen) this.closePalette();
        else if (this.inspectorOpen) this.closeInspector();
      }
    },

    startDrag(event) {
      if (event.button !== undefined && event.button !== 0) return;

      this.dragging = true;
      this.moved = false;
      this.dragOrigin = {
        pointerX: event.clientX,
        pointerY: event.clientY,
        bubbleX: this.bubble.x,
        bubbleY: this.bubble.y,
      };
      event.currentTarget?.setPointerCapture?.(event.pointerId);
    },

    drag(event) {
      if (!this.dragging || !this.dragOrigin) return;

      const deltaX = event.clientX - this.dragOrigin.pointerX;
      const deltaY = event.clientY - this.dragOrigin.pointerY;
      this.moved = this.moved || Math.abs(deltaX) + Math.abs(deltaY) > 5;
      this.bubble = {
        x: this.dragOrigin.bubbleX + deltaX,
        y: this.dragOrigin.bubbleY + deltaY,
      };
      this.clampBubble();
    },

    finishDrag() {
      if (!this.dragging) return;

      this.dragging = false;
      this.dragOrigin = null;
      this.clampBubble();
      this.persist();

      if (!this.moved) this.openInspector();
    },

    clampBubble() {
      const viewport = browser.viewport?.() ?? { width: 1024, height: 768 };
      const width = Math.min(236, Math.max(180, viewport.width - 24));
      const height = 64;
      const inset = 12;

      const initialX = Math.max(inset, viewport.width - width - 24);
      const initialY = Math.max(inset, viewport.height - height - 24);

      this.bubble = {
        x: Math.min(Math.max(this.bubble.x ?? initialX, inset), Math.max(inset, viewport.width - width - inset)),
        y: Math.min(Math.max(this.bubble.y ?? initialY, inset), Math.max(inset, viewport.height - height - inset)),
      };
    },
  };
}

export { STORAGE_KEY };
