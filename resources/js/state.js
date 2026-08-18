const STORAGE_KEY = 'newdebugbar.preferences.v1';

const defaultRuntime = () => ({
  storage: {
    getItem: (key) => window.localStorage.getItem(key),
    setItem: (key, value) => window.localStorage.setItem(key, value),
  },
  matchMedia: (query) => window.matchMedia(query),
  activeElement: () => document.activeElement,
  writeClipboard: (value) => window.navigator.clipboard?.writeText(value),
  highlight: () => window.newDebugBarHighlight?.(document.getElementById('newdebugbar')),
  afterPaint: (callback) => window.requestAnimationFrame(() => window.requestAnimationFrame(callback)),
  lockHost: (root) => {
    if (!root || root.__newDebugBarHostLock) return;

    const body = document.body;
    const scrollbarWidth = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
    const previous = {
      overflow: body.style.overflow,
      paddingRight: body.style.paddingRight,
      inert: [],
    };

    [...body.children].forEach((element) => {
      if (element === root
        || element.contains(root)
        || !(element instanceof HTMLElement)
        || element.matches('script, style, link')) return;

      previous.inert.push([element, element.inert]);
      element.inert = true;
    });

    body.style.overflow = 'hidden';

    if (scrollbarWidth > 0) {
      body.style.paddingRight = `${Number.parseFloat(window.getComputedStyle(body).paddingRight || '0') + scrollbarWidth}px`;
    }

    root.__newDebugBarHostLock = previous;
  },
  unlockHost: (root) => {
    const previous = root?.__newDebugBarHostLock;
    if (!previous) return;

    document.body.style.overflow = previous.overflow;
    document.body.style.paddingRight = previous.paddingRight;
    previous.inert.forEach(([element, inert]) => { element.inert = inert; });
    delete root.__newDebugBarHostLock;
  },
  toolbarPlacement: (root) => {
    const toolbar = root?.querySelector?.('[data-ndb-toolbar-shell]');
    if (!toolbar) return 'bottom';

    const toolbarBox = toolbar.getBoundingClientRect();
    const width = Math.min(toolbarBox.width, Math.max(0, window.innerWidth - 24));
    const height = toolbarBox.height;
    const left = (window.innerWidth - width) / 2;
    const candidates = {
      top: { left, right: left + width, top: 12, bottom: 12 + height },
      bottom: {
        left,
        right: left + width,
        top: window.innerHeight - height - 12,
        bottom: window.innerHeight - 12,
      },
    };
    const dialogs = [...document.querySelectorAll('dialog[open], [role="dialog"]')]
      .filter((dialog) => !root.contains(dialog))
      .map((dialog) => ({ dialog, box: dialog.getBoundingClientRect() }))
      .filter(({ dialog, box }) => {
        const style = window.getComputedStyle(dialog);

        return box.width > 0
          && box.height > 0
          && style.display !== 'none'
          && style.visibility !== 'hidden';
      })
      .map(({ box }) => box);
    const overlap = (candidate) => dialogs.reduce((area, dialog) => {
      const widthOverlap = Math.max(0, Math.min(candidate.right, dialog.right) - Math.max(candidate.left, dialog.left));
      const heightOverlap = Math.max(0, Math.min(candidate.bottom, dialog.bottom) - Math.max(candidate.top, dialog.top));

      return area + widthOverlap * heightOverlap;
    }, 0);

    return overlap(candidates.top) < overlap(candidates.bottom) ? 'top' : 'bottom';
  },
  watchHostDialogs: (_root, callback) => {
    let frame = null;
    const schedule = () => {
      if (frame !== null) return;
      frame = window.requestAnimationFrame(() => {
        frame = null;
        callback();
      });
    };
    const observer = new MutationObserver(schedule);
    observer.observe(document.body, {
      attributes: true,
      attributeFilter: ['open', 'aria-hidden', 'aria-modal', 'class', 'style'],
      childList: true,
      subtree: true,
    });
    window.addEventListener('resize', schedule);
    window.addEventListener('scroll', schedule, true);

    return () => {
      observer.disconnect();
      window.removeEventListener('resize', schedule);
      window.removeEventListener('scroll', schedule, true);
      if (frame !== null) window.cancelAnimationFrame(frame);
    };
  },
});

export function createNewDebugBar(summary = {}, runtime = null) {
  const browser = runtime ?? defaultRuntime();

  return {
    barVisible: true,
    inspectorOpen: false,
    inspectorReturnFocus: null,
    mobileSectionsOpen: false,
    mobileSectionsReturnFocus: null,
    mobileToolbarMenu: null,
    mobileToolbarReturnFocus: null,
    detailsRequested: false,
    detailsError: false,
    detailRequestVersion: 0,
    selected: 'overview',
    theme: ['system', 'light', 'dark'].includes(summary.theme) ? summary.theme : 'system',
    resolvedTheme: 'light',
    toolbarPlacement: 'bottom',
    stopToolbarPlacementWatch: null,
    favorites: [],
    favoriteDrag: null,
    favoriteDrop: null,
    favoriteDropAfter: false,
    queryFilter: 'all',
    querySearch: '',
    querySort: 'execution',
    viewSort: 'name',
    viewSortDirection: 'asc',
    visibleQueryCount: summary.query_count ?? 0,
    authorizationFilter: 'all',
    visibleAuthorizationCount: summary.section_counts?.authorization ?? 0,
    timelineFilter: 'key',
    timelineSearch: '',
    visibleTimelineCount: summary.section_counts?.timeline ?? 0,
    eventSource: 'application',
    eventSearch: '',
    visibleEventCount: summary.section_counts?.events ?? 0,
    logLevel: 'all',
    logSearch: '',
    visibleLogCount: summary.section_counts?.logs ?? 0,
    paletteOpen: false,
    paletteSearch: '',
    paletteIndex: 0,
    paletteShowQuiet: false,
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
      this.$nextTick?.(() => {
        this.syncSectionPanels();
        this.syncHostLock();
        this.syncToolbarPlacement();
        this.stopToolbarPlacementWatch = browser.watchHostDialogs?.(
          this.$root,
          () => this.syncToolbarPlacement(),
        ) ?? null;
      });
      this.colorScheme?.addEventListener?.('change', this.colorSchemeListener);
    },

    destroy() {
      this.colorScheme?.removeEventListener?.('change', this.colorSchemeListener);
      this.colorScheme = null;
      this.colorSchemeListener = null;
      this.stopToolbarPlacementWatch?.();
      this.stopToolbarPlacementWatch = null;
      browser.unlockHost?.(this.$root);
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
        hint: section.active === false ? 'Other collector' : (section.attention ? 'Needs attention' : 'Active section'),
        priority: section.attention ? 0 : (section.active === false ? 2 : 1),
      }));

      return [
        ...sections.sort((left, right) => left.priority - right.priority || left.label.localeCompare(right.label)),
        { id: 'theme:system', label: 'Use system theme', hint: 'Theme' },
        { id: 'theme:light', label: 'Use light theme', hint: 'Theme' },
        { id: 'theme:dark', label: 'Use dark theme', hint: 'Theme' },
      ];
    },

    get hiddenCommandCount() {
      return this.allCommands.filter((command) => command.hint === 'Other collector').length;
    },

    get filteredCommands() {
      const words = this.paletteSearch.toLowerCase().trim().split(/\s+/).filter(Boolean);

      if (words.length === 0 && !this.paletteShowQuiet) {
        const active = this.allCommands.filter((command) => command.hint !== 'Other collector');

        return this.hiddenCommandCount > 0
          ? [...active, { id: 'collectors:show', label: 'Show other collectors', hint: `${this.hiddenCommandCount} hidden` }]
          : active;
      }

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

    get firstVisibleNonFavoriteKey() {
      return this.orderedSections.find((section) => (
        !this.isFavorite(section.key) && this.isSectionVisible(section)
      ))?.key ?? null;
    },

    get selectedSection() {
      return (this.summary.sections ?? []).find((section) => section.key === this.selected)
        ?? { key: 'overview', label: 'Overview', description: '', count: null };
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

    selectSection(section, filter = null, focusHeading = false) {
      const focusContentHeading = focusHeading || this.mobileSectionsOpen;
      this.selected = this.sectionKeys.includes(section) ? section : 'overview';
      if (this.selected === 'queries' && ['repeated', 'slow'].includes(filter)) this.queryFilter = 'attention';
      if (this.selected === 'authorization' && ['all', 'allowed', 'denied'].includes(filter)) this.authorizationFilter = filter;
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.$nextTick?.(() => {
        this.syncSectionPanels();
        if (this.$refs?.content) this.$refs.content.scrollTop = 0;
        if (this.selected === 'queries') {
          this.applyQueryView();
          if (['repeated', 'slow'].includes(filter)) {
            const selector = filter === 'repeated'
              ? '[data-ndb-query-group]:not([hidden])'
              : '[data-ndb-query-item][data-slow="true"]:not([hidden]), [data-ndb-query-group][data-slow="true"]:not([hidden])';
            this.$refs?.queryResults?.querySelector?.(selector)?.scrollIntoView?.({ block: 'start' });
          }
        }
        if (this.selected === 'views') this.applyViewSort();
        if (this.selected === 'authorization') this.applyAuthorizationFilters();
        if (this.selected === 'timeline') this.applyTimelineFilters();
        if (this.selected === 'events') this.applyEventFilters();
        if (this.selected === 'logs') this.applyLogFilters();
        if (focusContentHeading) this.$refs?.sectionHeading?.focus?.();
        browser.highlight?.();
      });
    },

    navigateToSection(section, filter = null) {
      const target = this.sectionKeys.includes(section) ? section : 'overview';

      this.selectSection(target, filter, true);
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

    syncSectionHeading() {
      if (this.$refs?.sectionHeading) {
        this.$refs.sectionHeading.textContent = this.selectedSection.label;
      }
      if (this.$refs?.sectionDescription) {
        this.$refs.sectionDescription.textContent = this.selectedSection.description ?? '';
      }
    },

    syncHostLock() {
      if (this.barVisible && (this.inspectorOpen || this.paletteOpen)) browser.lockHost?.(this.$root);
      else browser.unlockHost?.(this.$root);
    },

    syncToolbarPlacement() {
      const placement = browser.toolbarPlacement?.(this.$root);

      if (['top', 'bottom'].includes(placement)) this.toolbarPlacement = placement;
    },

    openInspector(section = this.selected, returnFocus = null) {
      if (!this.barVisible) return;

      if (!this.inspectorOpen) {
        this.inspectorReturnFocus = returnFocus
          ?? (this.mobileToolbarMenu ? this.mobileToolbarReturnFocus : null)
          ?? browser.activeElement?.();
      }

      this.mobileToolbarMenu = null;
      this.mobileToolbarReturnFocus = null;
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.selectSection(section);
      this.inspectorOpen = true;
      this.syncHostLock();
      this.$nextTick?.(() => {
        const focus = () => this.$root
          ?.querySelector?.('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
          ?.focus?.();
        browser.afterPaint ? browser.afterPaint(focus) : focus();
      });

      if (!this.detailsRequested) {
        this.requestDetails();
      }
    },

    requestDetails() {
      if (this.detailsRequested) return;

      const profileId = this.summary.id;
      const requestVersion = ++this.detailRequestVersion;
      this.detailsRequested = true;
      this.detailsError = false;

      Promise.resolve(this.$wire?.loadDetails())
        .then(() => {
          if (requestVersion !== this.detailRequestVersion || profileId !== this.summary.id) return;

          this.$nextTick?.(() => {
            this.syncSectionPanels();
            this.applyQueryView();
            this.applyViewSort();
            this.applyAuthorizationFilters();
            this.applyTimelineFilters();
            this.applyEventFilters();
            this.applyLogFilters();
            this.syncHostLock();
            browser.highlight?.();
          });
        })
        .catch(() => {
          if (requestVersion !== this.detailRequestVersion || profileId !== this.summary.id) return;

          this.detailsRequested = false;
          this.detailsError = true;
        });
    },

    closeInspector() {
      if (!this.inspectorOpen) return;

      const returnFocus = this.inspectorReturnFocus;
      this.inspectorOpen = false;
      this.inspectorReturnFocus = null;
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.mobileToolbarMenu = null;
      this.mobileToolbarReturnFocus = null;
      this.syncHostLock();
      this.$nextTick?.(() => {
        const focus = () => returnFocus?.focus?.();
        browser.afterPaint ? browser.afterPaint(focus) : focus();
      });
    },

    dismissBar() {
      if (!this.barVisible) return;

      const activeElement = browser.activeElement?.();
      this.barVisible = false;
      this.inspectorOpen = false;
      this.inspectorReturnFocus = null;
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.mobileToolbarMenu = null;
      this.mobileToolbarReturnFocus = null;
      this.paletteOpen = false;
      this.paletteSearch = '';
      this.paletteIndex = 0;
      this.paletteShowQuiet = false;
      this.paletteReturnFocus = null;
      this.syncHostLock();
      this.$nextTick?.(() => {
        const blur = () => activeElement?.blur?.();
        browser.afterPaint ? browser.afterPaint(blur) : blur();
      });
    },

    switchProfile(summary) {
      this.detailRequestVersion++;
      this.summary = summary ?? {};
      this.detailsRequested = false;
      this.detailsError = false;
      this.selected = 'overview';
      this.queryFilter = 'all';
      this.querySearch = '';
      this.querySort = 'execution';
      this.viewSort = 'name';
      this.viewSortDirection = 'asc';
      this.authorizationFilter = 'all';
      this.eventSource = 'application';
      this.eventSearch = '';
      if (this.inspectorOpen) {
        this.openInspector('overview');
      } else {
        this.$nextTick?.(() => this.syncSectionPanels());
      }
    },

    noticeProfile(profileId) {
      if (!/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(profileId ?? '')) return;
      if (profileId === this.summary.id) return;

      Promise.resolve(this.$wire?.switchProfile(profileId)).catch(() => {});
    },

    copyText(value) {
      try {
        Promise.resolve(browser.writeClipboard?.(value)).catch(() => {});
      } catch {
        // Clipboard policies must never break the host page.
      }
    },

    setQueryFilter(filter) {
      if (!['all', 'attention', 'read', 'write'].includes(filter)) return;

      this.queryFilter = filter;
      this.applyQueryView();
    },

    setQuerySort(sort) {
      if (!['execution', 'duration'].includes(sort)) return;

      this.querySort = sort;
      this.applyQueryView();
    },

    applyQueryView() {
      const results = this.$refs?.queryResults;
      const search = this.querySearch.toLowerCase().trim();
      let visible = 0;

      if (results?.children) {
        [...results.children]
          .sort((left, right) => this.compareQueries(left, right))
          .forEach((result) => {
            const isGroup = result.dataset.queryKind === 'group';
            const isRepeatedItem = result.dataset.queryKind === 'item'
              && result.dataset.repeated === 'true';
            const matchesFilter = this.queryFilter === 'all'
              || (this.queryFilter === 'attention' && (isGroup || result.dataset.slow === 'true'))
              || (this.queryFilter === 'read' && result.dataset.type === 'read')
              || (this.queryFilter === 'write' && result.dataset.type === 'write');
            const matchesSearch = search === '' || result.dataset.search?.includes(search);
            result.hidden = isRepeatedItem || !matchesFilter || !matchesSearch;
            if (!result.hidden) visible += Number(result.dataset.resultCount ?? 1);
            results.appendChild?.(result);

            if (isGroup) {
              const executions = result.querySelector?.('[data-ndb-query-group-executions]');

              if (executions?.children) {
                [...executions.children]
                  .sort((left, right) => this.compareQueries(left, right))
                  .forEach((execution) => executions.appendChild?.(execution));
              }
            }
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

    toggleViewSort(sort) {
      if (!['name', 'count'].includes(sort)) return;

      if (this.viewSort === sort) {
        this.viewSortDirection = this.viewSortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.viewSort = sort;
        this.viewSortDirection = sort === 'count' ? 'desc' : 'asc';
      }

      this.applyViewSort();
    },

    applyViewSort() {
      const groups = this.$refs?.viewGroups
        ?? this.$root?.querySelector?.('[x-ref="viewGroups"]');

      if (!groups?.children) return;

      [...groups.children]
        .sort((left, right) => {
          const direction = this.viewSortDirection === 'asc' ? 1 : -1;

          if (this.viewSort === 'count') {
            return (Number(left.dataset.count ?? 0) - Number(right.dataset.count ?? 0)) * direction
              || Number(left.dataset.order ?? 0) - Number(right.dataset.order ?? 0);
          }

          return String(left.dataset.name ?? '').localeCompare(
            String(right.dataset.name ?? ''),
            undefined,
            { numeric: true, sensitivity: 'base' },
          ) * direction || Number(left.dataset.order ?? 0) - Number(right.dataset.order ?? 0);
        })
        .forEach((group) => groups.appendChild?.(group));
    },

    setAuthorizationFilter(filter) {
      if (!['all', 'allowed', 'denied'].includes(filter)) return;

      this.authorizationFilter = filter;
      this.applyAuthorizationFilters();
    },

    applyAuthorizationFilters() {
      const list = this.$refs?.authorizationItems
        ?? this.$root?.querySelector?.('[x-ref="authorizationItems"]');

      if (!list?.children) {
        this.visibleAuthorizationCount = 0;

        return;
      }

      let visible = 0;

      [...list.children].forEach((item) => {
        const matches = this.authorizationFilter === 'all'
          || item.dataset.result === this.authorizationFilter;
        item.hidden = !matches;
        if (matches) visible++;
      });

      this.visibleAuthorizationCount = visible;
    },

    setTimelineFilter(filter) {
      if (!this.sectionKeys.includes(filter) && !['all', 'key'].includes(filter)) return;

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
        const matches = (this.timelineFilter === 'all'
          || (this.timelineFilter === 'key' && item.dataset.key === 'true')
          || item.dataset.section === this.timelineFilter)
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

    toggleMobileToolbarMenu(menu, returnFocus = null) {
      if (this.mobileToolbarMenu === menu) {
        this.closeMobileToolbarMenu();

        return;
      }

      this.openMobileToolbarMenu(menu, returnFocus);
    },

    openMobileToolbarMenu(menu, returnFocus = null) {
      const compactMenu = menu === 'actions';
      const inspectorMenu = menu === 'header-actions';

      if (!this.barVisible
        || (!compactMenu && !inspectorMenu)
        || (compactMenu && this.inspectorOpen)
        || (inspectorMenu && !this.inspectorOpen)) return;

      this.mobileToolbarMenu = menu;
      this.mobileToolbarReturnFocus = returnFocus ?? browser.activeElement?.();
      this.$nextTick?.(() => {
        const focus = () => this.$root
          ?.querySelector?.(`[data-ndb-mobile-toolbar-menu="${menu}"] [role="menuitem"]`)
          ?.focus?.();
        browser.afterPaint ? browser.afterPaint(focus) : focus();
      });
    },

    openMobileSectionsFromToolbar() {
      const returnFocus = this.mobileToolbarReturnFocus;
      this.closeMobileToolbarMenu(false);
      this.openMobileSections(returnFocus);
    },

    closeMobileToolbarMenu(restoreFocus = true) {
      if (!this.mobileToolbarMenu) return;

      const returnFocus = this.mobileToolbarReturnFocus;
      this.mobileToolbarMenu = null;
      this.mobileToolbarReturnFocus = null;

      if (restoreFocus) this.$nextTick?.(() => {
        const focus = () => returnFocus?.focus?.();
        browser.afterPaint ? browser.afterPaint(focus) : focus();
      });
    },

    openPalette() {
      if (!this.barVisible) return;

      this.paletteReturnFocus = this.mobileToolbarMenu
        ? this.mobileToolbarReturnFocus
        : (this.mobileSectionsOpen ? this.mobileSectionsReturnFocus : browser.activeElement?.());
      this.mobileToolbarMenu = null;
      this.mobileToolbarReturnFocus = null;
      this.mobileSectionsOpen = false;
      this.mobileSectionsReturnFocus = null;
      this.paletteOpen = true;
      this.syncHostLock();
      this.paletteSearch = '';
      this.paletteIndex = 0;
      this.paletteShowQuiet = false;
      this.$nextTick?.(() => {
        const focus = () => this.$refs?.paletteSearch?.focus();
        browser.afterPaint ? browser.afterPaint(focus) : focus();
      });
    },

    closePalette(restoreFocus = true) {
      const returnFocus = this.paletteReturnFocus;
      this.paletteOpen = false;
      this.paletteSearch = '';
      this.paletteIndex = 0;
      this.paletteShowQuiet = false;
      this.paletteReturnFocus = null;
      this.syncHostLock();

      if (restoreFocus) this.$nextTick?.(() => {
        const focus = () => returnFocus?.focus?.();
        browser.afterPaint ? browser.afterPaint(focus) : focus();
      });
    },

    movePalette(direction) {
      const count = this.filteredCommands.length;
      if (count === 0) return;

      this.paletteIndex = (this.paletteIndex + direction + count) % count;
    },

    commandIndex(id) {
      return this.filteredCommands.findIndex((command) => command.id === id);
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

      if (kind === 'collectors' && value === 'show') {
        this.paletteShowQuiet = true;
        this.paletteIndex = 0;

        return;
      }

      this.closePalette();
    },

    handleShortcut(event) {
      if (!this.barVisible) return;

      if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key.toLowerCase() === 'p') {
        event.preventDefault();
        this.togglePalette();
      }

      if (event.key === 'Escape') {
        if (this.paletteOpen) this.closePalette();
        else if (this.mobileToolbarMenu) this.closeMobileToolbarMenu();
        else if (this.mobileSectionsOpen) this.closeMobileSections();
        else if (this.inspectorOpen) this.closeInspector();
      }
    },
  };
}

export { STORAGE_KEY };
