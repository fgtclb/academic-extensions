# Monorepo layout

`fgtclb/academic-extensions` is a mono repository. It develops a set of
interdependent TYPO3 extensions together, in one checkout, with one test
harness and one continuous integration workflow, and mirrors each extension
outward into its own standalone repository.

The reason is the dependency graph. Eleven of the twelve extensions require
`fgtclb/academic-base`, three require `fgtclb/category-types`, and
`academic-persons-sync` requires two further academic extensions on top — read
from the `require` sections of `packages/fgtclb/*/composer.json`. A change to
the base extension therefore regularly needs a matching change in several
dependents. Split across twelve repositories, that would be several pull
requests that cannot be tested together until all of them are merged. Here it
is one branch, one pull request, and one test run that sees the real, combined
code.

The repository root is itself a composer `project` (`composer.json:4`), not an
extension. It pulls every extension in through path repositories so that
composer resolves the whole graph from the working tree.

## Top-level directories

| Path                      | Tracked | What it is                                                                                        |
|---------------------------|---------|---------------------------------------------------------------------------------------------------|
| `packages/fgtclb/`        | yes     | The twelve extensions. One composer `typo3-cms-extension` each. Source changes go here.           |
| `packages-dev/`           | yes     | Three packages that exist only for development: shared constraints, test helpers, instance seeds. |
| `Build/`                  | yes     | The test harness and every tool configuration: phpunit, phpstan, php-cs-fixer, templates.         |
| `bin/`                    | yes     | Release tooling: `bin/set-version` and `bin/release`.                                             |
| `core-13/`, `core-14/`    | partly  | Ready-to-start development instances, one per supported core version.                             |
| `sqlite-databases/`       | yes     | Committed database templates the development instances are seeded from.                           |
| `patches/`                | yes     | Composer patch pool shared by both development instances. Currently only a `.gitkeep`.            |
| `.Build/`                 | no      | Generated composer install target. Removed and rebuilt by `composerUpdate`.                       |
| `.cache/`                 | no      | Composer download cache and the phpstan result cache.                                             |
| `documentation-rendered/` | no      | Output of the reST rendering suites, one folder per extension.                                    |

### `packages/fgtclb/`

The real extensions. Every subdirectory is a composer package of type
`typo3-cms-extension` with its own `composer.json`, `ext_emconf.php`, `VERSION`
file, `Tests/` folder and reST manual in `Documentation/`. All twelve carry
version `3.0.0-dev` on `main`.

### `packages-dev/`

Three packages that are never released as extensions and never shipped to an
installation:

- `packages-dev/monorepo-shared/` — `fgtclb/academics-monorepo-shared`, type
  `library`. It centralizes the TYPO3 core dependency constraints.
- `packages-dev/testing-helper/` — `fgtclb/academics-monorepo-testing-helper`,
  type `library`. Shared functional-test traits.
- `packages-dev/dev-site/` — `fgtclb/academics-monorepo-dev-site`, extension key
  `academics_dev_site`. The seed definitions the development instances are built
  from. The only one of the three that is a `typo3-cms-extension`, because its
  seeds are addressed as `EXT:academics_dev_site/Configuration/Seeds/*.yaml` —
  the one path form that resolves inside DDEV and on a host stack alike.

All three are covered in their own sections below. The third holds content
rather than code, and how that content is applied is described in
[Development environment](environment.md#seeding-an-instance).

### `Build/`

- `Build/Scripts/runTests.sh` — the containerized harness every check runs
  through.
- `Build/phpunit/` — `UnitTests.xml` and `FunctionalTests.xml`. Test discovery
  is repository-wide: the suites glob `../../packages/*/*/Tests/Unit/`
  (`Build/phpunit/UnitTests.xml:42`) and `../../packages/*/*/Tests/Functional/`
  (`Build/phpunit/FunctionalTests.xml:42`). There is no per-extension phpunit
  configuration; a run always covers all extensions unless it is restricted by
  a trailing path argument.
- `Build/phpstan/Core13/` and `Build/phpstan/Core14/` — one configuration and
  one baseline per core version. Of the three source gates (`cgl`, `phpstan`,
  `lintPhp`), phpstan is the only one that is core version specific, because it
  analyses against the installed core rather than against the sources alone.
- `Build/php-cs-fixer/` — `config.php` and `header-comment.php`.
- `Build/Documentation/Templates/` — reST skeletons for changelog entries
  (`Changelog-Breaking.rst`, `Changelog-Deprecation.rst`,
  `Changelog-Feature.rst`, `Changelog-Important.rst`).
- `Build/templates/extensions/LICENSE` — the per-extension license template.

### `bin/`

`bin/set-version <version> <type>` applies a version across the whole
repository: every extension's `composer.json`, `ext_emconf.php`, `VERSION` file
and tailor metadata, the functional-test fixture extensions, the three
`packages-dev/` packages, the development instances, the root `composer.json`
and the `COMPOSER_ROOT_VERSION` assignment in `runTests.sh`. It discovers the
package list from the repository rather than carrying one, so a new extension
needs no change there. It only edits files; it runs no git and no network
operation.

`bin/release <version>` drives the two-phase GitHub release on top of it —
release branch, `[RELEASE]` commit, pull request, tag, then the follow-up
version bump. Remote operations happen only with `--execute`.

### `core-13/` and `core-14/`

Two ready-to-start TYPO3 instances at the repository root, one per supported
core version, with DDEV project names `core13-academics-v3` and
`core14-academics-v3` (`core-13/.ddev/config.yaml:1`,
`core-14/.ddev/config.yaml:1`). Both run on SQLite, so no database container is
started, and both are seeded on first start from `sqlite-databases/`.

Both are themed with `bk2k/bootstrap-package` and each serves exactly one site,
identifier `academics`, `rootPageId: 1` and `base: /`
(`core-13/config/sites/academics/config.yaml`,
`core-14/config/sites/academics/config.yaml`). The page tree behind that root
page is not clicked together but described, in
`packages-dev/dev-site/Configuration/Seeds/Instance.yaml`, and written into an
empty instance with `ddev composer instance:seed`. Both instances use the same
seed, so a page tree found in one is the page tree of the other.

Their `config/` and `composer.lock` are tracked; `public/`, `var/`, `vendor/`
and `config/system/additional/*.php` are not (`.gitignore`). They consume the
extensions through path repositories pointing back at `../packages/*/*`, the
same way the root does.

The instances are development aids only. `runTests.sh` never touches them and
no test run depends on them.

### `sqlite-databases/` and `patches/`

`sqlite-databases/core-13.sqlite` and `sqlite-databases/core-14.sqlite` are the
committed database templates. Each instance declares five composer scripts
(`core-13/composer.json:74-98`, `core-14/composer.json:74-98`):
`instance:fresh`, `instance:seed`, `sqlite:apply`, `sqlite:backup` and
`system:refresh`. Of those, `sqlite:apply` and `sqlite:backup` are the ones that
touch a template — each instance restores from and backs up to its own file, and
both call `Build/Scripts/sqliteSnapshot.php` rather than copying it, see
[Development environment](environment.md#snapshotting-an-instance-database-is-not-a-copy)
for why a copy is wrong.

The templates are not the accumulated result of manual backend work either. They
are produced by seeding an empty instance from `packages-dev/dev-site` with
`instance:seed` and backing the result up, which is what makes them
reproducible.

Two of the five scripts do not go through a template at all. `instance:fresh`
drops the database and writes the git-ignored marker
`core-NN/.no-database-seed`, which stops `config/system/additional.php` from
seeding the instance again; `instance:seed` writes the seed definition into the
empty instance it leaves behind. See
[Rebuilding an instance from nothing](environment.md#rebuilding-an-instance-from-nothing).

`patches/` is the shared pool for `vaimo/composer-patches`. Both instances
require that plugin and declare `"patches-search": "patches/"`
(`core-14/composer.json:54`), and each has a `patches` symlink pointing at
`../patches`, so a patch is written once and applied by both. The pool is empty
at the moment and holds only a `.gitkeep` so git keeps the directory.

### `.Build/`, `.cache/` and `documentation-rendered/`

`.Build/` is where composer installs: `bin-dir` is `.Build/bin`, `vendor-dir`
is `.Build/vendor` (`composer.json:26-27`), and the TYPO3 web and app
directories are `.Build/Web` and `.Build` (`composer.json:37-38`). It is
git-ignored and disposable — `runTests.sh -s composerUpdate` starts by removing
it.

`.cache/` holds the composer download cache and the phpstan result cache. It
sits next to `.Build/` rather than inside it precisely because
`composerUpdate` deletes `.Build/`; a cache below it would be thrown away on
every dependency install, locally and in continuous integration alike. The
phpstan configuration points its `tmpDir` at `../../../.cache/phpstan`
(`Build/phpstan/Core13/phpstan.neon:11`).

`documentation-rendered/` receives the output of `checkRstRenderingAll` and
`checkRstRenderingSingle`, one folder per extension — the output directory is
built in `executeRstRendering()`. It is git-ignored and uploaded as a
continuous integration artifact.

## The extensions and their split repositories

Every extension is git-split out of this repository into a standalone,
**read-only** GitHub repository. Those mirrors are what composer and the TYPO3
Extension Repository consume — and they are never a source of truth. A commit
pushed to a split repository would be overwritten by the next split. All
changes happen here.

| Directory                                | Composer package                 | Extension key             | Split repository                                                                  |
|------------------------------------------|----------------------------------|---------------------------|-----------------------------------------------------------------------------------|
| `packages/fgtclb/academic-base`          | `fgtclb/academic-base`           | `academic_base`           | [fgtclb/academic-base](https://github.com/fgtclb/academic-base)                   |
| `packages/fgtclb/academic-bite-jobs`     | `fgtclb/academic-bite-jobs`      | `academic_bite_jobs`      | [fgtclb/academic-bite-jobs](https://github.com/fgtclb/academic-bite-jobs)         |
| `packages/fgtclb/academic-contact4pages` | `fgtclb/academic-contacts4pages` | `academic_contacts4pages` | [fgtclb/academic-contact4pages](https://github.com/fgtclb/academic-contact4pages) |
| `packages/fgtclb/academic-jobs`          | `fgtclb/academic-jobs`           | `academic_jobs`           | [fgtclb/academic-jobs](https://github.com/fgtclb/academic-jobs)                   |
| `packages/fgtclb/academic-partners`      | `fgtclb/academic-partners`       | `academic_partners`       | [fgtclb/academic-partners](https://github.com/fgtclb/academic-partners)           |
| `packages/fgtclb/academic-persons`       | `fgtclb/academic-persons`        | `academic_persons`        | [fgtclb/academic-persons](https://github.com/fgtclb/academic-persons)             |
| `packages/fgtclb/academic-persons-edit`  | `fgtclb/academic-persons-edit`   | `academic_persons_edit`   | [fgtclb/academic-persons-edit](https://github.com/fgtclb/academic-persons-edit)   |
| `packages/fgtclb/academic-persons-sync`  | `fgtclb/academic-persons-sync`   | `academic_persons_sync`   | [fgtclb/academic-persons-sync](https://github.com/fgtclb/academic-persons-sync)   |
| `packages/fgtclb/academic-programs`      | `fgtclb/academic-programs`       | `academic_programs`       | [fgtclb/academic-programs](https://github.com/fgtclb/academic-programs)           |
| `packages/fgtclb/academic-projects`      | `fgtclb/academic-projects`       | `academic_projects`       | [fgtclb/academic-projects](https://github.com/fgtclb/academic-projects)           |
| `packages/fgtclb/academic-study-plan`    | `fgtclb/academic-study-plan`     | `academic_study_plan`     | [fgtclb/academic-study-plan](https://github.com/fgtclb/academic-study-plan)       |
| `packages/fgtclb/typo3-category-types`   | `fgtclb/category-types`          | `category_types`          | [fgtclb/typo3-category-types](https://github.com/fgtclb/typo3-category-types)     |

The table was read from each `packages/fgtclb/*/composer.json`
(`extra.typo3/cms.extension-key` for the key, `name` for the package). It
agrees with the table in `README.md:95-108`, except that the link label for
`category_types` there reads `fgtclb/fgtclb/typo3-category-types` while its
target is the correct `https://github.com/fgtclb/typo3-category-types`.

### The directory name is not the extension key

Three identifiers exist per extension and they do not all follow from each
other. Ten of the twelve directories are the extension key with underscores
turned into hyphens, which makes the two that are not easy to miss:

| Directory                | Composer package                 | Extension key             | Divergence                                                            |
|--------------------------|----------------------------------|---------------------------|-----------------------------------------------------------------------|
| `academic-contact4pages` | `fgtclb/academic-contacts4pages` | `academic_contacts4pages` | Directory says `contact4pages`, package and key say `contacts4pages`. |
| `typo3-category-types`   | `fgtclb/category-types`          | `category_types`          | Directory carries a `typo3-` prefix that neither package nor key has. |

The authoritative source is always `extra.typo3/cms.extension-key` in the
package's `composer.json`. Anything that needs the key derives it from there
rather than from the path — the release workflow reads it with `jq` at runtime
for exactly this reason, in its TER artifact step, and
`bin/set-version` discovers the mapping the same way.

Note also that the split repository name follows the directory, not the package
— `fgtclb/academic-contact4pages` and `fgtclb/typo3-category-types`.

## Path repositories and package versions

The root declares two path repositories (`composer.json:72-79`):

```json
"repositories": {
    "packages-dev": { "type": "path", "url": "packages-dev/*" },
    "packages":     { "type": "path", "url": "packages/*/*" }
}
```

A stock composer `path` repository derives a package's version from its
`composer.json` `version` field and falls back to a placeholder otherwise,
which historically forced a `repositories.*.options.versions` map to be
maintained next to every path repository — in the root, in both development
instances and in `monorepo-shared`. Four maps for the same twelve packages,
each able to drift.

Those maps are gone. Every `composer.json` that declares a path repository also
requires the composer plugin `sbuerk/extended-path-repository`
(`composer.json:15`, `packages-dev/monorepo-shared/composer.json`,
`core-13/composer.json`, `core-14/composer.json`). The plugin extends the
built-in `path` repository with two behaviours: it derives a path package's
version from `composer.json` `version`, from `extra."typo3/cms".version` or
from a sibling `VERSION` file before falling back to composer's stock
determination, and it silently ignores a configured `url` that matches nothing
instead of aborting the run.

So a package's version is written on the package itself, in two places that are
kept identical:

- `extra.typo3/cms.version` in its `composer.json`
- its `VERSION` file

Both read `3.0.0-dev` for all twelve extensions and all three `packages-dev`
packages on `main`. A release additionally bumps `version` in
`ext_emconf.php`, which currently reads `3.0.0` everywhere — the publish
workflow fails deliberately when the release tag and an `ext_emconf.php`
version disagree.

Practical consequence: to change a version, change it on the package. There is
no second place to remember.

## `packages-dev/monorepo-shared/` — one place for core constraints

`fgtclb/academics-monorepo-shared` requires all twelve extensions plus the
TYPO3 system extensions the repository needs, and it is what the root and both
development instances require in turn (`composer.json:14`,
`core-13/composer.json:16`, `core-14/composer.json:16`).

The instances require two packages on top of it:
`fgtclb/academics-monorepo-dev-site` for the seed definitions
(`core-13/composer.json:15`, `core-14/composer.json:15`), which the root does
not require at all, and `sbuerk/theme-extension-development` for the
`theme:seed` command that applies them (`core-13/composer.json:18`,
`core-14/composer.json:18`; the root carries it as a dev dependency,
`composer.json:66`). The latter is required for its seeder only — its theme is
not used.

Its point is that the TYPO3 core constraint appears once per system extension
rather than once per consuming package. Every `typo3/cms-*` entry in
`packages-dev/monorepo-shared/composer.json:27-50` reads:

```
"typo3/cms-core": "~13.4.0@dev || ~14.3.6@dev",
```

Raising the supported v14 patch level, or adding v15 later, is one edit in that
file. The individual extensions declare only the system extensions they
themselves use, and adding a system extension for a test only needs it added
here.

## `packages-dev/testing-helper/` — shared test traits

`fgtclb/academics-monorepo-testing-helper` autoloads
`FGTCLB\TestingHelper\` from `Classes/` and is required as a dev dependency of
the root. It ships seven functional-test traits in
`packages-dev/testing-helper/Classes/FunctionalTestCase/`:

| Trait                                  | Purpose                                                                             |
|----------------------------------------|-------------------------------------------------------------------------------------|
| `DeprecatedCoreLabelsTrait`            | Asserts that no TCA label points at a core label deprecated on the running version. |
| `EnsureTtContentListTypeColumnTrait`   | Ensures the `tt_content` list type column exists for a test.                        |
| `ExtensionCoreVersionCompatTestsTrait` | Proves a suite really ran against the core version it was asked for.                |
| `ExtensionsLoadedTestsTrait`           | Asserts the extension set under test is loaded.                                     |
| `FrontendPluginRenderingTrait`         | Renders a frontend plugin through a real request.                                   |
| `PluginFlexFormDataStructureTrait`     | Resolves and asserts a plugin's FlexForm data structure.                            |
| `TcaHelperMethodsTrait`                | Shared TCA lookup helpers for assertions.                                           |

They live in a package rather than in one extension's `Tests/` folder because
every extension needs them and no extension may depend on another extension's
test code. The package is development-only and is never part of a release.

## `packages-dev/dev-site/` — the seed the instances are built from

`fgtclb/academics-monorepo-dev-site`, extension key `academics_dev_site`,
holds the description of what a development instance contains: the page tree,
the content elements and the records, in
`packages-dev/dev-site/Configuration/Seeds/Instance.yaml`. Both instances
require it and write it in with `ddev composer instance:seed`.

It is the only package below `packages-dev/` with type `typo3-cms-extension`
rather than `library`, and the extension key is the whole reason: a seed is
addressed as `EXT:academics_dev_site/Configuration/Seeds/Instance.yaml`, which
is the one path form that resolves the same inside DDEV and on a host stack. A
`library` is not an installed extension, so `EXT:` could not reach it.

Everything else about it is deliberately minimal. It carries no PHP and no
`Classes/` — only the seed, a `composer.json`, a `VERSION` file, a `LICENSE` and
a `README.md`. Like its two siblings it has no `ext_emconf.php`: it is never
released, never split out to a read-only repository and never published to the
TER, so `bin/set-version` needs no special case for it. It is versioned along
with everything else for one reason only — it is a path package, so its version
has to be resolvable.

## Per-package `.gitattributes`

Each package carries its own `.gitattributes` marking development-only paths
with `export-ignore`. `export-ignore` is a `git archive` directive: it removes
the marked path from a generated source archive. In this repository that
matters for the tag tarball GitHub generates for a split repository, which is
what composer downloads on a `dist` install — the root pins
`"preferred-install": { "*": "dist" }` (`composer.json:28-30`).

Two things it does **not** do, both worth knowing before changing such a file:

- It does not shrink the split repository. The split mirrors the tree, so an
  `export-ignore`d path is still present there. `fgtclb/academic-persons`
  marks `/Tests export-ignore` and the split repository contains `Tests`
  nonetheless.
- It does not shape the TER artifact. `typo3/tailor create-artefact` builds its
  zip from its own exclude list
  (`.Build/vendor/typo3/tailor/conf/ExcludeFromPackaging.php`, overridable via
  `TYPO3_EXCLUDE_FROM_PACKAGING`) and never reads `.gitattributes`. That list
  already drops `tests`, `build`, `.github`, `.ddev`, `vendor` and more.

### The files are not consistent

All twelve were read, and they fall into two shapes plus per-package
deviations. This is history, not design — the packages joined the mono
repository from separately maintained repositories and kept the file they
arrived with.

**Shape A** — a bare `export-ignore` list, no comments, no end-of-line
normalization. Eight packages: `academic-base`, `academic-bite-jobs`,
`academic-jobs`, `academic-persons`, `academic-persons-edit`,
`academic-persons-sync`, `academic-projects`, `academic-study-plan`. Typical
entries are `/.gitlab-ci.yml`, `/.php-cs-fixer.dist.php`, `/phpstan.neon`,
`/readme.md`, `/UnitTests.xml`, `/FunctionalTests.xml`, `/.ddev`, `/.gitlab`,
`/config`, `/Migrations`, `/patches` and `/Tests`.

**Shape B** — a commented `# Folders` / `# Files` list followed by a block of
`text eol=lf` rules for two dozen file extensions. Four packages:
`academic-contact4pages`, `academic-partners`, `academic-programs`,
`typo3-category-types`. It ignores `/.github`, `/.vscode`, `/.Build`,
`/Tests/`, `.php-cs-fixer.php`, `.phplint.yml`, `.stylelintrc` and
`docker-compose.yaml`.

The two shapes disagree on more than formatting:

| Aspect                        | Shape A                                           | Shape B                                               |
|-------------------------------|---------------------------------------------------|-------------------------------------------------------|
| End-of-line normalization     | none                                              | `text eol=lf` for 20 file extensions                  |
| `/.github`                    | kept in the archive                               | `export-ignore`                                       |
| `/.Build`                     | not listed                                        | `export-ignore`                                       |
| php-cs-fixer config file name | `.php-cs-fixer.dist.php`                          | `.php-cs-fixer.php`                                   |
| Ignored tooling files         | `phpstan.neon`, `UnitTests.xml`, `.gitlab-ci.yml` | `.phplint.yml`, `.stylelintrc`, `docker-compose.yaml` |

Individual deviations on top of that:

- `academic-persons-sync` has the shortest list of all and does **not**
  `export-ignore` `/Tests`, `/Migrations`, `/patches`, `UnitTests.xml` or
  `FunctionalTests.xml`. Its test folder ends up in a generated archive.
- `academic-persons-edit` lists `UnitTests.xml` but not `FunctionalTests.xml`.
- `academic-persons` is the only shape A package that also ignores `/Build` and
  `/tailor-version-artefact`.
- `academic-contact4pages` is the only shape B package that does not ignore
  `/Build/` or `/.ddev`.
- Several shape A files end without a trailing newline.
- Neither the repository root nor any of the three `packages-dev` packages has a
  `.gitattributes` at all. The root is a `project` that is never installed as a
  dependency and the three are development-only, so nothing generates an archive
  from them.

Most of the files also still reference tooling this repository no longer uses
per package — `.gitlab-ci.yml`, `.gitlab/`, `docker-compose.yaml`,
`phpstan.neon` next to the package. Checks run through
`Build/Scripts/runTests.sh` and `.github/workflows/ci.yml` at the root instead.
The stale entries are harmless: `export-ignore` on a path that does not exist
is a no-op. They are listed here so nobody reads them as evidence of a
per-package tool chain.

Aligning the twelve files is a worthwhile cleanup, but it is a deliberate
change with a release-visible effect on archive contents, not something to do
in passing while editing an extension.

## See also

- [Dual core setup](dual-core-setup.md) — why the installed dependency set has
  to match `-t` and `-p`
- [Development environment](environment.md) — host requirements and the
  `runTests.sh` options
- [Quality gates](quality-gates.md) — what each suite checks and how continuous
  integration stages them
- [Core version aware code](../architecture/core-version-aware-code.md)
- [`README.md`](../../README.md) — support matrix, split repository table and
  the maintainer release walkthrough
