# Seed verification

The development seed is three artifacts of one statement, and they have drifted
apart in silence twice in this repository:

| Artifact                                                                                                                                                                        | Written by                                                         |
|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------|
| [`packages-dev/dev-site/Configuration/DataFactory/academics-instance/`](../../packages-dev/dev-site/Configuration/DataFactory/academics-instance)                               | A human, editing YAML.                                             |
| [`sqlite-databases/core-13.sqlite`](../../sqlite-databases) and `core-14.sqlite`                                                                                                | A human, importing that YAML into an instance and taking a backup. |
| [`packages-dev/dev-site/Tests/Functional/Fixtures/SeedManifest-core13.json`](../../packages-dev/dev-site/Tests/Functional/Fixtures/SeedManifest-core13.json) and `-core14.json` | `Build/Scripts/runTests.sh -s seedManifest`.                       |

Nothing forced the first two to agree. A page added to the definition and never
re-snapshotted simply was not in the instance a fresh clone came up with, and
the disagreement was found by somebody wondering why a page was missing. The
manifest is the third artifact that makes it loud: the definition is measured
against it, and so is every snapshot.

Four test classes carry that, all in
[`packages-dev/dev-site/Tests/Functional/`](../../packages-dev/dev-site/Tests/Functional).
They are collected because `Build/phpunit/*.xml` globs `packages-dev/*/Tests/`
as well as `packages/*/*/Tests/` — see
[PHPUnit configuration](phpunit-configuration.md).

The other test classes of the package check what it ships besides the seed:
the three `IconOverview*` classes the icon overview page, and
`StylesheetClassesTest` (functional) and `StudyPlanControlIconRulesTest` (unit)
the stylesheet of the instances, see
[Frontend assets](../development/frontend-assets.md#the-stylesheet-of-the-development-instances).

## The manifest

`SeedManifest-core13.json` and `SeedManifest-core14.json` hold, per table the
seed writes:

- `rows` — how many rows the import produced;
- `columns` — the columns the seed states a value for;
- `checksum` — a SHA-1 over those columns of those rows.

It is **generated from a real import and never counted from the YAML**. The two
are not the same thing: `DataHandler` writes a `sys_file_reference` row for the
translation of a page and of a profile that no `references:` entry declares, so
that table ends up with 86 rows where `config.yml` names 56.

Two reductions make the measurement comparable between a functional test
instance and a development instance:

- **Rows are addressed by the uid the seed declares**, except in `sys_file` and
  `sys_file_reference`, which are read whole because the seed does not declare
  every row of them. Without that, `be_users` would compare the admin of an
  installation against a test fixture.
- **Columns are derived from the definition** — every column an entity of a
  scenario declares, plus the pid, language and default value columns its
  `entitySettings` add. A column outside that set is not something the seed says
  anything about, and it is where two correct installations differ: a
  development instance has EXT:bootstrap_package and its columns on `pages`, a
  functional test instance does not. `password` is dropped: `DataHandler` hashes
  it with a salt drawn per run.

Two values are normalised on the way in, both because drivers disagree about
them and not because seeds do: `null` and the empty string are folded together
(a column declared `NOT NULL DEFAULT ''` comes back as one on some database
systems and as the other on others — ACE-358), and a decimal loses its trailing
zeros (`pages.tx_academicprojects_budget` is `decimal(11,2)`, and SQLite answers
`120000` where PostgreSQL answers `120000.00`). Without the second one the
manifest is red on the PostgreSQL jobs of the matrix and green everywhere else.

Regenerate it after any change to the seed, once per core version:

```bash
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.2 -s seedManifest
Build/Scripts/runTests.sh -t 14 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 14 -p 8.2 -s seedManifest
```

The two runs write two files, and that is not symmetry for its own sake: the two
cores initialise a column the seed does not name differently. TYPO3 v13 writes
the schema default into `pages.geocode_status` (`open`) and
`tt_content.imagewidth` (`0`) where v14 leaves both `NULL` — on 218 and 236 rows
respectively. Folding that away would mean dropping the two columns from the
projection, which hides a difference that is real, and the artifact the manifest
is checked against is per core version anyway. Everything else in the two files
is identical.

`seedManifest` is a suite of its own rather than a `--group` on `functional`,
because a PHPUnit group filter given on the command line *replaces* the one in
the configuration file instead of adding to it. `functional` therefore excludes
the group `seed-manifest-update` itself, and `seedManifest` is the only run that
does not carry that exclusion.

## The four checks

### `SeedManifestTest` — the definition against the manifest

Imports the seed and measures it. It fails when the definition changed and
nobody regenerated the manifest, and it fails on the column lists too: those are
compared against what the definition produces *today*, so a column added to the
seed is reported rather than quietly dropping out of both sides of the checksum.

A second test asserts the manifest covers every table the import wrote, which
fails differently on purpose — a table the manifest does not list is a table
nothing is checking.

### `SnapshotManifestTest` — the snapshot against the manifest

Reads `sqlite-databases/core-<major>.sqlite` as a file, through PDO, read only,
and measures it the same way. Nothing is imported: the subject is the committed
artifact.

This is the test that fails when a snapshot was not regenerated after a change
to the seed. Regenerating it needs the DDEV instances and therefore the main
checkout — see
[Rebuilding an instance from nothing](../development/environment.md#rebuilding-an-instance-from-nothing).

### `LegacyDeliveryTest` — the two trees against each other

Renders every mirrored page of `/` and of `/legacy/`, in both languages, and
compares the markup — 112 page pairs, which is the 60 pages of the mirror minus
the four the seed hides, times two languages.

This is the only thing that catches the failure mode the `/legacy/` tree exists
to expose. `include_static_file` and `tsconfig_includes` are comma separated
lists read with `trimExplode`; an entry that resolves to nothing contributes
nothing, raises nothing, and the page still answers `200` with a piece of its
configuration missing. No assertion on one tree can see that. Removing a single
entry from the seeded `sys_template` record made 84 of the then 104 rendered page
pairs differ.

What is normalised away is listed in the test, one comment per rule, and it is
only what a mirror differs in by being one: the `/legacy` path segment, the
`websiteTitle` of the two sites, the titles of the two root pages, uids in
attribute values and query string values, the uid that keys the view mode of a
selected element in a query string, and four values drawn per request (the CSP
nonce, a cHash, an error page request id, the login form's request token). An
attribute value may carry two uids, the dialog id of a study plan shown by an
"Insert records" element names that element and the module
(`popup-c1069-225`), and a rule of its own masks exactly that shape,
`-c<uid>-<uid>`, rather than every value with two numbers.

A second test asserts what each page *owes* a visitor: `200`, `403` for the two
pages the seed puts behind a frontend user group, and `404` for the three detail
pages, of all profiles, of the hidden profiles and of the jobs. Their plugins end
a request without a record with the "page not found" handling of the site, on
TYPO3 v13 and v14 alike.

The theme is substituted on **both** sides. The `/` tree of a development
instance is themed by EXT:bootstrap_package and the `/legacy/` tree cannot be —
see [TypoScript and site sets](../architecture/typoscript-and-site-sets.md) —
so the test puts the same minimal page object on both, shipped by the seed
package in the two delivery forms. Everything academic is delivered exactly as
the seed declares it, through both mechanisms, which is what the comparison is
about.

### `DeliveryRegistrationTest` — the drift gate

Every `include_static_file` and every `tsconfig_includes` entry of the legacy
root has to **resolve** and be a **registered TCA item**.

The second half is the one that is easy to leave out and is the point. A static
template folder that is still on disk but is no longer offered by
`addStaticFile()` is a folder an integrator could not select any more: the seed
would be describing an installation nobody could build by hand, and the site
sets of the `/` tree would have taken the delivery over without anybody saying
so out loud. That is exactly what happened to EXT:bootstrap_package between its
versions 15 and 16.

"Resolves" for a static template means the folder holds a
`constants.typoscript`, a `setup.typoscript` **or** an `include_static_file.txt`
— the aggregate folders of the academic extensions hold nothing but the third
one.

## Three checks of the YAML itself

Three unit tests in
[`packages-dev/dev-site/Tests/Unit/`](../../packages-dev/dev-site/Tests/Unit)
look at the definition without importing it:

- `GeneratedLegacyScenarioTest` runs `generateLegacyScenario.php --check`, so a
  `Scenario.yaml` changed without regenerating `ScenarioLegacy.yaml` fails.
- `CategoryVariantTypeTest` asserts that every language variant of a category
  repeats the `type` of its original. `sys_category.type` is not
  `l10n_mode: exclude`, so a variant without it is stored as `default`, and the
  language overlay of the category repository then gives the category that type
  in the translated language: the German categories of the seed had lost their
  types that way, and no German list offered a category filter. Nothing else
  reports it - the manifest measures what the seed says, and it said nothing.
- `SeedBlockScalarChompingTest` rejects a literal or folded block without strip
  chomping in any file of a seed set. It reads the files as text, because the
  parser is what it guards against, see [The YAML parser](#the-yaml-parser).

Of the two other unit tests of the directory, `TemplateUpgradeWizardsTest`
checks the snapshots, see
[The upgrade wizards of the snapshots](#the-upgrade-wizards-of-the-snapshots),
and `StudyPlanControlIconRulesTest` belongs to the stylesheet of the instances,
not to the seed.

## The upgrade wizards of the snapshots

`TemplateUpgradeWizardsTest`, a unit test in the same directory, reads every
`sqlite-databases/core-*.sqlite` of the branch as a file, read only, through
PDO, and fails for each upgrade wizard of the extensions that a snapshot does
not mark as done. TYPO3 records a wizard as done in `sys_registry`, as the row
`installUpdate` / `<class name>` with the value `i:1;`, and `typo3 setup` writes
that row only for the wizards of the code a snapshot is built from. A wizard
added later is offered by every instance restored from the snapshot, as if the
seed still needed it. Two wizards were missed that way before the check existed
(ACE-899).

The wizards come from the code, not from a list. Every class the composer
autoloader maps below `packages/fgtclb/*/Classes/` whose file names
`UpgradeWizard` is loaded and reflected, so an aliased import or an attribute
wrapped over several lines is found as well. The match ignores case, as PHP
does for class names. A candidate file whose class does not match its path
fails the test rather than being skipped.
A wizard is a class with the `UpgradeWizard` attribute of TYPO3. One with
Symfony's `#[Exclude]` is not registered, and one implementing
`RepeatableInterface` is never marked done, so neither is expected in a
snapshot. Core and third-party wizards are not checked.

It is a unit test because it needs no TYPO3 instance, and it reads every
snapshot, not only the one of the installed core, so a run on either core
version checks both. It also fails when the snapshot of a core version the
branch supports is missing, and when the discovery finds fewer wizards than the
branch has, so a broken discovery does not pass with nothing to check.

A failure names the snapshot, the wizard class and the commands that mark it
done, see
[A new upgrade wizard is marked done in the templates](../development/instances.md#a-new-upgrade-wizard-is-marked-done-in-the-templates).

## Why the manifest and the snapshot can agree at all

The manifest is generated from an import in a **functional test instance** and
then used to measure the `sqlite-databases/core-NN.sqlite` snapshot, which is
produced by an import in a **real DDEV instance**. Those are two different
environments, and four of their differences reach the stored data. All are
pinned rather than excluded from the projection, because they are real
differences that a reader would otherwise have to know about:

**The timezone.** TYPO3 derives the timestamp it stores in a date field with the
server timezone, so `valid_from: 1704067200` in the seed does not come back out
as `1704067200` — it comes back out shifted by whatever the running instance is
set to. The DDEV containers are `Europe/Berlin`, the test containers are UTC,
and the two imports disagreed on 24 `pages` rows and 2 contracts by exactly one
hour. `core-NN/config/system/settings.php` therefore pins
`SYS/phpTimeZone` to `UTC`. That is not only about these two environments: left
unpinned, a maintainer in another timezone regenerating the snapshot would
commit a different binary for an unchanged seed.

**The site configurations.** `DataHandler` builds the prefix of a translated
field — `[Translate to German:]` — from the *title of the site language* the
record is translated into. With no site there is no language and the prefix
comes out as `[Translate to :]`, which the seed then writes into 22 translated
`sys_file_reference` rows. `AbstractSeedTestCase::importSeed()` therefore adopts
the committed site configurations of `core-NN/config/sites/` through
`SiteWriter` before it imports. They are copied, not rebuilt, so a site setting
changed in the instance reaches the measurement without anyone remembering to
mirror it.

### The YAML parser

The two environments read the seed with different releases of `symfony/yaml`:
the functional suites with whatever `composerUpdate` installed that day, the
development instances with the release their committed `composer.lock` pins.
That stays harmless only while every release reads the seed the same way, and
on 2026-09-29 one did not (ACE-776).

A literal block without a chomping indicator (`bodytext: |`) is *clipped*: the
value ends with exactly one newline. symfony/yaml 7.4.18 and earlier dropped
that newline when the block ended a mapping nested in a list item, such as the
`self:` of a seed entity, and the next line was less indented. 7.4.20 keeps it
everywhere, as the specification says. Most blocks of `Scenario.yaml` and
`ScenarioLegacy.yaml` are in that position, so the import of 7.4.20 wrote a
newline more into `tt_content.bodytext`, `tt_content.pi_flexform` and
`sys_template.constants`. `SeedManifestTest` failed on every functional job of
every pull request, while `SnapshotManifestTest` still passed against the
templates the instances built with their locked 7.4.15. Nothing in the
repository had changed.

The rule that keeps it from coming back:

- **Every block scalar of a seed set states strip chomping**: `|-`, or `>-` for
  a folded one. Strip removes every trailing newline in every release, so the
  value no longer depends on the parser. No value of the seed needs a final
  newline. `Build/Scripts/generateLegacyScenario.php` writes its blocks the
  same way, and `SeedBlockScalarChompingTest` fails on a block without `-`, in
  the `.yaml` and the `.yml` files of every set.
- **A manifest that drifts while the seed did not change points at a
  dependency**, not at the environment. Compare the `Installing` lines of the
  composer step of the last green and the first red CI run, and parse the seed
  with both releases of the suspect package before regenerating anything. A
  manifest regenerated to match one release is red against the snapshots,
  which the other release produced.

### The core release

The same holds for TYPO3 itself. The functional suites run on the latest patch
release `composerUpdate` finds, the instances on the one their `composer.lock`
pins. 14.3.9 writes the TCA default of a field into a new record even when its
type does not show the field, which earlier releases left out. That changed at
least one of four columns on all 258 `pages` rows of the seed
(`geocode_status`, `link` and two project fields) and turned `SeedManifestTest`
red on every v14 job, with no change in the repository (ACE-906). It moved
columns of `tt_content` as well, which the manifest does not measure.

When a core release changes what the import writes, three things move
together, in one change: the instance's `composer.lock` moves to that release,
the snapshot is rebuilt from nothing with it, and the manifest is regenerated.
Without a raised floor, the `--minimal-changes` update of
[Raising the supported core patch level](../development/monorepo-layout.md#raising-the-supported-core-patch-level)
keeps the locked release, because no constraint forces it to move. Name every
`typo3/cms-*` package of the lock instead, without `-W`, so nothing else moves.
A dry run with the target release names the dependency that has to come along:

```shell
ddev composer update <every typo3/cms-* package> 'typo3/cms-core:14.3.9' --dry-run
ddev composer update <every typo3/cms-* package> <the dependency it named>
```

The rule behind all four: when the manifest and the snapshot disagree, ask
which environment is wrong before adjusting what is measured. Dropping a column
from the projection hides a difference that is real.

## See also

- [PHPUnit configuration](phpunit-configuration.md) — the test discovery globs.
- [TypoScript and site sets](../architecture/typoscript-and-site-sets.md) — the
  two delivery mechanisms the seed runs side by side.
- [Development instances](../development/instances.md) — the instances the
  snapshots build.
- [`packages-dev/dev-site/README.md`](../../packages-dev/dev-site/README.md) —
  the seed itself.
