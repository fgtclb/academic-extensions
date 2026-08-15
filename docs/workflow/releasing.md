# Releasing

A release of this repository is a release of **fourteen packages at once**:
twelve TYPO3 extensions under `packages/fgtclb/` and the two meta packages under
`packages-dev/`. They share a single version number, they are tagged together,
and twelve of them end up in the TER — but never from here. The repository root
is a composer `project`, not an extension, and has nothing to publish.

Two scripts do the work, and neither of them is optional knowledge: `bin/set-version`
writes the version everywhere, `bin/release` drives git and GitHub around it.

## Where the version lives

Every package carries its own version. There is no central version file, and —
since the path repositories were reworked — no version map anywhere either:

| File                                                  | Value today | Written by           |
|-------------------------------------------------------|-------------|----------------------|
| `composer.json` → `extra.typo3/cms.version`           | `3.0.0-dev` | `composer config`    |
| `VERSION`                                             | `3.0.0-dev` | plain write          |
| `ext_emconf.php` → `version`                          | `3.0.0`     | `pkw extemconf:set`  |
| `Documentation/guides.xml` → `release`                | `3.0.0`     | `tailor set-version` |
| `Build/Scripts/runTests.sh` → `COMPOSER_ROOT_VERSION` | `3.0.0-dev` | `sed`, root only     |

The `-dev` suffix appears in the composer-facing files and not in the two TYPO3
facing ones, because `ext_emconf.php` and `guides.xml` express a released
version, not a range.

The two `packages-dev/*` meta packages are **not** an exception:
`fgtclb/academics-monorepo-shared` and
`fgtclb/academics-monorepo-testing-helper` both carry
`extra.typo3/cms.version` and a `VERSION` file, both at `3.0.0-dev`. They have
no `ext_emconf.php`, are not extensions, and are never published — but they are
path packages, so their version has to be right for the same reason.

**Why this matters:** every `composer.json` in this repository that declares a
`path` repository requires the composer plugin `sbuerk/extended-path-repository`,
which derives the version of a path package **from the package itself** — from
exactly those three places. Before it, each consuming `composer.json` carried a
`repositories.*.options.versions` map naming the version of every path package,
and every bump had to be mirrored into every map. Those maps are gone. Write the
version on the package and it is correct in the root project, in the sibling
extensions, in the `packages-dev` meta packages and in the `core-13`/`core-14`
development instances.

## `bin/set-version` — apply a version across the repository

```shell
bin/set-version <version> <type> [--source-branch=<name>] [--dry-run]
```

It edits working-tree files and does **nothing else**: no git, no network
(`bin/set-version:40-41`). That separation is what makes it safe to run and
inspect on its own.

`<version>` is always a bare `MAJOR.MINOR.PATCH`; the `-dev` suffixes are
derived, never passed. `<type>` decides how:

| Type           | Package version | `ext_emconf` | Academic dependency constraint | Branch alias |
|----------------|-----------------|--------------|--------------------------------|--------------|
| `release`      | `X.Y.Z`         | `X.Y.Z`      | `X.Y.Z@dev`                    | not written  |
| `post-release` | `X.Y.Z-dev`     | `X.Y.Z`      | `~X.Y.Z@dev`                   | `X.Y.x-dev`  |
| `dev`          | `X.Y.Z-dev`     | `X.Y.Z`      | `~X.Y.Z@dev`                   | `X.Y.x-dev`  |

`post-release` and `dev` share one derivation (`bin/set-version:173-186`); `dev`
is the thin variant used for branching and forced minor or major bumps.
`post-release` does **not** increment anything — the version passed is already
the next one.

What one run rewrites, in order (`bin/set-version:311-403`):

1. `Build/Scripts/runTests.sh` → `COMPOSER_ROOT_VERSION`
2. every split extension → academic composer dependencies,
   `extra.typo3/cms.version`, branch alias, `tailor set-version`, `VERSION`
3. functional-test fixture extensions → composer dependencies only
4. every `ext_emconf.php` (splits **and** fixtures) → `version`, plus the
   `depends`/`suggests` constraints for each academic extension key
5. `packages-dev/*` → academic dependencies, `extra.typo3/cms.version`, `VERSION`
6. `core-*/` development instances → the `academics-monorepo-shared` requirement
7. root `composer.json` → the `academics-monorepo-shared` requirement and the
   branch alias

Nothing in that list is hardcoded. The package set is discovered by looking for
directories under `packages/fgtclb/*/` that carry both a `composer.json` and an
`ext_emconf.php` (`bin/set-version:204-215`); the extension key is read from
`extra.typo3/cms.extension-key`, never guessed from the directory name; fixture
extensions are found under `Tests/Functional/Fixtures/Extensions`
(`bin/set-version:220-225`); the meta packages and the development instances are
discovered by path. A thirteenth extension or a `core-15` instance is picked up
by existing, which is precisely what the previously hardcoded instance list
failed to do.

`--dry-run` prints every change without touching a file and is the way to
rehearse a bump. `--source-branch=<name>` only selects which
`extra.branch-alias.dev-<branch>` key is written; it defaults to `main`, so a
release on branch `2` must pass `--source-branch=2`.

## `bin/release` — orchestrate the release

```shell
bin/release <release-version> [--source-branch=<name>] [--dry-run|--execute]
```

It owns git and `gh` and delegates all version rewriting to `bin/set-version`
(`bin/release:20-21`). One invocation runs two phases:

**Phase 1 — release** (`bin/release:211-232`)

1. `git checkout -b release-X.Y.Z <source-branch>`
2. `bin/set-version X.Y.Z release`
3. commit `[RELEASE] X.Y.Z`, push, `gh pr create --fill`
4. `gh pr checks --watch --interval 10 --fail-fast`
5. `gh pr merge --rebase --delete-branch --admin`
6. back on the refreshed source branch: `git tag X.Y.Z` and push the tag

**Phase 2 — post-release** (`bin/release:234-251`), with `W = Z + 1`

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
`PATH` (`bin/set-version:133-139`); `bin/release` additionally needs `git` and an
authenticated `gh` (`bin/release:154-162`).

## The tag has to match `ext_emconf.php`

The root `publish` workflow builds the TER artifacts with
`tailor create-artefact <version> <extension-key>` in its *Create local TER
package upload artifact* step, and **that command fails when the
version does not match the extension's `ext_emconf.php`**. This is a feature, not
an obstacle: it makes it impossible for a release to disagree with the extension
metadata that TYPO3 and the TER read.

`bin/set-version` is what keeps the two in sync — step 4 above writes
`ext_emconf.php` in the same run that writes everything else. All twelve
extensions currently declare `'version' => '3.0.0'`, so the tag for the next
release is `3.0.0`.

The workflow additionally rejects a tag that is not a bare
`MAJOR.MINOR.PATCH` before doing any work, in its *Verify tag* step. There is
no `v` prefix — `bin/release`
creates `3.0.0`, not `v3.0.0`.

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
verifies its shape, installs `typo3/tailor`, then loops over
`packages/fgtclb/*`, reads each extension key from that package's
`composer.json` at runtime, and builds one artifact per extension. The keys are
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
`contents: write`; only the per-package one actually spends the token, in
`ter:publish`.

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
      line: `main` for `3.x`, `2` for `2.x`. Releasing `2.4.0` needs
      `--source-branch=2`; the default is `main` and would write the wrong
      branch alias.
- [ ] **Changelog entries are complete** for the version, in every package that
      changed. Nothing enforces this, and after the tag it is too late — see
      [Changelog and documentation](changelog-and-documentation.md).
- [ ] **The changelog version directory exists** for the line being released
      (`Documentation/Changelog/<minor>/`), and its `Index.rst` is linked from
      `Changelog-<major>.rst`.
- [ ] **A dry run was read**, not just executed:
      `bin/set-version X.Y.Z release --dry-run` prints every file it would
      touch, and `bin/release X.Y.Z --dry-run` prints the whole plan.
- [ ] **`TYPO3_API_TOKEN` is present in the split repositories.** It is what
      `ter:publish` spends, and a missing one surfaces only in step 3, after the
      tag exists. The root workflow declares the same secret but never uses it.
- [ ] **The rendered documentation was looked at** for anything with
      user-visible documentation changes.

After the release, `bin/release` phase 2 leaves the source branch on
`X.Y.(Z+1)-dev`. A minor or major bump is a deliberate, separate
`bin/set-version <next> dev` run.

## See also

- [Changelog and documentation](changelog-and-documentation.md) — what has to be
  written before the tag.
- [Backporting](backporting.md) — the second maintained branch releases on its
  own version line.
- [Commit messages](commit-messages.md) — the `[RELEASE]` and `[TASK]` subjects
  the scripts generate.
- [Pull requests](pull-requests.md) — both release phases go through one.
- `README.md`, section "Releasing (maintainers)" — the contributor-facing
  summary of the same process.
- `bin/set-version`, `bin/release` — the scripts, both self-documenting via
  `--help`.
- `.github/workflows/publish.yml` — step 1 of the chain.
- `Build/templates/extensions/.github/workflows/publish.yml` — the reference for
  step 3.
