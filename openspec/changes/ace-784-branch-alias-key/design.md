## Context

See proposal.md for the motivation. The facts the design rests on were
established in a lab with Composer 2.10.1 and against the published packages.

- `ArrayLoader::getBranchAlias()` applies an entry only if its key equals,
  case-insensitively, the version composer derived from the branch
  (`VcsRepository`: `dev-<name>` for a named branch, `<normalized>.x-dev` for a
  numeric one), the target ends in `-dev`, and for a numeric key the target is
  a sub-version of it. The first entry that passes all checks wins, the others
  are skipped without any notice. `composer validate --strict` accepts a key
  that can never match. A numeric key with a target outside it
  (`"2.x-dev": "3.0.x-dev"`) is the one invalid combination, and a validating
  reader drops the whole branch for it.
- `bin/set-version:282-287` is the only writer of the key:
  `composer config extra.branch-alias.dev-${SOURCE_BRANCH} <target>`. Composer
  adds that key to the object, so a key of another branch stays. It writes the
  root and the twelve `packages/fgtclb/*/composer.json`, in the modes
  `post-release` and `dev`.
- `bin/set-version` resolves `composer`, `php`, `tailor`, `pkw`, `jq` and `sed`
  before it does anything (`:133-139`). The unit container has no `pkw`, so the
  script cannot run in a test.
- `bin/release` only forwards `--source-branch`. Its phase 1 runs
  `set-version … release`, merges and pushes the tag, phase 2 runs
  `post-release` afterwards.
- `DocumentationGuidesTest::branch()` takes the one root alias key and throws
  unless it starts with `dev-`.
- The cut of branch `2` changed, by hand and in five commits over eleven weeks:
  the alias key (wrong), the `SOURCE_BRANCH` default of both scripts, the
  version examples on `main`, `edit-on-github-branch` of twelve manuals, the
  DDEV names.
- Ruleset `version-branches` covers `~DEFAULT_BRANCH`, `2` and `2.[3-9]` upward,
  `required_status_checks` covers `refs/heads/[0-9]` and its minor forms.

## Goals / Non-Goals

**Goals:**

- One place that knows how composer names a branch, used by the writer and by
  the tests.
- A writer that cannot leave a stale key behind.
- A branch cut whose mechanical part is a script, and whose remaining part is a
  printed list rather than memory.

**Non-Goals:**

- Making `bin/set-version` itself runnable in the unit container.
- Changing the `bin/release` flow.

## Decisions

### A standalone `Build/Scripts/composerBranchVersion.sh`

It prints the version name composer gives a branch: `dev-<name>` for a name that
is not numeric, `N.x-dev` for `N` and `N.x`, `N.M.x-dev` for `N.M` and `N.M.x`,
`N.M.P.x-dev` for `N.M.P`. It exits 1 for `vN`, `VN`, `N.M.P.Q` and `N.x.M`:
composer names `v2` `v2.x-dev` as a package and `2.x-dev` as the root package,
so no single key fits, and the others are shapes no branch here uses. The
mapping mirrors `VersionParser::normalizeBranch()` plus the `.9999999` to `.x`
replacement of `VcsRepository`, and was compared with composer for 27 names.

Rejected: a function inside `bin/set-version`, which no test could reach. A PHP
helper on `composer/semver`, which is not a declared dependency of any
`packages-dev` package and would make a shell script depend on an autoloader.

### Replace the whole alias object, then assert it

`apply_branch_alias()` builds `{"<key>": "<target>"}` with `jq` and writes it
with `composer config --json extra.branch-alias`. Composer then re-serializes
only that object: one changed line per file, 2 and 4 space indentation kept, key
order of `extra` kept. A `jq -e` comparison afterwards dies unless the file
carries exactly that object, because composer's fallback path, used when it
cannot edit in place, splits a dotted key into nested objects.

Rejected: adding the new key, which keeps `dev-2`. `--unset` of the old key
first, which re-serializes the whole `extra` node and collapses the multi-line
arrays of the root file.

### Resolve and check the key first, in every mode

The key is derived right after the arguments are validated, before the tools are
resolved, also in `release` mode, which writes no alias. `bin/release` pushes a
tag between its two `bin/set-version` runs, so an unusable branch name has to
stop the first one. The same place dies when a numeric key's prefix is not a
prefix of the target (`--source-branch=2` with version `3.0.0`), the one
combination a reader answers by dropping the branch. A key equal to its target
(`2.2.x-dev` on a branch `2.2`) is written: composer accepts it, and one code
path keeps the one-alias invariant.

### `bin/set-version` writes `edit-on-github-branch`

In every mode, from `--source-branch`, in every
`packages/fgtclb/*/Documentation/guides.xml`, with an assertion that each file
carries the attribute exactly once with that value. Alias key and edit branch
then come from the same input, and the invariant `DocumentationGuidesTest`
checks is kept by the writer. On an ordinary run it changes nothing. Rejected:
writing it only in `bin/cut-branch`, which leaves a manual rename of a branch
unguarded.

### `bin/cut-branch <branch> <next-version>`

A third standalone script, same structure and safety model as `bin/release`:
`--dry-run` prints everything, the default runs local steps and prints remote
ones, `--execute` runs the remote ones. `--source-branch` defaults to the branch
the copy lives on. It has no version example in its help, so its copies differ
between branches in the default only.

Guards before anything is written: clean tree, the source branch up to date,
the new branch numeric through `composerBranchVersion.sh`, the twelve `VERSION`
files agreeing on one `-dev` version that lies inside the new branch (`3.0.0-dev`
for `3`), the next version above and outside it (`4.0.0`).

Phase 1: `git push origin <source-sha>:refs/heads/<branch>` creates the branch
at a commit that passed CI. If `origin/<branch>` already points at that commit,
the push is skipped, so a branch created by hand or a second run continues. A
local `cut-<branch>` then runs `bin/set-version <current> dev
--source-branch=<branch>` and rewrites the default of the three scripts, the
`SOURCE_BRANCH=` line and the `(default: <branch>)` of the help, asserting two
changed lines each. Commit `[TASK] Prepare branch
<branch>`, pull request against the new branch, checks, admin merge.

Phase 2: `set-version-<next>` from the source runs `bin/set-version <next> dev`
and replaces the version example, read from the help of `bin/set-version`,
where it is written as `e.g. <version>` in `bin/set-version` and `bin/release`,
asserting the count. Commit `[TASK] Set version <next>`, pull request, checks,
admin merge.

It ends with the checklist of what it does not write: the ruleset of the new
branch (a GitHub admin write), the `nightly.yml` branch list (a CI cost
decision), the DDEV names of the source branch and the prose that names a
version line (a docs change of their own), the next core versions of the source
branch, YouTrack versions and the Packagist check.

Rejected: a `branch` mode of `bin/set-version`, which leaves git and pull
requests to memory, the part that went wrong. A shared shell library sourced by
the three scripts: none exists, and each script stays readable on its own as
today.

### Tests

- `packages-dev/testing-helper/Tests/Unit/Build/ComposerBranchVersionTest.php`
  runs the script for the mapping table and the refusals, in the shape of
  `DdevWorktreeNamesTest`.
- `packages-dev/monorepo-shared/Tests/Unit/BranchAliasKeyTest.php`: the root
  and the twelve packages carry the same alias object with exactly one entry,
  the key round-trips through `composerBranchVersion.sh`, and a numeric key's
  target lies inside it. This is the data invariant `d8fcd06d3` broke.
- `DocumentationGuidesTest::branch()` maps `dev-<name>` to `<name>` and
  `<version>.x-dev` to `<version>`. `2.x-dev` could also be a branch named
  `2.x`. The test decides for the bare number, the naming every branch here
  uses.
- `bin/set-version` and `bin/cut-branch` need `gh`, `pkw`, `tailor` and a
  remote. They are verified by `--dry-run` and by a default-mode rehearsal in a
  scratch clone below `.agent/tmp/`, never by `--execute`.

## Risks / Trade-offs

- [The push that creates the branch meets `required_status_checks` on
  `refs/heads/[0-9]`] → it pushes a commit that already has its checks, and a
  refused push is resumable: create the branch by hand, run again.
- [The admin merge needs the bypass `bin/release` already relies on] → same
  gate, same maintainer.
- [`composerBranchVersion.sh` mirrors composer and could drift] → the rule has
  been stable since composer 1.0.0-alpha10 (2015), and the test table documents
  the expected names.
- [`bin/set-version` rewrites unrelated formatting on a real run] → known and
  out of scope, the rehearsal compares against the unmodified script so the
  change adds no new noise.

## Migration Plan

Nothing to migrate on `main`: its alias key stays `dev-main`. Branch `2` gets
the same scripts and tests, plus the corrected key in thirteen files, through
its own change. After the merge on `2`, the Packagist metadata of the twelve
packages has to record `{"2.x-dev": "2.4.x-dev"}`, and
`composer require fgtclb/academic-persons:2.4.x-dev` has to resolve without an
inline alias.
