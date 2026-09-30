## Context

See proposal.md for the motivation, and the design of the `main` change
(`openspec/changes/archive/2026-09-30-ace-784-branch-alias-key/design.md`
there) for the decisions. This document holds the backport analysis: what is
the same here, what differs, and how each difference is handled.

File-level diff of every file the `main` change touches, between `main` before
the change (`9b773d2c0`) and `origin/2` (`12f5c328b`):

| File                                                           | Difference on `2`                                                                                        | Consequence                                                 |
|----------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|-------------------------------------------------------------|
| `bin/set-version`                                              | the four per-branch lines only (default `2`, examples `2.4.0`)                                           | same patch, the rewritten help line keeps `(default: 2)`    |
| `bin/release`                                                  | the five per-branch lines only                                                                           | untouched, as on `main`                                     |
| `bin/cut-branch`                                               | new                                                                                                      | same file, `SOURCE_BRANCH="2"` and `(default: 2)`           |
| `Build/Scripts/composerBranchVersion.sh`                       | new                                                                                                      | same file                                                   |
| `DocumentationGuidesTest.php`                                  | identical                                                                                                | same patch                                                  |
| `DdevWorktreeNamesTest.php`                                    | the DDEV names only                                                                                      | the new script test follows the same shape unchanged        |
| `packages-dev/monorepo-shared/Tests/Unit/`                     | three tests, `main` has five                                                                             | `BranchAliasKeyTest` is the fourth here                     |
| `docs/workflow/releasing.md`                                   | its own text: the default is `2`, the key `dev-2` is stated as the intended state, other line references | re-derived here, not copied                                 |
| `docs/testing/unit-tests.md`, `testing-helper.md`, `AGENTS.md` | other counts and sections                                                                                | re-derived here                                             |
| `README.md`                                                    | no "Releasing (maintainers)" section                                                                     | the `bin/cut-branch` summary of `main` has no place here    |
| thirteen `composer.json`                                       | key `dev-2`                                                                                              | becomes `2.x-dev`, the one data change `main` does not have |
| `core-12/composer.lock`, `core-13/composer.lock`               | record `dev-2` for the twelve path packages                                                              | see below                                                   |

Composer resolution is the same on both branches (Composer 2.10.1, the rule
unchanged since 1.0.0-alpha10). PHP 8.1, the floor here, runs every line: the
newest constructs are `str_starts_with()` and `array_key_first()`. PHPUnit
attributes are in use in the tests here already.

## Goals / Non-Goals

**Goals:**

- The scripts and `DocumentationGuidesTest` stay identical to `main` apart from
  the per-branch lines, so the next change carries over unchanged.
- The data change lands after the test that guards it, and each commit is
  green on its own.

**Non-Goals:**

- Anything `main` did not do, apart from the key data.

## Decisions

### The thirteen keys are edited to the script's output, not by a script run

A real `bin/set-version 2.4.0 dev` run writes the key, and also re-sorts a
require line of both instance manifests and changes whitespace in an
`ext_emconf.php`, formatting it does not own. The commit carries only the
thirteen alias lines, and a run of the fixed script on an export of the branch
shows that it writes exactly those lines for the alias.

### The instance lock files

`core-12/composer.lock` and `core-13/composer.lock` record the `extra` of the
path packages, `dev-2` included. No gate reads them and the alias is inert for
a path package, whose version comes from the package itself. They are refreshed
in the data commit only if the refresh changes exactly those 24 entries, and
left otherwise. The pull request says which.

### Commit order

1. `[BUGFIX] ACE-784: Key branch alias to composer name`: helper, script,
   `ComposerBranchVersionTest`, `DocumentationGuidesTest` (which accepts
   `dev-2` and `2.x-dev` alike), docs. `BranchAliasKeyTest` is red against
   `dev-2`, so it cannot land here: it goes into the data commit.
2. `[TASK] ACE-784: Add a script to cut a version branch`.
3. `[BUGFIX] ACE-784: Use 2.x-dev as branch alias key`: thirteen keys,
   `BranchAliasKeyTest`, and the lock files if they qualify.
4. README tables, 5. changelog entries, 6. archive.

## Risks / Trade-offs

- [The split repositories and Packagist pick the key up only after the merge
  here] → verified after the merge with the p2 metadata of all twelve packages
  and a dry-run install of `2.4.x-dev` without an inline alias.
- [`~2.4.0@dev` resolves only until the next `post-release` run moves the
  constraint, if the key were wrong again] → `BranchAliasKeyTest` keeps it right.

## Migration Plan

None. After the merge, a project that added `2.x-dev as 2.4.x-dev` inline
aliases can drop them, as the changelog entries say.
