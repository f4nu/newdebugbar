const PROFILE_HEADER = 'X-New-Debug-Bar-Profile';
const PROFILE_EVENT = 'new-debug-bar-profile-discovered';
const PROFILE_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const XHR_URL = Symbol('newDebugBarUrl');
const XHR_LIVEWIRE = Symbol('newDebugBarLivewire');
const XHR_INERTIA = Symbol('newDebugBarInertia');
const XHR_INERTIA_PARTIAL = Symbol('newDebugBarInertiaPartial');

const header = (headers, name) => {
  if (!headers) return null;
  if (typeof headers.get === 'function') return headers.get(name);

  if (Array.isArray(headers)) {
    return headers.find(([key]) => String(key).toLowerCase() === name.toLowerCase())?.[1] ?? null;
  }

  const key = Object.keys(headers).find((candidate) => candidate.toLowerCase() === name.toLowerCase());
  return key ? headers[key] : null;
};

const requestFacts = (runtime, input, init = {}) => {
  try {
    const rawUrl = typeof input === 'string' || input instanceof URL ? String(input) : input?.url;
    const url = new URL(rawUrl, runtime.location.href);
    const livewire = header(init?.headers, 'X-Livewire')
      ?? header(input?.headers, 'X-Livewire')
      ?? header(init?.headers, 'X-Livewire-Navigate')
      ?? header(input?.headers, 'X-Livewire-Navigate');
    const inertia = header(init?.headers, 'X-Inertia')
      ?? header(input?.headers, 'X-Inertia');
    const inertiaPartial = header(init?.headers, 'X-Inertia-Partial-Component')
      ?? header(input?.headers, 'X-Inertia-Partial-Component');

    return {
      eligible: url.origin === runtime.location.origin
        && !url.pathname.startsWith('/__new-debug-bar/')
        && !url.pathname.includes('/livewire-')
        && !url.pathname.includes('/livewire/'),
      livewire: livewire !== null,
      foreground: inertia !== null && inertiaPartial === null,
      purpose: inertia !== null ? (inertiaPartial !== null ? 'inertia_partial' : 'inertia_visit') : 'background',
      url,
    };
  } catch {
    return { eligible: false, livewire: false, foreground: false, purpose: 'background', url: null };
  }
};

const notify = (runtime, response, transport, facts) => {
  try {
    if (!facts.eligible || facts.livewire) return;

    const responseUrl = response?.url ? new URL(response.url, runtime.location.href) : facts.url;
    if (!responseUrl || responseUrl.origin !== runtime.location.origin) return;

    const profileId = response.headers?.get?.(PROFILE_HEADER);
    if (!PROFILE_PATTERN.test(profileId ?? '')) return;

    runtime.dispatchEvent(new runtime.CustomEvent(PROFILE_EVENT, {
      detail: {
        profileId,
        transport,
        foreground: facts.foreground,
        purpose: facts.purpose,
      },
    }));
  } catch {
    // Request discovery must never change host request behavior.
  }
};

export function installProfileDiscoveryBridge(runtime = window) {
  if (runtime.__newDebugBarProfileDiscoveryBridge) return;
  runtime.__newDebugBarProfileDiscoveryBridge = true;

  runtime.addEventListener?.(PROFILE_EVENT, (event) => {
    try {
      const profileId = event.detail?.profileId;
      if (!PROFILE_PATTERN.test(profileId ?? '')) return;

      const root = runtime.document?.getElementById?.('new-debug-bar');
      const state = root ? runtime.Alpine?.$data?.(root) : null;

      if (typeof state?.noticeProfile === 'function') {
        state.noticeProfile(profileId, event.detail);
        return;
      }

      const toolbar = runtime.Livewire?.getByName?.('new-debug-bar.toolbar')?.[0];
      const action = event.detail?.foreground ? toolbar?.switchProfile : toolbar?.discoverProfile;
      Promise.resolve(action?.call?.(toolbar, profileId)).catch(() => {});
    } catch {
      // A stale or unavailable toolbar must never affect the host request.
    }
  });
}

export function installRequestDiscovery(runtime = window) {
  if (runtime.__newDebugBarRequestDiscovery) return;
  runtime.__newDebugBarRequestDiscovery = true;

  if (typeof runtime.fetch === 'function') {
    const originalFetch = runtime.fetch;
    runtime.fetch = function newDebugBarFetch(input, init) {
      const facts = requestFacts(runtime, input, init);

      return originalFetch.apply(this, arguments).then((response) => {
        notify(runtime, response, 'fetch', facts);
        return response;
      });
    };
  }

  const prototype = runtime.XMLHttpRequest?.prototype;
  if (!prototype?.open || !prototype?.send || !prototype?.setRequestHeader) return;

  const originalOpen = prototype.open;
  const originalSend = prototype.send;
  const originalSetRequestHeader = prototype.setRequestHeader;

  prototype.open = function newDebugBarOpen(_method, url) {
    this[XHR_URL] = url;
    this[XHR_LIVEWIRE] = false;
    this[XHR_INERTIA] = false;
    this[XHR_INERTIA_PARTIAL] = false;
    return originalOpen.apply(this, arguments);
  };

  prototype.setRequestHeader = function newDebugBarSetRequestHeader(name) {
    const normalized = String(name).toLowerCase();
    if (['x-livewire', 'x-livewire-navigate'].includes(normalized)) this[XHR_LIVEWIRE] = true;
    if (normalized === 'x-inertia') this[XHR_INERTIA] = true;
    if (normalized === 'x-inertia-partial-component') this[XHR_INERTIA_PARTIAL] = true;
    return originalSetRequestHeader.apply(this, arguments);
  };

  prototype.send = function newDebugBarSend() {
    const facts = requestFacts(runtime, this[XHR_URL]);
    facts.livewire = this[XHR_LIVEWIRE];
    facts.foreground = this[XHR_INERTIA] && !this[XHR_INERTIA_PARTIAL];
    facts.purpose = this[XHR_INERTIA_PARTIAL] ? 'inertia_partial' : (this[XHR_INERTIA] ? 'inertia_visit' : 'background');
    this.addEventListener?.('loadend', () => notify(runtime, {
      url: this.responseURL,
      headers: { get: (name) => this.getResponseHeader?.(name) },
    }, 'xhr', facts), { once: true });

    return originalSend.apply(this, arguments);
  };
}
