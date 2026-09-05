/**
 * The frontend asset build of every extension in this mono repository.
 *
 *   node esbuild.mjs                 the build, and what is committed
 *   node esbuild.mjs --dev           same, with an inline source map
 *   node esbuild.mjs --list-outputs  print the files the build would write
 *
 * Called through "Build/Scripts/runTests.sh -s buildJs", which runs it in a
 * container so nothing has to be installed on the host.
 *
 * It does not type check — esbuild never does. "typecheckJs" is the type gate
 * and runs as its own suite.
 *
 * See "docs/development/frontend-assets.md".
 */
import { build } from 'esbuild';
import { existsSync, readdirSync } from 'node:fs';
import { dirname, extname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import * as sass from 'sass';
import { extensions, passes, repositoryRoot } from './extensions.mjs';

const buildRoot = dirname(fileURLToPath(import.meta.url));
const development = process.argv.slice(2).includes('--dev');
const listOutputs = process.argv.slice(2).includes('--list-outputs');

/**
 * The browser floor. TYPO3 no longer polyfills the import map mechanism on
 * either supported core version, so targeting anything older would emit
 * transpiled output for browsers that cannot resolve the module anyway.
 */
const target = ['chrome89', 'firefox108', 'safari16.4'];

/**
 * Every source file below a directory, recursively.
 *
 * A file whose name starts with an underscore is a partial: it is reached
 * through "@use" from an entry point and never becomes one itself. That is the
 * sass convention, and applying it to TypeScript as well costs nothing.
 */
const entryPointsIn = (directory, allowed) => {
    const found = [];
    const walk = (current) => {
        for (const entry of readdirSync(current, { withFileTypes: true })) {
            const path = join(current, entry.name);
            if (entry.isDirectory()) {
                walk(path);
            } else if (allowed.includes(extname(entry.name)) && !entry.name.startsWith('_')) {
                found.push(path);
            }
        }
    };
    walk(directory);

    return found.sort();
};

/**
 * Hands the SCSS to dart-sass and the result back to esbuild as CSS, so that
 * "@use", partials and the url() rewriting all happen in the tool that is good
 * at each of them.
 *
 * "resolveDir" is what makes a relative url() in a partial resolve against the
 * file that wrote it rather than against the entry point.
 */
const sassPlugin = {
    name: 'sass',
    setup(pluginBuild) {
        pluginBuild.onLoad({ filter: /\.scss$/ }, (arguments_) => {
            const compiled = sass.compile(arguments_.path, {
                loadPaths: [dirname(arguments_.path)],
                style: 'expanded',
                quietDeps: true,
            });

            return {
                contents: compiled.css,
                loader: 'css',
                resolveDir: dirname(arguments_.path),
                watchFiles: compiled.loadedUrls
                    .filter((url) => url.protocol === 'file:')
                    .map((url) => fileURLToPath(url)),
            };
        });
    },
};

const shared = {
    target,
    // esbuild writes the path of each input into the bundled CSS as a comment,
    // relative to its working directory. Pinning that to the repository root
    // makes the emitted bytes the same whether the build was started from
    // "Build/" or from the root — which a gate comparing committed artifacts
    // against a fresh build depends on.
    absWorkingDir: repositoryRoot,
    // Never minified. The emitted files are meant to be readable, and nothing
    // here is large enough for the size to be worth the loss.
    minify: false,
    // Source maps are never committed. The development build carries an inline
    // one instead, so no ".map" file exists to be ignored or shipped.
    sourcemap: development ? 'inline' : false,
    legalComments: 'none',
    logLevel: listOutputs ? 'silent' : 'info',
    // Anything a stylesheet or module refers to is emitted next to the result,
    // so a relative reference keeps working in a composer installation where
    // "Resources/Public/" is published.
    loader: {
        '.png': 'file',
        '.jpg': 'file',
        '.jpeg': 'file',
        '.gif': 'file',
        '.svg': 'file',
        '.webp': 'file',
        '.avif': 'file',
        '.woff': 'file',
        '.woff2': 'file',
    },
    assetNames: 'assets/[name]-[hash]',
};

/**
 * What the build writes, derived from the sources without running it.
 *
 * "checkJsBuildClean" deletes exactly this set before rebuilding. It cannot
 * simply delete the output directories: they also hold vendored files that have
 * no source — a minified library and its images — and those must survive.
 *
 * Assets emitted through the loaders above are not listed, because their name
 * carries a content hash that is only known after the build. They live below
 * "assets/", which the gate removes wholesale.
 */
const outputsFor = (extension) => {
    const outputs = [];
    for (const pass of passes) {
        const sourceRoot = join(extension.path, pass.sources);
        if (!existsSync(sourceRoot)) {
            continue;
        }
        for (const entryPoint of entryPointsIn(sourceRoot, pass.extensions)) {
            const stem = relative(sourceRoot, entryPoint).replace(/\.[^.]+$/, '');
            const suffix = pass.extensions.includes('.ts') ? '.js' : '.css';
            outputs.push(join(extension.path, pass.output, stem + suffix));
        }
    }

    return outputs;
};

const found = extensions();

if (listOutputs) {
    for (const extension of found) {
        for (const output of outputsFor(extension)) {
            process.stdout.write(relative(repositoryRoot, output) + '\n');
        }
        for (const pass of passes) {
            const assets = join(extension.path, pass.output, 'assets');
            if (existsSync(assets)) {
                process.stdout.write(relative(repositoryRoot, assets) + '\n');
            }
        }
    }
    process.exit(0);
}

let built = 0;
for (const extension of found) {
    for (const pass of passes) {
        const sourceRoot = join(extension.path, pass.sources);
        if (!existsSync(sourceRoot)) {
            continue;
        }
        const entryPoints = entryPointsIn(sourceRoot, pass.extensions);
        if (entryPoints.length === 0) {
            continue;
        }

        const isScript = pass.extensions.includes('.ts');
        await build({
            ...shared,
            entryPoints,
            outbase: sourceRoot,
            outdir: join(extension.path, pass.output),
            // Scripts are emitted one module per source module, so every import
            // survives as written and is resolved in the browser through the
            // TYPO3 import map — which is what gives each one its "?bust="
            // cache key. Stylesheets are bundled, because "@use" and url() have
            // to be resolved at build time.
            bundle: !isScript,
            ...(isScript ? { format: 'esm', platform: 'browser', tsconfig: resolve(buildRoot, 'tsconfig.json') } : {}),
            ...(isScript ? {} : { plugins: [sassPlugin] }),
            banner: isScript
                ? { js: `/* Generated from ${pass.sources} — do not edit. */` }
                : { css: `/* Generated from ${pass.sources} — do not edit. */` },
        });
        built += entryPoints.length;
    }
}

if (built === 0) {
    process.stdout.write(
        'No frontend sources found. Add a file below "Resources/Private/TypeScript/"\n' +
        'or "Resources/Private/Scss/" of an extension and it is picked up here.\n',
    );
} else {
    process.stdout.write(`\nBuilt ${built} entry point(s) across ${found.length} extension(s).\n`);
}
