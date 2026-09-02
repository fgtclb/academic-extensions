# Development environment

Every check in this repository runs inside a container, driven by
[`Build/Scripts/runTests.sh`](../../Build/Scripts/runTests.sh). Nothing is run
against the host PHP: the wrapper starts a `ghcr.io/typo3/core-testing-php8x`
image, mounts the repository into it and executes composer, PHPUnit, PHPStan,
php-cs-fixer or the documentation renderer there.

That is the whole point of the harness. A gate behaves identically on a
developer machine and on a GitHub runner because both go through the same
script and the same image, so "green here, red in the pipeline" is not a class
of problem that exists in this repository.

## Host requirements

One container runtime, and nothing else. No PHP, no Composer, no PHPUnit on the
host. The script refuses to start when neither runtime is installed:

```bash
if ! type "docker" >/dev/null 2>&1 && ! type "podman" >/dev/null 2>&1; then
    echo "This script relies on docker or podman. Please install" >&2
    exit 1
fi
```

**podman is preferred.** When `-b` is not given, `CONTAINER_BIN` is resolved
from the `PATH`: podman if it is there, docker otherwise. `-b docker|podman`
overrides the choice, and the `b)` arm of the option parsing rejects anything
else as an invalid option.

The two runtimes are not interchangeable in their details, which is why the
script branches on `CONTAINER_BIN` where it assembles
`CONTAINER_COMMON_PARAMS`:

* docker needs `--add-host host.docker.internal:host-gateway` for Xdebug to
  reach an IDE on the host; podman has `host.containers.internal` built in.
* The tmpfs holding the SQLite test databases is mounted with an explicit
  `uid`/`gid` under docker, because `--user $HOST_UID` passes a user but no
  group and the container would not own the mount. Under rootless podman the
  container root already maps to the host user.
* On Linux, podman gets the SELinux relabel flag (`:Z`) on the repository
  mount.

The GitHub workflows pass `-b docker` on every step. That is not a statement
about which runtime is better — it works around a crun failure on hosted
runners and is documented in the header comment of
[`ci.yml`](../../.github/workflows/ci.yml). There is no reason to pass it
locally.

Each run creates its own container network — `NETWORK` is
`academic-extensions-${SUFFIX}` — and `cleanUp()` removes it together with
every attached container, so parallel runs on one machine do not collide.

Functional runs need one thing more, because the collision there is not in the
containers but in the files they share. The testing framework puts each test
case's TYPO3 instance in `typo3temp/var/tests/functional-<identifier>`, and
derives that identifier as `substr(sha1(<test class>), 0, 7)` — the same value in
every run. Two runs in one checkout would therefore work in the same instance
directories, and `removeOldInstanceIfExists()` of the one would delete the
instance the other is using. It surfaced as `No such file or directory`, `no such
table` and `UNIQUE constraint failed` in tests that had nothing to do with each
other, and it cost roughly one functional run in three (ACE-440).

`runTests.sh` therefore gives every functional run its own
`typo3temp/var/tests-${SUFFIX}` on the host and bind mounts it where the testing
framework looks. A bind mount rather than a tmpfs deliberately: 150 test classes
at some 5 MB each would put the better part of a gigabyte into RAM.

**A green run removes its directory; a red one keeps it and prints the path.**
The instance of a failing test — its configuration, its `typo3temp`, the files
the test wrote — is usually where the answer is, so it is worth looking at before
running anything else. They are not cleaned up automatically, so delete them when
you are done:

```bash
rm -rf .Build/Web/typo3temp/var/tests-*
```

### A pseudo TTY only when there is a terminal

`CONTAINER_INTERACTIVE` is `-it --init` by default, and drops to `--init` when
stdin **or** stdout is not a terminal — a script runner, a pipe, a redirect, a
non-interactive shell. `CI=true` clears it entirely, as before. That distinction
is load-bearing rather than cosmetic.

`-t` makes the runtime allocate a pseudo TTY inside the container, and every tool
started there then believes it may ask a question. Composer does. When a plugin
is missing from `config.allow-plugins` it asks whether the plugin is trusted —
and in a scripted run nobody answers, so the whole invocation **hangs with no
output at all**. Measured: a root `-s composer` command with one `allow-plugins`
entry removed was still waiting when it was killed at 45 seconds; with the entry
restored the same command returns in seconds.

Nothing about it points at composer. It looks exactly like a stuck image pull or
a broken container runtime, which is why it cost the time it did (ACE-383).

Without the TTY the same run fails immediately and says what is wrong:

```
In PluginManager.php line 821:
  sbuerk/extended-path-repository contains a Composer plugin which is blocked
  by your allow-plugins config. You may add it to the list if you consider it safe.
  You can run "composer config --no-plugins allow-plugins.… [true|false]" to enable it
```

An interactive run keeps `-t`, so colours and progress bars are unchanged when a
person is watching. The `input device is not a TTY` warning that used to prefix
every scripted run is gone with it.

Checking stdout as well as stdin is not redundancy: `runTests.sh -s unit > log`
from a real terminal has a terminal on stdin and a file on stdout, and with `-t`
the container writes TTY control characters into that file.

The condition and its comment are taken verbatim from
`web-vision/a11y-by-default`, the one harness in the portfolio that already had
them, so the two do not drift apart.

## Options

The wrapper parses exactly these options in its `while getopts` loop;
everything left over after them is the optional trailing argument.

| Option            | Meaning                                                                             | Default             |
|-------------------|-------------------------------------------------------------------------------------|---------------------|
| `-s <suite>`      | Suite to run. See the table below.                                                  | `help`              |
| `-t <13\|14>`     | TYPO3 core major version. Selects the dependency set and the PHPStan configuration. | `13`                |
| `-p <8.2…8.5>`    | PHP version, one of `8.2`, `8.3`, `8.4`, `8.5`. Picks the testing image.            | `8.2`               |
| `-d <dbms>`       | DBMS for functional tests: `sqlite`, `mariadb`, `mysql`, `postgres`.                | `sqlite`            |
| `-i <version>`    | DBMS version, only with `-d mariadb\|mysql\|postgres`.                              | per DBMS, see below |
| `-a <driver>`     | Database driver, only with `-d mariadb\|mysql`: `mysqli` or `pdo_mysql`.            | `mysqli`            |
| `-n`              | Dry run for `cgl`, `cglHeader`, `lintMarkdown` and `lintTypescript`.                | off                 |
| `-x`              | Enable Xdebug and send debugging information to the host IDE.                       | off                 |
| `-y <port>`       | Xdebug client port on the host, when the IDE does not listen on the default.        | `9003`              |
| `-o <seed>`       | Random order seed for `unitRandom`, to replay a specific order.                     | none                |
| `-u`              | Update the local `typo3/core-testing-*` images and drop dangling ones.              | —                   |
| `-b <runtime>`    | Container runtime, `docker` or `podman`.                                            | podman, else docker |
| `-h`              | Print the help text and exit.                                                       | —                   |
| trailing `[file]` | Path passed on to the tool of the suite — for PHPUnit a test file or directory.     | none                |

`-u` is not a modifier: its `u)` arm sets `TEST_SUITE=update`, so it cannot be
combined with `-s`.

`-t` and `-p` are validated against a fixed list in their own `getopts` arms;
anything else aborts the run before a container is started.

The `-a`, `-d` and `-i` combinations are validated together in
`handleDbmsOptions()`, which also holds the per-DBMS defaults and the accepted
version lists:

| `-d`       | Default `-i` | Accepted `-i`                     | `-a`                              |
|------------|--------------|-----------------------------------|-----------------------------------|
| `sqlite`   | —            | rejected                          | rejected                          |
| `mariadb`  | `10.4`       | `10.4` … `10.11`, `11.0` … `11.4` | `mysqli` (default) or `pdo_mysql` |
| `mysql`    | `8.0`        | `8.0`, `8.1`, `8.2`, `8.3`, `8.4` | `mysqli` (default) or `pdo_mysql` |
| `postgres` | `10`         | `10` … `16`                       | rejected                          |

### Restricting a run

**There is no option for handing extra arguments to PHPUnit.** No `-e`, and no
suite that takes a filter. A run is restricted with the trailing path:

```bash
# One directory.
Build/Scripts/runTests.sh -t 13 -s functional \
  packages/fgtclb/academic-persons/Tests/Functional/Domain

# One file.
Build/Scripts/runTests.sh -t 13 -s unit \
  packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/ProfileInformationTest.php
```

The trailing argument means different things per suite. For `unit`,
`unitRandom`, `functional` and `phpstan` it is appended to the tool's command
line, in each of those four `case` arms. For `checkRstRenderingSingle` and
`openDocumentation` it is not a path at all but the **extension folder name**
below `packages/fgtclb/`. For `composer` it is the composer command with its
arguments.

A `--` separator ends the wrapper's own option parsing, and the remainder
reaches the dispatched tool, because every one of those suites appends `"$@"`.
It is the supported way to hand an option to phpunit, composer or npm, and it
is documented in `-h` since ACE-396:

```bash
Build/Scripts/runTests.sh -s unit -- --filter SomeTest
Build/Scripts/runTests.sh -s npm -- install --save-dev sass@latest
```

For the two cases that have a dedicated option, prefer it: `-o` for the random
order seed and the trailing path for restricting a phpunit run. Neither
workflow in `.github/workflows/` uses the separator.

## Suites

Taken from the `case ${TEST_SUITE} in` statement, which is the authority — the
help text is prose and can drift, see below.

| Suite                     | What it runs                                                                                                  |
|---------------------------|---------------------------------------------------------------------------------------------------------------|
| `cgl`                     | php-cs-fixer with `Build/php-cs-fixer/config.php`. Fixes in place, `-n` only reports.                         |
| `cglHeader`               | php-cs-fixer with `Build/php-cs-fixer/header-comment.php` for the file header.                                |
| `checkRstRenderingAll`    | Renders `Documentation/` of every extension in `packages/fgtclb/`.                                            |
| `checkRstRenderingSingle` | The same for one extension folder, given as trailing argument.                                                |
| `composer`                | `composer` with all remaining arguments dispatched into the container.                                        |
| `composerUpdate`          | Installs the dependency set for `-t`, see below.                                                              |
| `functional`              | PHPUnit with `Build/phpunit/FunctionalTests.xml` against the DBMS from `-d`.                                  |
| `lintMarkdown`            | `Build/markdown.mjs` over every Markdown file. Fixes in place, `-n` only reports.                             |
| `lintPhp`                 | `php -l` over every `*.php` outside the excluded trees.                                                       |
| `openDocumentation`       | Opens a previously rendered documentation in the browser (Linux only, `xdg-open`).                            |
| `phpstan`                 | PHPStan with `Build/phpstan/Core<13\|14>/phpstan.neon`.                                                       |
| `phpstanGenerateBaseline` | Rewrites `Build/phpstan/Core<13\|14>/phpstan-baseline.neon`.                                                  |
| `seedManifest`            | Rewrites `packages-dev/dev-site/Tests/Functional/Fixtures/SeedManifest-core<13\|14>.json` from a real import. |
| `unit`                    | PHPUnit with `Build/phpunit/UnitTests.xml`.                                                                   |
| `unitRandom`              | The same, with `--order-by=random` and the seed from `-o`.                                                    |
| `update`                  | Pulls newer `ghcr.io/typo3/core-testing-*` images and removes dangling ones. Also reached via `-u`.           |
| `help`                    | Prints the help text. This is the default when `-s` is omitted.                                               |

Anything else falls to the `*)` arm, which prints the help and exits non-zero.

### The help text

`-h` renders a text block maintained by hand in `loadHelp()`. It had drifted
from the `case` statement, because the wrapper started as a copy of the TYPO3
Core runner and inherited its help: `-t` was documented for
`composerInstall|composerInstallMin|composerInstallMax`, `-n` for
`cglGit|cglHeaderGit`, `-a` and `-d` for `functionalDeprecated` and the
acceptance suites, and `-o` was not documented at all — none of those suites
exists here. That was corrected in ACE-396, and the help now names the suites
and options this script really has.

Two things are deliberately not derived from the script and can still go stale,
so read the `case` statement and the `getopts` string when a claim matters:

* The suite list and the option descriptions are prose. A new suite has to be
  added to both the `case` statement and `loadHelp()`.
* The `-i` lists carry TYPO3 Core's maintenance annotations for the DBMS
  versions. They describe the products, not this repository, and are as dated
  as the day they were copied. The values themselves are checked against the
  regular expressions in `handleDbmsOptions()`, so an unsupported one is
  rejected rather than silently used.

## Quick start

```bash
# 1. Install the dependency set for the core and PHP version to be tested.
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate

# 2. Run the gates.
Build/Scripts/runTests.sh -t 13 -p 8.2 -s lintPhp
Build/Scripts/runTests.sh -t 13 -p 8.2 -s cgl -n
Build/Scripts/runTests.sh -t 13 -p 8.2 -s phpstan
Build/Scripts/runTests.sh -t 13 -p 8.2 -s unit
Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional

# 3. Repeat for the other core version supported by this branch.
Build/Scripts/runTests.sh -t 14 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 14 -p 8.2 -s phpstan
# ...
```

## Dependencies: one tree, one core version, one PHP version

The `composerUpdate` arm does four things:

1. `rm -rf .Build composer.lock composer.json.orig` — the vendor tree is
   rebuilt from scratch, never patched.
2. Copies `composer.json` aside, then runs
   `composer require --dev --no-update typo3/minimal:"^${CORE_VERSION}"` inside
   the container, which is how `-t` reaches the dependency resolution.
3. `composer install`.
4. Restores the original `composer.json` from the copy, so the requirement
   added in step 2 never ends up in a commit.

Everything is installed into the git-ignored `.Build/` directory: the root
`composer.json` sets `vendor-dir` to `.Build/vendor`, `bin-dir` to
`.Build/bin`, and TYPO3's `web-dir` to `.Build/Web`. That is why every suite
calls `.Build/bin/phpunit`, `.Build/bin/phpstan` and `.Build/bin/php-cs-fixer`
rather than a globally installed tool.

Two consequences that cause most of the confusing local failures:

* **`-t` selects configuration, not dependencies.** Running `-t 14 -s phpstan`
  after a `-t 13 -s composerUpdate` analyses v14-flavoured configuration
  against a v13 vendor tree. The result is neither a pass nor a meaningful
  failure.
* **`-p` needs a `composerUpdate` just as much as `-t`.** The lock file is
  resolved for the PHP version of the container that resolved it, so switching
  `-p` without reinstalling can run a tree that the new PHP version was never
  offered.

The workflows follow this rule literally: every job that needs a vendor tree
runs `composerUpdate` for its own `-t`/`-p` pair first, and the `lint` job,
which needs no vendor tree, does not run it at all — see its `lint` job.

### Git worktrees

A `git worktree` is a supported checkout. `runTests.sh` mounts the repository's
common git directory alongside the working tree, because a worktree's `.git` is
a file pointing into the main checkout and everything git needs would otherwise
be outside the mount.

What that costs is a dependency set per worktree, not per branch: `.Build/`,
`.cache/` and `composer.lock` are all git-ignored and therefore per checkout, so
a fresh worktree starts cold and needs its own `-s composerUpdate` for each core
version before any suite that needs dependencies will run.

Without the mount the failure is misleading — composer cannot determine a
version for the path packages without git, so the install stops on the one path
package that carries no `branch-alias` rather than on anything git shaped:

```text
Root composer.json requires fgtclb/academics-monorepo-shared ~3.0.0@dev,
found fgtclb/academics-monorepo-shared[dev-main] but it does not match.
```

## Caches: `.cache/` at the repository root

Composer downloads land in `.cache/composer` and the PHPStan result cache in
`.cache/phpstan`. The directory is created before any container starts
by an explicit `mkdir -p .cache/composer` before any container starts, passed
in as `COMPOSER_CACHE_DIR` on every composer-facing suite, and configured as
PHPStan's `tmpDir`
(`Build/phpstan/Core13/phpstan.neon:11`).

It sits **next to** `.Build/`, not inside it, and that placement is deliberate.
`composerUpdate` begins with `rm -rf .Build`, so a cache below it would be
thrown away on every single dependency install — locally, and once per job in
CI. The reasoning is recorded in `.gitignore:17-20`.

The workflows cache only `.cache/composer/files` — the content-addressed dist
archives — and never the `repo/` metadata next to it, because restoring stale
packagist metadata makes the `restore-keys` fallback resolve against an old
package list — see the *Cache composer downloads* step in `ci.yml`.

`.cache` and `.php-cs-fixer.cache` are what `cleanCacheFiles()` removes.

## Container images

| Purpose          | Image                                              |
|------------------|----------------------------------------------------|
| PHP, all suites  | `ghcr.io/typo3/core-testing-php<version>:latest`   |
| Documentation    | `ghcr.io/typo3-documentation/render-guides:latest` |
| MariaDB          | `docker.io/mariadb:<-i>`                           |
| MySQL            | `docker.io/mysql:<-i>`                             |
| PostgreSQL       | `docker.io/postgres:<-i>-alpine`                   |
| Port wait helper | `docker.io/alpine:3.8`                             |

Defined in the `IMAGE_*` variables. The `docker.io` images are pulled through
`ensureImage()`, which
checks the local image first and retries a failed pull up to three times: an
anonymous Docker Hub pull is rate limited per source IP, hosted runners share
address space, and a single failed pull otherwise ends a job with exit 125
before a test has run. The `ghcr.io` images have never needed it, which is why
only the Docker Hub ones are guarded.

`-u` refreshes the PHP images that already exist locally and removes the
dangling ones, in the `update` arm. Run it when tests start
failing in ways that do not match the code.

## Xdebug

`-x` switches the PHP container from `XDEBUG_MODE=off` to `debug` and points
the client at the host through `XDEBUG_MODE` and `XDEBUG_CONFIG`. The host
address
differs per runtime — `host.docker.internal` for docker,
`host.containers.internal` for podman — and the wrapper sets the right one.
`-y <port>` changes the client port when the IDE does not listen on 9003.

```bash
Build/Scripts/runTests.sh -x -p 8.3 -s unit \
  packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/ProfileInformationTest.php
```

## Development instances are not part of the harness

`core-13/` and `core-14/` at the repository root are ready-to-start TYPO3
instances, one per supported core version, backed by SQLite and seeded from the
committed templates in `sqlite-databases/`. They are for looking at the
extensions in a running backend and frontend.

**`runTests.sh` never touches them.** They have their own `composer.json` and
their own `composer.lock`, their own git-ignored `vendor/`, `public/` and
`var/` trees, and they play no part in any suite. The single point of contact
is negative: `lintPhp` explicitly excludes `./core-1*/vendor/`,
`./core-1*/public/` and `./core-1*/var/` from the `find` in its `case` arm, because those are
third-party trees —
`typo3/class-alias-loader` ships a template file that is deliberately not valid
PHP. The instances' tracked `config/system/*.php` is still linted, on purpose.

Do not run a gate expecting an instance to be updated, and do not fix a failing
instance by running `composerUpdate` — that installs the root project's
dependency tree into `.Build/`, which is a different thing entirely. Instance
handling, the DDEV project names, the branch switching collision, the backend
and frontend accounts and what the seed puts on which page are described in
[Development instances](instances.md).

### Snapshotting an instance database is not a copy

Both directions of the snapshot — `ddev composer sqlite:backup` and
`ddev composer sqlite:apply` — go through
[`Build/Scripts/sqliteSnapshot.php`](../../Build/Scripts/sqliteSnapshot.php)
rather than `cp`. That is not a refinement. A plain copy is **wrong** here, and
it fails silently.

SQLite in write ahead logging mode keeps the newest transactions in a `-wal`
sidecar until a checkpoint folds them back into the main file. The checkpoint
happens when the *last* connection closes — and a backup is taken while the
instance is running, so php-fpm is holding one. Until it closes, the main file
can be almost empty.

Measured on PHP 8.2 with `pdo_sqlite`, 500 rows written in WAL mode with the
writer deliberately kept open:

| File              | Size    |
|-------------------|---------|
| `live.sqlite`     | 4 kB    |
| `live.sqlite-wal` | 2076 kB |

The plain copy of the main file opened, and then reported `no such table` for
every table the database had. That is the shape of the defect: not an error at
backup time, but a committed template that turns out to be empty the next time
somebody restores from it. The `cache:flush` that `sqlite:backup` runs first
does not help — it flushes TYPO3 caches, not the write ahead log.

The helper therefore checkpoints the source with `PRAGMA wal_checkpoint(TRUNCATE)`
before copying, removes the `-wal` and `-shm` sidecars of the file it replaces
— they belong to the database being overwritten, never to its replacement — and
verifies the result by opening it and counting its tables. It exits non-zero
with a readable message when the source is missing or the copy cannot be opened.

The checkpoint is harmless in any other journal mode; the pragma then reports
that there was nothing to do, which is why the helper needs no journal mode
detection. It uses nothing but PHP with `pdo_sqlite`, because it is called from
an instance whose dependencies may not be installed yet — and it is reachable at
the same relative path on both sides, because each instance bind-mounts the
repository's `Build/` into its container
(`core-*/.ddev/docker-compose.mounts.yaml`).

#### And a snapshot carries no runtime rows

`sqlite:backup` runs `cache:flush` before the copy, which is right and is not
enough. The flush and the copy are two steps against a **running** instance, so
any request that lands between them refills `cache_pages` and `cache_rootline`,
and the helper is usable on its own — where nothing flushes anything at all.

The search index is the larger half of the same problem, and no cache flush has
ever touched it. Measured on `core-13` after one crawl of the seeded tree:

| Table                    | Live instance | Committed template |
|--------------------------|---------------|--------------------|
| `index_rel`              | 8401          | 0                  |
| `index_words`            | 1038          | 0                  |
| `index_phash`            | 247           | 0                  |
| `cache_pages_tags`       | 514           | 0                  |
| `sys_file_processedfile` | 64            | 0                  |
| `fe_sessions`            | 6             | 0                  |
| **file size**            | **11.18 MB**  | **5.95 MB**        |

None of that is content, all of it comes back by using the instance, and every
byte of it would land in a binary git cannot delta-compress. So a backup empties
it in the **copy** — never in the live database — and vacuums afterwards:

* every table named `cache_*`, which is every database-backed TYPO3 cache,
* every table named `index_*`, the index of EXT:indexed_search,
* `be_sessions`, `fe_sessions`, `sys_lockedrecords` and
  `sys_file_processedfile`,
* and the rows of `sys_file`/`sys_file_metadata` whose `storage` is `0` — the
  records TYPO3 writes when a file delivered from an extension, a theme logo for
  instance, is used for the first time. No committed template has ever held one;
  files in a real storage are content and are left alone.

The count is reported, so a backup says what it took out:

```text
Backed up var/sqlite/core-13.sqlite -> ../sqlite-databases/core-13.sqlite
    (170 tables, 5.7 MB, 11827 transient rows removed)
```

A restore is left alone. It writes an instance, and an instance is allowed to
hold caches and a search index — it rebuilds them on the next request anyway.

This is a floor, not a proof. What proves a snapshot is
[`SnapshotManifestTest`](../testing/seed-verification.md), which measures the
committed file against the manifest of the seed.

### Site sets are cached, and the cache outlives a `git pull`

The set definitions an installation knows are cached. After pulling a branch
that adds, renames or repoints a site set, an instance keeps serving the
definitions it had — even though `core-*/vendor/fgtclb/*` are **symlinks into
`packages/`**, so the new `Configuration/Sets/` folders are already on disk.

```bash
ddev exec vendor/bin/typo3 site:sets:list      # may still show the old sets
ddev exec vendor/bin/typo3 cache:flush
ddev exec vendor/bin/typo3 site:sets:list      # now the current ones
```

This is worth knowing because a stale cache looks exactly like a broken
conversion: the sets a site depends on appear not to exist, and the frontend
loses the configuration they deliver. Flush before concluding anything. The
same applies to `site:show`, which reads the cached definitions to resolve
`dependencies:`.

### Seeding an instance

What an instance contains is **described**, not clicked together: the seed set
`packages-dev/dev-site/Configuration/DataFactory/academics-instance/` holds the
page tree, the content elements, their plugin FlexForms and every record the
plugins read — profiles with their contracts, the organisational units, the
frontend user and its group. Writing it into an empty instance is one command:

```shell
cd core-13
ddev composer instance:seed
```

which is `vendor/bin/typo3 data-factory:import academics-instance`. The command
comes from `sbuerk/data-factory`, required by both instances; the companion
`data-factory:list` shows every set the installation provides.

A set is a directory with a `config.yml` — the identifier the command takes, the
title, and the scenario files carrying the records — and the records themselves
are written in the scenario format of `typo3/testing-framework`, the one TYPO3
Core's own functional tests use.

Six things about the set are worth knowing before changing it:

- **It declares uids.** `config/sites/academics/config.yaml` points at
  `rootPageId: 1`, the plugins name their detail page and their profiles by uid,
  and none of that could be committed if the database assigned the numbers. A
  declared `id` is a *suggestion* to DataHandler, honoured only for an admin
  backend user — which is why the import runs as one. A record without an `id`
  is not written with an auto increment uid either: it gets one from a counter
  that starts at 10000 and runs per entity name, so every record here declares
  one.
- **`hidden: 0` sits on the wildcard entity and nowhere else.** The `pages` TCA
  defaults the column to `1`, so a page written without it exists and renders
  nothing. It must not be repeated on a declared entity: the wildcard is merged
  with `array_merge_recursive()`, so a key on both sides becomes a list and
  reaches the database as the string `Array`.
- **It refuses a non-empty page tree.** A set declaring uids collides rather
  than adding, so seeding is something you do to a freshly set up instance, not
  to a restored template.
- **One set serves both core versions.** The backend layouts of
  `bootstrap_package`, the six `academicpersons_*` CTypes and the felogin plugin
  are identical on v13 and v14, so a second set would be two definitions to keep
  in sync and no difference to express.
- **A plugin FlexForm is a raw XML string in the scenario.**
  `DataHandler::checkValueForFlex()` passes a non-array value through untouched,
  so what is declared is exactly what a backend save would store.
- **Everything is a child of the site root.** A page at the tree root is a root
  page: it is outside `rootPageId: 1`, answers 404, and makes TYPO3 write an
  `autogenerated-*` site configuration for it.

An inline relation has no construct of its own in this format: the children are
records of the same storage folder, and the parent writes the comma separated
list of the uids they declare into its relation field. DataHandler resolves that
list exactly like a backend form submit, which is what writes the child's parent
pointer, its sorting, and — for the profile information relations — its `type`
column from `foreign_match_fields`.

The package carried a site set `fgtclb/academics-dev-site` for a while, which
set the page template name for the two custom page types of
`EXT:academic_programs` and `EXT:academic_partners`. That was a workaround for a
defect of those extensions, not instance configuration, and it is gone: the
extensions set the name themselves now (ACE-450). The package holds nothing but
the seed set again.

Adding a page, a plugin or a record is therefore a reviewable diff, and
[rebuilding the instance](#rebuilding-an-instance-from-nothing) reproduces it
byte for byte on every machine.

### Seed files, and how they reach an instance

The seed does not only describe records, it references **files**: profile
images, page media, content element assets, job logos and one audio file. They
are real `sys_file` and `sys_file_reference` rows, because a seed that fakes
them proves nothing about the FAL wiring of the extensions.

The files are committed in the seed package, below
`packages-dev/dev-site/Resources/Public/SeedFiles/`, in a tree that mirrors
`fileadmin/` one to one:

| Path in `SeedFiles/`               | n   | Pixels   | Ratio | Referenced from                                       |
|------------------------------------|-----|----------|-------|-------------------------------------------------------|
| `academics-seed/profile-01…08.png` | 8   | 600×800  | 3:4   | `tx_academicpersons_domain_model_profile.image`       |
| `academics-seed/media-01…08.png`   | 8   | 1600×900 | 16:9  | `pages.media`                                         |
| `academics-seed/media-09.svg`      | 1   | 1600×900 | 16:9  | `pages.media`, the vector case                        |
| `academics-seed/content-01…04.png` | 4   | 1200×800 | 3:2   | `tt_content.assets`                                   |
| `academics-seed/logo-01…03.png`    | 3   | 800×800  | 1:1   | `tx_academicjobs_domain_model_job.image`              |
| `academics-seed/partner-01…02.png` | 2   | 1600×900 | 16:9  | `pages.media` of the partner pages                    |
| `academics-seed/module-audio.wav`  | 1   | —        | —     | `tx_academicstudyplan_domain_model_module.audio_file` |
| `global-content/jobs/logos/`       | —   | —        | —     | upload target of the `academic_jobs` job form         |
| `profile-images/`                  | —   | —        | —     | upload target of the `academic_persons_edit` form     |

27 files, together some 55 kB. Every picture draws its own name, the table
column it belongs to and its pixel dimensions on a background in a colour no
other file uses, so a **wrong reference is visible without opening the record**:
a page header that reads `PROFILE 03` is one, and so is a portrait that reads
`PAGE MEDIA 07`.

The two folders without files are there because nothing creates them at runtime.
They are the upload targets of the two frontend forms, and an upload into a
missing folder fails at the moment somebody tries the form. A dot file keeps
them in git and stays invisible in the TYPO3 file list, which hides dot files.

The `.wav` earns its place: `module.audio_file` is the only `type: file` column
in this repository whose `allowed` list is not images, so it is the only proof
that the non-image FAL path is wired. The `.svg` earns its place for the
opposite reason: `pages.media` has neither an `allowed` list nor a `maxitems`,
and the page templates hand the first file to `f:image`.

#### Regenerating them

```shell
php Build/Scripts/generateSeedFiles.php          # write the files
php Build/Scripts/generateSeedFiles.php --list   # show the table, write nothing
```

[`Build/Scripts/generateSeedFiles.php`](../../Build/Scripts/generateSeedFiles.php)
needs nothing but PHP with `ext-gd`, which the host, a DDEV instance and the
`typo3/core-testing-*` images all have. It does not go through `runTests.sh`: it
writes committed artefacts and is run by hand, not by a gate.

The drawing is deterministic — no randomness, no timestamps, no font file, no
resampling. Text is GD's built-in bitmap font 5, whose glyphs are compiled into
GD, enlarged by drawing one filled rectangle per source pixel rather than with
`imagecopyresized()`, whose interpolation is an implementation detail. The same
invocation therefore produces the same *picture* anywhere.

**The same picture is not the same bytes, and there is deliberately no
`checkSeedFilesClean` gate.** `imagepng()` output depends on the zlib and libpng
build behind GD, so a byte-equality check analogous to `checkJsBuildClean` could
go red on a contributor's machine with no defect behind it — a worse outcome
than no gate at all. The committed files are the artefact of record; the
generator is how they came to be. (SVG throughout would have been
byte-reproducible by construction and was rejected for a different reason: a
seed made only of vectors never exercises the raster path every real
installation uses.)

#### Getting them into an instance

`core-*/public/` is git-ignored, so a file committed under `packages-dev/`
does not reach `fileadmin/` by itself — and a committed database template full
of `sys_file` rows whose files are missing is a fresh clone with every image
broken.

`config/system/additional.php` therefore copies `SeedFiles/` into
`public/fileadmin/`, next to the branch that copies the sqlite template, and by
the same rule: seed what is missing.

- The check is one `is_dir()` per request. The copy runs when
  `fileadmin/academics-seed/` is absent — on a fresh clone, and again when
  somebody empties `fileadmin/` by hand.
- An existing file is never overwritten. What an editor uploaded into the
  instance is theirs.
- Both switches that suppress the database seeding suppress this too, and for a
  stronger reason than symmetry: while an instance is rebuilt from nothing,
  `composer instance:seed` writes these files itself through the FAL API, and
  FAL renames rather than overwrites. A leftover `profile-01.png` would make the
  import store `profile-01_01.png`, and the snapshot committed afterwards would
  name a file that exists nowhere else. `composer instance:fresh` removes
  `public/fileadmin/academics-seed/` for the same reason — only that folder,
  never anything else somebody put in `fileadmin/`.

Verified on TYPO3 v13 by copying the tree into a functional test instance and
retrieving the files through `ResourceFactory`: all of them index, the PNGs with
their dimensions and `image/png`, the SVG with `image/svg+xml` and 1600×900 —
which is why the generated SVG carries `width`, `height` **and** `viewBox` — and
the WAV with `audio/x-wav` and no dimensions.

### Rebuilding an instance from nothing

`config/system/additional.php` copies the committed template into `var/sqlite/`
whenever the instance database is missing. That is what makes a fresh clone work
with no setup step — check out, `ddev start`, log in — and it is also why
**deleting the database does not give you an empty instance**. The next request
puts the template straight back. So does the documented teardown: `git clean
-xdf` removes the database, and the auto seed restores it on the next start.

That matters as soon as the content is meant to be reproducible. Seeding on top
of a restored template produces a database in which nobody can tell seeded
content from hand-made content any more — and the seed definition declares uids,
so it collides with an existing tree instead of adding to it. A template that is
the result of the definition and nothing else has to be built from nothing.

Two switches suppress the seed. Either is enough, both are git-ignored, and both
belong to **one** instance, so one core version can be rebuilt while the other
keeps working:

| Switch                                          | Set by                    | Use it for                          |
|-------------------------------------------------|---------------------------|-------------------------------------|
| `core-NN/.no-database-seed`                     | `composer instance:fresh` | working on it by hand               |
| `ACADEMICS_NO_DATABASE_SEED`, any value but `0` | the environment           | scripts, `web_environment`, a vhost |

Neither is ever committed. With one of them in place a missing database stays
missing: nothing at boot recreates it, and TYPO3 is still told where the database
belongs, so it creates an empty one and reports that it is not installed.

**Order matters if you also wipe the checkout.** `git clean -xdf` deletes the
marker file along with everything else that is git-ignored, so the sequence is
teardown first, marker second, start third — never the other way round.

#### An index that cannot be added leaves the schema short

A rebuild is not only the way to get a *reproducible* template. It is the only
repair there is when the schema change is an added index, and that is worth
saying separately, because it looks exactly like something a migration should
be able to do.

Adding an index to a table of an installation that already exists is where the
SQLite schema migration gives up here, and it gives up **halfway**: the
operations it manages are written, the ones it does not are dropped, and it
reports success. The installation keeps every row it had. What it silently
loses are the columns and indexes the failed operations would have created.

Nothing looks wrong afterwards. The frontend renders, the backend lists
records, the seed manifest still matches — that measures the rows the seed
declares, and those are all there. Only a comparison against what the code
declares *today* finds the gap.

That is not hypothetical. Making the nine `academic_persons` tables workspace
aware (ACE-475) added `t3ver_oid`, `t3ver_stage`, `t3ver_state` and
`t3ver_wsid` to each of them, plus an index over `t3ver_oid`. Migrating the
existing instances instead of rebuilding them lost every one of those, on
**both** core versions, and the two committed templates carried the short
schema until they were rebuilt:

| Measured against the rebuild | core-13 | core-14 |
|------------------------------|---------|---------|
| Workspace columns missing    | 36      | 36      |
| `t3ver_oid` indexes missing  | 9       | 9       |
| Rows differing               | 0       | 0       |

The row counts are the reason it went unnoticed for as long as it did: the
content was complete and identical the whole time, so every check that looks
at rows was green.

So: **a change that adds an index to a table is a rebuild, not a migration.**
Run the walk-through below against an empty SQLite file, do it for both core
versions so the two templates stay comparable, and commit the two snapshots
together.

There may be a way around it in the core itself — change 95187 on
review.typo3.org, *[BUGFIX] Reject indexes over undeclared columns*
(https://review.typo3.org/c/Packages/TYPO3.CMS/+/95187), backported as a
composer patch. That is untested here, and a rebuild is correct either way.

#### The walk-through

Every command below was run against `core-13/`; the output quoted is what it
produced. For `core-14/` the three values that carry the version have to move
with it: the directory, `TYPO3_PROJECT_NAME="Academic extensions (TYPO3 v14)"`
and `var/sqlite/core-14.sqlite`. Nothing else differs.

```shell
cd core-13
ddev start
ddev composer instance:fresh      # drops var/sqlite/*.sqlite and writes the marker
```

Two things can stop `ddev start` before the rebuild even begins:

* **The project name is registered to the other version line.** The instance
  folders have the same path on every branch but the DDEV project names differ,
  so after working on branch `2` the path is still registered as
  `core13-academics-v2` and the start fails with *"this project root ... already
  contains a project named ..."*. Clear the registration — it removes nothing
  but the entry:

  ```shell
  ddev stop --unlist core13-academics-v2
  ```

* **The lock file is stale.** `ddev start` runs `composer install` as a
  post-start hook, and that refuses to run when `composer.json` has changed
  since `composer.lock` was written — which is exactly the case right after a
  dependency was added, removed or bumped. Refresh the lock first, naming the
  package so nothing else moves:

  ```shell
  ddev composer update <vendor>/<package>
  ```

Then install TYPO3 into the empty instance. **`extension:setup` cannot do this**
— it authenticates a backend user before it does anything else, so against a
database with no tables at all it stops at `no such table: be_users`. The
command that can is `typo3 setup`:

```shell
ddev exec 'TYPO3_DB_DRIVER=sqlite \
  TYPO3_SETUP_ADMIN_USERNAME=john-doe \
  TYPO3_SETUP_ADMIN_PASSWORD="John-Doe-1701D." \
  TYPO3_SETUP_ADMIN_EMAIL=john.doe@example.com \
  TYPO3_PROJECT_NAME="Academic extensions (TYPO3 v13)" \
  TYPO3_SERVER_TYPE=other \
  vendor/bin/typo3 setup --force --no-interaction'
```

The account is the one of the TYPO3 contribution guide, which is why it is
spelled exactly like that. `TYPO3_DB_DRIVER` takes the *connection type* —
`sqlite`, never `pdo_sqlite`, which is rejected with a list of the valid keys.
**No `--create-site`**: the site configurations are committed, and the seed
writes the tree they point at.

`setup` is an installer, not a repair tool, and it leaves two things behind that
have to be undone. Both are expected, neither is a defect:

1. **It rewrites `config/system/settings.php`**, which is *tracked* here. It
   replaces the install tool password, turns `BE/debug` and `FE/debug` off,
   rewrites the `GFX` processor and hard-codes an absolute database path. Restore
   the committed file — `git checkout core-13/config/system/settings.php`.
2. **It ignores the database name for SQLite** and always creates its own
   `var/sqlite/cms-<hash>.sqlite`, leaving the instance's own file empty. Adopt
   it:

   ```shell
   rm var/sqlite/core-13.sqlite
   mv var/sqlite/cms-*.sqlite var/sqlite/core-13.sqlite
   ```

   The rename is enough because `additional.php` recomputes the database path on
   every request anyway — the path in `settings.php` is advisory, and says so.

From here the instance is a normal one and the usual commands work:

```shell
ddev composer system:refresh                       # extension:setup, languages, caches
ddev exec vendor/bin/typo3 setup:begroups:default --no-interaction --groups=Both
ddev composer instance:seed                        # import the "academics-instance" set
ddev composer sqlite:backup                        # commit the result
ddev composer sqlite:apply                         # back to normal, clears the marker
```

**Check `config/sites/` before and after.** It must contain nothing but the two
tracked sites, `academics/` and `academics-legacy/`. TYPO3 writes a site
configuration for every new **root** page, so a seed run that puts pages at the tree root — a set whose sections are
not children of the site root — leaves a set of
`config/sites/autogenerated-<uid>-<hash>/` directories behind. They then claim
those pages, and every URL below them answers 404 while the page tree looks
perfectly correct in the backend. Delete them and flush the cache:

```shell
rm -rf config/sites/autogenerated-*
ddev exec vendor/bin/typo3 cache:flush
```

#### Verifying the result before committing it

A snapshot is a committed binary, so it is worth proving that the rebuild
produced what was intended rather than trusting that it did. The first thing to
run is the check that does it for you:

```shell
Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional \
    packages-dev/dev-site/Tests/Functional/SnapshotManifestTest.php
```

It measures the committed snapshot against the manifest of the seed and names
every table that disagrees — see
[Seed verification](../testing/seed-verification.md). The manual comparison
below is what to reach for when it reports a difference and the question is
which rows moved.

`sqlite:backup`
writes `sqlite-databases/core-NN.sqlite`, creating it when it does not exist and
overwriting it when it does, which means the previous state is still in git and
can be compared against.

When the rebuild is meant to change **nothing** — a seeder swap, a migrated
definition, a core update — extract the committed snapshot and diff the seeded
tables:

```shell
git show HEAD:sqlite-databases/core-13.sqlite > .agent/tmp/old-core-13.sqlite
```

```python
import sqlite3

old = sqlite3.connect('file:.agent/tmp/old-core-13.sqlite?mode=ro', uri=True)
new = sqlite3.connect('file:sqlite-databases/core-13.sqlite?mode=ro', uri=True)

print(
    old.execute('SELECT uid,pid,doktype,slug,title FROM pages ORDER BY uid').fetchall()
    == new.execute('SELECT uid,pid,doktype,slug,title FROM pages ORDER BY uid').fetchall()
)
```

Do the same for `tt_content` and for every `tx_academic*` table. Note that
`sqlite3` is a Python module here, not necessarily a command line tool — the
host does not have to have the `sqlite3` binary installed.

One difference is expected and is not a regression:

| Difference                                    | Why                                                                                                                           |
|-----------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| Table count higher than the previous template | A rebuild creates every table the installed code declares today; a hand-maintained template only has the ones it accumulated. |

Anything else is a real difference and needs an explanation before the snapshot
is committed.

`sys_file` used to be an expected difference too, on the grounds that FAL only
indexes a file the first time the frontend renders it, so a snapshot taken from
a browsed instance carried rows a pure post-seed one did not. That no longer
holds: the seed declares its images itself, so the import writes `sys_file` and
`sys_file_reference` rows. **Their absence is now a defect, not an
expectation** — it means the seed files never reached `fileadmin/`.

`sqlite:apply` clearing the marker is deliberate: restoring the committed
template *is* the end of a rebuild, and a marker that outlives one is a trap —
the instance would come up empty after some later teardown, long after anybody
remembers why.

The first rebuild done this way came out at **170 tables** where the
hand-maintained template it replaced had 146 — the honest measure of how far a
database that is clicked together drifts from what the code would create today.
That is the reason the templates are now produced by a rebuild and a seed rather
than by editing them further.

## See also

- [Quality gates](quality-gates.md) — what each suite asserts, and the CI
  staging that runs them.
- [Dual core setup](dual-core-setup.md) — why the dependency set has to match
  `-t` and `-p`, and how to tell which one is installed.
- [Monorepo layout](monorepo-layout.md) — what lives where, and why the harness
  sits at the repository root.
- [PHPUnit configuration](../testing/phpunit-configuration.md)
- [`AGENTS.md`](../../AGENTS.md) — the short form of this page.
- [`CONTRIBUTING.md`](../../CONTRIBUTING.md)
