/**
 * Resolves the module specifiers of the shipped frontend sources for node.
 *
 * In a browser those specifiers are resolved by the TYPO3 import map: an
 * extension publishes its compiled modules under "@<vendor>/<extension-key>/",
 * and TYPO3 core and other extensions publish theirs the same way. Node knows
 * nothing about that map, so the tests would either have to import the sources
 * by relative path — which is not how a browser reaches them, so the graph
 * under test would differ from the shipped one — or the map has to be modelled.
 * This hook models it.
 *
 * Two rules, in this order:
 *
 *   1. "@<vendor>/<package directory>/frontend/<path>.js" -> that extension's
 *      "Resources/Private/TypeScript/frontend/<path>.ts". The TypeScript
 *      source, never the compiled artifact: a test that ran against the
 *      artifact would pass on a stale one, which is the very thing
 *      "checkJsBuildClean" exists to prevent. Node 24 strips the types on load.
 *   2. Anything else: node's own resolution.
 *
 * **This branch stubs nothing**, and there is no list of stubs to keep short:
 * none of the four frontend modules here imports a library at all. The two
 * CKEditor 4 modules and the partner map reach for a global their template
 * loads from a content delivery network, which is not a module specifier and
 * cannot be resolved — a test of one of those has to put that global in place
 * itself.
 *
 * See "docs/testing/javascript-tests.md".
 */
import { existsSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

let byPrefix = new Map();

export const initialize = (data) => {
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
