# Frontend Tests for `academic-persons-edit`

This directory contains the isolated Jest/jsdom test environment for the production JavaScript modules in `../JavaScript/frontend/`.

The tests load the production source code directly. There are no separate copies of the tested functions. This ensures that the tests cover the exact modules that TYPO3 will later use in the browser.

## Quick Start

From the repository root:

```bash
cd packages/fgtclb/academic-persons-edit/Resources/Public/Development
npm install
npm test
```

A successful run starts with:

```text
Production JavaScript ES-module scope: OK
```

Afterward, all test suites and tests must pass. At the time of writing, the totals are:

```text
Test Suites: 8 passed, 8 total
Tests:       60 passed, 60 total
```

These numbers may increase as additional test cases are added. What matters is that no suite or test fails.

## Prerequisites

- Node.js `>= 18.14`
- a current npm version compatible with the installed Node.js version
- the `Development` and `JavaScript` directories as direct siblings under `Resources/Public`
- `../JavaScript/package.json` containing `"type": "module"`

Node.js and npm should come from the same installation. You can check the programs and versions in use as follows:

```bash
which node
which npm
node -v
npm -v
```

On an Apple Silicon Mac with Homebrew, both paths normally start with `/opt/homebrew/`. Neither `npm install` nor the test commands require `sudo`.

## Available Commands

| Command | Purpose |
| --- | --- |
| `npm install` | Installs or updates the local test dependencies. |
| `npm test` | Verifies the ESM module scope and then runs all tests once. |
| `npm run test:watch` | Starts Jest in watch mode and reruns affected tests when changes are made. |
| `npm run test:coverage` | Runs all tests and generates a coverage report. |
| `npm test -- tests/image.test.js` | Runs only a specific test file. |
| `npm test -- --testNamePattern="uploads"` | Runs only tests whose names match the specified pattern. |

The HTML coverage report is generated at `coverage/index.html`. On macOS, you can then open it with the following command:

```bash
open coverage/index.html
```

`node_modules/` and `coverage/` are not committed to version control.

## Test Run Sequence

`npm test` performs the following steps in order:

1. `scripts/verify-esm-environment.js` reads `../JavaScript/package.json` and verifies the `"type": "module"` entry.
2. The preliminary check natively imports a production frontend module with Node.js.
3. Jest starts with `jest.config.cjs` and `--runInBand`.
4. jsdom provides a browser-like DOM environment for the tests.
5. `babel-jest-transformer.cjs` transforms ES modules to CommonJS exclusively within the test process.
6. The test files import and test the unmodified production source code.

The Babel transformation does not modify any files under `Resources/Public/JavaScript`. The code there remains a native browser ES module.

The former `--experimental-vm-modules` option is no longer used. Therefore, the corresponding `ExperimentalWarning` must not appear when running the current test command.

## Test Suites

| Test file | Area covered |
| --- | --- |
| `tests/ckeditor.test.js` | CKEditor configuration, resolution of the global editor instance, initialization, and polling |
| `tests/common.test.js` | shared selectors and helper functions, profile IDs, status messages, and JSON requests |
| `tests/fields.test.js` | field types, previews, validation, autosave, cancel, save, group actions, “Edit all,” and rich-text initialization |
| `tests/image.test.js` | image preview, upload, deletion, validation, modal states, and error handling |
| `tests/profile.test.js` | initialization of the profile components through the entry module |
| `tests/rich-text.test.js` | rich-text detection, safe preview, editor lifecycle, initial values, and error handling |
| `tests/sticky-image.test.js` | sticky offsets, `ResizeObserver` behavior, resize fallback, and cleanup on `pagehide` |
| `tests/sync.test.js` | synchronization checkbox, persistence, applying the server value, and restoration after errors |

The tests do not make real network requests and do not require a running TYPO3 instance. External browser dependencies such as `fetch`, Bootstrap, `ResizeObserver`, and CKEditor are simulated with controlled mocks.

## Directory Structure

```text
Development/
├── README.md
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

- `package.json` defines the Node.js version, dependencies, and npm commands.
- `jest.config.cjs` configures jsdom, test paths, coverage, mocks, and transformation.
- `babel-jest-transformer.cjs` transforms the ES modules for Jest only.
- `scripts/verify-esm-environment.js` detects an incorrect Node.js module scope before the actual test run.
- `tests/setup.js` adds missing browser features and resets the DOM and global mocks after each test.
- `tests/mocks/ckeditor-modules.js` replaces the CKEditor packages provided through TYPO3's browser import map.

## Extending the Tests

For a new or modified frontend module:

1. Create or extend the corresponding test file under `tests/`.
2. Import the production code directly from `../../JavaScript/frontend/`.
3. Set up only the HTML required for the test in jsdom.
4. Simulate network and browser APIs with controlled Jest mocks.
5. Cover successful cases, invalid input, and error paths.
6. Simulate asynchronous APIs realistically; Promise-based methods must also return a Promise in the mock.
7. Finally, run `npm test` and, for larger changes, `npm run test:coverage`.

The PHP architecture test `Tests/Unit/Architecture/FrontendJavaScriptTestEnvironmentTest.php` additionally verifies that each frontend module has its own Jest suite and that exported functions are covered in the corresponding test file.

## Troubleshooting

### `cb.apply is not a function`

This error is typically caused by a very old npm version being used with a newer Node.js version. First, check which programs are actually in use:

```bash
which node
which npm
node -v
npm -v
```

Node.js and npm must come from the same installation. Then run `npm install` again.

### `Unexpected export statement in CJS module`

First, check whether the ESM marker is present and the current Jest configuration is being used:

```bash
grep -n '"type": "module"' ../JavaScript/package.json
grep -n 'verify-esm-environment' package.json
grep -n 'babel-jest-transformer.cjs' jest.config.cjs
```

Then run:

```bash
npm install
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
