# TYPO3 Academic Extensions (development)

## Description

`academic-extensions` is a mono repository to develop a couple of academic related TYPO3 extensions,
which may depend on others. To keep the maintenance burden across the set of extension small while
increasing the cross-over development and testing experience.

## Documentation

| For                        | Where                                                                     |
|----------------------------|---------------------------------------------------------------------------|
| Users and integrators      | The `Documentation/` folder of each extension, rendered to docs.typo3.org |
| Developers and maintainers | [`docs/`](docs/Index.md)                                                  |
| Contributors, entry point  | [`CONTRIBUTING.md`](CONTRIBUTING.md)                                      |
| AI coding agents           | [`AGENTS.md`](AGENTS.md)                                                  |

Each extension ships its own manual — there is no repository-wide one, because
each extension is released and published on its own. [`docs/`](docs/Index.md) is
the counterpart for the repository itself: the harness, the rules the code
follows, and how a release is cut.

## Repository version support

| Branch | Version     | TYPO3     | PHP                                          |
|--------|-------------|-----------|----------------------------------------------|
| main   | ^3, 3.x-dev | v13 + v14 | 8.2, 8.3, 8.4, 8.5                           |
| 2, 2.x | ^2, 2.x-dev | v12 + v13 | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3) |
| 1      | ^1, 1.x-dev | v11 + v12 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3)      |

**Testing 2.x.x extension version in projects (composer mode)**

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
composer require 'fgtclb/academic-persons':'2.*.*@dev'
```

That way, current main branch will be included and updated and as soon as 2.0.0 is released switcht to the release on
update.

## Upgrade from `1.x`

Upgrading from `1.x` to `2.x` includes breaking changes, which needs to be
addressed manualy in case not automatic upgrade path is available. See the
`UPGRADE.md` file of each extension for details.

### Extension Version Support Matrix

| Extension               | v11 | v12     | v13     | v14 |
|-------------------------|-----|---------|---------|-----|
| academic_base           | -   | <2>     | <2> (3) | (3) |
| academic_bite_jobs      | <1> | <1> <2> | <2> (3) | (3) |
| academic_contacts4pages | <1> | <1> <2> | <2> (3) | (3) |
| academic_study_plan     | -   | <2>     | <2> (3) | (3) |
| academic_jobs           | <1> | <1> <2> | <2> (3) | (3) |
| academic_partners       | <1> | <1> <2> | <2> (3) | (3) |
| academic_persons        | <1> | <1> <2> | <2> (3) | (3) |
| academic_persons_edit   | <1> | <1> <2> | <2> (3) | (3) |
| academic_persons_sync   | <1> | <1> <2> | <2> (3) | (3) |
| academic_programs       | <1> | <1> <2> | <2> (3) | (3) |
| academic_projects       | <1> | <1> <2> | <2> (3) | (3) |
| category_types          | <1> | <1> <2> | <2> (3) | (3) |

Legend:

```
  <X>   Allowed and used with X.y.z
  {X}   Allowed but not tested/verified with X.y.z, but may/could work
  -X-   Allowed but absolutely not tested and most likely not working (yet)
  (X)   Planned for the upcoming X.y.z line, not yet available/tested
```

**Roadmap: the planned `3.x` line**

The `(3)` marker documents the upcoming major `3.x` line, which will target
TYPO3 **v13 + v14** (see the branch support matrix above). It is a roadmap
signal only: `3.x` is not released yet and the extensions are not tested against
TYPO3 v14, so every `(3)` cell means *planned, not yet available or verified*.
As v14 support is actually implemented and tested, the state of the affected
cells will be promoted from `(3)` to `{3}` and finally to `<3>` per extension.

## List of TYPO3 extension and the split repositories (READ ONLY)

| Composer                       | TYPO3                   | Path                                                                                       | Split Repository                                                                  |
|--------------------------------|-------------------------|--------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| fgtclb/academic-base           | academic_base           | [packages/fgtclb/academic-base](packages/fgtclb/academic-base/README.md)                   | [fgtclb/academic-base](https://github.com/fgtclb/academic-base)                   |
| fgtclb/academic-bite-jobs      | academic_bite_jobs      | [packages/fgtclb/academic-bite-jobs](packages/fgtclb/academic-bite-jobs/README.md)         | [fgtclb/academic-bite-jobs](https://github.com/fgtclb/academic-bite-jobs)         |
| fgtclb/academic-contacts4pages | academic_contacts4pages | [packages/fgtclb/academic-contact4pages](packages/fgtclb/academic-contact4pages/README.md) | [fgtclb/academic-contact4pages](https://github.com/fgtclb/academic-contact4pages) |
| fgtclb/academic-study-plan     | academic_study_plan     | [packages/fgtclb/academic-study-plan](packages/fgtclb/academic-study-plan/README.md)       | [fgtclb/academic-study-plan](https://github.com/fgtclb/academic-study-plan)       |
| fgtclb/academic-jobs           | academic_jobs           | [packages/fgtclb/academic-jobs](packages/fgtclb/academic-jobs/README.md)                   | [fgtclb/academic-jobs](https://github.com/fgtclb/academic-jobs)                   |
| fgtclb/academic-partners       | academic_partners       | [packages/fgtclb/academic-partners](packages/fgtclb/academic-partners/README.md)           | [fgtclb/academic-partners](https://github.com/fgtclb/academic-partners)           |
| fgtclb/academic-persons        | academic_persons        | [packages/fgtclb/academic-persons](packages/fgtclb/academic-persons/README.md)             | [fgtclb/academic-persons](https://github.com/fgtclb/academic-persons)             |
| fgtclb/academic-persons-edit   | academic_persons_edit   | [packages/fgtclb/academic-persons-edit](packages/fgtclb/academic-persons-edit/README.md)   | [fgtclb/academic-persons-edit](https://github.com/fgtclb/academic-persons-edit)   |
| fgtclb/academic-persons-sync   | academic_persons_sync   | [packages/fgtclb/academic-persons-sync](packages/fgtclb/academic-persons-sync/README.md)   | [fgtclb/academic-persons-sync](https://github.com/fgtclb/academic-persons-sync)   |
| fgtclb/academic-programs       | academic_programs       | [packages/fgtclb/academic-programs](packages/fgtclb/academic-programs/README.md)           | [fgtclb/academic-programs](https://github.com/fgtclb/academic-programs)           |
| fgtclb/academic-projects       | academic_projects       | [packages/fgtclb/academic-projects](packages/fgtclb/academic-projects/README.md)           | [fgtclb/academic-projects](https://github.com/fgtclb/academic-projects)           |
| fgtclb/category-types          | category_types          | [packages/fgtclb/typo3-category-types](packages/fgtclb/typo3-category-types/README.md)     | [fgtclb/typo3-category-types](https://github.com/fgtclb/typo3-category-types)     |

## Development

Every test and quality tool runs in a container through the
[`Build/Scripts/runTests.sh`](Build/Scripts/runTests.sh) wrapper. The only
requirement on the host is a container runtime — **podman** (preferred) or
**docker**.

```bash
# Install dependencies for the core version and PHP version you will test.
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate

# Quality gates.
Build/Scripts/runTests.sh -t 12 -p 8.1 -s cgl -n
Build/Scripts/runTests.sh -t 12 -p 8.1 -s phpstan
Build/Scripts/runTests.sh -t 12 -p 8.1 -s lintPhp

# Tests.
Build/Scripts/runTests.sh -t 12 -p 8.1 -s unit
Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional

# All available options.
Build/Scripts/runTests.sh -h
```

`-t` selects configuration only, it does **not** reinstall dependencies.
Everything has to pass for **both** TYPO3 versions this branch supports, each
after its own `composerUpdate` — see
[Dual core setup](docs/development/dual-core-setup.md).

→ [`CONTRIBUTING.md`](CONTRIBUTING.md) for the contribution workflow ·
[`docs/`](docs/Index.md) for the full developer documentation

## Development instances

Two ready-to-start TYPO3 instances live at the repository root, one per supported
core version:

| Folder     | TYPO3 | DDEV project          | Theme               |
|------------|-------|-----------------------|---------------------|
| `core-12/` | v12   | `core12-academics-v2` | `bootstrap_package` |
| `core-13/` | v13   | `core13-academics-v2` | `bootstrap_package` |

Both run on **SQLite** — no database container is started (`omit_containers: [db]`).
Each instance is seeded on first start from the committed template in
`sqlite-databases/`, by `config/system/additional.php`. So there is no setup step:
check out, start, log in.

```shell
cd core-12 && ddev start && ddev launch /typo3/
```

What is in those templates is **described rather than clicked together**:
`packages-dev/dev-site/Configuration/DataFactory/academics-instance/` holds the page
tree, the content and the records, and `ddev composer instance:seed` writes it into an
empty instance. That is how a template is rebuilt once it has gone stale — see
[Rebuilding an instance from nothing](#rebuilding-an-instance-from-nothing).

The backend admin account is `john-doe`, and the seed also creates the frontend
user needed to look at `EXT:academic_persons_edit`. Both, and what is on which
page, are documented in
[Development instances](docs/development/instances.md#accounts).

### Database backup and restore

The instance database is git-ignored (`core-*/var/`); the template next to it is
committed. Five composer scripts move state around:

```shell
cd core-12
ddev composer sqlite:backup    # instance -> sqlite-databases/core-12.sqlite (commit this)
ddev composer sqlite:apply     # sqlite-databases/core-12.sqlite -> instance (discards changes)
ddev composer instance:fresh   # drop the database and suppress the automatic seeding
ddev composer instance:seed    # write the seed definition into an empty page tree
ddev composer system:refresh   # flush + warm caches, update languages, extension:setup
```

`sqlite:backup` rewrites a multi-megabyte binary that git cannot delta-compress,
so commit it when the content genuinely changed, not on every run.

Both directions go through `Build/Scripts/sqliteSnapshot.php` rather than `cp`,
because a running instance keeps its newest writes in a SQLite write ahead log
that a plain copy leaves behind — see
[Development environment](docs/development/environment.md#snapshotting-an-instance-database-is-not-a-copy).

### Rebuilding an instance from nothing

Deleting the database does not empty an instance: `config/system/additional.php`
copies the committed template back on the next request. That is what makes a
fresh clone work without a setup step, and it is in the way when the point is to
build the content again from scratch.

```shell
cd core-12
ddev composer instance:fresh   # drop the database and stop the automatic seeding
```

This writes the git-ignored marker `core-12/.no-database-seed`; the environment
variable `ACADEMICS_NO_DATABASE_SEED` does the same for scripted use. Neither is
ever committed, and `ddev composer sqlite:apply` clears the marker again.

Installing TYPO3 into the empty instance afterwards has two sharp edges — TYPO3's
own `setup` command rewrites the *tracked* `settings.php` and picks its own
database file name. The walk-through, with both fix-ups, is in
[Development environment](docs/development/environment.md#rebuilding-an-instance-from-nothing).

### Teardown

```shell
cd core-12 && ddev stop -ROU && git clean -xdf -e '.idea'
```

### Switching branches in the same checkout

The instance folders sit at the repository root on every branch, but each branch
names its DDEV projects after the version line it carries — `core13-academics-v2`
here on `2`, `core13-academics-v3` on `main`. Two different project names for the
same directory is a state DDEV refuses:

```
Failed to start app core13-academics-v2: this project root '…/core-13'
already contains a project named 'core13-academics-v3'.
```

That is not a broken checkout. DDEV remembers the project by path, and the name
changed underneath it. Unregister the one belonging to the other branch and start
again:

```shell
ddev stop --unlist core13-academics-v3
ddev start
```

`--unlist` only removes the registration; it touches neither the containers nor
any data. The instance database lives in the git-ignored `core-*/var/`, so it
survives the switch and keeps whatever the *other* branch left there — run
`ddev composer sqlite:apply` to reset it to this branch's committed template.

`core-*/vendor/` survives the switch the same way, and unlike the database it is
then **wrong**: it is git-ignored, so it is not per branch, and its autoloader
still points at the path packages of the branch it was installed from. Running
`vendor/bin/typo3` after a switch fails with a `Failed opening required …
EXT_CONSTANTS.php`. `ddev start` fixes it — its post-start hook runs
`composer install` — and `ddev composer install` does the same without a
restart.

Two more things survive the switch and are merely in the way, so the repository
ignores both: `core-*/.ddev/traefik/`, which holds the router certificate and its
private key — DDEV ignores them itself, but under the *current* project name
only, so the other name's certificate is left visible — and the instance folder
of the other version line, `core-14/` here and `core-12/` on `main`, whose
ignored trees stay behind when its tracked files go.

### Without DDEV

The instances do not depend on DDEV. `config/system/additional.php` recomputes the
database path from `__DIR__` and the site configurations use host-less, relative
`base` values, so a host stack only needs PHP with `pdo_sqlite` and a vhost
pointing at `core-12/public`. Local-only overrides — different binary paths, a
different mail transport — go into `core-*/config/system/additional/*.php`, which
is git-ignored and included automatically.
