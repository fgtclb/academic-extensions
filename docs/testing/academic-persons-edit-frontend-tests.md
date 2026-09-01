# Frontend Tests for `academic-persons-edit`

The isolated Jest/jsdom test environment covers the production JavaScript
modules in
[`../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/frontend/`](../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/frontend/).
All repository paths in this document are relative to the location of this
file under `docs/testing/` unless a command explicitly changes the working
directory.

The tests load the production source code directly. There are no separate copies of the tested functions. This ensures that the tests cover the exact modules that TYPO3 will later use in the browser.

## Quick Start

From the directory containing this file:

```bash
cd ../../packages/fgtclb/academic-persons-edit/Resources/Public/Development
npm ci
npm test
```

A successful run starts with:

```text
Production JavaScript ES-module scope: OK
```

Afterward, all test suites and tests must pass. At the time of writing, the totals are:

```text
Test Suites: 9 passed, 9 total
Tests:       76 passed, 76 total
```

These numbers may increase as additional test cases are added. What matters is that no suite or test fails.

## Prerequisites

- Node.js `>= 18.14`
- a current npm version compatible with the installed Node.js version
- the `Development` and `JavaScript` directories as direct siblings under `Resources/Public`
- [`../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/package.json`](../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/package.json) containing `"type": "module"`

Node.js and npm should come from the same installation. You can check the programs and versions in use as follows:

```bash
which node
which npm
node -v
npm -v
```

On an Apple Silicon Mac with Homebrew, both programs should come from the same
Homebrew installation. Neither `npm ci` nor the test commands require `sudo`.

## Available Commands

| Command                                   | Purpose                                                                    |
|-------------------------------------------|----------------------------------------------------------------------------|
| `npm ci`                                  | Reproducibly installs the dependencies from `package-lock.json`.           |
| `npm test`                                | Verifies the ESM module scope and then runs all tests once.                |
| `npm run test:watch`                      | Starts Jest in watch mode and reruns affected tests when changes are made. |
| `npm run test:coverage`                   | Runs all tests and generates a coverage report.                            |
| `npm test -- tests/image.test.js`         | Runs only a specific test file.                                            |
| `npm test -- --testNamePattern="uploads"` | Runs only tests whose names match the specified pattern.                   |

The HTML coverage report is generated at `coverage/index.html`. On macOS, you can then open it with the following command:

```bash
open coverage/index.html
```

`node_modules/` and `coverage/` are not committed to version control.

## Test Run Sequence

`npm test` performs the following steps in order:

1. [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/scripts/verify-esm-environment.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/scripts/verify-esm-environment.js) reads [`../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/package.json`](../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/package.json) and verifies the `"type": "module"` entry.
2. The preliminary check natively imports a production frontend module with Node.js.
3. Jest starts with [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/jest.config.cjs`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/jest.config.cjs) and `--runInBand`.
4. jsdom provides a browser-like DOM environment for the tests.
5. [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/babel-jest-transformer.cjs`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/babel-jest-transformer.cjs) transforms ES modules to CommonJS exclusively within the test process.
6. The test files import and test the unmodified production source code.

The Babel transformation does not modify any files under `Resources/Public/JavaScript`. The code there remains a native browser ES module.

The former `--experimental-vm-modules` option is no longer used. Therefore, the corresponding `ExperimentalWarning` must not appear when running the current test command.

## Test Suites

| Test file                                                                                                                                                                                                    | Area covered                                                                                                                           |
|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------|
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/ckeditor.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/ckeditor.test.js)         | CKEditor configuration, resolution of the global editor instance, initialization, and polling                                          |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/common.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/common.test.js)             | Shared selectors and helper functions, profile IDs, status messages, JSON requests, and Bootstrap popover initialization               |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/documents.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/documents.test.js)       | Structured-section modal forms, record-title headings, help texts, popovers, five row actions, CRUD requests, sorting, and DOM updates |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/fields.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/fields.test.js)             | Field types, previews, validation, autosave, cancel, save, group actions, “Edit all,” and rich-text initialization                     |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/image.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/image.test.js)               | Image ratio and crop configuration, preview, upload, deletion, validation, modal states, and error handling                            |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/profile.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/profile.test.js)           | Basic initialization through the profile entry module                                                                                  |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/rich-text.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/rich-text.test.js)       | Rich-text detection, safe preview, sanitizing, character limits, editor lifecycle, initial values, and error handling                  |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/sticky-image.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/sticky-image.test.js) | Sticky offsets, `ResizeObserver` behavior, resize fallback, and cleanup on `pagehide`                                                  |
| [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/sync.test.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/sync.test.js)                 | Synchronization checkbox, persistence, applying the server value, and restoration after errors                                         |

The tests do not make real network requests and do not require a running TYPO3 instance. External browser dependencies such as `fetch`, Bootstrap, `ResizeObserver`, and CKEditor are simulated with controlled mocks.

## Directory Structure

```text
Development/
├── package-lock.json
├── package.json
├── jest.config.cjs
├── babel-jest-transformer.cjs
├── scripts/
│   └── verify-esm-environment.js
└── tests/
    ├── setup.js
    ├── mocks/
    │   └── ckeditor-modules.js
    └── *.test.js
```

The most important files serve the following purposes:

- [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/package.json`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/package.json) defines the Node.js version, dependencies, and npm commands.
- [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/jest.config.cjs`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/jest.config.cjs) configures jsdom, test paths, coverage, mocks, and transformation.
- [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/babel-jest-transformer.cjs`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/babel-jest-transformer.cjs) transforms the ES modules for Jest only.
- [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/scripts/verify-esm-environment.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/scripts/verify-esm-environment.js) detects an incorrect Node.js module scope before the actual test run.
- [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/setup.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/setup.js) adds missing browser features and resets the DOM and global mocks after each test.
- [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/mocks/ckeditor-modules.js`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/mocks/ckeditor-modules.js) replaces the CKEditor packages provided through TYPO3's browser import map.

## Extending the Tests

For a new or modified frontend module:

1. Create or extend the corresponding test file under [`../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/`](../../packages/fgtclb/academic-persons-edit/Resources/Public/Development/tests/).
2. Import the production code from `../../JavaScript/frontend/`, relative to the test file.
3. Set up only the HTML required for the test in jsdom.
4. Simulate network and browser APIs with controlled Jest mocks.
5. Cover successful cases, invalid input, and error paths.
6. Simulate asynchronous APIs realistically; Promise-based methods must also return a Promise in the mock.
7. Finally, run `npm test` and, for larger changes, `npm run test:coverage`.

The PHP architecture test
[`../../packages/fgtclb/academic-persons-edit/Tests/Unit/Architecture/FrontendJavaScriptTestEnvironmentTest.php`](../../packages/fgtclb/academic-persons-edit/Tests/Unit/Architecture/FrontendJavaScriptTestEnvironmentTest.php)
additionally verifies that each frontend module has its own Jest suite and that
exported functions are referenced in the corresponding test file.

## Troubleshooting

### `cb.apply is not a function`

This error is typically caused by a very old npm version being used with a newer Node.js version. First, check which programs are actually in use:

```bash
which node
which npm
node -v
npm -v
```

Node.js and npm must come from the same installation. Then run `npm ci` again.

### `Unexpected export statement in CJS module`

First, check whether the ESM marker is present and the current Jest configuration is being used:

```bash
grep -n '"type": "module"' ../JavaScript/package.json
grep -n 'verify-esm-environment' package.json
grep -n 'babel-jest-transformer.cjs' jest.config.cjs
```

Then run:

```bash
npm ci
npm test
```

The run must output `Production JavaScript ES-module scope: OK`. If this line is missing, an outdated version of the `Development` directory is being used.

### Stack Traces Show a Path Under `.Trash`

In this case, the terminal is still in a deleted or moved copy of the project. Check the current path:

```bash
pwd
```

Then explicitly switch to the active project directory and run the test there again.

### Individual Tests Fail After the ESM Preliminary Check

If the ESM preliminary check succeeds, the module loader is working. The first failed assertion or stack trace then points to an actual test, mock, or production error. For asynchronous methods in particular, verify that the mock reproduces the real return type—for example, a Promise instead of `undefined`.

## Success Criteria

A change is successful from the perspective of this test environment when:

- the ESM preliminary check succeeds,
- all Jest suites pass,
- no unhandled errors appear in the console output, and
- for relevant changes, the coverage report shows no unintended gaps.

## See also

- [Functional tests](functional-tests.md)
- [Unit tests](unit-tests.md)
- [Fixture extensions](fixture-extensions.md)
- [PHPUnit configuration](phpunit-configuration.md)
