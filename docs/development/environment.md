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
host. The script refuses to start when neither runtime is installed
(`Build/Scripts/runTests.sh:384-387`):

```bash
if ! type "docker" >/dev/null 2>&1 && ! type "podman" >/dev/null 2>&1; then
    echo "This script relies on docker or podman. Please install" >&2
    exit 1
fi
```

**podman is preferred.** When `-b` is not given, the runtime is picked at
`Build/Scripts/runTests.sh:516-522`: podman if it is on the `PATH`, docker
otherwise. `-b docker|podman` overrides the choice, and an argument that is
neither is rejected as an invalid option
(`Build/Scripts/runTests.sh:414-419`).

The two runtimes are not interchangeable in their details, which is why the
script branches on the selected one (`Build/Scripts/runTests.sh:549-578`):

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
runners and is documented in `.github/workflows/ci.yml:44-52`. There is no
reason to pass it locally.

Each run creates its own container network named `academic-extensions-<random>`
(`Build/Scripts/runTests.sh:545-547`) and removes it together with every
attached container in `cleanUp()` (`Build/Scripts/runTests.sh:109-115`), so
parallel runs on one machine do not collide.

## Options

The wrapper parses exactly these options
(`Build/Scripts/runTests.sh:409-468`); everything left over after them is the
optional trailing argument.

| Option            | Meaning                                                                             | Default             |
|-------------------|-------------------------------------------------------------------------------------|---------------------|
| `-s <suite>`      | Suite to run. See the table below.                                                  | `help`              |
| `-t <13\|14>`     | TYPO3 core major version. Selects the dependency set and the PHPStan configuration. | `13`                |
| `-p <8.2…8.5>`    | PHP version, one of `8.2`, `8.3`, `8.4`, `8.5`. Picks the testing image.            | `8.2`               |
| `-d <dbms>`       | DBMS for functional tests: `sqlite`, `mariadb`, `mysql`, `postgres`.                | `sqlite`            |
| `-i <version>`    | DBMS version, only with `-d mariadb\|mysql\|postgres`.                              | per DBMS, see below |
| `-a <driver>`     | Database driver, only with `-d mariadb\|mysql`: `mysqli` or `pdo_mysql`.            | `mysqli`            |
| `-n`              | Dry run for `cgl` and `cglHeader`: report offending files, change nothing.          | off                 |
| `-x`              | Enable Xdebug and send debugging information to the host IDE.                       | off                 |
| `-y <port>`       | Xdebug client port on the host, when the IDE does not listen on the default.        | `9003`              |
| `-o <seed>`       | Random order seed for `unitRandom`, to replay a specific order.                     | none                |
| `-u`              | Update the local `typo3/core-testing-*` images and drop dangling ones.              | —                   |
| `-b <runtime>`    | Container runtime, `docker` or `podman`.                                            | podman, else docker |
| `-h`              | Print the help text and exit.                                                       | —                   |
| trailing `[file]` | Path passed on to the tool of the suite — for PHPUnit a test file or directory.     | none                |

`-u` is not a modifier: it sets the suite to `update`
(`Build/Scripts/runTests.sh:458-460`), so it cannot be combined with `-s`.

`-t` and `-p` are validated against a fixed list
(`Build/Scripts/runTests.sh:431-440`); anything else aborts the run before a
container is started.

The `-a`, `-d` and `-i` combinations are validated together in
`handleDbmsOptions()` (`Build/Scripts/runTests.sh:117-188`), which also holds
the per-DBMS defaults and the accepted version lists:

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
line (`Build/Scripts/runTests.sh:659`, `739`, `751`, `757`). For
`checkRstRenderingSingle` and `openDocumentation` it is not a path at all but
the **extension folder name** below `packages/fgtclb/`
(`Build/Scripts/runTests.sh:624`, `723`). For `composer` it is the composer
command with its arguments (`Build/Scripts/runTests.sh:642`).

> [!NOTE]
> A `--` separator does technically end the wrapper's own option parsing, and
> the remainder then reaches the dispatched tool, because every one of those
> suites appends `"$@"`. It is neither documented in `-h` nor used by the
> workflows, and the single mention of a separator in the help text — under
> `unitRandom`, `Build/Scripts/runTests.sh:269` — points at what `-o` already
> does. Use `-o` for the seed and the trailing path for everything else; do not
> build tooling on the separator.

## Suites

Taken from the `case` statement at `Build/Scripts/runTests.sh:589-783`, which
is the authority — the help text is not complete, see below.

| Suite                     | What it runs                                                                                        |
|---------------------------|-----------------------------------------------------------------------------------------------------|
| `cgl`                     | php-cs-fixer with `Build/php-cs-fixer/config.php`. Fixes in place, `-n` only reports.               |
| `cglHeader`               | php-cs-fixer with `Build/php-cs-fixer/header-comment.php` for the file header.                      |
| `checkRstRenderingAll`    | Renders `Documentation/` of every extension in `packages/fgtclb/`.                                  |
| `checkRstRenderingSingle` | The same for one extension folder, given as trailing argument.                                      |
| `composer`                | `composer` with all remaining arguments dispatched into the container.                              |
| `composerUpdate`          | Installs the dependency set for `-t`, see below.                                                    |
| `functional`              | PHPUnit with `Build/phpunit/FunctionalTests.xml` against the DBMS from `-d`.                        |
| `lintPhp`                 | `php -l` over every `*.php` outside the excluded trees.                                             |
| `openDocumentation`       | Opens a previously rendered documentation in the browser (Linux only, `xdg-open`).                  |
| `phpstan`                 | PHPStan with `Build/phpstan/Core<13\|14>/phpstan.neon`.                                             |
| `phpstanGenerateBaseline` | Rewrites `Build/phpstan/Core<13\|14>/phpstan-baseline.neon`.                                        |
| `unit`                    | PHPUnit with `Build/phpunit/UnitTests.xml`.                                                         |
| `unitRandom`              | The same, with `--order-by=random` and the seed from `-o`.                                          |
| `update`                  | Pulls newer `ghcr.io/typo3/core-testing-*` images and removes dangling ones. Also reached via `-u`. |
| `help`                    | Prints the help text. This is the default when `-s` is omitted.                                     |

Anything else prints the help and exits non-zero
(`Build/Scripts/runTests.sh:776-782`).

### Where the help text and the script disagree

`-h` renders a text block maintained by hand in `loadHelp()`
(`Build/Scripts/runTests.sh:246-381`), and it has drifted from the `case`
statement. Read the script when in doubt:

* **`functional` is missing from the help text entirely**, although it is a
  suite (`Build/Scripts/runTests.sh:657`), is described under `-d` and `-a`,
  and appears in the examples. `update` and `help` are missing as well.
* `-o` is not listed as an option at all, even though it is parsed
  (`Build/Scripts/runTests.sh:447-449`).
* `-t` is documented as being usable only with the suites `composerInstall`,
  `composerInstallMin` and `composerInstallMax`. None of those three exists
  here, and `-t` in fact also selects the PHPStan configuration and the
  excluded PHPUnit group.
* `-n` is documented for `cgl|cglGit|cglHeader|cglHeaderGit`; the `*Git`
  variants do not exist.
* The `-i` lists carry TYPO3 Core's maintenance annotations, which are dated
  and say nothing about this repository.

Correcting the help text is worthwhile; until then, the `case` statement and
the `getopts` string are the specification.

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

`composerUpdate` (`Build/Scripts/runTests.sh:646-656`) does four things:

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
which needs no vendor tree, does not run it at all
(`.github/workflows/ci.yml:154-171`).

## Caches: `.cache/` at the repository root

Composer downloads land in `.cache/composer` and the PHPStan result cache in
`.cache/phpstan`. The directory is created before any container starts
(`Build/Scripts/runTests.sh:502-503`), passed in as `COMPOSER_CACHE_DIR` on
every composer-facing suite, and configured as PHPStan's `tmpDir`
(`Build/phpstan/Core13/phpstan.neon:11`).

It sits **next to** `.Build/`, not inside it, and that placement is deliberate.
`composerUpdate` begins with `rm -rf .Build`, so a cache below it would be
thrown away on every single dependency install — locally, and once per job in
CI. The reasoning is recorded in `.gitignore:17-20`.

The workflows cache only `.cache/composer/files` — the content-addressed dist
archives — and never the `repo/` metadata next to it, because restoring stale
packagist metadata makes the `restore-keys` fallback resolve against an old
package list (`.github/workflows/ci.yml:88-103`).

`.cache` and `.php-cs-fixer.cache` are what `cleanCacheFiles()` removes
(`Build/Scripts/runTests.sh:190-196`).

## Container images

| Purpose          | Image                                              |
|------------------|----------------------------------------------------|
| PHP, all suites  | `ghcr.io/typo3/core-testing-php<version>:latest`   |
| Documentation    | `ghcr.io/typo3-documentation/render-guides:latest` |
| MariaDB          | `docker.io/mariadb:<-i>`                           |
| MySQL            | `docker.io/mysql:<-i>`                             |
| PostgreSQL       | `docker.io/postgres:<-i>-alpine`                   |
| Port wait helper | `docker.io/alpine:3.8`                             |

Defined at `Build/Scripts/runTests.sh:524-531`. The `docker.io` images are
pulled through `ensureImage()` (`Build/Scripts/runTests.sh:60-79`), which
checks the local image first and retries a failed pull up to three times: an
anonymous Docker Hub pull is rate limited per source IP, hosted runners share
address space, and a single failed pull otherwise ends a job with exit 125
before a test has run. The `ghcr.io` images have never needed it, which is why
only the Docker Hub ones are guarded.

`-u` refreshes the PHP images that already exist locally and removes the
dangling ones (`Build/Scripts/runTests.sh:761-770`). Run it when tests start
failing in ways that do not match the code.

## Xdebug

`-x` switches the PHP container from `XDEBUG_MODE=off` to `debug` and points
the client at the host (`Build/Scripts/runTests.sh:580-586`). The host address
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
`./core-1*/public/` and `./core-1*/var/` from the `find`
(`Build/Scripts/runTests.sh:709-718`), because those are third-party trees —
`typo3/class-alias-loader` ships a template file that is deliberately not valid
PHP. The instances' tracked `config/system/*.php` is still linted, on purpose.

Do not run a gate expecting an instance to be updated, and do not fix a failing
instance by running `composerUpdate` — that installs the root project's
dependency tree into `.Build/`, which is a different thing entirely. Instance
handling, DDEV project names, the SQLite backup/restore scripts and the branch
switching collision are described in
[`README.md`](../../README.md#development-instances).

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
