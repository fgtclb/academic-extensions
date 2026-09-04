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
