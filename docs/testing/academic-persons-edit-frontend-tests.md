# Frontend verification for `academic-persons-edit`

The extension's frontend source is TypeScript under
[`Resources/Private/TypeScript/`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/).
The repository build compiles it to committed JavaScript under
[`Resources/Public/JavaScript/`](../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/).

There is no extension-local Jest or jsdom environment. The former
`Resources/Public/Development/` tree was removed when the frontend moved to the
repository-wide TypeScript toolchain. Do not recreate test infrastructure below
`Resources/Public/`; that directory contains distributable artifacts.

## Repository gates

Run the frontend gates from the repository root:

```bash
Build/Scripts/runTests.sh -s buildJs
Build/Scripts/runTests.sh -s checkJsBuildClean
Build/Scripts/runTests.sh -s lintTypescript -n
Build/Scripts/runTests.sh -s typecheckJs
```

The gates have separate responsibilities:

- `buildJs` compiles the TypeScript sources and updates the committed
  JavaScript artifacts.
- `checkJsBuildClean` removes only generated outputs, rebuilds them and fails
  when the result differs from the working tree.
- `lintTypescript -n` checks the TypeScript without modifying it.
- `typecheckJs` runs TypeScript's type checker; esbuild transpiles types but
  does not validate them.

These suites are core-version independent and require no `composerUpdate`.
They use the repository's pinned Node.js container, so no package-local npm
installation is needed.

## PHP coverage

[`FrontendJavaScriptTestEnvironmentTest.php`](../../packages/fgtclb/academic-persons-edit/Tests/Unit/Architecture/FrontendJavaScriptTestEnvironmentTest.php)
guards the extension-specific architecture. It verifies that the obsolete
development tree stays absent, the repository build is present, Vue and its
adapter are wired correctly, generated modules exist, and the TypeScript,
Fluid and CSS contracts of the inline editors remain aligned.

Run that test with the normal unit suite:

```bash
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.2 -s unit \
  packages/fgtclb/academic-persons-edit/Tests/Unit/Architecture/FrontendJavaScriptTestEnvironmentTest.php
```

The functional tests under
[`Tests/Functional/Plugins/`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/)
cover rendered markup, module integration and the server endpoints used by the
frontend. Run them for both supported core versions after installing the
matching dependency set:

```bash
Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional \
  packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins

Build/Scripts/runTests.sh -t 14 -p 8.4 -s functional \
  packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins
```

The PHP tests inspect generated modules and frontend contracts, but they do not
execute the modules in a browser DOM. A change that needs browser-level proof
therefore requires suitable repository-level frontend test infrastructure; an
extension-local test environment below the public artifact tree is not the
replacement.

## Adding or changing frontend code

1. Change files below `Resources/Private/TypeScript/`, never the generated
   JavaScript directly.
2. Keep internal imports as bare extension specifiers so TYPO3's import map can
   apply cache-busting query parameters.
3. Run `buildJs`, then inspect and commit the generated JavaScript.
4. Run `lintTypescript -n`, `typecheckJs` and `checkJsBuildClean`.
5. Extend the PHP architecture or functional tests when the change introduces
   a new integration contract or rendered behavior.

## Success criteria

The frontend part of a change is complete when:

- TypeScript linting and type checking pass;
- the committed JavaScript is a clean rebuild of its source;
- affected unit and functional tests pass on both supported TYPO3 versions;
  and
- any browser behavior not exercised by the existing PHP tests is explicitly
  identified and verified at the appropriate level.

## See also

- [Frontend assets](../development/frontend-assets.md)
- [Functional tests](functional-tests.md)
- [Unit tests](unit-tests.md)
- [PHPUnit configuration](phpunit-configuration.md)
