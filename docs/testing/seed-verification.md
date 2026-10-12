# Seed verification

The development seed is three artifacts of one statement, and they have drifted
apart in silence twice in this repository:

| Artifact                                                                                                                                                                        | Written by                                                         |
|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------|
| [`packages-dev/dev-site/Configuration/DataFactory/`](../../packages-dev/dev-site/Configuration/DataFactory)                                                                     | A human, editing YAML.                                             |
| [`sqlite-databases/core-12.sqlite`](../../sqlite-databases) and `core-13.sqlite`                                                                                                | A human, importing that YAML into an instance and taking a backup. |
| [`packages-dev/dev-site/Tests/Functional/Fixtures/SeedManifest-core12.json`](../../packages-dev/dev-site/Tests/Functional/Fixtures/SeedManifest-core12.json) and `-core13.json` | `Build/Scripts/runTests.sh -s seedManifest`.                       |

Nothing forced the first two to agree. A page added to the definition and never
re-snapshotted simply was not in the instance a fresh clone came up with, and
the disagreement was found by somebody wondering why a page was missing. The
manifest is the third artifact that makes it loud: the definition is measured
against it, and so is every snapshot.

Five test classes carry that, all in
[`packages-dev/dev-site/Tests/Functional/`](../../packages-dev/dev-site/Tests/Functional).
They are collected because `Build/phpunit/*.xml` globs `packages-dev/*/Tests/`
as well as `packages/*/*/Tests/` — see
[PHPUnit configuration](phpunit-configuration.md).

## The manifest

`SeedManifest-core12.json` and `SeedManifest-core13.json` hold, per table the
seed writes:

- `rows` — how many rows the import produced;
- `columns` — the columns the seed states a value for;
- `checksum` — a SHA-1 over those columns of those rows.

It is **generated from a real import and never counted from the YAML**. The two
are not the same thing: `DataHandler` writes a `sys_file_reference` row for the
translation of a page and of a profile that no `references:` entry declares, so
that table ends up with 54 rows on v12 where `config.yml` names 35, and with 86
rows on v13 where the `config.yml` of the second set names 56.

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
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate
Build/Scripts/runTests.sh -t 12 -p 8.1 -s seedManifest
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.2 -s seedManifest
```

The two runs write two files, because the two cores import different sets.
TYPO3 v12 imports `academics-instance`, TYPO3 v13 imports the generated
`academics-instance-sets` with its `/legacy/` mirror, see
[Two sets, one of them generated](#two-sets-one-of-them-generated). Both files
list the same 28 tables with the same columns. Twenty of them are identical in
both: users and groups, files, categories and the records of the storage
folders, which the mirror does not repeat. Seven grow on v13 because the mirror
repeats their rows:

| Table                                            | v12 | v13 |
|--------------------------------------------------|-----|-----|
| `pages`                                          | 132 | 246 |
| `tt_content`                                     | 128 | 256 |
| `sys_file_reference`                             | 54  | 86  |
| `tx_academiccontacts4pages_domain_model_contact` | 22  | 44  |
| `tx_academicpartners_domain_model_partnership`   | 16  | 32  |
| `tx_academicstudyplan_domain_model_module`       | 48  | 96  |
| `tx_academicstudyplan_domain_model_semester`     | 16  | 32  |

`sys_template` holds one row on both and differs in its content: on v12 it is
the root record of the `/` tree, on v13 the one of the `/legacy/` tree.

`seedManifest` is a suite of its own rather than a `--group` on `functional`,
because a PHPUnit group filter given on the command line *replaces* the one in
the configuration file instead of adding to it. `functional` therefore excludes
the group `seed-manifest-update` itself, and `seedManifest` is the only run that
does not carry that exclusion.

## Two sets, one of them generated

The seed is imported as two sets, and which one an instance imports is the only
thing that differs between them:

| Set                       | Imported by | The `/` tree                              | A `/legacy/` mirror        |
|---------------------------|-------------|-------------------------------------------|----------------------------|
| `academics-instance`      | `core-12`   | root `sys_template` + `tsconfig_includes` | no                         |
| `academics-instance-sets` | `core-13`   | site sets                                 | yes, `sys_template` driven |

Site sets arrived in TYPO3 v13.1, so on v12 there is only one way to deliver and
only one tree worth having.

**The source is the `core-12` shape**, and `academics-instance-sets/` is written
from it by `Build/Scripts/generateLegacyScenario.php`, whose `--check` mode fails
the build when it would change. Generation *removes* three keys — the root page's
`tsconfig_includes`, the `sys_template` record below it and that record's entity
declaration — and adds the mirror. Removing named keys cannot lose anything else;
the reverse direction could, which is why it goes this way round and not the
other.

A scenario file cannot amend a record another file declares: `ScenarioComposer`
appends entities and rejects a repeated id with exception 1568146788. That is
why the two shapes are two full trees rather than one tree and a patch.

`GeneratedLegacyScenarioTest` runs the `--check` mode as a unit test, and
`SeedBlockScalarChompingTest` rejects a literal or folded block without strip
chomping in any file of either set. The second one reads the files as text,
because the parser is what it guards against, see
[The YAML parser](#the-yaml-parser).

`CategoryVariantTypeTest` asserts that every language variant of a category
states the `type` of its original. `sys_category.type` is not
`l10n_mode: exclude`, so a variant without it is stored as `default`, and the
category repository of `category_types` then reads that type in German: no
German list offered a category filter, and the manifest recorded the defect as
the expected state (ACE-834).

The fourth unit test of the directory, `TemplateUpgradeWizardsTest`, checks the
snapshots, see
[The upgrade wizards of the snapshots](#the-upgrade-wizards-of-the-snapshots).

## The checks

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
compares the markup: 106 page pairs, which is the 57 pages of the mirror minus
the four the seed hides, times two languages.

This is the only thing that catches the failure mode the `/legacy/` tree exists
to expose. `include_static_file` and `tsconfig_includes` are comma separated
lists read with `trimExplode`; an entry that resolves to nothing contributes
nothing, raises nothing, and the page still answers `200` with a piece of its
configuration missing. No assertion on one tree can see that. Removing a single
entry from the seeded `sys_template` record made 84 of the then 104 rendered
page pairs differ.

What is normalised away is listed in the test, one comment per rule, and it is
only what a mirror differs in by being one: the `/legacy` path segment, the
`websiteTitle` of the two sites, the titles of the two root pages, uids in
attribute values and query strings, and four values drawn per request (the CSP
nonce, a cHash, an error page request id, the login form's request token).

A second test asserts what each page *owes* a visitor: `200`, `403` for the two
pages the seed puts behind a frontend user group, and `404` for the three detail
pages, the profile detail of the lists, the one of the hidden profiles and the
job detail. Their plugins end a request without a record with the "page not
found" handling of the site, on TYPO3 v12 and v13 alike.

The theme is substituted on **both** sides. Both trees of the `core-13` instance
are themed by EXT:bootstrap_package 15, the `/` tree through its site set and the
`/legacy/` tree through its static template, but the functional suite does not
install the theme. So the test puts the same two things in its place on both
sides: the minimal page object of the seed package, shipped in the two delivery
forms, and fluid_styled_content.

- The `/` site gets the set `fgtclb/academics-dev-site-page-object` where the
  committed configuration names `bootstrap-package/full`, and that set depends
  on `typo3/fluid-styled-content`.
- In the legacy root `sys_template` record, the test replaces the entry
  `EXT:bootstrap_package/Configuration/TypoScript` with
  `EXT:fluid_styled_content/Configuration/TypoScript` after the import, in the
  same position. It fails instead of comparing when the record no longer names
  the theme exactly once. The seed and the manifest tests keep the record as
  the seed declares it.

Everything academic is delivered exactly as the seed declares it, through both
mechanisms, which is what the comparison is about.

The legacy half is not cosmetic, and its absence kept the class skipped on this
branch until ACE-468. Extbase registers the TypoScript of every plugin for the
content rendering, and a `sys_template` tree receives it only right after a
static template listed in
`$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates']`. A site set
tree receives it without that condition. The definitions of the core content
elements are that template itself: fluid_styled_content, or in the instance the
theme, which brings its own. The theme is the only such template the legacy
record names, so with the theme missing, 86 of 106 page pairs differed, and
every content element of the legacy tree, `header` and `text` included,
reported that it has no rendering definition.
`main` has no substitution on the legacy side, because its legacy record names
fluid_styled_content itself.

### `SeedTranslationParentTest`: the translations against their originals

Imports the seed and asserts three things the manifest cannot see, because the
manifest is generated from an import and records whatever that import wrote:

- every translation points at its original through its language pointers, the
  uid of the original plus 500 as the header of `Scenario.yaml` numbers them;
- every column with `l10n_mode: exclude` holds the value of the original,
  because the frontend overlay reads it from the translation;
- the import deletes nothing.

It exists because the TYPO3 v12 snapshot carried German partnerships and page
contacts that pointed at one unrelated uid, and translations of the partner,
program and project pages that had been deleted and localized again under new
uids (ACE-834). Their tables declared no `l10n_parent` column in their TCA.
TYPO3 v13 creates a missing one as a select of the table itself, TYPO3 v12 adds
it as `passthrough`, and a `NEW` placeholder in a passthrough language pointer
goes to the remap stack of `DataHandler` in an entry without a function:
`processRemapStack()` then writes whatever value the entry before it computed.
Both tables declare the columns since ACE-854. Stating the pointers as plain
uids in the seed does not work around it: the localization data map processor
looks the parent up in the database, finds nothing because it is written in
the same run, and stores every excluded column of the translation empty, which
the second assertion reports.

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

## The upgrade wizards of the snapshots

`TemplateUpgradeWizardsTest`, a unit test in
[`packages-dev/dev-site/Tests/Unit/`](../../packages-dev/dev-site/Tests/Unit),
reads every `sqlite-databases/core-*.sqlite` of the branch as a file, read
only, through PDO, and fails for each upgrade wizard of the extensions that a
snapshot does not mark as done. TYPO3 records a wizard as done in
`sys_registry`, as the row `installUpdate` / `<class name>` with the value
`i:1;`, and `typo3 setup` writes that row only for the wizards of the code a
snapshot is built from. A wizard added later is offered by every instance
restored from the snapshot, as if the seed still needed it. Two wizards were
missed that way before the check existed (ACE-899).

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
[When an upgrade wizard is added](../development/environment.md#when-an-upgrade-wizard-is-added).

## Why the manifest and the snapshot can agree at all

The manifest is generated from an import in a **functional test instance** and
then used to measure the `sqlite-databases/core-NN.sqlite` snapshot, which is
produced by an import in a **real DDEV instance**. Those are two different
environments, and three of their differences could reach the stored data. They
are pinned rather than excluded from the projection, because they are real
differences that a reader would otherwise have to know about:

**The timezone — measured, and currently not a problem here.** TYPO3 can derive
the timestamp it stores in a date field with the server timezone, and the DDEV
containers of this branch run `Europe/Berlin` while the test containers run UTC.
On the `main` branch that difference reaches the data and is pinned away with
`SYS/phpTimeZone`. On this branch it does not: the seed's `valid_from:
1704067200` comes back out of both imports as `1704067200`, verified against the
committed `core-12` snapshot.

So nothing is pinned here — but the agreement is a property of how these fields
are configured on TYPO3 v12 and v13, not a guarantee. If a date column ever
starts disagreeing between the manifest and the snapshot by exactly one hour,
this is what it is, and `main` already carries the fix to copy.

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
development instances with the release their committed `composer.lock` pins,
a 6.4 one in `core-12` and a 7.4 one in `core-13`. That stays harmless only
while every release reads the seed the same way, and on 2026-09-29 two did not
(ACE-776).

A literal block without a chomping indicator (`bodytext: |`) is *clipped*: the
value ends with exactly one newline. symfony/yaml 6.4.45, 7.4.18 and earlier
releases dropped that newline when the block ended a mapping nested in a list
item, such as the `self:` of a seed entity, and the next line was less
indented. 6.4.47 and 7.4.20 keep it everywhere, as the specification says. The import of the new releases wrote a
newline more into `tt_content.bodytext`, `tt_content.pi_flexform` and
`sys_template.constants`, and `SeedManifestTest` failed on both core versions,
while `SnapshotManifestTest` still passed against the templates the instances
built with their locked releases. Nothing in the repository had changed.

The rule that keeps it from coming back:

- **Every block scalar of a seed set states strip chomping**: `|-`, or `>-` for
  a folded one. Strip removes every trailing newline in every release, so the
  value no longer depends on the parser. No value of the seed needs a final
  newline. `Build/Scripts/generateLegacyScenario.php` writes the blocks of
  `ScenarioLegacy.yaml` the same way, and the dumper of symfony/yaml writes
  `|-` into the generated `Scenario.yaml` for every block it writes of a value
  without a final newline. `SeedBlockScalarChompingTest` fails on a block
  without `-`, in the `.yaml` and the `.yml` files of both sets.
- **A manifest that drifts while the seed did not change points at a
  dependency**, not at the environment. Compare the `Installing` lines of the
  composer step of the last green and the first red CI run, and parse the seed
  with both releases of the suspect package before regenerating anything. A
  manifest regenerated to match one release is red against the snapshots,
  which the other release produced.

The rule behind all three: when the manifest and the snapshot disagree, ask
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
