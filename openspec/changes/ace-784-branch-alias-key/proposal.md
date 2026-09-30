## Why

This backports `ace-784-branch-alias-key` of `main` (pull request #816,
archived there as `2026-09-30-ace-784-branch-alias-key`), and it is the branch
the defect actually hurts.

Composer applies an `extra.branch-alias` entry only when its key is the version
name it gives the branch, `2.x-dev` for this branch. The root `composer.json`
and all twelve `packages/fgtclb/*/composer.json` here carry `dev-2`, which
composer skips without a word, so `2.4.x-dev` does not exist on Packagist and
none of the eleven packages that require a sibling with `~2.4.0@dev` installs
from `2.x-dev`. `bin/set-version` writes that key and would put it back after a
hand fix.

## What Changes

- The same `Build/Scripts/composerBranchVersion.sh`, `bin/set-version` fix and
  `bin/cut-branch` as on `main`, with `2` as the default source branch and the
  version examples of this branch.
- `DocumentationGuidesTest` reads both key forms, new `BranchAliasKeyTest` and
  `ComposerBranchVersionTest`.
- The key becomes `2.x-dev` (`"2.x-dev": "2.4.x-dev"`) in the root and in all
  twelve package manifests, as its own commit after the test changes.
- The README version tables name `3.0.x-dev (dev-main)` and
  `2.4.x-dev (2.x-dev)`.
- `Documentation/Changelog/2.4/Important-BranchAliasKey.rst` in every package.

Affected: all twelve extensions, `academic_base` (`academic-base`),
`academic_bite_jobs` (`academic-bite-jobs`), `academic_contacts4pages`
(`academic-contact4pages`), `academic_jobs` (`academic-jobs`),
`academic_partners` (`academic-partners`), `academic_persons`
(`academic-persons`), `academic_persons_edit` (`academic-persons-edit`),
`academic_persons_sync` (`academic-persons-sync`), `academic_programs`
(`academic-programs`), `academic_projects` (`academic-projects`),
`academic_study_plan` (`academic-study-plan`), `category_types`
(`typo3-category-types`).

TYPO3 v12 and v13 behave alike, nothing changes at runtime. What an integrator
observes is composer resolution: `2.4.x-dev` and `~2.4.0@dev` resolve from the
branch once the split repositories and Packagist carry the change.

## Capabilities

### New Capabilities

None. The change sets `skip_specs: true`: it touches packaging metadata,
maintainer scripts, tests and documentation, no capability of an extension. The
integrator facing effect is recorded in the changelog entries.

### Modified Capabilities

None.

## Impact

`bin/`, `Build/Scripts/`, tests in `packages-dev/`, `docs/`, `AGENTS.md`, the
thirteen `composer.json` files, the READMEs and twelve changelog entries.

## Non-goals

- Branches `2.2` and `1`, which carry the same key pattern and are unmaintained.
- The formatting `bin/set-version` rewrites beyond the alias on a real run.
- A `Releasing` section in the root README, which this branch does not have.
