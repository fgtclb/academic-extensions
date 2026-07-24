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

- Branch `main` = version `3.0.0-dev`, supporting **TYPO3 v13 + v14**, PHP 8.2–8.5.
- Branch `2` = `^2` (`2.4.x-dev), v12+v13. Branch `1` = `^1`, v11+v12 (legacy).
- The per-extension support/test status matrix is in `README.md` — consult it before assuming an extension works on a given core/PHP combination; many `2.x` combinations are explicitly "not tested yet".

## Build / test / lint — `Build/Scripts/runTests.sh`

All checks run through the containerized harness (docker or podman, auto-selected;
override with `-b docker|podman`). It mirrors the TYPO3 Core `runTests.sh`. Key flags:

- `-s <suite>` — suite to run.
- `-t <13|14>` — TYPO3 core version (default 13). Drives `composerUpdate`/install and which `Build/phpstan/Core12|Core13` config is used.
- `-p <8.2|8.3|8.4|8.5>` — PHP version (default 8.2).
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

## Quality gates

Ensure to always install dependency for the core version to test about (`composerUpdate`)
using the `-t` option for `runTests.sh`. For every commit change ensure to execute php
linting, phpstan and cgl together with unit test as bare minimum for all (current) supported
core versions on that branch (or source branch for the pull-request work).

During upgrades it can be that special for the new major version on-going to implement that
unit/functional test and similar may fail, but once active in the related GitHub action it
musst always pass green. Try to execute subsets if possible locally during investigation and
error analysis, but always a full run in the end.

In any-case watch pull-request pipelines for pipeline errors when pushing pull-requests.

## Core-version-aware code (v12 vs v13)

Currently we do not have core-version aware code pattern in one of the academic
extension packages, but the technique can be looked up in `web-vision/deepltranslate-core`
or `fgtclb/environment-state-manager` packages and extension. The generic principal is
to have two additional class folders (for each supported core version of the branch):

* `Core13/`: For TYPO3 v13 only implementation or core-version aware implementation
   of shared interfaces (`Classes/`) adding the core-version as third level to the
   PHP namespace, which is registered for all supported versions as composer autoload
  (`PSR-4`) and using `Configuration/Services.php` with a code-snippet to only autowire,
  autoconfigue and make registration core-version aware at least with a simple code-snippet:

  ```php
    // TYPO3 core-version specific sources: only the folder matching the running
    // TYPO3 major version is loaded. The concrete services are published and
    // wired through Symfony dependency injection attributes on the classes
    // themselves (#[AsAlias], #[Autoconfigure], #[Autowire]).
    $services->load(
        sprintf('FGTCLB\\EnvironmentStateManager\\Core%d\\', $majorVersion),
        sprintf(__DIR__ . '/../Core%d/*', $majorVersion),
    );
  ```

If nothing else is required keep only a `Services.php` file and prefer PHP attribute
usage for symfony dependency injection configuration over `Services.yaml` entries;
For TYPO3 v12 or older support respect that not all TYPO3 attributes may be exist in
both versions, for example the `#[AsEventListener]` and requires the registration as
tag in `Services.yaml` (if not doable using other symfony php attributes) - never use
the `#[AsEventListener]` attribute from `symfony.

`phpstan` core-version aware configurations requires to add the related core version
folder to the `paths` configuration; note that core-version aware functional tests
should be handled in related subfolders (`Tests/Unit/Core13/`) and using phpunit
phpattribute for the group using the `not-core14` as execute only for not that group
selection (there should be examples). If it is only about database fixuters or simpler
stuff keep the tests in the shared folder using only the attributes.

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
