/**
 * Every extension in this mono repository, found rather than listed.
 *
 * Extracted from "esbuild.mjs" so that the build and the JavaScript test
 * harness cannot disagree about what an extension is or what its import map
 * prefix resolves to. The build turns the result into entry points, the test
 * resolve hook turns it into a specifier map, and both start from this file.
 *
 * See "docs/development/frontend-assets.md" and "docs/testing/javascript-tests.md".
 */
import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { dirname, join, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

export const repositoryRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');

/**
 * Where a package keeps its sources, and where the result belongs. Both are
 * optional: a package that has neither directory contributes nothing, and
 * adding one is picked up without touching any configuration.
 */
export const passes = [
    { sources: 'Resources/Private/TypeScript', output: 'Resources/Public/JavaScript', extensions: ['.ts'] },
    { sources: 'Resources/Private/Scss', output: 'Resources/Public/Css', extensions: ['.scss', '.css'] },
];

/**
 * The bare specifier an extension's compiled modules are published under, or
 * null for a package that publishes none.
 *
 * It is derived from the *directory*, not from the extension key: every
 * "Configuration/JavaScriptModules.php" in this repository maps
 * "@<vendor>/<package directory>/frontend/" onto
 * "EXT:<extension_key>/Resources/Public/JavaScript/frontend/", and the two
 * differ wherever a key uses underscores — "academic-persons-edit" against
 * "academic_persons_edit". "Build/tsconfig.json" spells the same convention in
 * its "paths".
 *
 * Only "packages/<vendor>/<name>" has a vendor level to derive a scope from.
 * The three "packages-dev/" packages are development tooling, publish no
 * frontend modules and are therefore not addressable.
 */
const specifierFor = (path) => {
    const segments = relative(repositoryRoot, path).split(sep);

    return segments.length === 3 && segments[0] === 'packages'
        ? `@${segments[1]}/${segments[2]}`
        : null;
};

/**
 * A directory counts as an extension when it declares an extension key, which
 * is what makes it a TYPO3 extension with a "Resources/" tree — and hands over
 * the key for the import map prefix in the same step.
 */
export const extensions = () => {
    const candidates = [];
    for (const group of ['packages', 'packages-dev']) {
        const groupPath = resolve(repositoryRoot, group);
        if (!existsSync(groupPath)) {
            continue;
        }
        for (const entry of readdirSync(groupPath, { withFileTypes: true })) {
            if (!entry.isDirectory()) {
                continue;
            }
            // "packages/" nests one level deeper, by vendor.
            const path = join(groupPath, entry.name);
            const nested = group === 'packages'
                ? readdirSync(path, { withFileTypes: true })
                    .filter((child) => child.isDirectory())
                    .map((child) => join(path, child.name))
                : [path];
            candidates.push(...nested);
        }
    }

    return candidates
        .map((path) => {
            const manifest = join(path, 'composer.json');
            if (!existsSync(manifest)) {
                return null;
            }
            const key = JSON.parse(readFileSync(manifest, 'utf8'))?.extra?.['typo3/cms']?.['extension-key'];
            return key ? { path, key, specifier: specifierFor(path) } : null;
        })
        .filter(Boolean)
        .sort((one, other) => one.path.localeCompare(other.path));
};
