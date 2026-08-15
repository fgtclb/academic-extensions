/**
 * eslint 9 flat configuration.
 *
 *   Build/Scripts/runTests.sh -s lintTypescript        fix in place
 *   Build/Scripts/runTests.sh -s lintTypescript -n     check only, as CI does
 *
 * Type aware linting is deliberately not enabled. "tsc --noEmit" is the type
 * gate and runs as its own suite, so the rules here are the ones a compiler
 * does not have.
 *
 * ## Every path below is relative to the repository root, not to this file
 *
 * eslint refuses to lint a file above the base path of its configuration, and
 * the sources live in the packages while this file lives in "Build/" — so a
 * configuration rooted here could never reach them. The base path is the
 * directory eslint was started in **whenever the configuration is named with
 * "--config"**, and the directory of the configuration file only when it was
 * found by searching upwards.
 *
 * The "lint" script of "Build/package.json" therefore changes into the
 * repository root and names this file explicitly. Moving the file up there
 * instead would have been the obvious alternative and does not work: its plugin
 * imports resolve through "node_modules" directories above *it*, and the only
 * manifest in this repository is the one next to it.
 *
 * See "docs/development/frontend-assets.md".
 */
import js from '@eslint/js';
import globals from 'globals';
import tseslint from 'typescript-eslint';

/**
 * The house rules, applied to every source tree alike. Extracted only so the
 * blocks below cannot drift apart.
 */
const houseRules = {
    '@typescript-eslint/explicit-function-return-type': 'error',
    '@typescript-eslint/consistent-type-imports': 'error',
    eqeqeq: 'error',
    'no-console': 'error',
    'prefer-const': 'error',
};

export default tseslint.config(
    {
        // Not ours: the vendor trees, the development instances, the generated
        // artifacts, and the agent working tree.
        ignores: [
            '**/node_modules/**',
            '.Build/**',
            '.agent/**',
            'core-*/**',
            'documentation-rendered/**',
            '**/Documentation-GENERATED-temp/**',
            '**/Resources/Public/**',
        ],
    },
    js.configs.recommended,
    tseslint.configs.recommended,
    {
        // The frontend and backend sources of every extension.
        files: [
            'packages/*/*/Resources/Private/TypeScript/**/*.ts',
            'packages-dev/*/Resources/Private/TypeScript/**/*.ts',
        ],
        languageOptions: {
            globals: globals.browser,
        },
        rules: houseRules,
    },
    {
        // The build configuration itself runs in node, not in a browser.
        files: ['Build/*.mjs'],
        languageOptions: {
            globals: globals.nodeBuiltin,
        },
    },
);
