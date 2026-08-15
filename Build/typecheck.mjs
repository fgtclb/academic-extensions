/**
 * The type gate, which esbuild does not provide: it transpiles TypeScript
 * without ever checking it, so a build can be green on code that does not
 * compile.
 *
 *   node typecheck.mjs        as "npm run typecheck", and as the "typecheckJs" suite
 *
 * This exists as a wrapper rather than a plain "tsc --noEmit" for one reason:
 * an extension is not required to ship TypeScript, and today none does. With
 * nothing to check, "tsc" aborts with TS18003 "No inputs were found in config
 * file", which is a configuration error rather than a type error — and would
 * make the suite red for a repository that is perfectly fine.
 *
 * The source list comes from the build itself, so the two cannot disagree about
 * what counts as a source.
 *
 * See "docs/development/frontend-assets.md".
 */
import { spawnSync } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const buildRoot = dirname(fileURLToPath(import.meta.url));

const outputs = spawnSync(process.execPath, [resolve(buildRoot, 'esbuild.mjs'), '--list-outputs'], {
    cwd: buildRoot,
    encoding: 'utf8',
});

if (outputs.status !== 0) {
    process.stderr.write(outputs.stderr ?? 'Could not determine the frontend sources.\n');
    process.exit(1);
}

const scripts = outputs.stdout.split('\n').filter((line) => line.endsWith('.js'));

if (scripts.length === 0) {
    process.stdout.write('No TypeScript sources to check.\n');
    process.exit(0);
}

const result = spawnSync(resolve(buildRoot, 'node_modules/.bin/tsc'), ['--noEmit'], {
    cwd: buildRoot,
    stdio: 'inherit',
});

process.exit(result.status ?? 1);
