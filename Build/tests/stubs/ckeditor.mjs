/**
 * Stands in for the six "@ckeditor/ckeditor5-*" bundles EXT:rte_ckeditor
 * publishes. Real CKEditor 5 needs a browser, and what the tests are about is
 * this repository's code around the editor: when one is created, on which
 * field, and — the reason this stub exists at all — when it is destroyed.
 *
 * It reports through the DOM rather than through an exported registry, so a
 * test asserts on the textarea it already has and needs no import of its own:
 *
 *   data-test-ckeditor="live"        an editor was created on this textarea
 *   data-test-ckeditor="destroyed"   and later destroyed
 *   data-test-ckeditor-destroys="n"  how many times "destroy()" was called
 *
 * The last one matters because destroying twice is not an error a single flag
 * would show, and the close path is reached from more than one direction.
 *
 * See "docs/testing/javascript-tests.md".
 */

// The plugin classes. Nothing but identity is asked of them: the production
// configuration puts them into an array and hands the array to "create()".
export const Bold = 'stub:Bold';
export const Italic = 'stub:Italic';
export const Essentials = 'stub:Essentials';
export const Link = 'stub:Link';
export const List = 'stub:List';
export const Paragraph = 'stub:Paragraph';

class StubEditor {
    #element;
    #data;
    #listeners = [];

    constructor(element, config) {
        this.#element = element;
        // Real CKEditor normalises what it is handed - bare text becomes a
        // paragraph - so the value it reports back is not always the value the
        // template rendered. A test that is about that difference says what the
        // editor makes of the source with "data-test-ckeditor-initial"; every
        // other test gets the source unchanged, which is the simpler model and
        // the one the rest of the suite is written against.
        this.#data = element.getAttribute('data-test-ckeditor-initial') ?? element.value ?? '';
        this.config = config;
        // The real editor replaces the textarea with a contenteditable and
        // puts the caret there. jsdom has no such view, so the textarea stands
        // in for it: "document.activeElement === textarea" is how a test says
        // "the caret is in this field's editor", which is the only way a focus
        // that goes through the editor can be observed at all.
        this.editing = {
            view: {
                focus: () => {
                    this.focused = true;
                    element.focus();
                },
            },
        };
        this.focused = false;
        this.model = {
            document: {
                on: (eventName, listener) => {
                    if (eventName === 'change:data') {
                        this.#listeners.push(listener);
                    }
                },
            },
        };
        element.setAttribute('data-test-ckeditor', 'live');
    }

    getData() {
        return this.#data;
    }

    setData(value) {
        this.#data = value;
    }

    /**
     * What a keystroke in the real editor ends up doing: the data changes and
     * "change:data" fires. Exposed so a test can drive the change handler the
     * production code registers.
     */
    typeData(value) {
        this.#data = value;
        this.#listeners.forEach((listener) => listener());
    }

    destroy() {
        const destroys = Number.parseInt(this.#element.getAttribute('data-test-ckeditor-destroys') ?? '0', 10) + 1;
        this.#element.setAttribute('data-test-ckeditor', 'destroyed');
        this.#element.setAttribute('data-test-ckeditor-destroys', String(destroys));
        this.#listeners = [];

        return Promise.resolve();
    }
}

export const ClassicEditor = {
    create: (element, config) => Promise.resolve(new StubEditor(element, config)),
};
