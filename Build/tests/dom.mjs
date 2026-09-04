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
    'Blob',
    'CustomEvent',
    'DOMParser',
    'DocumentFragment',
    'Element',
    'Event',
    'File',
    'FormData',
    'HTMLButtonElement',
    'HTMLElement',
    'HTMLFieldSetElement',
    'HTMLFormElement',
    'HTMLImageElement',
    'HTMLInputElement',
    'HTMLLabelElement',
    'HTMLSelectElement',
    'HTMLSourceElement',
    'HTMLTemplateElement',
    'HTMLTextAreaElement',
    'Node',
    'addEventListener',
    'cancelAnimationFrame',
    'customElements',
    'dispatchEvent',
    'getComputedStyle',
    'removeEventListener',
    'requestAnimationFrame',
    'scrollTo',
];

// The ones that are methods of the window rather than constructors, and lose
// their receiver when they are copied onto "globalThis". The sources reach for
// them unqualified or as "globalThis.x" - the sticky image and the image editor
// register a "pagehide" listener that way, and both would fail on a bare node
// global object, which has no event target of its own.
const windowMethods = new Set([
    'addEventListener',
    'cancelAnimationFrame',
    'dispatchEvent',
    'getComputedStyle',
    'removeEventListener',
    'requestAnimationFrame',
    'scrollTo',
]);

/**
 * Window properties that are read as values rather than called. "scrollY" is a
 * live getter in a browser and a frozen zero here: jsdom does not lay anything
 * out, so nothing ever scrolls. A test that needs a different value assigns one
 * itself.
 */
const windowValues = ['scrollY'];

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

/**
 * "matchMedia", which jsdom does not implement at all and the image editor calls
 * on every open and close to decide between a smooth and an instant scroll.
 *
 * Modelled as "nothing matches", which is what a browser reports for
 * "(prefers-reduced-motion: reduce)" unless the visitor asked for it. A test
 * that needs the other answer replaces "globalThis.matchMedia" for its own
 * duration - the two branches differ in one argument to "scrollTo()".
 */
const matchMedia = (query) => ({
    matches: false,
    media: String(query),
    onchange: null,
    addEventListener: () => undefined,
    removeEventListener: () => undefined,
    addListener: () => undefined,
    removeListener: () => undefined,
    dispatchEvent: () => false,
});

/**
 * "scrollIntoView", which jsdom does not implement either - there is nothing to
 * scroll - and which the editors call whenever they open. Left undefined it is
 * a "TypeError" in the middle of the open path, and the editor would appear to
 * fail for a reason that has nothing to do with the code under test.
 *
 * Modelled rather than emptied out, so a test can assert that the element was
 * brought into view and with which alignment. It reports through the DOM, like
 * the stubs:
 *
 *   data-test-scrolled-into-view="<block>"   the value of the "block" option
 */
const installScrollIntoView = (window) => {
    window.Element.prototype.scrollIntoView = function scrollIntoView(options = {}) {
        this.setAttribute(
            'data-test-scrolled-into-view',
            typeof options === 'object' ? String(options.block ?? 'start') : 'start',
        );
    };
};

/**
 * Object URLs, which neither realm can provide on its own.
 *
 * The window's "URL" has no "createObjectURL" in jsdom, and node's - the one a
 * bare "URL" resolves to - refuses anything but a node "Blob", while jsdom's
 * "FormData" refuses anything but a jsdom one. A browser has one realm and no
 * such split, so the browser's behaviour is modelled here: an opaque url per
 * object, released when it is revoked.
 *
 * Keeping the register is the point. The image editor creates a preview url
 * for every file that is chosen and has to revoke each of them; a test asserts
 * that with "isObjectUrlAlive()", which nothing else can observe.
 */
const objectUrls = new Map();
let objectUrlSequence = 0;

const installObjectUrls = () => {
    globalThis.URL.createObjectURL = (object) => {
        objectUrlSequence += 1;
        const url = `blob:https://example.test/${objectUrlSequence}`;
        objectUrls.set(url, object);

        return url;
    };
    globalThis.URL.revokeObjectURL = (url) => {
        objectUrls.delete(url);
    };
};

/** Whether the object url is still registered, i.e. was not revoked. */
export const isObjectUrlAlive = (url) => objectUrls.has(url);

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
    for (const name of [...browserGlobals, ...windowValues]) {
        const value = dom.window[name];
        if (value === undefined) {
            throw new Error(`jsdom does not provide "${name}". Model it here rather than in a test.`);
        }
        globalThis[name] = windowMethods.has(name) ? value.bind(dom.window) : value;
    }
    globalThis.CSS = { escape: escapeCssIdentifier };
    globalThis.matchMedia = matchMedia;
    installScrollIntoView(dom.window);
    installObjectUrls();

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

/**
 * Waits for the next animation frame.
 *
 * "settle()" drains microtasks and never reaches a timer, which is exactly what
 * makes it useful - but the close of the document editor is reported a frame
 * after the transition ends, deliberately, so that the owner does not tear the
 * element out of the document from inside Lit's own update cycle. A test that
 * is about the close therefore has to wait for a frame rather than for a
 * microtask.
 */
export const nextFrame = () =>
    new Promise((resolve) => globalThis.requestAnimationFrame(() => resolve(undefined)));

/**
 * A drag event, which jsdom implements neither as "DragEvent" nor as
 * "DataTransfer" - and the document list is sorted by dragging.
 *
 * The event carries what the handlers of "documents.ts" read: the pointer
 * position they compare against the row's midpoint, and a data transfer that
 * records what was written to it. Everything else about a real drag - the
 * browser's own drag image, the sequence of dragenter/dragleave - is not
 * modelled, because no handler asks about it.
 */
export const createDragEvent = (type, { clientX = 0, clientY = 0 } = {}) => {
    const dataTransfer = {
        data: new Map(),
        dropEffect: 'none',
        effectAllowed: 'uninitialized',
        dragImage: null,
        setData(format, value) {
            this.data.set(format, String(value));
        },
        getData(format) {
            return this.data.get(format) ?? '';
        },
        setDragImage(element, x, y) {
            this.dragImage = { element, x, y };
        },
    };
    const event = new globalThis.Event(type, { bubbles: true, cancelable: true });
    Object.defineProperties(event, {
        clientX: { value: clientX },
        clientY: { value: clientY },
        dataTransfer: { value: dataTransfer },
    });

    return event;
};

/**
 * Gives an element a client size, for the same reason as "setBoundingRect()"
 * and with a different mechanism: "clientWidth" and "clientHeight" are getters
 * on the prototype rather than a method, so they are shadowed on the instance.
 *
 * The cropper measures the canvas it sits in through them and refuses to place
 * a selection in a box of zero, which is what jsdom reports for everything.
 */
export const setClientSize = (element, { width = 0, height = 0 }) => {
    Object.defineProperty(element, 'clientWidth', { value: width, configurable: true });
    Object.defineProperty(element, 'clientHeight', { value: height, configurable: true });
};

/**
 * Places a rectangle on an element, because jsdom lays nothing out and reports
 * zero for every "getBoundingClientRect()". The drop position and the scroll
 * target are arithmetic over those rectangles, so a test that is about either
 * has to say what the geometry is.
 */
export const setBoundingRect = (element, { top = 0, left = 0, width = 0, height = 0 }) => {
    element.getBoundingClientRect = () => ({
        top,
        left,
        width,
        height,
        right: left + width,
        bottom: top + height,
        x: left,
        y: top,
        toJSON: () => undefined,
    });
};
