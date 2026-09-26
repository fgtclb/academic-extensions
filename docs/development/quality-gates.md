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
| `testJs`                                          | `node --test`, jsdom | `Build/tsconfig.tests.json`, `Build/tests/`      | no                          |

## Coding guidelines — `cgl`

The `cgl` arm of `runTests.sh` runs php-cs-fixer with
[`Build/php-cs-fixer/config.php`](../../Build/php-cs-fixer/config.php). Without
`-n` it **rewrites files in place**; with `-n` it adds `--dry-run --diff` and
only reports, which is the form CI uses in its `cgl` job.

The rule set is `@PER-CS1x0` plus `@DoctrineAnnotation` and some fifty
individual rules (`Build/php-cs-fixer/config.php:70-139`), risky rules allowed.
It is TYPO3 Core's set, with the same `@todo` markers for the rules that can be
dropped once `@PER-CS2x0` is adopted. That spelling needs php-cs-fixer 3.88.0 or
newer — below it the set is named `@PER-CS1.0` and the config aborts the run on
an unknown rule set, so the floor in `composer.json` must not be relaxed past
it.

What it scans matters more than the rules, because it is narrower than the
repository (`Build/php-cs-fixer/config.php:49-68`):

| Finder call              | Value                                        |
|--------------------------|----------------------------------------------|
| `in()`                   | `packages/fgtclb/`, `packages-dev/`, `Build` |
| `exclude()`              | `.Build/`, `Build/`, `var/`, `node_modules`  |
| `ignoreVCSIgnored(true)` | anything git ignores is skipped              |

So `bin/`, `core-13/`, `core-14/` and the PHP files at the repository root are
**not** covered by this gate at all. A file placed there is never reformatted
and never reported — which is worth knowing before concluding from a green run
that the whole repository is formatted. `packages-dev/` *is* covered: all three
of its packages carry PHP and tests of their own, and a formatting standard
that stops at a directory boundary is one nobody remembers.

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
| `phpstan-baseline.neon` | its own findings, 216 lines                | its own findings, 226 lines              |

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
<directory>../../packages-dev/*/Tests/Unit/</directory>
```

(`Build/phpunit/UnitTests.xml:42` and `:49`; `FunctionalTests.xml` carries the
two `Tests/Functional/` globs on the same lines.)

The first glob is `packages/*/*`, so it picks up the tests of **every**
extension in the mono repository in one run — twelve of them under
`packages/fgtclb/` today — and would pick up another vendor directory as well.
The second collects `packages-dev/`, where all three packages carry tests of
their own: the seed definition of `packages-dev/dev-site`, the scripts behind
`runTests.sh -j` in `packages-dev/testing-helper`, and the `ext_emconf.php`
dependency key check and the extension name check of translations in
`packages-dev/monorepo-shared`.

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

The database containers keep their data directory in a tmpfs as well, and are
thrown away after the run. MySQL and MariaDB therefore start without a binary
log, without flushing the redo log on every commit and without the doublewrite
buffer (`MYSQL_SERVER_OPTIONS` in `runTests.sh`, ACE-698): durability buys
nothing there, and the functional jobs took 7 to 15 % less time on MySQL and 5
to 10 % less on MariaDB. The same settings for PostgreSQL made no measurable
difference and are not used.

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
  `packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileImageUploadTest.php:27`.
* **Single method**, when a class differs in one behaviour only:
  `packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php:568` is
  `not-core-14`, and `:592` is `not-core-13` — the two halves of the same
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
cgl     ─┐                ┌─> functional (SQLite)
phpstan ─┼─> unit ────────┤
lint    ─┘                └─> functional (MySQL, MariaDB, Postgres)

frontend assets, markdown, documentation   (independent)
```

| Job                 | Needs              | Matrix                     |
|---------------------|--------------------|----------------------------|
| `cgl`               | —                  | PHP 8.2, v13               |
| `phpstan`           | —                  | PHP 8.2 × v13, v14         |
| `lint`              | —                  | PHP 8.2, 8.3, 8.4, 8.5     |
| `unit`              | cgl, phpstan, lint | PHP 8.2, 8.5 × v13, v14    |
| `functional-sqlite` | unit               | PHP 8.2, 8.5 × v13, v14    |
| `functional-dbms`   | unit               | the same × 4 DBMS, 16 jobs |
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
container. It used to run only after the same tests had passed on SQLite, so a
defect that is not DBMS specific was reported by four jobs instead of twenty. It
now starts next to the SQLite jobs (ACE-694). In 400 pull request runs the
staging stopped a run 8 times, while it added the whole SQLite stage — 11
minutes before ACE-692, some 4 after it — to the critical path of every run that
went on to pass. A defect found on SQLite now costs the DBMS jobs of that run as
well.

Both functional jobs run their suite with `-j 4`, four chunks in parallel, one
per vCPU of a hosted runner (ACE-692). A single PHPUnit process left three of
the four idle. Split and run in parallel, the suite took 2.1 to 4 times less
time per job on `main`, measured on the same runner type. Each job uploads the
JUnit logs of its chunks, which is where the recorded durations the chunks are
balanced by are refreshed from — see
[Parallel functional runs](environment.md#parallel-functional-runs--j).

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

Every job runs on `ubuntu-26.04`, named rather than `ubuntu-latest` (ACE-697):
that label moves to Ubuntu 26.04 job by job over the weeks from October 19,
2026, while a named version moves every job at once. Both maintained branches
were probed on it before the switch. The label is shared by every workflow of
the repository — `ci.yml`, `nightly.yml`, `pr-comment.yml`, `publish.yml`, the
twelve package publish workflows and the extension template — and is changed
in all of them together.

The three deviations from a plain local run are all explained in the workflow
itself: `-b docker` on every step, explained in its header comment, the
`composerUpdate` that precedes every job needing a vendor tree, and a workflow
level `COMPOSER_AUTH` built from `github.token`, which `runTests.sh` forwards
into the container with a bare `-e COMPOSER_AUTH` so that a job with a cold
composer cache is not throttled to 60 dist downloads per hour (ACE-452).

The release workflows set the same variable for the same reason, but for tooling
they install on the runner host rather than in a container — see
[Releasing](../workflow/releasing.md).

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

### The nightly run

`ci.yml` runs for pull requests, so a merged state is never checked again, and
without a `composer.lock` a new TYPO3 or dependency release changes what a run
installs without any commit here. [`nightly.yml`](../../.github/workflows/nightly.yml)
therefore runs the complete `CI` workflow every night at 02:17 UTC, for `main`
and for `2` (ACE-693). It is the pull request run, job for job.

It does not carry a `schedule` of its own in `ci.yml` because a schedule only
fires on the default branch, with the workflow file of the default branch —
branch `2` has a `ci.yml` of its own that a schedule on `main` cannot reach.
`nightly.yml` lives on `main` and starts `ci.yml` on each branch through
`workflow_dispatch`, which runs the workflow file of the branch it is given.
Changes to `nightly.yml` therefore only take effect on `main`, and branch `2`
carries no copy of it.

Each of its two jobs then waits for the run it started and takes over its
result. A run started by a dispatch belongs to `github-actions` and notifies
nobody when it fails; the nightly job failing does, the way GitHub reports a
failed scheduled workflow. A nightly run on a branch and a pull request run
never cancel each other: `ci.yml` groups the one by branch and the other by
pull request number.

`Run workflow` on the *Nightly* workflow in the Actions tab starts the same
thing by hand.

### Reducing the pull request matrix — documented, not applied

Pull requests run all 16 DBMS jobs today. Should that become too expensive, the
matrix can shrink for pull requests while the nightly run keeps all of them.
These are the cells, chosen in the analysis of ACE-691:

A 4-cell variant covers every DBMS family and every core/PHP edge once, with
PostgreSQL on both core versions because it is where query defects surface:

| Core / PHP    | DBMS         |
|---------------|--------------|
| v13 / PHP 8.2 | postgres 10  |
| v14 / PHP 8.5 | postgres 10  |
| v14 / PHP 8.2 | mysql 8.0    |
| v13 / PHP 8.5 | mariadb 10.4 |

An 8-cell variant runs every DBMS on the lowest PHP version of each core version:
mysql 8.0, mariadb 10.4, mariadb 10.6 and postgres 10, each on v13 / PHP 8.2 and
v14 / PHP 8.2.

**If a reduction is applied, this page and the CI section of `AGENTS.md` must
name the executed matrix explicitly** — which cells a pull request runs, and
which only the nightly run covers — because a green pull request would then no
longer mean what it means today.

## Before pushing

* `lintPhp`, `cgl -n`, `phpstan` and `unit` green for **every** core version
  the branch supports, each after its own `composerUpdate`.
* `functional` green for the same versions whenever the change can affect
  runtime behaviour, and against a real DBMS when it writes. Run it with `-j
  auto` (or a fixed `-j <n>`): the chunks of one run are isolated from each
  other, and a serial run takes some 10 minutes on SQLite and some 37 on MySQL
  where `-j` takes 2 and 14 — see
  [Choosing a chunk count locally](environment.md#choosing-a-chunk-count-locally--j-auto).
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
- [Seed verification](../testing/seed-verification.md) — the one committed
  artifact a gate rewrites, and the suite that rewrites it.
- [Core version aware code](../architecture/core-version-aware-code.md)
- [Pull requests](../workflow/pull-requests.md) — what has to be green before
  pushing, and how to read a red pipeline.
- [Releasing](../workflow/releasing.md) — the publish workflows, and the token
  they share with continuous integration.
- [`AGENTS.md`](../../AGENTS.md) — the short form, including the definition of
  done.
- [`CONTRIBUTING.md`](../../CONTRIBUTING.md)
