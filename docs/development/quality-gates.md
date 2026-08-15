# Quality gates

The same gates run on a developer machine and in the GitHub Actions workflows,
through the same wrapper and the same container images — see
[Development environment](environment.md). A change is finished when they are
green for **every core version this branch supports**, each after its own
`composerUpdate`.

```bash
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.2 -s lintPhp
Build/Scripts/runTests.sh -t 13 -p 8.2 -s cgl -n
Build/Scripts/runTests.sh -t 13 -p 8.2 -s phpstan
Build/Scripts/runTests.sh -t 13 -p 8.2 -s unit
Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional

Build/Scripts/runTests.sh -t 14 -p 8.2 -s composerUpdate
# ... the same list again
```

| Gate                                              | Tool                 | Configuration                                    | Depends on the core version |
|---------------------------------------------------|----------------------|--------------------------------------------------|-----------------------------|
| `cgl`                                             | php-cs-fixer         | `Build/php-cs-fixer/config.php`                  | no                          |
| `cglHeader`                                       | php-cs-fixer         | `Build/php-cs-fixer/header-comment.php`          | no                          |
| `lintPhp`                                         | `php -l`             | none, a `find` in the suite itself               | no                          |
| `phpstan`                                         | PHPStan, level 8     | `Build/phpstan/Core13/`, `Build/phpstan/Core14/` | **yes**                     |
| `unit`, `unitRandom`                              | PHPUnit              | `Build/phpunit/UnitTests.xml`                    | through the excluded group  |
| `functional`                                      | PHPUnit              | `Build/phpunit/FunctionalTests.xml`              | through the excluded group  |
| `checkRstRenderingAll`, `checkRstRenderingSingle` | render-guides        | each extension's own `Documentation/`            | no                          |
| `lintMarkdown`                                    | `Build/markdown.mjs` | none, the conventions are the specification      | no                          |

## Coding guidelines — `cgl`

The `cgl` arm of `runTests.sh` runs php-cs-fixer with
[`Build/php-cs-fixer/config.php`](../../Build/php-cs-fixer/config.php). Without
`-n` it **rewrites files in place**; with `-n` it adds `--dry-run --diff` and
only reports, which is the form CI uses in its `cgl` job.

The rule set is `@PER-CS1.0` plus `@DoctrineAnnotation` and some fifty
individual rules (`Build/php-cs-fixer/config.php:64-133`), risky rules allowed.
It is TYPO3 Core's set, with the same `@todo` markers for the rules that can be
dropped once `@PER-CS2.0` is adopted.

What it scans matters more than the rules, because it is narrower than the
repository (`Build/php-cs-fixer/config.php:49-62`):

| Finder call              | Value                                       |
|--------------------------|---------------------------------------------|
| `in()`                   | `packages/fgtclb/`, `Build`                 |
| `exclude()`              | `.Build/`, `Build/`, `var/`, `node_modules` |
| `ignoreVCSIgnored(true)` | anything git ignores is skipped             |

So `packages-dev/`, `bin/`, `core-13/`, `core-14/` and the PHP files at the
repository root are **not** covered by this gate at all. A file placed there is
never reformatted and never reported — which is worth knowing before concluding
from a green run that the whole repository is formatted.

`ignoreVCSIgnored(true)` is what keeps generated trees out even when they sit
inside a scanned directory, so the gate does not depend on the `exclude()` list
staying complete.

## File headers — `cglHeader`

A second php-cs-fixer run with
[`Build/php-cs-fixer/header-comment.php`](../../Build/php-cs-fixer/header-comment.php),
which enables exactly two rules: `header_comment` and `no_extra_blank_lines`,
with risky rules disabled (`Build/php-cs-fixer/header-comment.php:59-71`). The
header is placed `after_declare_strict` and reads "This file is part of the
fgtclb/academic extension collection."
(`Build/php-cs-fixer/header-comment.php:46-57`).

Its Finder scans the same two roots but skips `Configuration`, `Documentation`,
`node_modules` and `Acceptance/Support/_generated`, plus the files that must not
carry a header: `*locallang*.php`, `ext_localconf.php`, `ext_tables.php`,
`ext_emconf.php` and `ClassAliasMap.php`
(`Build/php-cs-fixer/header-comment.php:26-43`).

> [!IMPORTANT]
> **This gate is currently not enforced.** Its CI step is commented out with a
> `@todo` — "Disabled until the correct file header has been determined for
> extensions" — the commented-out *CGL (header comments)* step of the `cgl`
> job. Running it locally without
> `-n` therefore rewrites headers across the code base and produces a diff
> nobody asked for. Run it with `-n` if at all, until the header is settled.

## PHP linting — `lintPhp`

`php -l` over every `*.php` in the repository, four processes in parallel, with
Xdebug off. It has no configuration file; the specification is the `find`
invocation in its `case` arm, and its exclusions are load-bearing:

* `./.Build/*` — the installed vendor tree.
* `./.agent/*` — the git-ignored working tree for drafts and partial snippets;
  a snippet that does not parse must not turn this gate red.
* `./core-1*/vendor/*`, `./core-1*/public/*`, `./core-1*/var/*` — the
  development instances' generated trees. `typo3/class-alias-loader` ships a
  template file there that is deliberately not valid PHP.

The instances' **tracked** `config/system/*.php` is deliberately still linted.

This is the only gate that needs neither a vendor tree nor a core version,
which is why the `lint` job runs it without `composerUpdate` and across all
four PHP versions. It is also the cheapest way to
find a syntax error that is specific to one PHP version.

## Static analysis — `phpstan`

PHPStan runs at **level 8** against `packages/`, excluding `ext_emconf.php`,
`EXT_CONSTANTS.php` and `Migrations/*`
(`Build/phpstan/Core13/phpstan.neon:13-21`). Three extension packages are
included from the installed vendor tree: `bnf/phpstan-psr-container`,
`friendsoftypo3/phpstan-typo3` and `phpstan/phpstan-phpunit`.

### Why it is configured per core version

`-t` selects `Build/phpstan/Core${CORE_VERSION}/phpstan.neon` in the `phpstan`
arm. PHPStan analyses the sources **against
the core that is installed in `.Build/`**, so the same code produces different
findings on v13 and on v14: a method that exists only in one of them, a
signature that changed, a return type that was narrowed. Running the gate for
one version would miss half of them. It is the only source gate CI runs per
core version — its `phpstan` job has the core version as a matrix axis.

The two `phpstan.neon` files are byte-identical today. The version specific
part sits next to them:

| File                    | Core13                                     | Core14                                   |
|-------------------------|--------------------------------------------|------------------------------------------|
| `phpstan.neon`          | identical                                  | identical                                |
| `phpstan-constants.php` | `#[Cascade]` constants as `['value' => …]` | `#[Cascade]` constants as a plain string |
| `phpstan-baseline.neon` | its own findings, 216 lines                | its own findings, 231 lines              |

`phpstan-constants.php` is loaded as a `bootstrapFile` and mirrors
`packages/fgtclb/academic-persons/EXT_CONSTANTS.php`, which resolves the
Extbase `#[Cascade]` attribute shape that differs between the two versions.
That single file is why the identical `phpstan.neon` pair must stay a pair.

If a core-version-specific source folder is ever introduced, it has to be added
to the `paths` of the matching configuration — nothing discovers it
automatically.

### The baseline

Each core version has its own `phpstan-baseline.neon`, included from its
`phpstan.neon:1`. It is regenerated per version:

```bash
Build/Scripts/runTests.sh -t 13 -s phpstanGenerateBaseline
Build/Scripts/runTests.sh -t 14 -s phpstanGenerateBaseline
```

which writes `Build/phpstan/Core<version>/phpstan-baseline.neon` with
`--allow-empty-baseline`, in the `phpstanGenerateBaseline` arm.

**A growing baseline is a defect, not a configuration change.** Regenerating it
to make a new finding disappear removes exactly the signal the gate exists for,
and it does so silently: the pull request stays green and the entry is one more
line in a file nobody reads. Fix the finding; regenerate only when the tooling
itself changed, and say so in the commit message.

### A stale result cache after switching core versions

PHPStan's result cache lives in `.cache/phpstan`
(`Build/phpstan/Core13/phpstan.neon:11`) and is **shared by both core
versions** — the `tmpDir` is the same path in both configurations. After
switching from one `-t` to the other, a run has been seen to report entries
like "Ignored error pattern … was not matched in reported errors" for baseline
entries that are perfectly valid, because the finding they refer to came from
the cache instead of being reported again. Before treating such a report as a
change to the baseline, clear the cache and run once more:

```bash
rm -rf .cache/phpstan
Build/Scripts/runTests.sh -t 14 -p 8.2 -s phpstan
```

The directory holds nothing but cached analysis results, so removing it costs
one slower run and never loses anything.

## Tests — `unit`, `unitRandom`, `functional`

`unit` and `unitRandom` use
[`Build/phpunit/UnitTests.xml`](../../Build/phpunit/UnitTests.xml),
`functional` uses
[`Build/phpunit/FunctionalTests.xml`](../../Build/phpunit/FunctionalTests.xml),
each named in its own `case` arm. `unitRandom` adds
`--order-by=random` and the seed from `-o`, which is how an order dependency
between tests is found and then replayed.

### Strictness

Both configurations carry the same flags. Four `failOn*` switches are on, which
means the suite is red for far more than a failed assertion:

| Setting                                   | Value   | Effect                                            |
|-------------------------------------------|---------|---------------------------------------------------|
| `failOnDeprecation`                       | `true`  | A triggered deprecation fails the test.           |
| `failOnNotice`                            | `true`  | A notice fails the test.                          |
| `failOnRisky`                             | `true`  | A risky test fails.                               |
| `failOnWarning`                           | `true`  | A warning fails the test.                         |
| `beStrictAboutTestsThatDoNotTestAnything` | `false` | A test without an assertion passes silently.      |
| `backupGlobals`                           | `true`  | Globals are restored between tests.               |
| `cacheResult`                             | `false` | No result cache, every run starts from scratch.   |
| `requireCoverageMetadata`                 | `false` | No coverage annotation required.                  |
| `displayDetailsOnTestsThatTrigger…`       | `true`  | Deprecations, errors, notices and warnings shown. |

(`Build/phpunit/UnitTests.xml:20-34`, `Build/phpunit/FunctionalTests.xml:20-34`.)

Two of these have practical consequences worth stating.

`failOnDeprecation="true"` makes the test suite the enforcement point for
TYPO3 deprecations. Code that calls a v14-deprecated API turns the suite red on
v14 even when it behaves correctly — which is intended, and is why some
migrations are blocked until v13 support is dropped rather than being worked
around.

`beStrictAboutTestsThatDoNotTestAnything="false"` is the one strictness knob
that is off. A test that asserts nothing — because the assertion was lost in a
refactoring, or because the subject silently returned early — is reported as
passing. Nothing in the harness will tell you; a new test has to be shown to
fail before it is accepted as proof.

### Discovery: all extensions at once

```xml
<directory>../../packages/*/*/Tests/Unit/</directory>
<directory>../../packages/*/*/Tests/Functional/</directory>
```

(`Build/phpunit/UnitTests.xml:42`, `Build/phpunit/FunctionalTests.xml:42`.)

The glob is `packages/*/*`, so it picks up the tests of **every** extension in
the mono repository in one run — twelve of them under `packages/fgtclb/` today
— and would pick up another vendor directory as well.

**There is no per-extension PHPUnit configuration**, and adding one would be a
step backwards: the extensions depend on each other, and a test suite that only
sees one of them cannot detect that a change in `academic-base` broke
`academic-persons`. Restrict a run with the trailing path instead:

```bash
Build/Scripts/runTests.sh -t 13 -s functional \
  packages/fgtclb/academic-persons/Tests/Functional
```

### Functional tests and the DBMS

`functional` starts the database in its own container, waits for the port,
hands the connection parameters to PHPUnit as environment variables and removes
the container afterwards — all of it in the `functional` arm. With the
default `-d sqlite` no container is started; the databases are created in a
tmpfs below `.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/`.

SQLite is the fast default, not the complete one. It accepts SQL that MariaDB,
MySQL and PostgreSQL reject, so a defect in a query can pass locally and fail
in the DBMS matrix. Run PostgreSQL as well for anything that writes:

```bash
Build/Scripts/runTests.sh -t 13 -s functional -d postgres -i 16
```

`functional` also excludes the group `not-${DBMS}`, so a test that cannot work
on one DBMS can
be tagged `#[Group('not-postgres')]` instead of being skipped at runtime. No
test uses this at the moment; a DBMS difference has so far always been a defect
in the query, not a property of the test.

## Core version aware tests: `not-core-13` and `not-core-14`

Every PHPUnit suite is started with `--exclude-group not-core-${CORE_VERSION}`
— the `functional`, `unit` and `unitRandom` arms. The name reads as an
exclusion, so the effect is inverted from what it looks like:

| Group attribute           | Runs on |
|---------------------------|---------|
| `#[Group('not-core-13')]` | v14     |
| `#[Group('not-core-14')]` | v13     |

Two shapes are in use, and the choice follows the size of the difference:

* **Whole class**, when the test only makes sense on one version — the
  attribute goes on the class:
  `packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormUploadTest.php:32`
  and
  `packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileImageUploadTest.php:29`.
* **Single method**, when a class differs in one behaviour only:
  `packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php:565` is
  `not-core-14`, and `:584` is `not-core-13` — the two halves of the same
  assertion about a signature that changed between the versions.

The `Tests/Unit/Core13/` and `Tests/Unit/Core14/` folder split is the other
option; no extension uses it at the moment.

A group attribute is invisible in a green run. When a test is tagged, the
reason belongs in the code as a comment or in the commit message, otherwise the
tag outlives the incompatibility that justified it.

## Documentation rendering — `checkRstRenderingAll` / `checkRstRenderingSingle`

The user-facing manuals live per package in
`packages/fgtclb/<extension>/Documentation/` and are reST, not Markdown.
`checkRstRenderingAll` iterates over every extension folder that has a
`Documentation/` directory — twelve today — and renders each one with the
`ghcr.io/typo3-documentation/render-guides` image; the rendering itself is in
`executeRstRendering()`.

**This is a real gate, not an artifact producer.** The renderer is invoked with
`--fail-on-log --fail-on-error`, so a warning fails the job. The results are
collected in `documentation-rendered/<extension>/Documentation-GENERATED-temp/`,
which CI uploads as an artifact.

```bash
# Everything.
Build/Scripts/runTests.sh -s checkRstRenderingAll

# One extension, by folder name below packages/fgtclb/.
Build/Scripts/runTests.sh -s checkRstRenderingSingle academic-persons

# Open the result (Linux only).
Build/Scripts/runTests.sh -s openDocumentation academic-persons
```

Note the argument is the **folder** name, which is not always the extension
key — `academic-contact4pages` ships `academic_contacts4pages`.

## Markdown documentation — `lintMarkdown`

The counterpart for the Markdown half: `docs/`, the files at the repository
root, and the per-package `README.md` and `CONTRIBUTING.md`. `Build/markdown.mjs`
checks four conventions:

| Convention                                                   | Fixable |
|--------------------------------------------------------------|---------|
| Relative links resolve to a file that exists                 | no      |
| Table rows are padded so the pipes line up                   | yes     |
| No trailing whitespace, and one newline at the end of file   | yes     |
| Every `docs/` page but an `Index.md` ends in a `## See also` | no      |

It mirrors `cgl`: it repairs in place by default and only reports with `-n`,
which is the form CI uses. What it will not do is invent a decision — a link
that points nowhere and a page without a *See also* are reported, never
rewritten.

```bash
# Report, change nothing. This is what CI runs.
Build/Scripts/runTests.sh -s lintMarkdown -n

# Repair the padding and the whitespace, then report what is left.
Build/Scripts/runTests.sh -s lintMarkdown
```

Two properties are worth knowing. It **skips symlinks**, so `AGENTS.md` is
checked once rather than four times through `CLAUDE.md`, `GEMINI.md` and
`.github/copilot-instructions.md`. And it uses nothing but the node standard
library, which is why it is the one node suite that runs without an `npm ci`
first.

The per-package files are in scope on purpose: they are the front page of the
split repository each package is mirrored into, so a dead link there is seen by
someone who is not us. That is how the missing `UPGRADE.md` of ten packages was
found (ACE-399).

## Continuous integration

[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) is the single
pull-request workflow. The TYPO3 core version is a matrix dimension rather than
a separate workflow file, and that is what makes the staging possible at all:
job dependencies cannot cross workflows, so the earlier layout of one workflow
file per core version had no way to make the expensive jobs wait for the cheap
ones.

```
cgl     ─┐
phpstan ─┼─> unit ─> functional (SQLite) ─> functional (MySQL, MariaDB, Postgres)
lint    ─┘

frontend assets, markdown, documentation   (independent)
```

| Job                 | Needs              | Matrix                     |
|---------------------|--------------------|----------------------------|
| `cgl`               | —                  | PHP 8.2, v13               |
| `phpstan`           | —                  | PHP 8.2 × v13, v14         |
| `lint`              | —                  | PHP 8.2, 8.3, 8.4, 8.5     |
| `unit`              | cgl, phpstan, lint | PHP 8.2, 8.5 × v13, v14    |
| `functional-sqlite` | unit               | PHP 8.2, 8.5 × v13, v14    |
| `functional-dbms`   | functional-sqlite  | the same × 4 DBMS, 16 jobs |
| `frontend-assets`   | —                  | none                       |
| `markdown`          | —                  | none                       |
| `documentation`     | —                  | none                       |
| `all checks`        | all of the above   | none                       |

`frontend-assets`, `markdown` and `documentation` carry no matrix because none
of them reads the installed core: they look at sources and committed artifacts,
so repeating them per core and PHP version would check the same files four
times.

`all checks` runs no gate of its own. It needs every other job, runs with
`if: always()` so that it reports rather than being skipped, and treats a
skipped dependency as a failure. It is the **required status check** of the
`required_status_checks` ruleset — the one check whose name does not move with
the matrix — so a pull request whose pipeline is not green cannot be merged, by
anybody. See [Pull requests](../workflow/pull-requests.md).

The DBMS matrix is the expensive part — sixteen jobs, each starting a database
container. It runs only after the same tests passed on SQLite for both core
versions and both edge PHP versions, so a defect that is not DBMS specific is
reported by four jobs instead of twenty — `functional-dbms` needs
`functional-sqlite`.

### Three PHP sets

They are three, and they have to be changed together — the table in the header
comment of `ci.yml` records which set each job uses:

| Set    | PHP versions       | Used by              | Why                                            |
|--------|--------------------|----------------------|------------------------------------------------|
| all    | 8.2, 8.3, 8.4, 8.5 | `lint`               | A syntax error can be specific to one version. |
| edges  | 8.2, 8.5           | `unit`, `functional` | The ends of the supported range.               |
| lowest | 8.2                | `cgl`, `phpstan`     | They inspect files, not the running PHP.       |

### Every step goes through `runTests.sh`

There is no `composer install` step, no `php -l` step and no PHPUnit
invocation in the workflow — every step is a `Build/Scripts/runTests.sh` call.
A gate therefore cannot behave differently in CI than locally, and reproducing
a red pipeline is a matter of copying the command from the log.

The two deviations from a plain local run are both explained in the workflow
itself: `-b docker` on every step, explained in its header comment, and the
`composerUpdate` that precedes every job needing a vendor tree.

### Why the pull-request comment is a separate workflow

[`.github/workflows/pr-comment.yml`](../../.github/workflows/pr-comment.yml)
posts one comment, updated in place, linking the rendered documentation
artifact. It is a separate workflow on the `workflow_run` event on purpose.

A pull request **from a fork** gets a read-only `GITHUB_TOKEN` and no secrets.
Commenting needs `pull-requests: write`, so a comment step inside `ci.yml`
would work for branches in this repository and silently fail for exactly the
contributors it is meant to serve. `ci.yml` therefore declares
`permissions: contents: read` and writes nothing at all.

`workflow_run` fires when `ci.yml` finishes, runs in the context of the default
branch rather than the fork, and its token can write. No code from the pull
request is checked out or executed there, which is what makes the write
permission safe. `pull_request_target` is deliberately not used: it also has a
write token, but it runs the pull request's own code under it.

Two consequences follow, and both have cost time before:

1. **A change to `pr-comment.yml` only takes effect once it is on the default
   branch.** It never changes the behaviour of the pull request that changes
   it.
2. `github.event.workflow_run.pull_requests` is empty for a fork, so the pull
   request number cannot be read from the event. `ci.yml` writes it into the
   `pull-request-context` artifact before rendering — the *Record the pull
   request number* and *Upload the pull request context* steps of its
   `documentation` job — so the comment lands on the right pull request even
   when the rendering fails.

## Before pushing

* `lintPhp`, `cgl -n`, `phpstan` and `unit` green for **every** core version
  the branch supports, each after its own `composerUpdate`.
* `functional` green for the same versions whenever the change can affect
  runtime behaviour, and against a real DBMS when it writes.
* New behaviour has a test, and the test was shown to fail without the change —
  the suite will not tell you, see `beStrictAboutTestsThatDoNotTestAnything`
  above.
* `checkRstRenderingAll` when any `Documentation/` changed.

## See also

- [Development environment](environment.md) — the wrapper, its options and the
  dependency rules these gates depend on.
- [Dual core setup](dual-core-setup.md) — running every gate twice, and how the
  test groups follow from it.
- [PHPUnit configuration](../testing/phpunit-configuration.md) — the two
  configurations and their bootstraps in detail.
- [Core version aware code](../architecture/core-version-aware-code.md)
- [Pull requests](../workflow/pull-requests.md) — what has to be green before
  pushing, and how to read a red pipeline.
- [`AGENTS.md`](../../AGENTS.md) — the short form, including the definition of
  done.
- [`CONTRIBUTING.md`](../../CONTRIBUTING.md)
