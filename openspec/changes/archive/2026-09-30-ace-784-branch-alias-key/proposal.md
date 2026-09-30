## Why

Composer applies an `extra.branch-alias` entry only when its key is the version
name composer gives the branch: `dev-main` for `main`, `2.x-dev` for a branch
called `2`. `bin/set-version` writes the key as `dev-<source-branch>`, which is
right for `main` and wrong for every numeric branch. On branch `2` the key
`dev-2` is ignored, and no split package with a `~2.4.0@dev` sibling
requirement installs from the branch. A hand edit would not last, the next
`post-release` run adds `dev-2` again. The next branch cut, done by hand today,
would repeat it.

## What Changes

- `bin/set-version` derives the alias key from the version name composer gives
  the source branch, replaces the whole alias object, and refuses a branch name
  composer names in two ways (`v3`) and a numeric key whose target is no
  sub-version of it. Nothing changes for `main`: its key stays `dev-main`.
- `bin/set-version` writes `edit-on-github-branch` of every
  `Documentation/guides.xml` from the same branch name, so the writer keeps what
  `DocumentationGuidesTest` checks.
- New `bin/cut-branch <branch> <next-version>` cuts a version branch in two
  pull requests, with the `--dry-run`/`--execute` model of `bin/release`, and
  ends with the steps it leaves to the maintainer.
- `DocumentationGuidesTest` reads the branch from both key forms. A new test
  checks that the thirteen alias objects are equal and carry the key composer
  gives their branch.
- The README version tables name the dev versions that exist: `3.0.x-dev` or
  `dev-main` for `main`, not `3.x-dev`.
- Every package gets `Documentation/Changelog/2.4/Important-BranchAliasKey.rst`,
  describing the fix that lands on branch `2`.

Affected: all twelve extensions, by their `composer.json` on branch `2`, their
README and their changelog. `academic_base` (`academic-base`),
`academic_bite_jobs` (`academic-bite-jobs`), `academic_contacts4pages`
(`academic-contact4pages`), `academic_jobs` (`academic-jobs`),
`academic_partners` (`academic-partners`), `academic_persons`
(`academic-persons`), `academic_persons_edit` (`academic-persons-edit`),
`academic_persons_sync` (`academic-persons-sync`), `academic_programs`
(`academic-programs`), `academic_projects` (`academic-projects`),
`academic_study_plan` (`academic-study-plan`), `category_types`
(`typo3-category-types`).

TYPO3 v13 and v14 are not affected at runtime. The change is packaging metadata,
maintainer scripts, tests and documentation.

## Capabilities

### New Capabilities

None. Nothing an editor, integrator or visitor observes changes on this branch,
and the change sets `skip_specs: true`. The integrator facing effect, that
`2.4.x-dev` becomes installable, lands on branch `2` through its own change.

### Modified Capabilities

None.

## Impact

`bin/`, `Build/Scripts/`, tests in `packages-dev/`, `docs/`, `AGENTS.md`, the
READMEs and twelve changelog entries. No `composer.json` changes on `main`.

## Non-goals

- The thirteen `composer.json` files on branch `2`. They change in the backport
  change there, after its own backport analysis.
- A `3.x-dev` alias key on `main`. Composer never applies it on a branch called
  `main`, and `dev-main` pointing at `3.x-dev` would break the `~3.0.0@dev`
  sibling constraints.
- Branches `2.2` and `1`. They carry the same key pattern and are unmaintained.
- Renaming the DDEV projects, the ruleset of the new branch, the `nightly.yml`
  branch list and the prose that names a version line. `bin/cut-branch` lists
  them, it does not write them.
- `bin/set-version` rewriting unrelated formatting (`providesPackages`,
  `ext_emconf.php` whitespace) on an unchanged version.
