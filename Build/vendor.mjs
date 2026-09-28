/**
 * The third party libraries an extension ships, built from their npm packages.
 *
 * A library that TYPO3 does not deliver is a pinned dependency of
 * "Build/package.json", and this pass writes it into the extension that loads
 * it. On this branch the frontend has no import map - TYPO3 v12 renders none -
 * so the libraries are classic scripts at the paths the templates load, and a
 * library publishes a global for the modules of the extension to read.
 *
 * Leaflet and its marker cluster plugin publish "window.LeafletObject" rather
 * than "window.L", so they cannot collide with another Leaflet on the page. The
 * copies this replaces were the same releases with "L" renamed by replacing the
 * text, which also broke the SVG path command "L" inside Leaflet. A small entry
 * per library publishes the globals instead and leaves the code alone. Both run
 * in strict mode, as the UMD builds did.
 *
 * The scripts are minified, unlike the output of this repository's own sources:
 * they are not ours to read, the readable source is the pinned package, and a
 * page should not load more than the release does. Licence comments are kept.
 *
 * See "docs/development/frontend-assets.md".
 */
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const buildRoot = dirname(fileURLToPath(import.meta.url));
const modules = join(buildRoot, 'node_modules');

/**
 * @type {Array<{
 *   library: string,
 *   output: string,
 *   entry: string,
 *   inject?: string[],
 *   banner?: string,
 * }>}
 */
export const vendorLibraries = [
    {
        library: 'leaflet',
        output: 'packages/fgtclb/academic-partners/Resources/Public/JavaScript/leaflet.js',
        entry: join(buildRoot, 'vendor', 'leaflet-classic.mjs'),
    },
    {
        library: 'leaflet.markercluster',
        output: 'packages/fgtclb/academic-partners/Resources/Public/JavaScript/markerCluster.js',
        // The package publishes only UMD builds that read "window.L". Its sources
        // are ES modules that read and extend the global "L", which the shim
        // turns into the global Leaflet publishes. Loaded after "leaflet.js".
        entry: join(buildRoot, 'vendor', 'markercluster-classic.mjs'),
        inject: [join(buildRoot, 'vendor', 'leaflet-global.mjs')],
        banner: '/*! Leaflet.markercluster {version}, (c) 2012-2017 Dave Leaver, smartrak. MIT licence, see https://github.com/Leaflet/Leaflet.markercluster/blob/v{version}/MIT-LICENCE.txt */',
    },
];

const versionOf = (library) =>
    JSON.parse(readFileSync(join(modules, library, 'package.json'), 'utf8')).version;

export const buildVendorLibraries = async (repositoryRoot, target) => {
    for (const entry of vendorLibraries) {
        const version = versionOf(entry.library);
        await build({
            entryPoints: [entry.entry],
            outfile: join(repositoryRoot, entry.output),
            bundle: true,
            format: 'iife',
            platform: 'browser',
            target,
            minify: true,
            legalComments: 'inline',
            inject: entry.inject ?? [],
            // esbuild writes "use strict" itself for an ES module entry, so both
            // scripts run in strict mode, as the UMD builds did.
            banner: entry.banner ? { js: entry.banner.replaceAll('{version}', version) } : undefined,
            logLevel: 'info',
        });
    }

    return vendorLibraries.length;
};
