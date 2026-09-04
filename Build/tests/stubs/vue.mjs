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
 * be rewritten by the port it exists to protect. The functions the modules
 * under test call are therefore modelled by their contract, not by Vue:
 *
 *   reactive(value)  a mutable object the code writes state into. Vue's deep
 *                    proxy exists to re-render a template; no template is
 *                    rendered here, so the object itself is the contract.
 *   nextTick()       "after the pending state changes have been applied". A
 *                    resolved promise is that, one microtask later.
 *   createApp()      the ordering the root element depends on: "setup()" first,
 *                    then "mount()", then the callbacks "onMounted()" collected
 *                    during "setup()".
 *
 * ## The one place the stub knowingly differs from Vue
 *
 * Vue's own "mount()" assigns the container's "innerHTML" as the template and
 * then empties the container ("i.template=r.innerHTML" followed by
 * "r.textContent=''" in the production bundle), so the markup the initialisers
 * query is Vue's rendering of it and not the markup that was there before. This
 * stub leaves the container alone, because the fixture *is* the rendered markup
 * - it is transcribed from what the Fluid partials produce with the directives
 * resolved - and re-rendering it is exactly what the port removes.
 *
 * Consequence, and it is a real limit: a test using this stub proves nothing
 * about rendering. It proves what the controllers and the root element do -
 * which handler runs, with which argument, in which order - and that is what
 * has to survive the port unchanged.
 *
 * See "docs/testing/javascript-tests.md".
 */
export const reactive = (value) => value;

export const nextTick = () => Promise.resolve();

export const ref = (value) => ({ value });

/**
 * The applications created since the last "resetVue()", oldest first.
 *
 * A test asserts on these for the two things that have no other observable
 * effect: that an editor is mounted exactly once, and that the application is
 * told which tags are custom elements rather than components.
 */
export const createdApps = [];

export const resetVue = () => {
    createdApps.length = 0;
    collecting = null;
};

// The callbacks "onMounted()" is handed while a "setup()" is running, or null
// when none is. Vue keeps the same association through its current instance.
let collecting = null;

export const onMounted = (callback) => {
    if (collecting === null) {
        // Outside a "setup()" Vue warns and never calls back. Calling it at
        // once keeps a controller test that reaches for it working without a
        // mount, which is what the shipped modules did before the root element
        // existed.
        callback();

        return;
    }
    collecting.push(callback);
};

export const createApp = (component) => {
    const app = {
        config: { compilerOptions: {} },
        container: null,
        scope: null,
        mount(container) {
            const outer = collecting;
            collecting = [];
            let callbacks;
            try {
                this.scope = component.setup();
            } finally {
                callbacks = collecting;
                collecting = outer;
            }
            this.container = container;
            container.setAttribute('data-test-vue-mounted', '');
            for (const callback of callbacks) {
                callback();
            }

            return this.scope;
        },
    };
    createdApps.push(app);

    return app;
};
