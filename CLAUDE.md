# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

`fgtclb/academic-extensions` is a **mono repository** that develops a set of
interdependent TYPO3 CMS extensions (`academic_*` + `category_types`). Each
extension lives under `packages/fgtclb/<dir>/` and is git-split out to its own
standalone read-only GitHub repo (e.g. `fgtclb/academic-persons`). **Never treat
the split repos as a source of truth — all changes happen here**, in the mono
repo, and are mirrored outward.

The repository root is itself a composer `project` (not an extension). It pulls
all extensions in via path repositories so they can be developed and tested
together.

## Layout

- `packages/fgtclb/<name>/` — the real extensions (one composer `typo3-cms-extension` each). Edit code here.
- `packages-dev/monorepo-shared/` — `fgtclb/academics-monorepo-shared`: a meta-package centralizing the TYPO3 core dependency constraints for all extensions, root, and DDEV instances. Change TYPO3 version constraints here, not per-extension where avoidable.
- `packages-dev/testing-helper/` — `fgtclb/academics-monorepo-testing-helper`: shared functional-test traits (`ExtensionsLoadedTestsTrait`, `TcaHelperMethodsTrait`, `ExtensionCoreVersionCompatTestsTrait`).
- `Build/` — test harness, phpunit/phpstan/php-cs-fixer configs, docs build.
- `.Build/` — generated composer install target (`vendor-dir`, `bin-dir`, `Web/`). Not committed.
- `core-12/`, `core-13/` — ready-to-start development instances, one per core version. SQLite only, no database container; seeded on first start from `sqlite-databases/core-*.sqlite` by `config/system/additional.php`. Their `config/` and `composer.lock` are **tracked**; `public/`, `var/`, `vendor/` and `config/system/additional/*.php` are not. They are not part of any test run — `runTests.sh` never touches them.
- `sqlite-databases/` — committed database templates for those instances. `core-*/patches` symlinks into the shared `patches/` pool consumed by `vaimo/composer-patches`.
- Switching branches in one checkout collides in DDEV: the instance folders have the same path on every branch but the project names differ per version line (`core13-academics-v2` on `2`, `core13-academics-v3` on `main`), and DDEV refuses a second name for a known path. `ddev stop --unlist <other-name>` clears it; it removes only the registration. The instance database in the git-ignored `core-*/var/` survives the switch, so reset it with `ddev composer sqlite:apply`.

The extension directory name does not always equal the extension key: e.g.
`packages/fgtclb/academic-contact4pages/` ships extension key
`academic_contacts4pages`. The extension key is the authoritative one in
`composer.json` → `extra.typo3/cms.extension-key`.

## Version support

- Branch `main` = version `2.4.x-dev`, supporting **TYPO3 v12 + v13**, PHP 8.1–8.5.
- Branch `2` = `^2`, v12+v13. Branch `1` = `^1`, v11+v12 (legacy).
- The per-extension support/test status matrix is in `README.md` — consult it before assuming an extension works on a given core/PHP combination; many `2.x` combinations are explicitly "not tested yet".

## Build / test / lint — `Build/Scripts/runTests.sh`

All checks run through the containerized harness (docker or podman, auto-selected;
override with `-b docker|podman`). It mirrors the TYPO3 Core `runTests.sh`. Key flags:

- `-s <suite>` — suite to run.
- `-t <12|13>` — TYPO3 core version (default 12). Drives `composerUpdate`/install and which `Build/phpstan/Core12|Core13` config is used.
- `-p <8.1|8.2|8.3|8.4|8.5>` — PHP version (default 8.2).
- `-d <sqlite|mariadb|mysql|postgres>` — DBMS for functional tests (default sqlite).
- `-n` — dry-run for `cgl` (report only, don't modify).
- `-x` / `-y <port>` — enable xdebug to a host IDE (default port 9003).
- `-e "<args>"` — pass extra args through to phpunit (e.g. `--filter`).
- Trailing `[file]` — restrict phpunit to a path.

Typical workflow — **always prepare deps first** for the target core version:

```bash
# Install/refresh dependencies for the core version you will test against
Build/Scripts/runTests.sh -t 13 -p 8.3 -s composerUpdate

Build/Scripts/runTests.sh -t 13 -p 8.3 -s cgl        # auto-fix CGL (php-cs-fixer)
Build/Scripts/runTests.sh -t 13 -p 8.3 -s cgl -n     # CGL check only (CI mode)
Build/Scripts/runTests.sh -t 13 -p 8.3 -s phpstan    # static analysis (level 8)
Build/Scripts/runTests.sh -t 13 -p 8.3 -s lintPhp    # php lint
Build/Scripts/runTests.sh -t 13 -p 8.3 -s unit       # unit tests
Build/Scripts/runTests.sh -t 13 -p 8.3 -s functional # functional tests (sqlite)
```

Run a single test / filter:

```bash
Build/Scripts/runTests.sh -t 13 -s unit -e "--filter someTestMethod"
Build/Scripts/runTests.sh -t 13 -s functional packages/fgtclb/academic-persons/Tests/Functional/Domain
```

Other suites: `composer` (dispatch arbitrary composer command), `composerUpdate`,
`unitRandom`, `phpstanGenerateBaseline`, `checkRstRenderingAll`,
`checkRstRenderingSingle`, `openDocumentation`, `cglHeader`.

Test discovery: phpunit globs `packages/*/*/Tests/Unit/` and
`packages/*/*/Tests/Functional/` across **all** extensions at once
(`Build/phpunit/*.xml`) — there is no per-extension test config.

## CI (`.github/workflows/`)

`ci.yml` is the single pull-request workflow. The TYPO3 core version is a matrix
dimension, not a separate file, which is what makes the staging below possible —
job dependencies cannot cross workflows:

```
cgl     ─┐
phpstan ─┼─> unit ─> functional (SQLite) ─> functional (MySQL, MariaDB, Postgres)
lint    ─┘
documentation   (independent)
```

The DBMS matrix (16 jobs) only starts once the same functional tests passed on
SQLite for both core versions and both edge PHP versions.

Supported PHP versions differ **per core version** on this branch, so the
core/PHP pairs are listed explicitly as a `combo` axis rather than formed by a
cross product — a plain `php-version × typo3` matrix would generate unsupported
combinations such as v13 on PHP 8.1. `unit` and `functional` run v12 on 8.1/8.4
and v13 on 8.2/8.5; `cgl` runs on v12 + 8.1; `phpstan` runs per core version
(v12 + 8.1 and v13 + 8.2) because it analyses against the installed core via
`Build/phpstan/Core12|Core13`. `lint` needs neither `-t` nor `composerUpdate` —
`lintPhp` runs `php -l` over the sources and excludes `.Build/` plus the
`vendor/`, `public/` and `var/` trees of the `core-*` instances — so it covers
all of PHP 8.1–8.5 with no core dimension. The instances' *tracked*
`config/system/*.php` is deliberately still linted.

The `documentation` job runs `checkRstRenderingAll` (a real gate — the renderer
uses `--fail-on-log --fail-on-error`) and uploads `documentation-rendered/`,
which holds one folder per extension. `pr-comment.yml` posts the link to that
artifact as a single, updated-in-place pull-request comment. It is a **separate**
workflow on the `workflow_run` event on purpose: a pull request from a fork gets
a read-only token, so a comment step inside `ci.yml` would work for branches here
and silently fail for external contributors. `pull_request_target` is
deliberately not used.

Two consequences of `workflow_run`, both observed in practice:

* It always runs the workflow file from the repository's **default branch**
  (`main`), never from the pull request's base or head branch. **The copy on this
  branch therefore never executes** — pull requests against `2` are commented on
  by `main`'s `pr-comment.yml`. It is kept here only so the branches do not
  diverge; edit it on `main`.
* Changes to it take effect only once they are on the default branch, never
  within the pull request that changes them. That is why PR #350 — the one that
  introduced the file — got no comment, while #351 against this branch did.

Composer downloads and the phpstan result cache live in `.cache/` at the
repository root, **not** under `.Build/` — `composerUpdate` starts with
`rm -rf .Build`, so a cache inside it would be discarded on every dependency
install (locally and in CI).

There are no `core-*.yml` workflows any more; `core-11.yml` … `core-13.yml` were
consolidated into `ci.yml`.

`publish.yml` triggers on a version tag matching `X.Y.Z`: it builds a TER
artifact per extension with `typo3/tailor`. **The tag version must match each
extension's `ext_emconf.php` version or the artifact step fails.**

## Database queries

Two rules, both learned from defects that reached a release (ACE-349, ACE-356).

**Never hand a raw array to `in()` or `notIn()`.** Quote it with the query
builder helper meant for it:

```php
$queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($uids));
$queryBuilder->expr()->in('CType', $queryBuilder->quoteArrayBasedValueListToStringList($types));
```

Both return the string `NULL` for an empty array, so the condition becomes
`field IN (NULL)`: valid on every DBMS and matching no row. They exist since
TYPO3 v11.5 and are therefore available on both core versions of this branch.
A raw `[]` instead reaches the database as `IN ()` on TYPO3 v12 — MariaDB, MySQL
and PostgreSQL reject it, **SQLite accepts it**, so the default `-d sqlite` run
does not show the defect — while TYPO3 v13 raises `\InvalidArgumentException`
1701857902 before the query is built. A
`createNamedParameter($values, PARAM_*_ARRAY)` is equally safe; Doctrine renders
an empty array as `NULL` too. Do not write a caller-side `if ($values === [])`
guard instead.

**Build a constraint on the query builder that executes it.** A named parameter
is bound to the builder that created it, so a `WHERE` assembled on one builder
and passed to another references a placeholder that was never bound — and may
collide with a placeholder the target builder created itself, e.g. through
`set()`. That silently updated nothing on MySQL, MariaDB and SQLite and threw on
PostgreSQL. When a loop builds one statement per record, keep expression and
execution on the same object.

This is the **decorated TYPO3 `QueryBuilder`** (`TYPO3\CMS\Core\Database\Query`),
not the Extbase one.

## Core-version-aware code (v12 vs v13)

The reference pattern lives in `academic-base`. Where v12 and v13 APIs diverge,
code is split into `Classes/Core12/...` and `Classes/Core13/...` (and likewise
`Configuration/FlexForms/Core12|Core13/`), and the correct implementation is
wired at runtime in the extension's `Configuration/Services.php` by branching on
`(new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion()`. This keeps
shared classes free of conditional version logic. phpstan excludes the
non-matching `Core12` paths when analysing against v13 (see
`Build/phpstan/Core13/phpstan.neon`).

Note: extensions here still use `Configuration/Services.php` (PHP-form) for DI
rather than Symfony attributes. Match the surrounding extension's existing DI
style when editing it.

## Releasing / versions

Every package carries its own version (on this branch `2.4.0-dev`) in
`extra.typo3/cms.version` and its `VERSION` file — including the two
`packages-dev/*` meta packages. The composer plugin
`sbuerk/extended-path-repository`, required by every composer.json that declares
a `path` repository, derives the path package version from exactly those, so
there are **no** `repositories.*.options.versions` maps to keep in sync any more.
Write the version on the package and it is right everywhere. A release bumps
each extension's `ext_emconf.php` `version` and `VERSION` file. Commit subjects
use TYPO3 Core conventions (see recent history: `[RELEASE]`, `[TASK]`,
`[BUGFIX]`, and `ACE-NNN` issue refs in the subject/footer). The public issue/PR
tracker is GitHub (`fgtclb/academic-extensions`); the `ACE-NNN` references are
YouTrack keys — verify them against YouTrack before writing them into a commit.
