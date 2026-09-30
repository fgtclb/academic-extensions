## 1. Branch version name and the alias writer

- [x] 1.1 Add `Build/Scripts/composerBranchVersion.sh` and
  `packages-dev/testing-helper/Tests/Unit/Build/ComposerBranchVersionTest.php`
  byte-identical to `main`. Shown red here as on `main`, by a script that
  prints `dev-<name>` for every branch.
- [x] 1.2 Apply the `bin/set-version` change of `main` (key through the helper,
  whole object, check, guard, help ending at the header), keeping the four
  per-branch lines of this branch. Verify that `diff` against `main`'s patched
  copy shows exactly those four lines, and that `--dry-run` refuses
  `--source-branch=v3` and `3.0.0` for `2`.
- [x] 1.3 Apply the `DocumentationGuidesTest` change, byte-identical to `main`.
  Verify it passes with `dev-2` and with `2.x-dev` in the root.

## 2. `bin/cut-branch`

- [x] 2.1 Apply the `edit-on-github-branch` writer of `bin/set-version`, and add
  `bin/cut-branch` with `SOURCE_BRANCH="2"` and `(default: 2)`. Verify that
  `diff` against `main`'s copies shows only the per-branch lines, and that a
  real run of `bin/set-version 2.4.0 post-release` on an export changes no
  `guides.xml`.
- [x] 2.2 `--dry-run` of `bin/cut-branch 2.4 2.5.0` in a scratch clone prints
  both phases with the key `2.4.x-dev` for the new branch and changes nothing.

## 3. The key data

- [x] 3.1 Verify on an export that the fixed `bin/set-version 2.4.0 dev` turns
  exactly the thirteen `"dev-2": "2.4.x-dev"` lines into
  `"2.x-dev": "2.4.x-dev"`, apart from formatting it does not own, and make
  exactly that edit in the thirteen files.
- [x] 3.2 Add `packages-dev/monorepo-shared/Tests/Unit/BranchAliasKeyTest.php`,
  byte-identical to `main`. Shown red against the unchanged `dev-2` data, green
  after 3.1.
- [x] 3.3 Refresh `core-12/composer.lock` and `core-13/composer.lock` only if
  the refresh changes exactly the 24 alias entries, and state the outcome.

## 4. Documentation

- [x] 4.1 `docs/workflow/releasing.md` of this branch: the default and key
  paragraph (which calls `dev-2` the intended state), the line references into
  `bin/set-version`, the edit branch, a `bin/cut-branch` section.
- [x] 4.2 `docs/testing/unit-tests.md` (manual links, the alias key test, the
  measured class count), `docs/testing/testing-helper.md`, `AGENTS.md` (four
  `monorepo-shared` tests, the script tests of `testing-helper`).
- [x] 4.3 The version tables of `README.md` and the twelve package READMEs as
  on `main`. Verify with `git grep -n '3\.x-dev' -- '*.md'`.
- [x] 4.4 `Documentation/Changelog/2.4/Important-BranchAliasKey.rst` in the
  twelve packages, identical to `main`, labels included (as ACE-731 did).

## 5. Definition of done

- [x] 5.1 `lintPhp` green.
- [x] 5.2 After `-t 12 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` (SQLite) green with `-t 12`.
- [x] 5.3 The same with `-t 13` after its own `composerUpdate`.
- [x] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green (render output
  removed before the lint).
- [x] 5.5 Commits as in design.md, TYPO3 Core format, each green on its own.
- [x] 5.6 Archive the change as the last commit of the pull request.
- [ ] 5.7 After the merge: `repo.packagist.org/p2/fgtclb/<pkg>~dev.json`
  records `{"2.x-dev": "2.4.x-dev"}` for all twelve, and a dry run of
  `composer require fgtclb/academic-persons:2.4.x-dev` resolves without an
  inline alias.
