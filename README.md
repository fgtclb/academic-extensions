# TYPO3 Academic Extensions (development)

## Description

`academic-extensions` is a mono repository to develop a couple of academic related TYPO3 extensions,
which may depend on others. To keep the maintenance burden across the set of extension small while
increasing the cross-over development and testing experience.

## Repository version support

| Branch | Version       | TYPO3     | PHP                                          |
|--------|---------------|-----------|----------------------------------------------|
| main   | ^3, 3.x-dev   | v13 + v14 | 8.2, 8.3, 8.4, 8.5                           |
| 2, 2.x | ^2, 2.x-dev   | v12 + v13 | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3) |
| 1      | ^1, 1.x-dev   | v11 + v12 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3)      |

**Testing 3.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and than for example:

```shell
composer require 'fgtclb/academic-persons':'3.*.*@dev'
```

That way, current main branch will be included and updated and as soon as 3.0.0 is released switcht to the release on
update.

## Upgrade from `1.x`

Upgrading from `1.x` to `2.x` includes breaking changes, which needs to be
addressed manualy in case not automatic upgrade path is available. See the
`UPGRADE.md` file of each extension for details.

### Extension Version Support Matrix

| Extension               | v11 | v12     | v13     | v14 |
|-------------------------|-----|---------|---------|-----|
| academic_base           | -   | <2>     | <2> <3> | <3> |
| academic_bite_jobs      | <1> | <1> <2> | <2> <3> | <3> |
| academic_contacts4pages | <1> | <1> <2> | <2> <3> | <3> |
| academic_study_plan     | -   | <2>     | <2> <3> | <3> |
| academic_jobs           | <1> | <1> <2> | <2> <3> | <3> |
| academic_partners       | <1> | <1> <2> | <2> <3> | <3> |
| academic_persons        | <1> | <1> <2> | <2> <3> | <3> |
| academic_persons_edit   | <1> | <1> <2> | <2> <3> | <3> |
| academic_persons_sync   | <1> | <1> <2> | <2> <3> | <3> |
| academic_programs       | <1> | <1> <2> | <2> <3> | <3> |
| academic_projects       | <1> | <1> <2> | <2> <3> | <3> |
| category_types          | <1> | <1> <2> | <2> <3> | <3> |

Legend:

```
  <X>   Allowed and used with X.y.z
  {X}   Allowed but not tested/verified with X.y.z, but may/could work
  -X-   Allowed but absolutely not tested and most likely not working (yet)
  (X)   Planned for the upcoming X.y.z line, not yet available/tested
```

**The `3.x` line (in development)**

The `<3>` marker documents the upcoming major `3.x` line, which targets TYPO3
**v13 + v14** (see the branch support matrix above). Both core versions are
implemented and verified for every extension above by the `TYPO3 v13` and
`TYPO3 v14` CI workflows. The `3.x` line itself is still in development
(`3.0.0-dev`) and not released yet.

## List of TYPO3 extension and the split repositories (READ ONLY)

| Composer                       | TYPO3                   | Path                                                                                       | Split Repository                                                                     |
|--------------------------------|-------------------------|--------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------|
| fgtclb/academic-base           | academic_base           | [packages/fgtclb/academic-base](packages/fgtclb/academic-base/README.md)                   | [fgtclb/academic-base](https://github.com/fgtclb/academic-base)                      |
| fgtclb/academic-bite-jobs      | academic_bite_jobs      | [packages/fgtclb/academic-bite-jobs](packages/fgtclb/academic-bite-jobs/README.md)         | [fgtclb/academic-bite-jobs](https://github.com/fgtclb/academic-bite-jobs)            |
| fgtclb/academic-contacts4pages | academic_contacts4pages | [packages/fgtclb/academic-contact4pages](packages/fgtclb/academic-contact4pages/README.md) | [fgtclb/academic-contact4pages](https://github.com/fgtclb/academic-contact4pages)    |
| fgtclb/academic-study-plan     | academic_study_plan     | [packages/fgtclb/academic-study-plan](packages/fgtclb/academic-study-plan/README.md)       | [fgtclb/academic-study-plan](https://github.com/fgtclb/academic-study-plan)          |
| fgtclb/academic-jobs           | academic_jobs           | [packages/fgtclb/academic-jobs](packages/fgtclb/academic-jobs/README.md)                   | [fgtclb/academic-jobs](https://github.com/fgtclb/academic-jobs)                      |
| fgtclb/academic-partners       | academic_partners       | [packages/fgtclb/academic-partners](packages/fgtclb/academic-partners/README.md)           | [fgtclb/academic-partners](https://github.com/fgtclb/academic-partners)              |
| fgtclb/academic-persons        | academic_persons        | [packages/fgtclb/academic-persons](packages/fgtclb/academic-persons/README.md)             | [fgtclb/academic-persons](https://github.com/fgtclb/academic-persons)                |
| fgtclb/academic-persons-edit   | academic_persons_edit   | [packages/fgtclb/academic-persons-edit](packages/fgtclb/academic-persons-edit/README.md)   | [fgtclb/academic-persons-edit](https://github.com/fgtclb/academic-persons-edit)      |
| fgtclb/academic-persons-sync   | academic_persons_sync   | [packages/fgtclb/academic-persons-sync](packages/fgtclb/academic-persons-sync/README.md)   | [fgtclb/academic-persons-sync](https://github.com/fgtclb/academic-persons-sync)      |
| fgtclb/academic-programs       | academic_programs       | [packages/fgtclb/academic-programs](packages/fgtclb/academic-programs/README.md)           | [fgtclb/academic-programs](https://github.com/fgtclb/academic-programs)              |
| fgtclb/academic-projects       | academic_projects       | [packages/fgtclb/academic-projects](packages/fgtclb/academic-projects/README.md)           | [fgtclb/academic-projects](https://github.com/fgtclb/academic-projects)              |
| fgtclb/category-types          | category_types          | [packages/fgtclb/typo3-category-types](packages/fgtclb/typo3-category-types/README.md)     | [fgtclb/fgtclb/typo3-category-types](https://github.com/fgtclb/typo3-category-types) |

## Releasing (maintainers)

A release is always cut from the branch owning that version line (see the
[branch support matrix](#repository-version-support)) using the two scripts in
`bin/`. All extensions of the mono repository are released together, sharing one
version number.

| Branch | Release line | Example version | Release tooling                             |
|--------|--------------|-----------------|---------------------------------------------|
| main   | `3.x`        | `3.0.0`         | `bin/release`, `bin/set-version`            |
| 2      | `2.x`        | `2.4.0`         | `bin/release`, `bin/set-version`            |
| 2.2    | `2.2.x`      | `2.2.2`         | `bin/release`, `bin/set-version`            |
| 1      | `1.x`        | -               | none — that branch has no `bin/` scripts    |

Both scripts are kept as the *same* implementation on every branch. Only two
things legitimately differ per branch: the `--source-branch` default (which
equals the branch itself) and the version examples in the help output (which
must lie inside that branch's version range).

### Required tooling

`bin/set-version` resolves `composer`, `php`, `tailor`, `pkw`, `jq` and `sed`
from `PATH`; `bin/release` additionally needs `git` and an authenticated `gh`.
Both abort with an explicit error if a tool is missing, before changing
anything.

### `bin/set-version` — apply a version across the mono repository

```shell
bin/set-version <version> <type> [--source-branch=<name>] [--dry-run]
```

`<type>` selects how the version is written:

| Type           | Result                                                                   |
|----------------|--------------------------------------------------------------------------|
| `release`      | tag/release version — `X.Y.Z`, academic deps `X.Y.Z@dev`                  |
| `post-release` | next dev version — `X.Y.W-dev`, deps `~X.Y.W@dev`, branch-alias `X.Y.x-dev`; the version passed is *already* the next one, no `+1` happens here |
| `dev`          | force a plain dev version everywhere (`X.Y.Z-dev`); thin variant of `post-release`, used for branching and forced minor/major bumps |

It rewrites, in one pass:

1. `Build/Scripts/runTests.sh` → `COMPOSER_ROOT_VERSION`
2. split extensions → academic composer deps, `extra.typo3/cms.version`,
   branch-alias, `tailor set-version`, `VERSION` file
3. functional-test fixture extensions → composer deps only
4. `ext_emconf.php` → `version` plus `depends`/`suggests` constraints
5. `packages-dev/monorepo-shared` → academic deps + packages version map
6. `ddev-instances/*` → both version maps + `academics-monorepo-shared` require
7. root `composer.json` → both version maps, `monorepo-shared` require, alias

The script only edits working-tree files — it performs no git and no network
operations. `--dry-run` prints every single change without touching a file and
is the safe way to rehearse a bump.

### `bin/release` — orchestrate the full release

```shell
bin/release <release-version> [--source-branch=<name>] [--dry-run|--execute]
```

It runs two phases, delegating all version rewriting to `bin/set-version`:

* **Phase 1 (release)** — branch `release-X.Y.Z`, `set-version X.Y.Z release`,
  commit `[RELEASE] X.Y.Z`, push, open a PR, wait for the checks, admin
  rebase-merge, then tag `X.Y.Z` on the refreshed source branch and push the tag.
* **Phase 2 (post-release)** — branch `set-version-X.Y.W` (`W = Z+1`),
  `set-version X.Y.W post-release`, commit `[TASK] Set version X.Y.W`, push,
  PR, checks, admin rebase-merge.

Two independent safety gates control how far a run goes:

| Invocation  | Local steps | Remote/irreversible steps (push, PR, merge, tag) |
|-------------|-------------|--------------------------------------------------|
| *(bare)*    | executed    | **only printed** — a bare run can never mutate the remote or create a tag |
| `--dry-run` | printed     | printed                                          |
| `--execute` | executed    | executed                                         |

`--dry-run` and `--execute` are mutually exclusive. Pre-flight checks refuse to
run outside a git work tree or when the target tag already exists; a dirty
working tree is fatal for `--execute` and only a warning otherwise, so the flow
stays rehearsable.

### What the pushed tag triggers

Pushing the tag starts the `publish` workflow
([`.github/workflows/publish.yml`](.github/workflows/publish.yml)), which:

1. verifies the tag matches `MAJOR.MINOR.PATCH`,
2. builds one TER upload artifact per extension via
   `tailor create-artefact` — **this step fails when the tag does not match an
   extension's `ext_emconf.php` version**, which is exactly what
   `bin/set-version` keeps in sync, and
3. creates the GitHub release `[RELEASE] <version>` with generated release notes
   and attaches the artifacts plus `LICENSE`.

The mono repository itself is **not** published to the TER — it is a composer
`project`, not an extension. TER publishing happens per extension, one step
later in the chain:

1. the tag is pushed here and the workflow above creates the GitHub release,
2. the (external) splitter mirrors the tagged state into the read-only split
   repositories listed above,
3. each split repository carries its **own** `publish` workflow, which reacts to
   the tag arriving there and runs `tailor ter:publish` for that single
   extension.

That per-extension workflow is maintained in this repository as
`packages/fgtclb/<package>/.github/workflows/publish.yml` (all 12 packages ship
one) and is split out together with the package — so TER publishing is changed
here, never in a split repository.

Note that the documentation-rendering step of the root workflow is currently
commented out.
