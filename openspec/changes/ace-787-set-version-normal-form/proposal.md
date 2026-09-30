## Why

`bin/set-version` run with the version a branch already carries is not a
no-op on `main`. It rewrites 22 files it does not own, and every release and
every branch cut carries that into its commits (ACE-787). The tools behave as
they are meant to: packwright regenerates `ext_emconf.php` from its data, so a
comment disappears and a constraint it sets moves to the end of its list, and
`composer config` re-encodes `extra.typo3/cms`, so `"providesPackages": {}`
comes back as `[]`. What is wrong is the committed form of the files, which is
not the form the tools write.

## What Changes

- The files are stored in the form the tools write them:
  - the comments in five `ext_emconf.php` files are removed, and what they
    explained moves into `docs/`,
  - five `ext_emconf.php` files get their academic constraints in the order
    `bin/set-version` sets them,
  - `"providesPackages": {}` becomes `[]` in the twelve package manifests,
    which TYPO3 reads alike (it casts the value to an array and checks it with
    `isset()`, verified in 14.3.7).
- A new unit test in `packages-dev/monorepo-shared` holds that form: no comment
  in an `ext_emconf.php`, the academic constraints last and in the order
  `bin/set-version` sets them, no `"providesPackages": {}` in a manifest
  `bin/set-version` writes, and the `require` sections of a manifest with
  `sort-packages` in composer's order.
- `docs/` say why, and the pre-release checklist asks for a clean tree after a
  run with the current version.

Affected: all twelve extensions by their manifests, `academic_base`
(`academic-base`) and `academic_bite_jobs` (`academic-bite-jobs`) by their
`ext_emconf.php`, and the fixture extensions of `academic-base`,
`academic-persons-edit` and `academic-programs`.

Nothing changes at runtime on TYPO3 v13 or v14.

## Capabilities

### New Capabilities

None. The change is repository tooling and data, and sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

Two `ext_emconf.php` files of extensions and eight of fixtures, twelve
`composer.json`, one new test, `docs/`, `AGENTS.md`. `bin/set-version` and
packwright are not changed.

## Non-goals

- Changing packwright. Its purpose is to write the file it reads the same way
  again, and a lean `ext_emconf.php` is the form that lets it.
- A changelog entry: nothing an installation observes changes.
- Branch `2`, which gets the same through its own backport.
