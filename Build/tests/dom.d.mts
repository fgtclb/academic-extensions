/**
 * The types of "dom.mjs", the only harness module a test file imports.
 *
 * Hand written rather than inferred: the harness is plain JavaScript because it
 * is loaded before node's type stripping has anything to do with it, and
 * "allowJs" would infer "any" for everything that passes through jsdom, which
 * ships no types of its own. Three functions is a surface small enough to
 * declare, and declaring it is what makes the DOM in a test typed.
 *
 * See "docs/testing/javascript-tests.md".
 */

/**
 * Creates the window and puts the browser globals on "globalThis". Called once
 * per process by "register.mjs"; calling it again returns the same window.
 */
export declare const installDom: () => void;

/**
 * Replaces the document body with the given markup and returns it. The call
 * every test starts with, so that no test inherits the markup of the previous.
 */
export declare const resetBody: (html?: string) => HTMLElement;

/**
 * Lets the pending microtasks run, for a code path that starts a promise chain
 * without handing it back to the caller.
 */
export declare const settle: (turns?: number) => Promise<void>;

/**
 * A drag event with a modelled data transfer, for the handlers of the document
 * list - jsdom implements neither "DragEvent" nor "DataTransfer".
 */
export declare const createDragEvent: (
  type: string,
  init?: { clientX?: number; clientY?: number },
) => Event & {
  readonly clientX: number;
  readonly clientY: number;
  readonly dataTransfer: {
    dropEffect: string;
    effectAllowed: string;
    dragImage: { element: Element; x: number; y: number } | null;
    setData: (format: string, value: string) => void;
    getData: (format: string) => string;
    setDragImage: (element: Element, x: number, y: number) => void;
  };
};

/**
 * Places a rectangle on an element. jsdom lays nothing out and reports zero for
 * every "getBoundingClientRect()", so the geometry a test is about is injected.
 */
export declare const setBoundingRect: (
  element: Element,
  rect: { top?: number; left?: number; width?: number; height?: number },
) => void;

/**
 * Whether an object url created by the page is still registered. The image
 * editor has to revoke every preview url it creates, and nothing but the
 * register can observe that it did.
 */
export declare const isObjectUrlAlive: (url: string) => boolean;
