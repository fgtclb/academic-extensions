/**
 * Resolves the module specifiers of the shipped frontend sources for node.
 *
 * In a browser those specifiers are resolved by the TYPO3 import map: an
 * extension publishes its compiled modules under "@<vendor>/<extension-key>/",
 * and TYPO3 core and other extensions publish theirs the same way. Node knows
 * nothing about that map, so the tests would either have to import the sources
 * by relative path — which is not how they import each other, so the graph
 * under test would differ from the shipped one — or the map has to be modelled.
 * This hook models it.
 *
 * Three rules, in this order:
 *
 *   1. An explicit stub, for a library the tests must not load for real.
 *   2. "@<vendor>/<extension-key>/frontend/<path>.js" -> that extension's
 *      "Resources/Private/TypeScript/frontend/<path>.ts". The TypeScript source,
 *      never the compiled artifact: a test that ran against the artifact would
 *      pass on a stale one, which is the very thing "checkJsBuildClean" exists
 *      to prevent. Node 24 strips the types on load.
 *   3. Anything else: node's own resolution.
 *
 * See "docs/testing/javascript-tests.md".
 */
import { existsSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

let harnessRoot = '';
let byPrefix = new Map();

/**
 * Specifiers that must never reach their real implementation.
 *
 * CKEditor 5 and CropperJS are delivered by TYPO3 and by the extension's own
 * vendor tree, are browser-only and pull in a rendering engine each; the Vue
 * runtime is a compiled browser bundle that the source imports through a path
 * that only exists in the *output* tree. What the tests are about is the code
 * of this repository around them, so each is replaced by a stub that records
 * what was asked of it.
 *
 * A stub is a liability, so the list is kept short on purpose and every entry
 * is a library this repository does not own.
 */
const stubs = new Map([
    ['@ckeditor/ckeditor5-basic-styles', 'stubs/ckeditor.mjs'],
    ['@ckeditor/ckeditor5-editor-classic', 'stubs/ckeditor.mjs'],
    ['@ckeditor/ckeditor5-essentials', 'stubs/ckeditor.mjs'],
    ['@ckeditor/ckeditor5-link', 'stubs/ckeditor.mjs'],
    ['@ckeditor/ckeditor5-list', 'stubs/ckeditor.mjs'],
    ['@ckeditor/ckeditor5-paragraph', 'stubs/ckeditor.mjs'],
    ['@fgtclb/academic-persons-edit/cropper', 'stubs/cropper.mjs'],
    ['@fgtclb/academic-persons-edit/frontend/vue.js', 'stubs/vue.mjs'],
]);

/**
 * Libraries that are resolved for real, but not from where the importer stands.
 *
 * "lit" is delivered by TYPO3 core through the import map — EXT:core maps
 * "lit", "lit/", "lit-element" and "lit-html" with no tag and no dependency, so
 * a frontend module of this extension resolves it in a browser without any
 * configuration of its own. Node resolves a bare specifier from the importing
 * file upwards instead, and the sources live in "packages/", where there is no
 * "node_modules" at all. So the resolution is retried from the harness, whose
 * "Build/package.json" pins the exact versions core ships (3.2.0 / 4.1.0 /
 * 2.0.4) — the tests run the same Lit the browser will, or the pin is wrong.
 *
 * Only a real dependency belongs here, never a stub: Lit is what the elements
 * are written in, and stubbing it would test nothing.
 */
const relocated = ['lit'];

export const initialize = (data) => {
    harnessRoot = data.harnessRoot;
    byPrefix = new Map(
        data.extensions
            .filter(({ specifier }) => specifier !== null)
            .map(({ path, specifier }) => [
                `${specifier}/frontend/`,
                `${path}/Resources/Private/TypeScript/frontend/`,
            ]),
    );
};

export const resolve = (specifier, context, nextResolve) => {
    const stub = stubs.get(specifier);
    if (stub !== undefined) {
        return { url: new URL(stub, harnessRoot).href, shortCircuit: true };
    }

    if (relocated.some((name) => specifier === name || specifier.startsWith(`${name}/`))) {
        return nextResolve(specifier, { ...context, parentURL: harnessRoot });
    }

    for (const [prefix, sourceRoot] of byPrefix) {
        if (!specifier.startsWith(prefix)) {
            continue;
        }
        const source = sourceRoot + specifier.slice(prefix.length).replace(/\.js$/, '.ts');
        if (!existsSync(source)) {
            // Not a silent fall-through: a specifier that looks like one of this
            // repository's modules and has no source behind it is a typo, and
            // node's own error would blame a package name instead.
            throw new Error(`No TypeScript source for the module specifier "${specifier}" (looked for "${source}").`);
        }
        return { url: pathToFileURL(source).href, shortCircuit: true };
    }

    return nextResolve(specifier, context);
};
