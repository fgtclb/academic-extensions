/**
 * The DOM the tests run against: one jsdom window, installed on "globalThis".
 *
 * ## One window per test file, never per test
 *
 * A shipped module keeps module level state — the study plan holds a map of the
 * plans it has started — and node's module cache hands every test file in a
 * process the same module instance. A fresh window per test would leave that
 * state pointing at a document nobody sees.
 *
 * "register.mjs" therefore calls "installDom()" once per process, before any
 * test file is loaded, and a test file only calls "resetBody()" per test.
 *
 * ## Only the globals a browser gives a module for free
 *
 * The sources are written for a browser and reach for "document", "Element" or
 * "HTMLTextAreaElement" without importing anything. Each name below is one of
 * those, and the list is deliberately short: this branch ships four frontend
 * modules, and it holds what those four use. It is explicit rather than a
 * wholesale copy of the window, so that a source reaching for something new
 * fails loudly here instead of silently picking up a node global of the same
 * name.
 *
 * See "docs/testing/javascript-tests.md".
 */
import { JSDOM } from 'jsdom';

const browserGlobals = [
    'Element',
    'Event',
    'HTMLDialogElement',
    'HTMLElement',
    'HTMLTextAreaElement',
    'Node',
];

/**
 * The three methods of "<dialog>", which jsdom declares the element and the
 * reflected "open" property for and implements none of: "show()", "showModal()"
 * and "close()" are simply not functions, so a module that opens a dialog dies
 * with a "TypeError" rather than failing an assertion. The study plan opens one
 * per module.
 *
 * Modelled through the attribute jsdom already reflects, so "dialog.open" is
 * the truth here exactly as it is in a browser, together with the "close"
 * event, the return value and the focus the opening moves into the dialog — a
 * module that opens one and takes the focus somewhere else has to be
 * observable.
 *
 * The top layer, the backdrop and the escape key are not modelled: nothing here
 * lays anything out, and a test about them would be testing the model. Neither
 * is the focus a browser puts back where it was when the dialog closes — a
 * module that restores it itself keeps having to, which fails loudly here
 * rather than passing on a model that did the work for it. And "close" is
 * dispatched synchronously where a browser queues it as a task, so a listener
 * that relies on running after the current task is not observable here.
 *
 * Which of the two ways it was opened is reported through the DOM, because a
 * module that means "showModal()" and calls "show()" looks the same in every
 * other observable respect:
 *
 *   data-test-dialog="modal"   opened with showModal()
 *   data-test-dialog="open"    opened with show()
 */
const installDialog = (window) => {
    if (typeof window.HTMLDialogElement.prototype.showModal === 'function') {
        throw new Error('jsdom implements "<dialog>" now. Drop this model and use it.');
    }

    const open = (dialog, modality) => {
        if (dialog.hasAttribute('open')) {
            // Opening an already open dialog the other way round throws, which is
            // the one case a module can reach by accident: "show()" first and
            // "showModal()" afterwards leaves it without a backdrop in a browser.
            if (dialog.getAttribute('data-test-dialog') !== modality) {
                throw new window.DOMException('The dialog is already open.', 'InvalidStateError');
            }

            return;
        }
        if (modality === 'modal' && !dialog.isConnected) {
            throw new window.DOMException('The dialog is not in a document.', 'InvalidStateError');
        }

        dialog.setAttribute('open', '');
        dialog.setAttribute('data-test-dialog', modality);

        // The "dialog focusing steps": the first control inside it that can take
        // the focus, or the dialog itself when it holds none.
        const focusable = dialog.querySelector(
            ['button', '[href]', 'input', 'select', 'textarea', '[tabindex]:not([tabindex="-1"])']
                .map((selector) => `${selector}:not([disabled]):not([hidden])`)
                .join(', '),
        );
        (focusable ?? dialog).focus({ preventScroll: true });
    };

    window.HTMLDialogElement.prototype.show = function show() {
        open(this, 'open');
    };
    window.HTMLDialogElement.prototype.showModal = function showModal() {
        open(this, 'modal');
    };
    window.HTMLDialogElement.prototype.close = function close(returnValue) {
        if (!this.hasAttribute('open')) {
            return;
        }

        this.removeAttribute('open');
        this.removeAttribute('data-test-dialog');
        if (returnValue !== undefined) {
            this.returnValue = String(returnValue);
        }
        this.dispatchEvent(new window.Event('close'));
    };
};

let installed = null;

export const installDom = () => {
    if (installed !== null) {
        return installed;
    }

    // "pretendToBeVisual" is what gives the window requestAnimationFrame; the
    // url gives it an origin.
    const dom = new JSDOM('<!doctype html><html lang="en"><body></body></html>', {
        pretendToBeVisual: true,
        url: 'https://example.test/',
    });

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    for (const name of browserGlobals) {
        const value = dom.window[name];
        if (value === undefined) {
            throw new Error(`jsdom does not provide "${name}". Model it here rather than in a test.`);
        }
        globalThis[name] = value;
    }
    installDialog(dom.window);

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
 * Lets the pending microtasks run, for a code path that starts a promise chain
 * without handing it back to the caller.
 */
export const settle = async (turns = 3) => {
    for (let turn = 0; turn < turns; turn += 1) {
        await Promise.resolve();
    }
};

/**
 * A keyboard event, which jsdom implements but does not put on "globalThis".
 *
 * The globals above are the ones the *sources* reach for; "KeyboardEvent" is
 * only ever constructed by a test, so it is modelled here rather than added to
 * that list. Built from the window's own constructor, so the event a handler
 * receives is the same kind of object a browser dispatches.
 */
export const createKeyboardEvent = (type, { key = '' } = {}) =>
    new (installDom().window.KeyboardEvent)(type, {
        key,
        bubbles: true,
        cancelable: true,
    });

/**
 * Sets the viewport width the modules read as "window.innerWidth".
 *
 * jsdom reports a fixed 1024 and has no way to resize, while the study plan
 * switches between a column layout and an accordion at 768. The property is
 * redefined rather than assigned, because jsdom declares it on the window.
 */
export const setViewportWidth = (width) => {
    Object.defineProperty(installDom().window, 'innerWidth', { value: width, configurable: true });
};
