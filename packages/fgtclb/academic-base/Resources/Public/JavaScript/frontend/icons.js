/* Generated from Resources/Private/TypeScript — do not edit. */
const Sizes = {
  default: "default",
  small: "small",
  medium: "medium",
  large: "large",
  mega: "mega"
};
const IDENTIFIER_PATTERN = /^[a-z0-9_][a-z0-9_.-]{0,99}$/;
const MAX_IDENTIFIERS_PER_REQUEST = 32;
const REQUEST_TIMEOUT_MILLISECONDS = 1e4;
const MAP_SELECTOR = 'script[type="application/json"][data-academic-icons]';
const isSize = (value) => Object.values(Sizes).includes(value);
const keyOf = (identifier, size) => `${size}|${identifier}`;
const icons = /* @__PURE__ */ new Map();
const seededElements = /* @__PURE__ */ new WeakSet();
const pending = /* @__PURE__ */ new Map();
const seedFromDocument = () => {
  for (const element of document.querySelectorAll(MAP_SELECTOR)) {
    if (seededElements.has(element)) {
      continue;
    }
    seededElements.add(element);
    const size = element.getAttribute("data-academic-icons-size") ?? Sizes.small;
    if (!isSize(size)) {
      continue;
    }
    let map;
    try {
      map = JSON.parse(element.textContent ?? "");
    } catch {
      continue;
    }
    if (typeof map !== "object" || map === null) {
      continue;
    }
    for (const [identifier, markup] of Object.entries(map)) {
      const key = keyOf(identifier, size);
      if (typeof markup === "string" && !icons.has(key)) {
        icons.set(key, Promise.resolve(markup));
      }
    }
  }
};
const settle = (batch, answer, size) => {
  for (const icon of batch) {
    const markup = answer[icon.identifier];
    if (typeof markup === "string") {
      icon.resolve(markup);
      continue;
    }
    icon.reject(new Error(`The icon "${icon.identifier}" (${size}) is not available.`));
  }
};
const fail = (batch, size, reason) => {
  for (const icon of batch) {
    icons.delete(keyOf(icon.identifier, size));
    icon.reject(new Error(`The icon "${icon.identifier}" (${size}) could not be loaded: ${reason}`));
  }
};
const request = async (endpoint, size, batch) => {
  const url = new URL(endpoint.url, document.baseURI);
  url.searchParams.set("i", batch.map((icon) => icon.identifier).join(","));
  url.searchParams.set("s", size);
  if (endpoint.version !== "") {
    url.searchParams.set("v", endpoint.version);
  }
  const controller = new AbortController();
  const timeout = setTimeout(
    () => controller.abort(new Error(`no answer within ${REQUEST_TIMEOUT_MILLISECONDS / 1e3} seconds`)),
    REQUEST_TIMEOUT_MILLISECONDS
  );
  try {
    const response = await fetch(url.toString(), {
      headers: { Accept: "application/json" },
      // The answer does not depend on a session, and a request carrying cookies
      // is passed to the backend by many proxies and CDNs instead of being
      // answered from their cache.
      credentials: "omit",
      signal: controller.signal
    });
    if (!response.ok) {
      fail(batch, size, `HTTP ${response.status}`);
      return;
    }
    const answer = await response.json();
    if (typeof answer !== "object" || answer === null) {
      fail(batch, size, "the answer is not an object");
      return;
    }
    settle(batch, answer, size);
  } catch (error) {
    fail(batch, size, error instanceof Error ? error.message : String(error));
  } finally {
    clearTimeout(timeout);
  }
};
const flush = () => {
  const groups = [...pending.values()];
  pending.clear();
  for (const { endpoint, size, icons: batch } of groups) {
    for (let offset = 0; offset < batch.length; offset += MAX_IDENTIFIERS_PER_REQUEST) {
      void request(endpoint, size, batch.slice(offset, offset + MAX_IDENTIFIERS_PER_REQUEST));
    }
  }
};
const enqueue = (endpoint, identifier, size) => {
  const promise = new Promise((resolve, reject) => {
    const groupKey = `${endpoint.url}|${endpoint.version}|${size}`;
    let group = pending.get(groupKey);
    if (group === void 0) {
      if (pending.size === 0) {
        queueMicrotask(flush);
      }
      group = { endpoint, size, icons: [] };
      pending.set(groupKey, group);
    }
    group.icons.push({ identifier, resolve, reject });
  });
  promise.catch(() => void 0);
  return promise;
};
const endpointFrom = (root) => {
  const element = root.hasAttribute("data-academic-icons-url") ? root : root.querySelector(`${MAP_SELECTOR}[data-academic-icons-url]`);
  const url = (element == null ? void 0 : element.getAttribute("data-academic-icons-url")) ?? "";
  if (url === "") {
    return null;
  }
  return { url, version: (element == null ? void 0 : element.getAttribute("data-academic-icons-version")) ?? "" };
};
class IconFactory {
  endpoint;
  /**
   * @param endpoint Where to ask for an icon the page does not carry. Without
   *   one the factory answers from the JSON icon maps of the page only.
   */
  constructor(endpoint = null) {
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
  getIcon(identifier, size = Sizes.small) {
    if (!IDENTIFIER_PATTERN.test(identifier)) {
      return Promise.reject(new Error(`"${identifier}" is not an icon identifier.`));
    }
    const key = keyOf(identifier, size);
    let icon = icons.get(key);
    if (icon === void 0) {
      seedFromDocument();
      icon = icons.get(key);
    }
    if (icon !== void 0) {
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
  async prefetch(identifiers, size = Sizes.small) {
    await Promise.allSettled(identifiers.map((identifier) => this.getIcon(identifier, size)));
  }
  /**
   * The icon as a new element, ready to be inserted - a fresh one on every
   * call, so the same icon can be placed more than once.
   */
  async getIconElement(identifier, size = Sizes.small) {
    const template = document.createElement("template");
    template.innerHTML = (await this.getIcon(identifier, size)).trim();
    const element = template.content.firstElementChild;
    if (element === null) {
      throw new Error(`The icon "${identifier}" (${size}) has no markup.`);
    }
    return element;
  }
}
export {
  IDENTIFIER_PATTERN,
  IconFactory,
  Sizes,
  endpointFrom
};
