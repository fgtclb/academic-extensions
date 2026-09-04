/**
 * The DOM the tests run against: one jsdom window, installed on "globalThis".
 *
 * ## One window per test file, never per test
 *
 * The shipped modules keep module level state — WeakMaps of live editors, a
 * request counter, and later a custom element registry, which cannot be
 * undefined once "customElements.define()" has run. A fresh window per test
 * would either lose that state or collide with it, and node's module cache
 * hands every test file in a process the same module instance anyway.
 *
 * "register.mjs" therefore calls "installDom()" once per process, before any
 * test file is loaded, and a test file only calls "resetBody()" per test.
 *
 * ## Only the globals a browser gives a module for free
 *
 * The sources are written for a browser and reach for "document",
 * "HTMLTextAreaElement" or "CSS.escape" without importing anything. Each name
 * below is one of those. The list is explicit rather than a wholesale copy of
 * the window, so that a source reaching for something new fails loudly here
 * instead of silently picking up a node global of the same name.
 *
 * See "docs/testing/javascript-tests.md".
 */
import { JSDOM } from 'jsdom';

const browserGlobals = [
    'CustomEvent',
    'DOMParser',
    'DocumentFragment',
    'Element',
    'Event',
    'FormData',
    'HTMLButtonElement',
    'HTMLElement',
    'HTMLFormElement',
    'HTMLImageElement',
    'HTMLInputElement',
    'HTMLSelectElement',
    'HTMLTextAreaElement',
    'Node',
    'cancelAnimationFrame',
    'getComputedStyle',
    'requestAnimationFrame',
];

// The three that are methods of the window rather than constructors, and lose
// their receiver when they are copied onto "globalThis".
const windowMethods = new Set(['cancelAnimationFrame', 'getComputedStyle', 'requestAnimationFrame']);

/**
 * "CSS.escape", which jsdom does not implement and the sources use ten times to
 * build an attribute selector from an element id.
 *
 * This is the algorithm of the CSSOM specification, transcribed rather than
 * approximated: an escape that is merely good enough for the ids this
 * repository happens to render would pass a test the browser fails.
 *
 * https://drafts.csswg.org/cssom/#the-css.escape()-method
 */
const escapeCssIdentifier = (value) => {
    const string = String(value);
    const firstCodeUnit = string.charCodeAt(0);

    if (string.length === 1 && firstCodeUnit === 0x002d) {
        return `\\${string}`;
    }

    let result = '';
    for (let index = 0; index < string.length; index += 1) {
        const codeUnit = string.charCodeAt(index);

        if (codeUnit === 0x0000) {
            result += '\ufffd';
        } else if (
            (codeUnit >= 0x0001 && codeUnit <= 0x001f)
            || codeUnit === 0x007f
            || (index === 0 && codeUnit >= 0x0030 && codeUnit <= 0x0039)
            || (index === 1 && codeUnit >= 0x0030 && codeUnit <= 0x0039 && firstCodeUnit === 0x002d)
        ) {
            result += `\\${codeUnit.toString(16)} `;
        } else if (
            codeUnit >= 0x0080
            || codeUnit === 0x002d
            || codeUnit === 0x005f
            || (codeUnit >= 0x0030 && codeUnit <= 0x0039)
            || (codeUnit >= 0x0041 && codeUnit <= 0x005a)
            || (codeUnit >= 0x0061 && codeUnit <= 0x007a)
        ) {
            result += string.charAt(index);
        } else {
            result += `\\${string.charAt(index)}`;
        }
    }

    return result;
};

let installed = null;

export const installDom = () => {
    if (installed !== null) {
        return installed;
    }

    // "pretendToBeVisual" is what gives the window requestAnimationFrame; the
    // url gives it an origin, which "credentials: same-origin" requests need.
    const dom = new JSDOM('<!doctype html><html lang="en"><body></body></html>', {
        pretendToBeVisual: true,
        url: 'https://example.test/profile',
    });

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    for (const name of browserGlobals) {
        const value = dom.window[name];
        if (value === undefined) {
            throw new Error(`jsdom does not provide "${name}". Model it here rather than in a test.`);
        }
        globalThis[name] = windowMethods.has(name) ? value.bind(dom.window) : value;
    }
    globalThis.CSS = { escape: escapeCssIdentifier };

    installed = dom;

    return dom;
};

/**
 * Replaces the document body and returns it. The one call every test starts
 * with, so that no test inherits the markup of the previous one.
 */
export const resetBody = (html = '') => {
    const dom = installDom();
    dom.window.document.body.innerHTML = html;

    return dom.window.document.body;
};

/**
 * Lets the pending microtasks run. The close paths chain promises rather than
 * timers — "await destroyRichTextEditors()" resolves through the editor's own
 * "destroy()" promise — and a test that asserts before those settle would be
 * asserting on an unfinished operation.
 */
export const settle = async (turns = 3) => {
    for (let turn = 0; turn < turns; turn += 1) {
        await Promise.resolve();
    }
};
