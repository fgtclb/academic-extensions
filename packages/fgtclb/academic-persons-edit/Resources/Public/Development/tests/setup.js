import { afterEach } from "@jest/globals";

if (!globalThis.CSS) {
  globalThis.CSS = {};
}
if (typeof globalThis.CSS.escape !== "function") {
  globalThis.CSS.escape = (value) =>
    String(value).replace(/[^a-zA-Z0-9_-]/g, (character) =>
      `\\${character.codePointAt(0).toString(16)} `,
    );
}

afterEach(() => {
  document.body.replaceChildren();
  delete globalThis.bootstrap;
  delete globalThis.fetch;
  delete globalThis.ResizeObserver;
  delete window.CKEDITOR;
});
