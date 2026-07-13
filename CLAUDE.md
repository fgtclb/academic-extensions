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
- `ddev-instances/core-12`, `ddev-instances/core-13` — DDEV setups per core version.

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

`core-12.yml` / `core-13.yml` run the same `runTests.sh` suites (CGL `-n`,
phpstan, lintPhp, unit, functional) across the PHP matrix. `core-11.yml` is
disabled. `publish.yml` triggers on a version tag matching `X.Y.Z`: it builds a
TER artifact per extension with `typo3/tailor` and publishes. **The tag version
must match each extension's `ext_emconf.php` version or publish fails.**

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

Every package is pinned to the same dev version (currently `2.3.5-dev`) in the
root and shared `composer.json` path-repository `versions` maps. A release bumps
each extension's `ext_emconf.php` `version` and `VERSION` file. Commit subjects
use TYPO3 Core conventions (see recent history: `[RELEASE]`, `[TASK]`,
`[BUGFIX]`, and `ACE-NNN` issue refs in the subject/footer). The public issue/PR
tracker is GitHub (`fgtclb/academic-extensions`); the `ACE-NNN` references are
YouTrack keys — verify them against YouTrack before writing them into a commit.
