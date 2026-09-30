## Context

See proposal.md and ACE-787 for the measurement. The mechanisms, verified:

- packwright 0.1.0 (`pkw`) reads `ext_emconf.php` into its data object,
  generates the file from it and writes when the result differs from the file
  on disk (`SetCommand.php:297-319` of packwright). Comments are not part of the
  data, so a file with a comment always differs and loses it.
  `extemconf:constraints:set --update-only` removes the key and appends it
  again, so it ends up last, even with an unchanged value.
- `bin/set-version` sets the constraints of every academic extension key in the
  order it discovers the packages, `packages/fgtclb/*/` (`bin/set-version:226`),
  `depends` then `suggests` per key. After one run the academic keys are the
  last entries of their list, in that order, and a second run leaves them there.
- `composer config extra.typo3/cms.version` re-encodes the `extra.typo3/cms`
  object, and an empty `providesPackages` object decodes to an empty PHP array.
  TYPO3 14.3.7 reads the value with `(array)` in `Package::loadFlagsFromComposerManifest()`
  and `PackageManager` line 439, and with `isset()` in
  `PackageManager::isComposerOnlyCapable()`, so `{}` and `[]` behave alike. The
  fixture manifests already carry `[]`, as `docs/testing/fixture-extensions.md`
  shows.

## Goals / Non-Goals

**Goals:**

- `bin/set-version` with the current version leaves a clean tree.
- A test that fails when a file drifts from that form again, in the unit
  container, where `pkw` and `tailor` are not available.

**Non-Goals:**

- Changing `bin/set-version` or packwright.

## Decisions

### Store the files as the tools write them

The five `ext_emconf.php` files that change order and the twelve manifests are
written by one real run of `bin/set-version 3.0.0 post-release` after the
comments are removed, so the committed form is the tools' own output rather
than a hand imitation of it. A second run is the check.

### Where the removed comments go

- `academic-base/ext_emconf.php` explained why `environment_state_manager` is a
  dependency. `docs/architecture/upgrade-checks.md` says it already.
- The four fixtures of `academic-base` explained why their `composer.json`
  keeps `version` and `Package.providesPackages`: from TYPO3 v14 on,
  `PackageManager::isComposerOnlyCapable()` merges `ext_emconf.php` into the
  manifest unless both are declared, and the upgrade check tests fail on v14
  alone. `docs/testing/fixture-extensions.md` gets that reason in its steps for
  adding a fixture, next to the rule that `ext_emconf.php` carries no comments.

### The test

`packages-dev/monorepo-shared/Tests/Unit/SetVersionNormalFormTest.php`, four
checks over the repository:

- no `T_COMMENT` or `T_DOC_COMMENT` token in any `ext_emconf.php` below
  `packages/fgtclb/`, fixtures included,
- in `depends` and `suggests` of those files, the keys of the academic
  extensions are the last entries, in the order of their package directories,
  read by evaluating the file with `$_EXTKEY` set,
- no `"providesPackages": {}` in `packages/fgtclb/*/composer.json` and
  `packages-dev/*/composer.json`, the manifests `bin/set-version` writes with
  `composer config`,
- every `composer.json` of the repository with `config.sort-packages` has its
  `require` and `require-dev` in the order composer sorts them, platform
  packages first, compared with `strnatcmp()` as composer's `JsonManipulator`
  does. That one holds on `main` today and fails on branch `2`.

Rejected: running `bin/set-version` inside a test, which needs `pkw`, `tailor`
and `composer` on the host. The test states the form, the pre-release checklist
asks for the run.

## Risks / Trade-offs

- [packwright or composer change their output in a later version] → the second
  run in the pre-release checklist shows it, and the test states which form is
  expected.
- [A new `ext_emconf.php` written by hand is not in packwright's exact form] →
  the test covers comments and key order, the parts that were found to move.
  `docs/testing/fixture-extensions.md` says to run `pkw extemconf:normalize` on
  a new file.

## Migration Plan

None.
