const STORAGE_KEY = 'new-debug-bar.preferences.v1';

const defaultRuntime = () => ({
  storage: {
    getItem: (key) => window.localStorage.getItem(key),
    setItem: (key, value) => window.localStorage.setItem(key, value),
  },
  matchMedia: (query) => window.matchMedia(query),
  activeElement: () => document.activeElement,
  writeClipboard: (value) => window.navigator.clipboard?.writeText(value),
  highlight: () => window.newDebugBarHighlight?.(document.getElementById('new-debug-bar')),
});

export function createNewDebugBar(summary = {}, runtime = null) {
  const browser = runtime ?? defaultRuntime();

  return {
    inspectorOpen: false,
    inspectorReturnFocus: null,
    mobileSectionsOpen: false,
    mobileSectionsReturnFocus: null,
    detailsRequested: false,
    selected: 'overview',
    theme: ['system', 'light', 'dark'].includes(summary.theme) ? summary.theme : 'system',
    resolvedTheme: 'light',
    favorites: [],
    favoriteDrag: null,
    favoriteDrop: null,
    favoriteDropAfter: false,
    queryFilter: 'all',
    querySearch: '',
    querySort: 'execution',
    visibleQueryCount: summary.query_count ?? 0,
    historyPath: '',
    historyMethod: '',
    historyStatus: '',
    historyWarning: 'all',
    visibleHistoryCount: 0,
    discoveredProfileId: null,
    timelineFilter: 'all',
    timelineSearch: '',
    visibleTimelineCount: summary.section_counts?.timeline ?? 0,
    eventSource: 'all',
    eventSearch: '',
    visibleEventCount: summary.section_counts?.events ?? 0,
    logLevel: 'all',
    logSearch: '',
    visibleLogCount: summary.section_counts?.logs ?? 0,
    paletteOpen: false,
    paletteSearch: '',
    paletteIndex: 0,
    paletteReturnFocus: null,
    colorScheme: null,
    colorSchemeListener: null,
    summary,

    init() {
      this.restore();
      this.colorScheme = browser.matchMedia?.('(prefers-color-scheme: dark)') ?? null;
      this.colorSchemeListener = () => {
        if (this.theme === 'system') this.applyTheme();
      };
      this.applyTheme();
      this.$nextTick?.(() => this.syncSectionPanels());
      this.colorScheme?.addEventListener?.('change', this.colorSchemeListener);
    },

    destroy() {
      this.colorScheme?.removeEventListener?.('change', this.colorSchemeListener);
      this.colorScheme = null;
      this.colorSchemeListener = null;
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
      const allSections = this.summary.sections ?? [];
      const byKey = new Map(allSections.map((section) => [section.key, section]));
      const favorites = this.favorites.map((key) => byKey.get(key)).filter(Boolean);
      const overview = this.isFavorite('overview') ? null : byKey.get('overview');
      const sections = allSections
        .filter((section) => section.key !== 'overview' && !this.isFavorite(section.key))
        .sort((left, right) => left.label.localeCompare(right.label, undefined, { sensitivity: 'base' }));

      return [...favorites, ...(overview ? [overview] : []), ...sections];
    },

    get sidebarSections() {
      return this.orderedSections.filter((section) => this.isSectionVisible(section));
    },

    get firstVisibleNonFavoriteKey() {
      return this.orderedSections.find((section) => (
        !this.isFavorite(section.key) && this.isSectionVisible(section)
      ))?.key ?? null;
    },

    get selectedSection() {
      return (this.summary.sections ?? []).find((section) => section.key === this.selected)
        ?? { key: 'overview', label: 'Overview', count: null };
    },

    isFavorite(key) {
      return this.favorites.includes(key);
    },

    isSectionActive(section) {
      return section?.active !== false;
    },

    isSectionVisible(section) {
      return this.isSectionActive(section)
        || this.isFavorite(section.key)
        || section.key === this.selected;
    },

    selectSection(section) {
      const focusContentHeading = this.mobileSectionsOpen;
      this.selected = this.sectionKeys.includes(section) ? section : 'overview';
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.$nextTick?.(() => {
        this.syncSectionPanels();
        if (this.selected === 'queries') this.applyQueryView();
        if (this.selected === 'history') this.applyHistoryFilters();
        if (this.selected === 'timeline') this.applyTimelineFilters();
        if (this.selected === 'events') this.applyEventFilters();
        if (this.selected === 'logs') this.applyLogFilters();
        if (this.$refs?.content) this.$refs.content.scrollTop = 0;
        if (focusContentHeading) this.$refs?.sectionHeading?.focus?.();
        browser.highlight?.();
      });
    },

    openMobileSections(returnFocus = null) {
      if (this.mobileSectionsOpen) return;

      this.mobileSectionsReturnFocus = returnFocus ?? browser.activeElement?.();
      this.mobileSectionsOpen = true;
      this.$nextTick?.(() => {
        const navigation = this.$refs?.mobileSectionsNav;
        const selectedSection = navigation
          ?.querySelector?.('[data-ndb-select-section][aria-current="page"]');
        const firstSection = navigation?.querySelector?.('[data-ndb-select-section]');

        (selectedSection ?? firstSection)?.focus?.();
      });
    },

    toggleMobileSections() {
      this.mobileSectionsOpen
        ? this.closeMobileSections()
        : this.openMobileSections();
    },

    closeMobileSections(restoreFocus = true) {
      const returnFocus = this.mobileSectionsReturnFocus;
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;

      if (restoreFocus) this.$nextTick?.(() => returnFocus?.focus?.());
    },

    syncSectionPanels() {
      const panels = this.$root?.querySelectorAll?.('[data-ndb-section-panel]') ?? [];

      panels.forEach((panel) => {
        panel.hidden = panel.dataset.ndbSectionPanel !== this.selected;
      });
    },

    openInspector(section = this.selected, returnFocus = null) {
      if (!this.inspectorOpen) {
        this.inspectorReturnFocus = returnFocus ?? browser.activeElement?.();
      }

      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.selectSection(section);
      this.inspectorOpen = true;
      this.$nextTick?.(() => this.$refs?.inspectorClose?.focus());

      if (!this.detailsRequested) {
        this.detailsRequested = true;
        Promise.resolve(this.$wire?.loadDetails())
          .then(() => this.$nextTick?.(() => {
            this.syncSectionPanels();
            this.applyQueryView();
            this.applyHistoryFilters();
            this.applyTimelineFilters();
            this.applyEventFilters();
            this.applyLogFilters();
            browser.highlight?.();
          }))
          .catch(() => {
            this.detailsRequested = false;
          });
      }
    },

    closeInspector() {
      const returnFocus = this.inspectorReturnFocus;
      this.inspectorOpen = false;
      this.inspectorReturnFocus = null;
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.$nextTick?.(() => returnFocus?.focus?.());
    },

    switchProfile(summary) {
      this.summary = summary ?? {};
      this.detailsRequested = false;
      const section = this.sectionKeys.includes(this.selected) ? this.selected : 'overview';

      if (this.inspectorOpen) {
        this.openInspector(section);
      } else {
        this.selected = section;
        this.$nextTick?.(() => this.syncSectionPanels());
      }
    },

    noticeProfile(profileId) {
      if (!/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(profileId ?? '')) return;
      if (profileId === this.summary.profile_id) return;

      this.discoveredProfileId = profileId;

      Promise.resolve(this.$wire?.discoverProfile(profileId))
        .then(() => this.$nextTick?.(() => this.applyHistoryFilters()))
        .catch(() => {});
    },

    copyText(value) {
      try {
        Promise.resolve(browser.writeClipboard?.(value)).catch(() => {});
      } catch {
        // Clipboard policies must never break the host page.
      }
    },

    setQueryFilter(filter) {
      if (!['all', 'repeated', 'slow', 'read', 'write'].includes(filter)) return;

      this.queryFilter = filter;
      this.applyQueryView();
    },

    reviewQueryEvidence(filter) {
      if (!['repeated', 'slow'].includes(filter)) return;

      this.setQueryFilter(filter);
      this.$nextTick?.(() => {
        const list = filter === 'repeated' ? this.$refs?.queryGroups : this.$refs?.queryItems;
        const target = list?.querySelector?.('[data-ndb-query-group]:not([hidden]), [data-ndb-query-item]:not([hidden])');

        target?.scrollIntoView?.({ block: 'start' });
      });
    },

    setQuerySort(sort) {
      if (!['execution', 'duration'].includes(sort)) return;

      this.querySort = sort;
      this.applyQueryView();
    },

    applyQueryView() {
      const itemList = this.$refs?.queryItems;
      const groupList = this.$refs?.queryGroups;
      const search = this.querySearch.toLowerCase().trim();
      let visible = 0;

      if (itemList?.children) {
        const items = [...itemList.children];

        items
          .sort((left, right) => this.compareQueries(left, right))
          .forEach((item) => {
            const matchesFilter = this.queryFilter === 'all'
              || (this.queryFilter === 'slow' && item.dataset.slow === 'true')
              || (this.queryFilter === 'read' && item.dataset.type === 'read')
              || (this.queryFilter === 'write' && item.dataset.type === 'write');
            const matchesSearch = search === '' || item.dataset.search?.includes(search);
            item.hidden = this.queryFilter === 'repeated' || !matchesFilter || !matchesSearch;
            if (!item.hidden) visible++;
            itemList.appendChild?.(item);
          });
      }

      if (groupList?.children) {
        const groups = [...groupList.children];

        groups
          .sort((left, right) => this.compareQueries(left, right))
          .forEach((group) => {
            const matchesSearch = search === '' || group.dataset.search?.includes(search);
            group.hidden = this.queryFilter !== 'repeated' || !matchesSearch;
            if (!group.hidden) visible++;
            groupList.appendChild?.(group);
          });
      }

      this.visibleQueryCount = visible;
    },

    compareQueries(left, right) {
      if (this.querySort === 'duration') {
        return Number(right.dataset.duration ?? 0) - Number(left.dataset.duration ?? 0)
          || Number(left.dataset.execution ?? 0) - Number(right.dataset.execution ?? 0);
      }

      return Number(left.dataset.execution ?? 0) - Number(right.dataset.execution ?? 0);
    },

    setHistoryWarning(filter) {
      if (!['all', 'warning', 'clean'].includes(filter)) return;

      this.historyWarning = filter;
      this.applyHistoryFilters();
    },

    applyHistoryFilters() {
      const list = this.$refs?.historyList;

      if (!list?.children) {
        this.visibleHistoryCount = 0;

        return;
      }

      const path = this.historyPath.toLowerCase().trim();
      const method = this.historyMethod.toUpperCase().trim();
      const status = this.historyStatus.trim();
      let visible = 0;

      [...list.children].forEach((profile) => {
        const matches = (path === '' || profile.dataset.path?.includes(path))
          && (method === '' || profile.dataset.method === method)
          && (status === '' || profile.dataset.status === status)
          && (this.historyWarning === 'all'
            || (this.historyWarning === 'warning' && profile.dataset.warning === 'true')
            || (this.historyWarning === 'clean' && profile.dataset.warning === 'false'));
        profile.hidden = !matches;
        if (matches) visible++;
      });

      this.visibleHistoryCount = visible;
    },

    setTimelineFilter(filter) {
      if (!this.sectionKeys.includes(filter) && filter !== 'all') return;

      this.timelineFilter = filter;
      this.applyTimelineFilters();
    },

    applyTimelineFilters() {
      const list = this.$refs?.timelineList ?? this.$root?.querySelector?.('[x-ref="timelineList"]');

      if (!list?.children) {
        this.visibleTimelineCount = 0;

        return;
      }

      const search = this.timelineSearch.toLowerCase().trim();
      let visible = 0;

      [...list.children].forEach((item) => {
        const matches = (this.timelineFilter === 'all' || item.dataset.section === this.timelineFilter)
          && (search === '' || item.dataset.search?.includes(search));
        item.hidden = !matches;
        if (matches) visible++;
      });

      this.visibleTimelineCount = visible;
    },

    setEventSource(source) {
      if (!['all', 'application', 'framework'].includes(source)) return;

      this.eventSource = source;
      this.applyEventFilters();
    },

    applyEventFilters() {
      const list = this.$refs?.eventList ?? this.$root?.querySelector?.('[x-ref="eventList"]');
      const search = this.eventSearch.toLowerCase().trim();
      let visible = 0;

      [...(list?.children ?? [])].forEach((item) => {
        const matches = (this.eventSource === 'all' || item.dataset.source === this.eventSource)
          && (search === '' || item.dataset.search?.includes(search));
        item.hidden = !matches;
        if (matches) visible++;
      });

      this.visibleEventCount = visible;
    },

    setLogLevel(level) {
      const list = this.$refs?.logList ?? this.$root?.querySelector?.('[x-ref="logList"]');
      const available = [...(list?.children ?? [])].map((item) => item.dataset.level);
      if (level !== 'all' && !available.includes(level)) return;

      this.logLevel = level;
      this.applyLogFilters();
    },

    applyLogFilters() {
      const list = this.$refs?.logList ?? this.$root?.querySelector?.('[x-ref="logList"]');
      const search = this.logSearch.toLowerCase().trim();
      let visible = 0;

      [...(list?.children ?? [])].forEach((item) => {
        const matches = (this.logLevel === 'all' || item.dataset.level === this.logLevel)
          && (search === '' || item.dataset.search?.includes(search));
        item.hidden = !matches;
        if (matches) visible++;
      });

      this.visibleLogCount = visible;
    },

    keepFocusWithin(event, container) {
      if (event.key !== 'Tab') return;

      const focusable = [...(container?.querySelectorAll?.('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])') ?? [])]
        .filter((element) => element.hidden !== true && (element.getClientRects?.().length ?? 1) > 0);

      if (focusable.length === 0) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      const active = browser.activeElement?.();

      if (event.shiftKey && (active === first || !container.contains(active))) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && (active === last || !container.contains(active))) {
        event.preventDefault();
        first.focus();
      }
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
      this.syncFavoriteDragVisuals();
    },

    hoverFavorite(key, after = false) {
      if (!this.favoriteDrag || this.favoriteDrag === key || !this.isFavorite(key)) return;

      this.favoriteDrop = key;
      this.favoriteDropAfter = after;
      this.syncFavoriteDragVisuals();
    },

    leaveFavorite(key) {
      if (this.favoriteDrop !== key) return;

      this.favoriteDrop = null;
      this.favoriteDropAfter = false;
      this.syncFavoriteDragVisuals();
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
      this.syncFavoriteDragVisuals();
    },

    syncFavoriteDragVisuals() {
      const rows = this.$root?.querySelectorAll?.('[data-ndb-section]') ?? [];

      rows.forEach((row) => {
        const key = row.dataset.ndbSection;
        const dragging = this.favoriteDrag === key;
        const dropBefore = this.favoriteDrop === key && !this.favoriteDropAfter;
        const dropAfter = this.favoriteDrop === key && this.favoriteDropAfter;

        row.dataset.ndbDragging = dragging ? 'true' : 'false';
        row.classList.toggle('ndb-favorite-dragging', dragging);
        row.querySelector('[data-ndb-favorite-drop-before]')?.toggleAttribute('hidden', !dropBefore);
        row.querySelector('[data-ndb-favorite-drop-after]')?.toggleAttribute('hidden', !dropAfter);
      });
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
        ? ((this.colorScheme ?? browser.matchMedia?.('(prefers-color-scheme: dark)'))?.matches ? 'dark' : 'light')
        : this.theme;
    },

    togglePalette() {
      this.paletteOpen ? this.closePalette() : this.openPalette();
    },

    openPalette() {
      this.paletteReturnFocus = this.mobileSectionsOpen
        ? this.mobileSectionsReturnFocus
        : browser.activeElement?.();
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.paletteOpen = true;
      this.paletteSearch = '';
      this.paletteIndex = 0;
      this.$nextTick?.(() => this.$refs?.paletteSearch?.focus());
    },

    closePalette(restoreFocus = true) {
      const returnFocus = this.paletteReturnFocus;
      this.paletteOpen = false;
      this.paletteSearch = '';
      this.paletteIndex = 0;
      this.paletteReturnFocus = null;

      if (restoreFocus) this.$nextTick?.(() => returnFocus?.focus?.());
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

      if (kind === 'section') {
        const returnFocus = this.paletteReturnFocus;
        this.closePalette(false);
        this.openInspector(value, returnFocus);

        return;
      }

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
        else if (this.mobileSectionsOpen) this.closeMobileSections();
        else if (this.inspectorOpen) this.closeInspector();
      }
    },
  };
}

export { STORAGE_KEY };
