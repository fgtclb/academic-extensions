/**
 * The entry point of the JavaScript test harness, loaded with "--import" by the
 * "test" script of Build/package.json and therefore by "runTests.sh -s testJs".
 *
 * It does two things, both before the first test file is loaded:
 *
 *   - installs the module resolve hook, so that a test can import a shipped
 *     module by the very specifier the TYPO3 import map resolves in a browser;
 *   - installs the DOM, so that a module reaching for "document" while it is
 *     being evaluated finds one. An import is hoisted above every statement of
 *     the importing file, so a test file cannot install it early enough itself.
 *
 * Node runs each test file in a child process of its own and passes it the same
 * "--import", which is what makes one window per process the same thing as one
 * window per test file — see "dom.mjs" for why that matters.
 *
 * See "docs/testing/javascript-tests.md".
 */
import { register } from 'node:module';
import { extensions } from '../extensions.mjs';
import { installDom } from './dom.mjs';

register('./resolve-hook.mjs', import.meta.url, {
    // The hook runs on its own thread and cannot reach this module's scope, so
    // everything it needs travels as structured-cloneable data.
    data: {
        harnessRoot: new URL('.', import.meta.url).href,
        extensions: extensions().map(({ path, specifier }) => ({ path, specifier })),
    },
});

installDom();
