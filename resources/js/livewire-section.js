export function createLivewireSection() {
  return {
    livewireTab: 'overview',

    selectLivewireTab(tab) {
      if (['overview', 'components', 'timeline', 'events'].includes(tab)) {
        this.livewireTab = tab;
      }
    },

    handleLivewireTabKey(event) {
      const tabs = [...(event.currentTarget?.parentElement?.querySelectorAll?.('[role="tab"]') ?? [])];
      const current = tabs.indexOf(event.currentTarget);
      if (current < 0 || tabs.length === 0) return;

      const target = event.key === 'Home'
        ? tabs[0]
        : event.key === 'End'
          ? tabs.at(-1)
          : event.key === 'ArrowRight'
            ? tabs[(current + 1) % tabs.length]
            : event.key === 'ArrowLeft'
              ? tabs[(current - 1 + tabs.length) % tabs.length]
              : null;

      if (!target) return;
      event.preventDefault?.();
      this.selectLivewireTab(target.dataset.ndbLivewireTab);
      target.focus?.();
    },
  };
}
