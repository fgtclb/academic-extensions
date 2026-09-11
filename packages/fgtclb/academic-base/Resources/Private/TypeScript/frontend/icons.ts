/**
 * The markup of registered icons for frontend code that picks an icon by its
 * identifier at runtime.
 *
 * Modelled on the backend icon API of TYPO3 (`@typo3/backend/icons.js`), which
 * cannot be used on a frontend page: it asks a backend AJAX route that answers
 * a logged-in backend user only. The markup here comes from the server in two
 * ways, both rendered by `FrontendIconRenderer` of EXT:academic_base:
 *
 * - the JSON data blocks `<ab:frontendIconMap>` renders into the page,
 *   `<script type="application/json" data-academic-icons>`, which are read
 *   without a request;
 * - the icon endpoint `<site base>/_academic/icons.json`, asked for whatever
 *   the page did not carry.
 *
 * What differs from the backend API, on purpose:
 *
 * - The calls of one microtask are sent as one request per size, of at most 32
 *   identifiers each, instead of one request per icon.
 * - A promise per icon and size is kept for the lifetime of the page and shared
 *   by every factory on it, so an icon is asked for once.
 * - Nothing is written to `localStorage`. The endpoint answers the current
 *   version token as immutable, so the browser cache holds the answer, and web
 *   storage would be one more place a script injected into the page could
 *   plant markup for later pages.
 * - No overlay, no state, no alternative markup: the markup is always the
 *   `inline` one, `<core:icon ... alternativeMarkupIdentifier="inline" />`.
 * - An identifier that is not one is rejected without a request, and a
 *   request is sent without cookies and given up after ten seconds.
 *
 * @internal Experimental until a module outside the academic extensions uses
 *   it; every export may change without a breaking change entry.
 */

/** @internal */
export const Sizes = {
  default: 'default',
  small: 'small',
  medium: 'medium',
  large: 'large',
  mega: 'mega',
} as const;

/** @internal */
export type Size = (typeof Sizes)[keyof typeof Sizes];

/**
 * The identifiers the endpoint accepts: `FrontendIconRenderer::IDENTIFIER_PATTERN`
 * of EXT:academic_base, `/\A[a-z0-9_][a-z0-9_.-]{0,99}\z/` in PHP. Without the
 * `m` flag `$` matches at the very end of the input only, so `^…$` is the same
 * pattern here. Kept in sync by hand; `Tests/JavaScript/icons.test.ts` reads the
 * PHP constant and fails when the two differ.
 *
 * @internal
 */
export const IDENTIFIER_PATTERN = /^[a-z0-9_][a-z0-9_.-]{0,99}$/;

/** @internal */
export interface IconEndpoint {
  /** The endpoint of the site language, absolute or relative to the document. */
  url: string;
  /** The version token of the icon set the page was rendered with. */
  version: string;
}

/**
 * What the endpoint takes in one request, `FrontendIconEndpoint::MAX_IDENTIFIERS`;
 * held against it by the tests as well.
 */
const MAX_IDENTIFIERS_PER_REQUEST = 32;

/** How long a request may take before its icons are given up and asked for again. */
const REQUEST_TIMEOUT_MILLISECONDS = 10_000;

const MAP_SELECTOR = 'script[type="application/json"][data-academic-icons]';

const isSize = (value: string): value is Size => Object.values<string>(Sizes).includes(value);

/** The key of an icon in every map below. */
const keyOf = (identifier: string, size: Size): string => `${size}|${identifier}`;

/** One promise per icon and size, for the lifetime of the page. */
const icons = new Map<string, Promise<string>>();

/** The JSON data blocks already read into `icons`. */
const seededElements = new WeakSet<Element>();

interface PendingIcon {
  identifier: string;
  resolve: (markup: string) => void;
  reject: (reason: Error) => void;
}

/** The icons asked for in the current microtask, by endpoint and size. */
const pending = new Map<string, { endpoint: IconEndpoint; size: Size; icons: PendingIcon[] }>();

/**
 * Reads every JSON icon map in the document that has not been read yet. Called
 * on a cache miss rather than once, so a map that arrives later - with markup
 * loaded into the page - is found as well.
 */
const seedFromDocument = (): void => {
  for (const element of document.querySelectorAll(MAP_SELECTOR)) {
    if (seededElements.has(element)) {
      continue;
    }
    seededElements.add(element);
    const size = element.getAttribute('data-academic-icons-size') ?? Sizes.small;
    if (!isSize(size)) {
      continue;
    }
    let map: unknown;
    try {
      map = JSON.parse(element.textContent ?? '');
    } catch {
      continue;
    }
    if (typeof map !== 'object' || map === null) {
      continue;
    }
    for (const [identifier, markup] of Object.entries(map)) {
      const key = keyOf(identifier, size);
      if (typeof markup === 'string' && !icons.has(key)) {
        icons.set(key, Promise.resolve(markup));
      }
    }
  }
};

const settle = (batch: PendingIcon[], answer: Record<string, unknown>, size: Size): void => {
  for (const icon of batch) {
    const markup = answer[icon.identifier];
    if (typeof markup === 'string') {
      icon.resolve(markup);
      continue;
    }
    // Refused by the server - not registered, not allowed, deprecated. That
    // stays true for the page, so the rejection stays cached.
    icon.reject(new Error(`The icon "${icon.identifier}" (${size}) is not available.`));
  }
};

const fail = (batch: PendingIcon[], size: Size, reason: string): void => {
  for (const icon of batch) {
    // A failed request may succeed later, so it is not cached.
    icons.delete(keyOf(icon.identifier, size));
    icon.reject(new Error(`The icon "${icon.identifier}" (${size}) could not be loaded: ${reason}`));
  }
};

const request = async (endpoint: IconEndpoint, size: Size, batch: PendingIcon[]): Promise<void> => {
  const url = new URL(endpoint.url, document.baseURI);
  url.searchParams.set('i', batch.map((icon) => icon.identifier).join(','));
  url.searchParams.set('s', size);
  if (endpoint.version !== '') {
    url.searchParams.set('v', endpoint.version);
  }
  // Without a timeout a request that hangs - behind a stalled proxy, say - keeps
  // its promises pending, and cached, for the life of the page. An aborted one
  // fails like any other and is asked for again on the next call.
  const controller = new AbortController();
  const timeout = setTimeout(
    () => controller.abort(new Error(`no answer within ${REQUEST_TIMEOUT_MILLISECONDS / 1000} seconds`)),
    REQUEST_TIMEOUT_MILLISECONDS,
  );
  try {
    const response = await fetch(url.toString(), {
      headers: { Accept: 'application/json' },
      // The answer does not depend on a session, and a request carrying cookies
      // is passed to the backend by many proxies and CDNs instead of being
      // answered from their cache.
      credentials: 'omit',
      signal: controller.signal,
    });
    if (!response.ok) {
      fail(batch, size, `HTTP ${response.status}`);
      return;
    }
    const answer: unknown = await response.json();
    if (typeof answer !== 'object' || answer === null) {
      fail(batch, size, 'the answer is not an object');
      return;
    }
    settle(batch, answer as Record<string, unknown>, size);
  } catch (error) {
    fail(batch, size, error instanceof Error ? error.message : String(error));
  } finally {
    clearTimeout(timeout);
  }
};

const flush = (): void => {
  const groups = [...pending.values()];
  pending.clear();
  for (const { endpoint, size, icons: batch } of groups) {
    for (let offset = 0; offset < batch.length; offset += MAX_IDENTIFIERS_PER_REQUEST) {
      void request(endpoint, size, batch.slice(offset, offset + MAX_IDENTIFIERS_PER_REQUEST));
    }
  }
};

const enqueue = (endpoint: IconEndpoint, identifier: string, size: Size): Promise<string> => {
  const promise = new Promise<string>((resolve, reject) => {
    const groupKey = `${endpoint.url}|${endpoint.version}|${size}`;
    let group = pending.get(groupKey);
    if (group === undefined) {
      if (pending.size === 0) {
        queueMicrotask(flush);
      }
      group = { endpoint, size, icons: [] };
      pending.set(groupKey, group);
    }
    group.icons.push({ identifier, resolve, reject });
  });
  // The cached promise is handed to every caller, and a caller may not be
  // there yet when it rejects.
  promise.catch(() => undefined);

  return promise;
};

/**
 * The endpoint an element names: its own `data-academic-icons-url` and
 * `data-academic-icons-version`, or those of the first JSON icon map inside it
 * that carries them - which is what `<ab:frontendIconMap endpoint="1">`
 * renders. `null` where there is none.
 *
 * @internal
 */
export const endpointFrom = (root: Element): IconEndpoint | null => {
  const element = root.hasAttribute('data-academic-icons-url')
    ? root
    : root.querySelector(`${MAP_SELECTOR}[data-academic-icons-url]`);
  const url = element?.getAttribute('data-academic-icons-url') ?? '';
  if (url === '') {
    return null;
  }

  return { url, version: element?.getAttribute('data-academic-icons-version') ?? '' };
};

/**
 * Answers icons by identifier, from the JSON icon maps of the page and, for
 * the rest, from the endpoint it was given.
 *
 * @internal
 */
export class IconFactory {
  private readonly endpoint: IconEndpoint | null;

  /**
   * @param endpoint Where to ask for an icon the page does not carry. Without
   *   one the factory answers from the JSON icon maps of the page only.
   */
  constructor(endpoint: IconEndpoint | null = null) {
    this.endpoint = endpoint;
  }

  /**
   * The markup of an icon, the `<span class="t3js-icon ...">` wrapper included.
   * Rejects when the server does not serve the identifier, when the request
   * fails, and when the page carries neither the icon nor an endpoint.
   *
   * An identifier the server would refuse as malformed rejects at once and is
   * never sent: the endpoint answers a request with one malformed entry with
   * `400` as a whole, which would fail every icon batched with it.
   */
  getIcon(identifier: string, size: Size = Sizes.small): Promise<string> {
    if (!IDENTIFIER_PATTERN.test(identifier)) {
      return Promise.reject(new Error(`"${identifier}" is not an icon identifier.`));
    }
    const key = keyOf(identifier, size);
    let icon = icons.get(key);
    if (icon === undefined) {
      seedFromDocument();
      icon = icons.get(key);
    }
    if (icon !== undefined) {
      return icon;
    }
    if (this.endpoint === null) {
      return Promise.reject(new Error(`The icon "${identifier}" (${size}) is not on the page, and there is no endpoint to ask.`));
    }
    icon = enqueue(this.endpoint, identifier, size);
    icons.set(key, icon);

    return icon;
  }

  /**
   * Asks for several icons at once, so that a later `getIcon()` is answered
   * without a request. Resolves once every one of them is settled, whether it
   * could be loaded or not.
   */
  async prefetch(identifiers: readonly string[], size: Size = Sizes.small): Promise<void> {
    await Promise.allSettled(identifiers.map((identifier) => this.getIcon(identifier, size)));
  }

  /**
   * The icon as a new element, ready to be inserted - a fresh one on every
   * call, so the same icon can be placed more than once.
   */
  async getIconElement(identifier: string, size: Size = Sizes.small): Promise<Element> {
    const template = document.createElement('template');
    template.innerHTML = (await this.getIcon(identifier, size)).trim();
    const element = template.content.firstElementChild;
    if (element === null) {
      throw new Error(`The icon "${identifier}" (${size}) has no markup.`);
    }

    return element;
  }
}
