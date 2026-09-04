/**
 * The types of the Vue stub, for the tests that assert on it.
 *
 * A test reaches for this file by its relative path rather than by the module
 * specifier the resolve hook rewrites: the specifier resolves to the real
 * `vue.ts` for TypeScript, which knows nothing about a stub. Both spellings are
 * the same file at run time, so the registry a test reads is the one the module
 * under test wrote.
 *
 * Only the applications are typed. `reactive`, `ref`, `nextTick` and
 * `onMounted` are reached through the module under test and never by a test.
 *
 * See "docs/testing/javascript-tests.md".
 */

/** One application created by the stub, in the state its mount left it in. */
export interface StubbedVueApp {
  readonly config: {
    compilerOptions: {
      isCustomElement?: (tag: string) => boolean;
    };
  };
  /** The element the application was mounted on, `null` until it was. */
  readonly container: Element | null;
  /** What `setup()` returned, `null` until the application was mounted. */
  readonly scope: Record<string, unknown> | null;
}

/** The applications created since the last `resetVue()`, oldest first. */
export declare const createdApps: StubbedVueApp[];

/** Empties the registry. The call a test file makes in its `beforeEach`. */
export declare const resetVue: () => void;
