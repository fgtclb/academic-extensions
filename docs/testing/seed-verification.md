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
compares the markup — 104 page pairs, which is the 56 pages of the mirror minus
the four the seed hides, times two languages.

This is the only thing that catches the failure mode the `/legacy/` tree exists
to expose. `include_static_file` and `tsconfig_includes` are comma separated
lists read with `trimExplode`; an entry that resolves to nothing contributes
nothing, raises nothing, and the page still answers `200` with a piece of its
configuration missing. No assertion on one tree can see that. Removing a single
entry from the seeded `sys_template` record makes 84 of the 104 rendered page
pairs differ.

What is normalised away is listed in the test, one comment per rule, and it is
only what a mirror differs in by being one: the `/legacy` path segment, the
`websiteTitle` of the two sites, the titles of the two root pages, uids in
attribute values and query strings, and four values drawn per request (the CSP
nonce, a cHash, an error page request id, the login form's request token).

A second test asserts what each page *owes* a visitor: `200`, `403` for the two
pages the seed puts behind a frontend user group, and `404` on TYPO3 v14 for the
profile detail page — `ProfileController::detailAction()` answers
`ErrorController::pageNotFoundAction()` when it is called without a profile, and
v13 still renders the page as `200` where v14 lets that 404 reach the response.
Stated rather than asserted away: it is a difference between the two cores over
the same seed and the same extension.

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

## The ACE-462 workaround

`AbstractSeedTestCase` re-runs the initialisation of `PageDoktypeRegistry` after
the TCA is built, on TYPO3 v13 only. Without it `DataHandler` refuses every
`tt_content` element and every academic record of the import — "Attempt to
insert record on pages:221 where table … is not allowed" — and the seed comes
out as a page tree with nothing on it.

It is in the harness and not in the seed on purpose: the defect is in the
registry, and a seed that compensated for it would hide the fix when ACE-462
lands. Remove it then.

## See also

- [PHPUnit configuration](phpunit-configuration.md) — the test discovery globs.
- [TypoScript and site sets](../architecture/typoscript-and-site-sets.md) — the
  two delivery mechanisms the seed runs side by side.
- [Development instances](../development/instances.md) — the instances the
  snapshots build.
- [`packages-dev/dev-site/README.md`](../../packages-dev/dev-site/README.md) —
  the seed itself.
