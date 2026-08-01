const STORAGE_KEY = 'new-debug-bar.preferences.v1';

const defaultRuntime = () => ({
  storage: window.localStorage,
  matchMedia: (query) => window.matchMedia(query),
  activeElement: () => document.activeElement,
  highlight: () => window.newDebugBarHighlight?.(document.getElementById('new-debug-bar')),
});

export function createNewDebugBar(summary = {}, runtime = null) {
  const browser = runtime ?? defaultRuntime();

  return {
    inspectorOpen: false,
    detailsRequested: false,
    selected: 'overview',
    theme: ['system', 'light', 'dark'].includes(summary.theme) ? summary.theme : 'system',
    resolvedTheme: 'light',
    favorites: [],
    favoriteDrag: null,
    favoriteDrop: null,
    favoriteDropAfter: false,
    paletteOpen: false,
    paletteSearch: '',
    paletteIndex: 0,
    paletteReturnFocus: null,
    summary,

    init() {
      this.restore();
      this.applyTheme();

      const scheme = browser.matchMedia?.('(prefers-color-scheme: dark)');
      scheme?.addEventListener?.('change', () => {
        if (this.theme === 'system') this.applyTheme();
      });
    },

    restore() {
      try {
        const saved = JSON.parse(browser.storage?.getItem(STORAGE_KEY) ?? '{}');

        if (['system', 'light', 'dark'].includes(saved.theme)) this.theme = saved.theme;
        if (Array.isArray(saved.favorites)) {
          const allowed = this.sectionKeys;
          this.favorites = [...new Set(saved.favorites)].filter((key) => allowed.includes(key));
        }
      } catch {
        // A broken preference must never break the host page.
      }
    },

    persist() {
      try {
        browser.storage?.setItem(STORAGE_KEY, JSON.stringify({
          theme: this.theme,
          favorites: this.favorites,
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

    get unpinnedSections() {
      return (this.summary.sections ?? []).filter((section) => !this.favorites.includes(section.key));
    },

    selectSection(section) {
      this.selected = this.sectionKeys.includes(section) ? section : 'overview';
      this.$nextTick?.(() => {
        if (this.$refs?.content) this.$refs.content.scrollTop = 0;
        browser.highlight?.();
      });
    },

    openInspector(section = this.selected) {
      this.selectSection(section);
      this.inspectorOpen = true;

      if (!this.detailsRequested) {
        this.detailsRequested = true;
        Promise.resolve(this.$wire?.loadDetails())
          .then(() => this.$nextTick?.(() => browser.highlight?.()))
          .catch(() => {
            this.detailsRequested = false;
          });
      }
    },

    closeInspector() {
      this.inspectorOpen = false;
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

    startFavoriteDrag(key, event = null) {
      if (!this.favorites.includes(key)) return;

      this.favoriteDrag = key;
      event?.dataTransfer?.setData?.('text/plain', key);
      if (event?.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    },

    hoverFavorite(key, after = false) {
      if (!this.favoriteDrag || this.favoriteDrag === key) return;

      this.favoriteDrop = key;
      this.favoriteDropAfter = after;
    },

    leaveFavorite(key) {
      if (this.favoriteDrop !== key) return;

      this.favoriteDrop = null;
      this.favoriteDropAfter = false;
    },

    dropFavorite(target, after = false) {
      const source = this.favoriteDrag;
      this.endFavoriteDrag();

      if (!source || source === target || !this.favorites.includes(target)) return;

      const reordered = this.favorites.filter((key) => key !== source);
      const targetIndex = reordered.indexOf(target);
      reordered.splice(targetIndex + (after ? 1 : 0), 0, source);
      this.favorites = reordered;
      this.persist();
    },

    endFavoriteDrag() {
      this.favoriteDrag = null;
      this.favoriteDrop = null;
      this.favoriteDropAfter = false;
    },

    toggleTheme() {
      this.setTheme(this.resolvedTheme === 'dark' ? 'light' : 'dark');
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
  };
}

export { STORAGE_KEY };
