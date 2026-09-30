## Why

This backports `ace-787-set-version-normal-form` of `main`. `bin/set-version`
run with the version this branch already carries rewrites five files it does
not own (ACE-787): packwright regenerates `ext_emconf.php` and moves the
constraints it sets to the end of their list, and `composer require` sorts the
`require` of the two instance manifests, which ask for `sort-packages` and are
not sorted. Every release carries that into its commits.

## What Changes

- The five files are stored in the form the tools write them:
  `academic-bite-jobs/ext_emconf.php` and the fixtures
  `test_programs_category_type_priority` and `test_programs_extra_category_type`
  of `academic-programs` get their academic constraints in the order
  `bin/set-version` sets them, `core-12/composer.json` and
  `core-13/composer.json` get a sorted `require`.
- The same `SetVersionNormalFormTest` as on `main` holds that form.
- `docs/` say why, and the pre-release checklist asks for a clean tree after a
  run with the current version.

Affected: `academic_bite_jobs` (`academic-bite-jobs`) by its `ext_emconf.php`,
the fixture extensions of `academic-programs`, the development instances.
Nothing changes at runtime on TYPO3 v12 or v13.

## Capabilities

### New Capabilities

None. Repository tooling and data, `skip_specs: true`.

### Modified Capabilities

None.

## Impact

Three `ext_emconf.php`, two instance manifests, one test, `docs/`,
`AGENTS.md`.

## Non-goals

- Changing `bin/set-version` or packwright.
- The comments and `providesPackages` of `main`: no `ext_emconf.php` here
  carries a comment, and no package manifest declares `providesPackages`.
