const cleanIds = (ids) => Array.isArray(ids) ? ids.filter((id) => typeof id === 'string' && id !== '') : [];

export function createLivewireSection(options = {}) {
  const componentIds = cleanIds(options.componentIds);
  const eventIds = cleanIds(options.eventIds);

  return {
    livewireTab: 'overview',
    componentIds,
    eventIds,
    selectedComponentId: componentIds[0] ?? null,
    selectedEventId: eventIds[0] ?? null,

    selectLivewireTab(tab) {
      if (['overview', 'components', 'events'].includes(tab)) {
        this.livewireTab = tab;
      }
    },

    selectLivewireItem(type, id) {
      const ids = type === 'component' ? this.componentIds : type === 'event' ? this.eventIds : [];
      if (!ids.includes(id)) return;

      if (type === 'component') this.selectedComponentId = id;
      if (type === 'event') this.selectedEventId = id;
    },

    handleLivewireTabKey(event) {
      this.moveSelection(event, '[role="tab"]', (target) => {
        this.selectLivewireTab(target.dataset.ndbLivewireTab);
      }, true);
    },

    handleLivewireItemKey(event, type) {
      this.moveSelection(event, '[role="option"]', (target) => {
        this.selectLivewireItem(type, target.dataset.ndbLivewireChoice);
      }, false);
    },

    moveSelection(event, selector, select, horizontal) {
      const container = event.currentTarget?.closest?.('[role="tablist"], [role="listbox"]')
        ?? event.currentTarget?.parentElement;
      const items = [...(container?.querySelectorAll?.(selector) ?? [])];
      const current = items.indexOf(event.currentTarget);
      if (current < 0 || items.length === 0) return;

      const nextKey = horizontal ? 'ArrowRight' : 'ArrowDown';
      const previousKey = horizontal ? 'ArrowLeft' : 'ArrowUp';
      const target = event.key === 'Home'
        ? items[0]
        : event.key === 'End'
          ? items.at(-1)
          : event.key === nextKey
            ? items[(current + 1) % items.length]
            : event.key === previousKey
              ? items[(current - 1 + items.length) % items.length]
              : null;

      if (!target) return;
      event.preventDefault?.();
      select(target);
      target.focus?.();
    },
  };
}
