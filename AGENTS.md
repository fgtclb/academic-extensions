# Agent instructions

This file is the entry point for AI coding agents working in this repository.
`CLAUDE.md`, `GEMINI.md` and `.github/copilot-instructions.md` are symlinks to
it — there is one set of instructions, not four. Edit `AGENTS.md`.

It does **not** repeat the developer documentation. Everything about how this
repository is built lives in [`docs/`](docs/Index.md) and
[`CONTRIBUTING.md`](CONTRIBUTING.md), and is linked from here. What this file
adds are the rules that apply *specifically* to working as an agent, and the
handful of things that are easy to get wrong and expensive to discover later.

## Read this before changing code

| Topic                                                   | Page                                                                    |
|---------------------------------------------------------|-------------------------------------------------------------------------|
| Container based tooling, every suite and option         | [Development environment](docs/development/environment.md)              |
| What lives where, extension keys, split repositories    | [Monorepo layout](docs/development/monorepo-layout.md)                  |
| **Dual core setup — read this first**                   | [Dual core setup](docs/development/dual-core-setup.md)                  |
| The gates and what they check                           | [Quality gates](docs/development/quality-gates.md)                      |
| The TypeScript and SCSS build, and its committed output | [Frontend assets](docs/development/frontend-assets.md)                  |
| Version differences, and the v15 blockers               | [Core version aware code](docs/architecture/core-version-aware-code.md) |
| Service configuration and stateless services            | [Dependency injection](docs/architecture/dependency-injection.md)       |
| `final`, `readonly`, injection, data objects            | [Class design](docs/architecture/class-design.md)                       |
| **Quoting value lists and binding parameters**          | [Database queries](docs/architecture/database-queries.md)               |
| Both suites, their strictness and their conventions     | [Testing](docs/testing/Index.md)                                        |
| The shared functional test traits                       | [Testing helper](docs/testing/testing-helper.md)                        |
| Commit message conventions                              | [Commit messages](docs/workflow/commit-messages.md)                     |
| Analysing a backport instead of cherry-picking it       | [Backporting](docs/workflow/backporting.md)                             |

## Local additions and overrides

A machine-local `AGENTS.local.md` may sit next to this file. It is git-ignored
and never committed, so a fresh clone starts without it. `CLAUDE.local.md` and
`GEMINI.local.md` are symlinks to it.

**Read it if it is present.** It belongs to whoever works on this checkout, it
may add to or override anything in this file, and where the two differ it takes
precedence.

## Who you are working for

The maintainer of this repository is an **experienced senior developer with a
maintainer background** — TYPO3 Core Team, and maintainer of a number of public
extensions and packages. Work is held to TYPO3 Core review standards.

What follows from that:

- **Explain decisions, not concepts.** No introductions to what dependency
  injection or a functional test is. Say what you chose and why you rejected the
  alternative.
- **Correctness before volume.** A small change that is verified beats a large
  one that is plausible.
- **Say what you did not do.** A gap that is named is a decision; a gap that is
  hidden is a defect.
- **Disagreement is useful.** If the requested approach has a real problem, say
  so in a sentence or two, then deliver what was asked under stated assumptions.

## AI-assisted contributions

1. **Responsibility is indivisible, and AI is not an author.** Whoever submits a
   change is responsible for it in full, exactly as if every line had been typed
   by hand. A model is a tool. It is therefore never credited as an author: no
   `Co-authored-by:` trailer for a model or a tool, no `AI-assisted:` trailer,
   and no "Generated with …" notice — not in a commit, a pull request, an issue
   or a file.
2. **The quality bar does not move.** Every gate in this file applies unchanged.
   Never submit code that has only been reasoned about; run it.
3. **Be deliberate about security.** Use the TYPO3 `QueryBuilder` rather than
   string concatenation, TYPO3 request handling rather than `$_GET`/`$_POST`,
   and never hardcode credentials.
4. **Write in the maintainer's voice.** Commit messages, pull request texts and
   documentation are written as a human author would write them.

## Working files belong in `.agent/`

Never write scratch files, notes, plans, downloads or drafts into the repository
tree, and **never into `/tmp/`** — it is a ramdisk, it is lost on restart and it
costs RAM. Use `.agent/` in the repository root, which is git-ignored:

| Path              | For                                                                                          |
|-------------------|----------------------------------------------------------------------------------------------|
| `.agent/plans/`   | Plans and step tracking, **one subdirectory per plan** so all files of a plan stay together. |
| `.agent/reports/` | Analyses, investigations and hand-off documents.                                             |
| `.agent/commits/` | Commit messages to draft, review or edit before committing.                                  |
| `.agent/tmp/`     | Everything else: scripts, downloads, scratch checkouts, tool output.                         |

Nothing below `.agent/` is ever committed.

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
- `packages-dev/testing-helper/` — `fgtclb/academics-monorepo-testing-helper`: shared functional-test traits. Seven of them; see [Testing helper](docs/testing/testing-helper.md) rather than a list here that goes stale.
- `Build/` — test harness, phpunit/phpstan/php-cs-fixer configs, docs build.
- `.Build/` — generated composer install target (`vendor-dir`, `bin-dir`, `Web/`). Not committed.
- `core-13/`, `core-14/` — ready-to-start development instances, one per core version. SQLite only, no database container; seeded on first start from `sqlite-databases/core-*.sqlite` by `config/system/additional.php`. Their `config/` and `composer.lock` are **tracked**; `public/`, `var/`, `vendor/` and `config/system/additional/*.php` are not. They are not part of any test run — `runTests.sh` never touches them.
- `sqlite-databases/` — committed database templates for those instances. `core-*/patches` symlinks into the shared `patches/` pool consumed by `vaimo/composer-patches`.
- Switching branches in one checkout collides in DDEV: the instance folders have the same path on every branch but the project names differ per version line (`core13-academics-v3` on `main`, `core13-academics-v2` on `2`), and DDEV refuses a second name for a known path. `ddev stop --unlist <other-name>` clears it; it removes only the registration. The instance database in the git-ignored `core-*/var/` survives the switch, so reset it with `ddev composer sqlite:apply`.

The extension directory name does not always equal the extension key: e.g.
`packages/fgtclb/academic-contact4pages/` ships extension key
`academic_contacts4pages`. The extension key is the authoritative one in
`composer.json` → `extra.typo3/cms.extension-key`.

## Version support

- Branch `main` = version `3.0.0-dev`, supporting **TYPO3 v13 + v14**, PHP 8.2–8.5.
- Branch `2` = `^2` (`2.4.x-dev`), v12+v13. Branch `1` = `^1`, v11+v12 (legacy).
- The per-extension support/test status matrix is in `README.md` — consult it before assuming an extension works on a given core/PHP combination; many `2.x` combinations are explicitly "not tested yet".

### Backport targets

**The only maintained backport targets are `main` and `2`.**

**Branch `2.2` is no longer maintained.** Never backport to it, and do not
propose it as a target — not even when it demonstrably carries the same defect —
unless it is explicitly requested for that specific change. Branch `1` is legacy
and is treated the same way. Stating factually which branches contain a defect is
fine; proposing work on `2.2` is not.

**A backport starts by reading the target branch's own `AGENTS.md` and `docs/`.**
They are not copies of these: each branch supports a different pair of TYPO3
versions, so the instructions differ, and several of them differ by being
*inverted* rather than merely absent. `not-core-13` excludes a test from v13
here and means v12-only on `2`. The phpstan configurations are byte-identical
here and differ there. `TYPO3\CMS\Core\Attribute\AsEventListener` exists here and
does not exist on v12 at all. Applying a rule from this file to branch `2`
without checking its copy first is how a change lands that is confidently wrong.

→ [Backporting](docs/workflow/backporting.md) for the file-level diff recipe.

## Build / test / lint — `Build/Scripts/runTests.sh`

All checks run through the containerized harness (docker or podman, auto-selected;
override with `-b docker|podman`). It mirrors the TYPO3 Core `runTests.sh`. Key flags:

- `-s <suite>` — suite to run.
- `-t <13|14>` — TYPO3 core version (default 13). Drives `composerUpdate`/install and which `Build/phpstan/Core13|Core14` config is used.
- `-p <8.2|8.3|8.4|8.5>` — PHP version (default 8.2).
- `-d <sqlite|mariadb|mysql|postgres>` — DBMS for functional tests (default sqlite).
- `-i <version>` — DBMS version, when the default of the selected `-d` does not fit.
- `-a <driver>` — database driver, e.g. `pdo_mysql` or `mysqli` (default: driver of `-d`).
- `-n` — dry-run for `cgl` (report only, don't modify).
- `-x` / `-y <port>` — enable xdebug to a host IDE (default port 9003).
- `-o <seed>` — random order seed for `unitRandom`.
- `-u` — update the `typo3/core-testing-*` container images.
- Trailing `[file]` — restrict phpunit to a path.

There is **no `-e` option**. Everything after a `--` separator is appended to the
suite's command, so `-s unit -- --filter Foo` reaches phpunit. Prefer restricting
a run with the trailing path, and `-o` for the random order seed.

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

Restrict a run to a directory or a single test file:

```bash
Build/Scripts/runTests.sh -t 13 -s functional packages/fgtclb/academic-persons/Tests/Functional/Domain
Build/Scripts/runTests.sh -t 13 -s unit packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/ProfileInformationTest.php
```

`-t` selects configuration only, it does **not** reinstall dependencies. After a
`composerUpdate` for one core version, a run for the other one silently uses the
wrong vendor tree and fails in confusing ways — always `composerUpdate` for the
version you are about to test.

The complete suite list is `buildJs`, `cgl`, `cglHeader`, `checkJsBuildClean`,
`checkRstRenderingAll`, `checkRstRenderingSingle`, `cleanJs`, `composer`
(dispatch an arbitrary composer command), `composerUpdate`, `functional`,
`lintPhp`, `lintTypescript`, `npm` (dispatch an arbitrary npm command),
`openDocumentation`, `phpstan`, `phpstanGenerateBaseline`, `typecheckJs`,
`unit`, `unitRandom`; plus `help` and `update`.

The six frontend suites — `buildJs`, `checkJsBuildClean`, `cleanJs`,
`lintTypescript`, `npm` and `typecheckJs` — are **core version independent**.
They look at the sources and the committed artifacts, never at the installed
core, so `-t` changes nothing for them and they need no `composerUpdate`. That
makes them the only suites safe to run while the other core version's dependency
set is installed.

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
SQLite for both core versions and both edge PHP versions, so a defect that is
not DBMS specific is reported by 4 jobs instead of 20.

Three PHP sets, to be changed together: `lint` uses all of 8.2–8.5, `unit` and
`functional` use the edges 8.2 + 8.5, `cgl` and `phpstan` use 8.2 only.
`phpstan` is the only source gate that runs per core version (it analyses
against the installed core via `Build/phpstan/Core13|Core14`). `lint` needs
neither `-t` nor `composerUpdate` — `lintPhp` runs `php -l` over the sources and
excludes `.Build/` plus the `vendor/`, `public/` and `var/` trees of the
`core-*` instances. Those exclusions are load-bearing: they are git-ignored
vendor trees, and `typo3/class-alias-loader` ships a template file that is
deliberately not valid PHP. The instances' *tracked* `config/system/*.php` is
deliberately still linted.

The `documentation` job runs `checkRstRenderingAll` (a real gate — the renderer
uses `--fail-on-log --fail-on-error`) and uploads `documentation-rendered/`,
which holds one folder per extension.

`pr-comment.yml` posts the link to that artifact as a single, updated-in-place
pull-request comment. It is a **separate** workflow on the `workflow_run` event
on purpose: a pull request from a fork gets a read-only token, so a comment step
inside `ci.yml` would work for branches here and silently fail for external
contributors. `pull_request_target` is deliberately not used. Consequence:
changes to `pr-comment.yml` only take effect once they are on the default
branch, never within the pull request that changes them.

Composer downloads and the phpstan result cache live in `.cache/` at the
repository root, **not** under `.Build/` — `composerUpdate` starts with
`rm -rf .Build`, so a cache inside it would be discarded on every dependency
install (locally and in CI). `ci.yml` caches `.cache/composer` per PHP and core
version.

There are no `core-*.yml` workflows any more; `core-11.yml` … `core-14.yml` were
consolidated into `ci.yml`. No badge in this repository referenced them.

`publish.yml` triggers on a version tag matching `X.Y.Z`: it builds a TER
artifact per extension with `typo3/tailor` and creates the GitHub release. It
does **not** publish to the TER — the mono repository is a composer `project`,
not an extension. **The tag version must match each extension's
`ext_emconf.php` version or the artifact step fails.**

TER publishing happens one step later, per extension: the external splitter
mirrors the tagged state into the read-only split repositories, where each
package's own `publish` workflow runs `tailor ter:publish`. That workflow is
maintained here as `packages/fgtclb/<package>/.github/workflows/publish.yml` and
is split out with the package, so it is changed in this repository, never
downstream. See the "Releasing (maintainers)" section of `README.md`.

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

## The test suites are deliberately hard breaking

`Build/phpunit/UnitTests.xml` and `FunctionalTests.xml` both set
`failOnDeprecation`, `failOnNotice`, `failOnRisky` and `failOnWarning` to
`true`. A deprecation raised anywhere in a test run is a failing test, not a
line of output to scroll past.

Never silence one, never add a group to skip it, never lower a flag to get a run
green. Fix the cause. A deprecation that cannot be fixed on the older supported
core version is a version split — see below — not a suppression.

The one flag that is *not* strict is `beStrictAboutTestsThatDoNotTestAnything`,
which is `false`: a test without an assertion passes here. That makes proving a
new test can fail more important, not less.

## Verify, do not assume

The most common failure mode of an agent in this repository is a confident,
plausible, wrong statement. Two habits prevent it:

- **Read the source rather than recalling it.** The installed core is right
  there under `.Build/vendor/typo3/`, and the changelogs ship with it in
  `.Build/vendor/typo3/cms-core/Documentation/Changelog/`. An API that "should"
  exist on a core version either does or does not, and checking costs seconds.
  This matters most for anything claimed about the *older* supported version.
- **Prove a new test can fail.** A test that passes may be passing for the wrong
  reason. Break the thing it covers on purpose, watch the test go red, restore.
  This is cheap and it is the difference between a test and a decoration.

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
TYPO3 v11.5 and are therefore available on every core version any branch here
supports. A raw `[]` instead raises `\InvalidArgumentException` 1701857902 from
TYPO3 v13 on, and on v12 reaches the database as `IN ()`, which MariaDB, MySQL
and PostgreSQL reject while **SQLite accepts it** — so the default `-d sqlite`
run does not show the defect. A `createNamedParameter($values, PARAM_*_ARRAY)`
is equally safe; Doctrine renders an empty array as `NULL` too. Do not write a
caller-side `if ($values === [])` guard instead.

**Build a constraint on the query builder that executes it.** A named parameter
is bound to the builder that created it, so a `WHERE` assembled on one builder
and passed to another references a placeholder that was never bound — and may
collide with a placeholder the target builder created itself, e.g. through
`set()`. That silently updated nothing on MySQL, MariaDB and SQLite and threw on
PostgreSQL. When a loop builds one statement per record, keep expression and
execution on the same object.

This is the **decorated TYPO3 `QueryBuilder`** (`TYPO3\CMS\Core\Database\Query`),
not the Extbase one.

## Core-version-aware code (v13 vs v14)

There is no `Core13/`/`Core14/` split in any academic extension yet. The two
places that currently differ per core version are single switches on
`(new Typo3Version())->getMajorVersion()`, both in
`academic-base/Classes/TcaManipulator.php`:
`addContentElementPlugin()` (the `addPlugin()` signature changed) and
`addContentElementPluginFlexForm()` (the FlexForm `ds` shape differs and
**neither version tolerates the other's**, see ACE-293).

Keep it that way while the difference is a line or two. Reach for the folder
split below only when a whole class has to differ — the technique can be looked
up in `web-vision/deepltranslate-core` or `fgtclb/environment-state-manager`.
The generic principle is two additional class folders, one per supported core
version of the branch:

* `Core13/`, `Core14/`: For TYPO3 v13 or v14 only implementation, or core-version aware implementation
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
usage for symfony dependency injection configuration over `Services.yaml` entries.
Not every TYPO3 attribute exists in every supported version, so check before using
one — and never use Symfony's own `#[AsEventListener]`, always TYPO3's.

`Build/phpstan/Core13/phpstan.neon` and `Core14/phpstan.neon` are byte-identical
and their `paths` is `../../../packages` wholesale, so a new `Core13/`/`Core14/`
folder is analysed without any change — what such a split would need instead is
an `excludePaths` entry for the folder that does not match the analysed version.
The real per-version difference is `phpstan-constants.php` and the two baselines.
Note that `packages-dev/` is not analysed at all.

Core-version aware **tests** come in two shapes:

* A whole test class that only applies to one version goes in a `Core13/`/`Core14/`
  subfolder of `Tests/Unit` or `Tests/Functional`.
* A single test method, or a class that only differs in fixtures, stays in the
  shared folder and carries a phpunit group attribute instead.

The group names are **`not-core-13`** and **`not-core-14`**: `runTests.sh` always
runs `--exclude-group not-core-${CORE_VERSION}`, so a test tagged `not-core-13`
runs on v14 only. Examples:
`academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormUploadTest.php`
(class level) and `academic-base/Tests/Unit/TcaManipulatorTest.php` (method level).

Note: the DI style here is **not** what a fresh extension would use. Eleven of
the twelve extensions configure services in `Configuration/Services.yaml`; only
`academic-persons` also has a `Configuration/Services.php`, and that file holds
nothing but `registerForAutoconfiguration()` calls. Attributes are already in use
in production code alongside it, so the two styles coexist.

Keep the two vendors apart when you use them. `#[Autoconfigure]`, `#[Autowire]`,
`#[AsAlias]` and `#[Exclude]` are **Symfony's**, from
`Symfony\Component\DependencyInjection\Attribute`. The only **TYPO3** attribute
in use here is `Install\Attribute\UpgradeWizard`. That distinction is what the
`#[AsEventListener]` rule above turns on — TYPO3 ships its own, Symfony's must
never stand in for it, and Symfony's fails silently rather than loudly: it
registers nothing, so the listener simply never fires.

Match the surrounding extension when editing it, and see
[Dependency injection](docs/architecture/dependency-injection.md) for the counts
— they are measured there, and are deliberately not repeated here.

## TYPO3 v15 blockers — do not migrate these yet

Three APIs on `main` are deprecated in TYPO3 v14 and removed in v15. **None of
them can be migrated while the branch still supports TYPO3 v13**, because the
replacement does not exist there. Verified against both vendor trees:

| API                                                    | Replacement                                       | Present on v13.4.33?                                                                  | Call sites                |
|--------------------------------------------------------|---------------------------------------------------|---------------------------------------------------------------------------------------|---------------------------|
| `Extbase\Annotation\*`                                 | `Extbase\Attribute\*`                             | no — `cms-extbase/Classes/Attribute/` absent                                          | 10 in 6 files             |
| `Install\Updates\*`, `Install\Attribute\UpgradeWizard` | `Core\Upgrades\*`, `Core\Attribute\UpgradeWizard` | no — `cms-core/Classes/Upgrades/` absent                                              | 8 wizards in 5 extensions |
| `Core\Service\FlexFormService`                         | `Core\Configuration\FlexForm\FlexFormTools`       | class exists on v13, but without `convertFlexFormContentToArray()` on `FlexFormTools` | 1 file                    |

They are tracked as **ACE-294** (epic) with ACE-295, ACE-296 and ACE-297.
Static analysis and IDE inspections will keep suggesting the replacements —
ignore them here. A "helpful" import rewrite is a fatal error on v13.

Two ways out, and the choice belongs to the epic, not to an individual change:
drop v13 support first (expected), or introduce the `Core13/Core14` split
described above — disproportionate for ~19 call sites.

**Not on this list:** references to core labels marked `x-unused-since="14.0"`.
They look the same — the replacements are v14-only XLIFF 2.0 files — but they
are labels on *our own* TCA, so shipping our own text resolves them today on
both core versions (**ACE-298**). It also has to happen before backend-form
tests are added, because the suite runs with `failOnDeprecation` and those
labels emit `E_USER_DEPRECATED` on every form render on v14.

## Releasing / versions

Every package carries its own version (on this branch `3.0.0-dev`) in
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

## Definition of done

Before reporting a change as complete:

- [ ] `lintPhp`, `cgl -n`, `phpstan` and `unit` green for **every** core version
      the branch supports, each after its own `composerUpdate`.
- [ ] `functional` green for the same versions when the change can affect
      runtime behaviour.
- [ ] New behaviour has a test, and the test was shown to fail without the
      change.
- [ ] [`docs/`](docs/Index.md) updated in the same change — a new concept gets a
      page or a section, and it is linked from the section `Index.md`.
- [ ] The affected extension's `Documentation/` updated when the change is user
      or integrator facing, with a `Documentation/Changelog/<version>/` entry —
      `Breaking-*.rst`, `Deprecation-*.rst`, `Feature-*.rst` or
      `Important-*.rst`. Templates are in `Build/Documentation/Templates/`.
- [ ] `README.md` and `CONTRIBUTING.md` still only summarize and link — no
      duplicated content.
- [ ] Commit message follows the TYPO3 Core conventions, with a verified
      `ACE-NNN` reference and no AI attribution of any kind.
- [ ] Anything left out is stated explicitly, with the reason.
