/**
 * Stands in for "@fgtclb/academic-persons-edit/frontend/vue.js".
 *
 * That module cannot be loaded from its source: it imports the vendored runtime
 * as "../vendor/vue/3.5.42/vue.esm-browser.prod.js", a path that resolves
 * relative to the *compiled* module in "Resources/Public/JavaScript/" and does
 * not exist next to the TypeScript.
 *
 * Loading the vendored bundle instead was rejected for a second reason: Vue is
 * what ACE-509 removes. A test net that binds to Vue's reactivity would have to
 * be rewritten by the port it exists to protect. The two functions the modules
 * under test call are therefore modelled by their contract, not by Vue:
 *
 *   reactive(value)  a mutable object the code writes state into. Vue's deep
 *                    proxy exists to re-render a template; no template is
 *                    rendered here, so the object itself is the contract.
 *   nextTick()       "after the pending state changes have been applied". A
 *                    resolved promise is that, one microtask later.
 *
 * Consequence, and it is a real limit: a test using this stub proves nothing
 * about rendering. It proves what the controller does — which handler runs,
 * with which argument, in which order — and that is what has to survive the
 * port unchanged.
 *
 * See "docs/testing/javascript-tests.md".
 */
export const reactive = (value) => value;

export const nextTick = () => Promise.resolve();

export const ref = (value) => ({ value });

export const onMounted = (callback) => callback();

export const createApp = () => {
    throw new Error('createApp() is not modelled: mount the DOM the test needs and call the controller directly.');
};
