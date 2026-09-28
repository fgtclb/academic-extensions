/**
 * The third party libraries an extension ships, built from their npm packages.
 *
 * A library that TYPO3 does not deliver is a pinned dependency of
 * "Build/package.json", and this pass writes it below
 * "Resources/Public/JavaScript/vendor/<library>/<version>/" of the extension
 * that loads it: the ES module, the files the package ships next to it, and
 * its licence. The extension's "Configuration/JavaScriptModules.php" publishes
 * the module under a bare specifier.
 *
 * The version in the directory name is read from the installed package, so a
 * version change in "package.json" moves the directory and the import map
 * entry has to follow. "--list-outputs" names the directory of the library
 * rather than the version below it, so "checkJsBuildClean" deletes a directory
 * a previous version left behind as well.
 *
 * The modules are minified, unlike the output of this repository's own
 * sources: they are not ours to read, the readable source is the pinned
 * package, and a page should not load more than the release does. Licence
 * comments are kept.
 *
 * See "docs/development/frontend-assets.md".
 */
import { build } from 'esbuild';
import { chmodSync, cpSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const buildRoot = dirname(fileURLToPath(import.meta.url));
const modules = join(buildRoot, 'node_modules');

/**
 * Makes the global "L" of a Leaflet plugin the Leaflet ES module.
 *
 * Plugins written for the classic build read and extend the global "L": the
 * marker cluster plugin adds "L.MarkerClusterGroup" and reads it back further
 * down. A module namespace cannot take new members, so the plugin gets a copy
 * of the namespace. The copy holds the same classes, so what the plugin
 * includes into "L.Marker" reaches every marker the map creates.
 */
const leafletGlobalShim = join(buildRoot, 'vendor', 'leaflet-global.mjs');

/**
 * @type {Array<{
 *   library: string,
 *   extension: string,
 *   module: string,
 *   external?: string[],
 *   inject?: string[],
 *   banner?: string,
 *   files: Record<string, string>,
 * }>}
 */
export const vendorLibraries = [
    {
        library: 'leaflet',
        extension: 'packages/fgtclb/academic-partners',
        module: 'dist/leaflet-src.esm.js',
        files: {
            'leaflet.css': 'dist/leaflet.css',
            images: 'dist/images',
            LICENSE: 'LICENSE',
        },
    },
    {
        library: 'leaflet.markercluster',
        extension: 'packages/fgtclb/academic-partners',
        // The package publishes only classic builds. Its sources are ES
        // modules that read the global "L", see the shim above.
        module: 'src/index.js',
        external: ['leaflet'],
        inject: [leafletGlobalShim],
        banner: '/*! Leaflet.markercluster {version}, (c) 2012-2017 Dave Leaver, smartrak. MIT licence, see MIT-LICENCE.txt */',
        files: {
            'MarkerCluster.css': 'dist/MarkerCluster.css',
            'MarkerCluster.Default.css': 'dist/MarkerCluster.Default.css',
            'MIT-LICENCE.txt': 'MIT-LICENCE.txt',
        },
    },
];

/** The files copied as text: stylesheets and licences, never the images. */
const textFile = /(\.css|\.txt|^LICENSE)$/;

const versionOf = (library) =>
    JSON.parse(readFileSync(join(modules, library, 'package.json'), 'utf8')).version;

/** The directory "--list-outputs" names and "checkJsBuildClean" deletes. */
export const vendorDirectoryOf = (repositoryRoot, entry) =>
    join(repositoryRoot, entry.extension, 'Resources/Public/JavaScript/vendor', entry.library);

export const buildVendorLibraries = async (repositoryRoot, target) => {
    for (const entry of vendorLibraries) {
        const version = versionOf(entry.library);
        const outdir = join(vendorDirectoryOf(repositoryRoot, entry), version);
        mkdirSync(outdir, { recursive: true });

        await build({
            entryPoints: [join(modules, entry.library, entry.module)],
            outfile: join(outdir, `${entry.library}.js`),
            bundle: true,
            format: 'esm',
            platform: 'browser',
            target,
            minify: true,
            legalComments: 'inline',
            external: entry.external ?? [],
            inject: entry.inject ?? [],
            banner: entry.banner ? { js: entry.banner.replace('{version}', version) } : undefined,
            logLevel: 'info',
        });

        for (const [destination, source] of Object.entries(entry.files)) {
            const from = join(modules, entry.library, source);
            const to = join(outdir, destination);
            if (textFile.test(destination)) {
                // Leaflet ships its stylesheet and licence with CRLF line endings.
                // git would store them as LF on one machine and as CRLF on another,
                // and the gate compares what the build writes with what git holds.
                // The line endings are the one thing the build changes.
                writeFileSync(to, readFileSync(from, 'utf8').replace(/\r\n/g, '\n'));
            } else {
                cpSync(from, to, { recursive: true });
            }
        }
        // A package may ship its files executable, and git stores the mode.
        // Every file the pass writes is a plain file, whatever the package had.
        for (const file of readdirSync(outdir, { recursive: true, withFileTypes: true })) {
            if (file.isFile()) {
                chmodSync(join(file.parentPath, file.name), 0o644);
            }
        }
    }

    return vendorLibraries.length;
};
