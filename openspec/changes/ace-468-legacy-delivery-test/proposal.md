## Why

`LegacyDeliveryTest` is the one check that the `/legacy/` tree of the
development seed, delivered through a root `sys_template` record, renders the
same pages as the `/` tree, delivered through site sets. On this branch it has
been skipped since ACE-460, so every change to the seed reaches the legacy tree
unverified. The skip and the documentation give a cause that was disproved
since (the TCA cache and `addStaticFile()`).

The actual cause is in the harness. The legacy root record names
`EXT:bootstrap_package/Configuration/TypoScript` as its only content rendering
template, and the functional suite does not install that theme. The entry
resolves to nothing, so the legacy tree never receives the content rendering
TypoScript the core appends after a content rendering template: no plugin
definition and no core content element definition. The `/` side of the test
already replaces the theme set with a set that depends on
`typo3/fluid-styled-content`, so only the legacy side loses its replacement.

## What Changes

- After the seed import, the test replaces the theme entry in the
  `include_static_file` of the legacy root `sys_template` row with
  `EXT:fluid_styled_content/Configuration/TypoScript`, in the same position,
  mirroring the substitution on the `/` side. It fails loudly when the seed no
  longer names the theme entry.
- The skip is removed. Both test methods run again on TYPO3 v13.
- The stale explanations are corrected: the class docblock and the skip comment
  of the test, the `LegacyDeliveryTest` section of
  `docs/testing/seed-verification.md`, and the comment above the theme entry in
  `Build/Scripts/generateLegacyScenario.php`, which claims that an unloaded
  entry costs nothing else.

No extension under `packages/fgtclb/` changes. The affected package is
`packages-dev/dev-site` (extension key `academics_dev_site`), which is never
released, and `Build/Scripts/`.

Core versions: the test is tagged `not-core-12` and runs on TYPO3 v13 only,
because the `core-12` instance has one tree and no site sets. On TYPO3 v12
nothing changes, and its gates stay green. The cause itself, a content
rendering template that is not loaded, behaves the same on both versions.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

None. The change sets `skip_specs: true`: it touches a functional test, a
comment of a build script and developer documentation. Nothing an editor,
integrator or visitor observes changes, and the seed, the generated
`ScenarioLegacy.yaml`, the SQLite templates and the seed manifests stay as
they are.

## Impact

- `packages-dev/dev-site/Tests/Functional/LegacyDeliveryTest.php`
- `Build/Scripts/generateLegacyScenario.php` (comment only, so the generated
  file is unchanged and `--check` stays clean)
- `docs/testing/seed-verification.md`

## Non-goals

- Adding `EXT:fluid_styled_content` to the seed record of this branch the way
  `main` names it. In the `core-13` instance it would sit beside the theme as a
  second content rendering template and change the markup of a real instance.
- Installing `bk2k/bootstrap-package` in the functional suite.
- Any change on `main`. Its legacy record names `fluid_styled_content` itself
  since ACE-460, and its `LegacyDeliveryTest` runs.
- Normalising new differences away. If the run with the substitution still
  reports a difference, it is analysed, not masked.
