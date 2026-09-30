# Releasing

A release of this repository is a release of **fifteen packages at once**:
twelve TYPO3 extensions under `packages/fgtclb/` and the three packages under
`packages-dev/`. They share a single version number, they are tagged together,
and twelve of them end up in the TER — but never from here. The repository root
is a composer `project`, not an extension, and has nothing to publish.

Two scripts do the work, and neither of them is optional knowledge: `bin/set-version`
writes the version everywhere, `bin/release` drives git and GitHub around it.
A third, `bin/cut-branch`, cuts a new version branch with the same two.

## Where the version lives

Every package carries its own version. There is no central version file, and —
since the path repositories were reworked — no version map anywhere either:

| File                                                  | Value today | Written by           |
|-------------------------------------------------------|-------------|----------------------|
| `composer.json` → `extra.typo3/cms.version`           | `2.4.0-dev` | `composer config`    |
| `VERSION`                                             | `2.4.0-dev` | plain write          |
| `ext_emconf.php` → `version`                          | `2.4.0`     | `pkw extemconf:set`  |
| `Documentation/guides.xml` → `release`                | `2.4.0`     | `tailor set-version` |
| `Build/Scripts/runTests.sh` → `COMPOSER_ROOT_VERSION` | `2.4.0-dev` | `sed`, root only     |

The `-dev` suffix appears in the composer-facing files and not in the two TYPO3
facing ones, because `ext_emconf.php` and `guides.xml` express a released
version, not a range.

The three `packages-dev/*` packages are **not** an exception:
`fgtclb/academics-monorepo-shared`,
`fgtclb/academics-monorepo-testing-helper` and
`fgtclb/academics-monorepo-dev-site` all carry `extra.typo3/cms.version` and a
`VERSION` file, all at `2.4.0-dev`. None of them has an `ext_emconf.php`, none
is released, and none is ever published — but they are path packages, so their
version has to be right for the same reason. That `dev-site` is a
`typo3-cms-extension` rather than a `library` changes nothing here: the type is
what makes `EXT:academics_dev_site/…` resolvable, not a statement about
shipping it.

**Why this matters:** every `composer.json` in this repository that declares a
`path` repository requires the composer plugin `sbuerk/extended-path-repository`,
which derives the version of a path package **from the package itself** — from
exactly those three places. Before it, each consuming `composer.json` carried a
`repositories.*.options.versions` map naming the version of every path package,
and every bump had to be mirrored into every map. Those maps are gone. Write the
version on the package and it is correct in the root project, in the sibling
extensions, in the `packages-dev` packages and in the `core-12`/`core-13`
development instances.

The constraint is `^1.1.0` — in the root `composer.json`, in
`packages-dev/monorepo-shared/composer.json` and in both development instances,
and the same on `main`. The version installed here is `1.1.0`, and it requires
PHP `^8.1`, which is what this branch needs. Do not lower the constraint without
checking that the older release still supports PHP 8.1.

## `bin/set-version` — apply a version across the repository

```shell
bin/set-version <version> <type> [--source-branch=<name>] [--dry-run]
```

It edits working-tree files and does **nothing else**: no git, no network
(`bin/set-version:44-45`). That separation is what makes it safe to run and
inspect on its own.

`<version>` is always a bare `MAJOR.MINOR.PATCH`; the `-dev` suffixes are
derived, never passed. `<type>` decides how (`bin/set-version:184-208`):

| Type           | Package version | `ext_emconf` | Academic dependency constraint | Branch alias |
|----------------|-----------------|--------------|--------------------------------|--------------|
| `release`      | `X.Y.Z`         | `X.Y.Z`      | `X.Y.Z@dev`                    | not written  |
| `post-release` | `X.Y.Z-dev`     | `X.Y.Z`      | `~X.Y.Z@dev`                   | `X.Y.x-dev`  |
| `dev`          | `X.Y.Z-dev`     | `X.Y.Z`      | `~X.Y.Z@dev`                   | `X.Y.x-dev`  |

`post-release` and `dev` share one derivation (`bin/set-version:195-208`); `dev`
is the thin variant used for branching and forced minor or major bumps.
`post-release` does **not** increment anything — the version passed is already
the next one.

What one run rewrites, in order (`bin/set-version:359-455`):

1. `Build/Scripts/runTests.sh` → `COMPOSER_ROOT_VERSION`
2. every split extension → academic composer dependencies,
   `extra.typo3/cms.version`, branch alias, `tailor set-version`, `VERSION`
3. functional-test fixture extensions → composer dependencies only
4. every `ext_emconf.php` (splits **and** fixtures) → `version`, plus the
   `depends`/`suggests` constraints for each academic extension key
5. `packages-dev/*` → academic dependencies, `extra.typo3/cms.version`, `VERSION`
6. `core-*/` development instances → the `academics-monorepo-shared` requirement
   (`core-12/` and `core-13/` here)
7. root `composer.json` → the `academics-monorepo-shared` requirement and the
   branch alias

Nothing in that list is hardcoded. The package set is discovered by looking for
directories under `packages/fgtclb/*/` that carry both a `composer.json` and an
`ext_emconf.php` (`bin/set-version:226-237`); the extension key is read from
`extra.typo3/cms.extension-key`, never guessed from the directory name; fixture
extensions are found under `Tests/Functional/Fixtures/Extensions`
(`bin/set-version:242-247`); the `packages-dev` packages and the development
instances are discovered by path, `for dir in packages-dev/*/`
(`bin/set-version:425`) and `for dir in core-*/` (`bin/set-version:440`). A
thirteenth extension, a fourth `packages-dev` package or a `core-14` instance is
picked up by existing, which is precisely what the previously hardcoded instance
list failed to do — and is how `packages-dev/dev-site/` was versioned correctly
the moment it was added.

`--dry-run` prints every change without touching a file and is the way to
rehearse a bump. `--source-branch=<name>` names the branch the version is
applied on. **On this branch it defaults to `2`** (`bin/set-version:85`), which
is what a `2.x` release wants, so the flag is not needed here.

The branch alias is keyed to the version name composer gives that branch:
`2.x-dev` for `2`, `dev-main` for `main`, `2.2.x-dev` for `2.2`.
`Build/Scripts/composerBranchVersion.sh` prints it (`bin/set-version:125-142`).
Composer applies an alias only under that key and skips any other without a
word, and `composer validate --strict` accepts it as well. This branch carried
`dev-2` from its cut until ACE-784, so `2.4.x-dev` did not exist and no split
package requiring a sibling with `~2.4.0@dev` installed from it. The script
replaces the whole alias object, so a key written for another branch cannot
survive, and it stops before it touches anything when composer names the branch
in two ways (`v3`) or when the version lies outside a numeric branch (`3.0.0`
for `2`). A reader that validates the package drops the whole branch for such
an alias.

The copy of the script on `main` defaults to `main`. That is the whole
difference between the copies: a
`git diff origin/2 origin/main -- bin/set-version bin/release bin/cut-branch`
shows eleven changed lines, the `--source-branch` default of the three scripts,
in the variable and in the help, and the version used as an example in the help
and error texts. Keep it that way, a real change to any of them is backported
like any other.

The `edit-on-github-branch` of every `Documentation/guides.xml` names the
branch the manual is edited on, `2` here and `main` there, and is written from
`--source-branch` as well. The branch alias and those attributes have to agree,
and a unit test fails until they do, see
[Unit tests](../testing/unit-tests.md#the-links-of-the-manuals). On an ordinary
run neither changes.

## `bin/release` — orchestrate the release

```shell
bin/release <release-version> [--source-branch=<name>] [--dry-run|--execute]
```

It owns git and `gh` and delegates all version rewriting to `bin/set-version`
(`bin/release:20-21`). One invocation runs two phases:

**Phase 1 — release** (`bin/release:213-232`)

1. `git checkout -b release-X.Y.Z <source-branch>`
2. `bin/set-version X.Y.Z release`
3. commit `[RELEASE] X.Y.Z`, push, `gh pr create --fill`
4. `gh pr checks --watch --interval 10 --fail-fast`
5. `gh pr merge --rebase --delete-branch --admin`
6. back on the refreshed source branch: `git tag X.Y.Z` and push the tag

**Phase 2 — post-release** (`bin/release:237-251`), with `W = Z + 1`

1. `git checkout -b set-version-X.Y.W <source-branch>`
2. `bin/set-version X.Y.W post-release`
3. commit `[TASK] Set version X.Y.W`, push, PR, checks, admin rebase-merge

The next development version is derived as patch + 1 (`bin/release:144`). A
minor or major bump afterwards is a separate `bin/set-version … dev` run, not
something `bin/release` decides.

### Two independent safety gates

| Invocation  | Local steps (branch, set-version, commit) | Remote and irreversible steps (push, PR, merge, tag) |
|-------------|-------------------------------------------|------------------------------------------------------|
| *(bare)*    | executed                                  | **only printed**                                     |
| `--dry-run` | printed                                   | printed                                              |
| `--execute` | executed                                  | executed                                             |

A bare run can therefore never mutate the remote or create a tag
(`bin/release:76-89`). The two flags are mutually exclusive
(`bin/release:129-131`). This matters more than it looks: the failure mode of a
release script is not a wrong file, it is a pushed tag that cannot be taken
back.

### What it refuses to do

Pre-flight (`bin/release:178-200`):

* not inside a git work tree → abort,
* the tag `X.Y.Z` already exists locally → abort, always, in every mode
  ("refusing to re-release"),
* the working tree is dirty → fatal for `--execute`, a warning otherwise, so the
  flow stays rehearsable,
* a version that is not `MAJOR.MINOR.PATCH` → abort.

Tooling is resolved and verified up front, before anything changes:
`bin/set-version` needs `composer`, `php`, `tailor`, `pkw`, `jq` and `sed` on
`PATH` (`bin/set-version:156-162`); `bin/release` additionally needs `git` and an
authenticated `gh` (`bin/release:153-162`).

## `bin/cut-branch`: cut a version branch

```shell
bin/cut-branch <branch> <next-version> [--source-branch=<name>] [--dry-run|--execute]
```

A version branch is cut when the source branch moves on to the next major or
minor version and the current one stays maintained: `bin/cut-branch 2.4 2.5.0`
here leaves `2.4.x` on a branch `2.4` and moves `2` to `2.5.0-dev`, and
`bin/cut-branch 3 4.0.0` does the same for `main`. This branch was cut by hand,
in five commits over eleven weeks, and got the branch alias key wrong (ACE-784),
the default of both scripts (ACE-249) and the edit branch of the manuals
(ACE-773). The script takes over what is mechanical. It has the
safety gates of `bin/release`, see [above](#two-independent-safety-gates).

**Phase 1, the new branch** (`bin/cut-branch:303-324`)

1. `git push origin <source commit>:refs/heads/<branch>`, skipped when the
   branch already exists on `origin` at that commit, so a branch created by
   hand or an interrupted run continues
2. `git checkout -b cut-<branch> <source commit>`
3. `bin/set-version <current version> dev --source-branch=<branch>`: the branch
   alias becomes `<branch>.x-dev`, the edit branch of the manuals `<branch>`
4. the `--source-branch` default of `bin/set-version`, `bin/release` and
   `bin/cut-branch` becomes `<branch>`, two lines each
5. commit `[TASK] Prepare branch <branch>`, push, PR against the new branch,
   checks, admin rebase-merge

**Phase 2, the source branch** (`bin/cut-branch:329-344`)

1. `git checkout -b set-version-<next-version> <source commit>`
2. `bin/set-version <next-version> dev`
3. the version example in the help of `bin/set-version` and `bin/release`
   becomes `<next-version>`
4. commit `[TASK] Set version <next-version>`, push, PR, checks, admin
   rebase-merge

It refuses before anything is touched (`bin/cut-branch:196-230`, `:264-298`) a
branch name that is not `MAJOR` or `MAJOR.MINOR`, versions that do not agree
across the twelve `VERSION` files or are no dev version, a current version
outside the new branch, a next version inside it or below the current one, a
next version outside a numeric source branch, an existing local branch of the
three it creates, a branch on `origin` at another commit, and a dirty tree
unless it is a dry run.

It ends with what it leaves to the maintainer, because each needs a permission
or a judgement the script must not have:

- the GitHub ruleset `version-branches`, which covers `2` and `2.[3-9]` upward
  but no branch `3` yet, and protects against deletion and force pushes
- the branch list of `.github/workflows/nightly.yml`, which lives on `main`
- the DDEV project names of the source branch and the pages that name them
- the prose that names a version line: the README version tables, `AGENTS.md`,
  `docs/Index.md`, [Backporting](backporting.md), `openspec/config.yaml`
- the TYPO3 core versions the source branch supports next
- the YouTrack versions, and the new branch on Packagist once the splitter
  carried it over

## The tag has to match `ext_emconf.php`

The root `publish` workflow builds the TER artifacts with
`tailor create-artefact <version> <extension-key>` in its *Create local TER
package upload artifact* step, and **that command fails when the
version does not match the extension's `ext_emconf.php`**. This is a feature, not
an obstacle: it makes it impossible for a release to disagree with the extension
metadata that TYPO3 and the TER read.

`bin/set-version` is what keeps the two in sync — step 4 above writes
`ext_emconf.php` in the same run that writes everything else. All twelve
extensions currently declare `'version' => '2.4.0'`, so the tag for the next
release from this branch is `2.4.0`.

The workflow additionally rejects a tag that is not a bare
`MAJOR.MINOR.PATCH` before doing any work, in its *Verify tag* step. There is
no `v` prefix — `bin/release`
creates `2.4.0`, not `v2.4.0`.

Note that the trigger is `tags: ['*']`, not a version pattern: the shape check
in the job is what rejects everything else. A tag pushed from this branch and a
tag pushed from `main` run the same workflow, each against the tree it points
at.

## The three-step publishing chain

```
tag X.Y.Z pushed to fgtclb/academic-extensions
  |
  |  1. this repository, .github/workflows/publish.yml
  |     tailor create-artefact, once per extension  ->  GitHub release
  |     no TER upload
  v
  |  2. external splitter
  |     mirrors the tagged state into the twelve read-only split
  |     repositories, tag included
  v
  |  3. each split repository, its own .github/workflows/publish.yml
  |     tailor create-artefact  ->  GitHub release  ->  tailor ter:publish
  v
 published on extensions.typo3.org, one extension at a time
```

**Step 1 — here.** `.github/workflows/publish.yml` triggers on any pushed tag,
verifies its shape, installs `typo3/tailor` with an authenticated composer (see
below), then loops over `packages/fgtclb/*`, reads each extension key from that
package's `composer.json` at runtime, and builds one artifact per extension. The keys are
read rather than derived from the directory name because they differ:
`academic-contact4pages` ships `academic_contacts4pages`. Finally
`softprops/action-gh-release` creates the release `[RELEASE] <version>` with
generated notes and attaches all artifacts plus `LICENSE`, failing on an
unmatched file.

This workflow contains **no** `ter:publish` step, and that is deliberate; the
header comment of the file says why. There is no `academic_extensions`
extension to publish.

A *Render documentation of all academic extensions* step exists in the file but
is commented out; the rendered manual is currently
produced only by the CI workflow on pull requests, see
[Changelog and documentation](changelog-and-documentation.md).

**Step 2 — the splitter.** An external splitting setup mirrors this
repository's packages into their standalone read-only repositories
(`fgtclb/academic-persons`, `fgtclb/typo3-category-types`, …), tags included.
The split repositories are never a source of truth and are never committed to
directly.

**Step 3 — per extension.** Each package ships its own publish workflow at
`packages/fgtclb/<pkg>/.github/workflows/publish.yml` — all twelve have one.
When the tag arrives in the split repository, that workflow builds the single
artifact for its extension, creates the release there, and runs:

```yaml
tailor ter:publish --comment "<link to the GitHub release>" <version> \
  --artefact=tailor-version-artefact/<key>_<version>.zip
```

Because it lives inside the package, it is **split out with the package**. That
is the whole point: TER publishing is maintained here and never edited
downstream — a change made in a split repository would be overwritten by the
next split. The reference copy for new packages is
`Build/templates/extensions/.github/workflows/publish.yml`, which the shipped
copies currently differ from only in the `actions/checkout` version (`v6` in the
packages, `v4` in the template).

Both publish workflows declare the `TYPO3_API_TOKEN` secret and grant
`contents: write`; only the per-package one actually spends that secret, in
`ter:publish`.

Both also set a workflow level `COMPOSER_AUTH` from `github.token`, because
`composer global require typo3/tailor` runs on the runner host rather than in a
container — [`Build/Scripts/runTests.sh`](../../Build/Scripts/runTests.sh) never
sees it, so there is nothing to forward and the workflow has to carry the token
itself. Unauthenticated it shares the 60 requests per hour and per runner IP
that took out the pull-request pipeline (ACE-452), and a release fails as a
whole rather than as one job out of twenty. `shivammathur/setup-php` writes the
same credential into composer's `auth.json` on its own, but refuses to for the
composer versions affected by `GHSA-f9f8-rm49-7jv2` and `tools: composer:v2` is
a floating tag — so the explicit variable is what makes it version independent,
and its `github-token` input is left alone because it already defaults to
`github.token`. The same block sits in the root workflow, in all twelve package
workflows and in the template; change them together.

## Pre-release checklist

Everything the scripts check themselves is listed as such — the point of the
list is the handful of things they do *not* check.

**Checked by the scripts (they will stop you):**

- [ ] Version is `MAJOR.MINOR.PATCH`, no `v`, no suffix.
- [ ] `composer`, `php`, `tailor`, `pkw`, `jq`, `sed`, `git` and an
      authenticated `gh` are on `PATH`.
- [ ] The working tree is clean (fatal for `--execute`).
- [ ] No **local** tag with that version exists — the check is
      `git rev-parse refs/tags/X.Y.Z`, so fetch first, or a tag that only exists
      on the remote will not be seen.
- [ ] CI is green — `bin/release` waits for it with
      `gh pr checks --watch --fail-fast` and stops at the first failure.
- [ ] The tag matches every `ext_emconf.php` — guaranteed by the `release` run
      of `bin/set-version`, enforced by `tailor create-artefact`.

**Not checked — verify by hand before starting:**

- [ ] **The right branch.** A release is cut from the branch owning that version
      line: `2` for `2.x`, `main` for `3.x`. Both scripts default to
      `--source-branch=2` in the copy that lives here, so `bin/release 2.4.0`
      is correct as written — but a `3.x` release is cut on `main`, from the
      copy there, never from this branch with an overridden flag.
      `bin/set-version` refuses a `3.x` version for `--source-branch=2`.
- [ ] **Changelog entries are complete** for the version, in every package that
      changed. Nothing enforces this, and after the tag it is too late — see
      [Changelog and documentation](changelog-and-documentation.md).
- [ ] **The changelog version directory exists** for the line being released
      (`Documentation/Changelog/<minor>/`, so `2.4/` today), and its `Index.rst`
      is linked from `Changelog-2.rst`.
- [ ] **A dry run was read**, not just executed:
      `bin/set-version 2.4.0 release --dry-run` prints every file it would
      touch, and `bin/release 2.4.0 --dry-run` prints the whole plan.
- [ ] **`TYPO3_API_TOKEN` is present in the split repositories.** It is what
      `ter:publish` spends, and a missing one surfaces only in step 3, after the
      tag exists. The root workflow declares the same secret but never uses it.
- [ ] **The rendered documentation was looked at** for anything with
      user-visible documentation changes.

After the release, `bin/release` phase 2 leaves this branch on `X.Y.(Z+1)-dev` —
`2.4.1-dev` after `2.4.0`. A minor bump is a deliberate, separate
`bin/set-version 2.5.0 dev` run. A major bump does not happen here: `3.0` is
`main`'s version line.

## See also

- [Changelog and documentation](changelog-and-documentation.md) — what has to be
  written before the tag.
- [Backporting](backporting.md) — the second maintained branch releases on its
  own version line.
- [Commit messages](commit-messages.md) — the `[RELEASE]` and `[TASK]` subjects
  the scripts generate.
- [Pull requests](pull-requests.md) — both release phases go through one.
- `CONTRIBUTING.md`, section "Releasing" — the contributor-facing summary of the
  same process.
- `bin/set-version`, `bin/release` — the scripts, both self-documenting via
  `--help`.
- `.github/workflows/publish.yml` — step 1 of the chain.
- `Build/templates/extensions/.github/workflows/publish.yml` — the reference for
  step 3.
