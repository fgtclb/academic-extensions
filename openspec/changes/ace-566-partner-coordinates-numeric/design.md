## Context

Measured on `main` `e75f86b95`, against TYPO3 13.4.35 and 14.3.7 with
Doctrine DBAL 4.4.4. Paths are below `packages/fgtclb/academic-partners/`.

- `ext_tables.sql:9-10` declares `geocode_longitude` and `geocode_latitude` as
  `VARCHAR(20) DEFAULT NULL`. The TCA (`Configuration/TCA/Overrides/pages.php:147-178`)
  is `type => input`, `max => 20`, `eval => trim`, with
  `allowLanguageSynchronization`, and no `nullable`.
- `Domain/Model/Partner.php:31-32` types both as `float` defaulting to `0`, the
  accessors at `:130-148` likewise. Extbase's `DataMapper::thawProperties()`
  skips a `NULL` column (`isset()` on the row, both cores), so the property keeps
  its default, and casts `''` to `0.0`.
- Three drawable rules exist since ACE-562, and each documents where it
  disagrees with the others:
  - `PartnerRepository::drawableCoordinatesConstraint()` (`:163-212`, used by
    `findByDemand()` with `drawableOnly` at `:80-81` and by `findGeoLocated()`
    at `:148`): not `''`, and not the string pair `'0'`/`'0'`.
  - `Partner::isDrawable()` (`:150-186`): finite, and not `0.0`/`0.0`. A pair
    with one `NULL` column reads as drawable.
  - `Resources/Private/TypeScript/frontend/map.ts:138-165`: neither attribute
    empty, both finite, not both zero. `Number('52,52')` is `NaN`.
- `Command/GeocodeCommand.php:133-134` writes `(string)(float)` of the
  Nominatim result, through the DataHandler (`writeGeocodeResult()`), so a TCA
  eval applies to it.
- `Upgrades/SynchronizePartnerTranslationsUpgradeWizard.php:48-51` names both
  columns, and compares the values as strings (`:203`).
- `Resources/Private/Partials/Partner/Map.html:28` guards a single partner with
  `{partner.shownOnMap}`, `:93-94` renders `data-lat`/`data-lng`.
- 28 functional test fixtures of the extension store `""` in a coordinate
  column, 270 cells in all.

Core behaviour that shapes the approach, read in both vendor trees:

- TCA `type => number` with `format => decimal` rounds to two decimals
  (`DataHandler::checkValueForNumber()`, `$precision = 2` with
  `@todo Make precision configurable`, identical on v13 and v14). Two decimals
  are about a kilometre. The core's own `sys_file_metadata.latitude` is
  `decimal(24,14)` behind a TCA `input` with `eval => trim` for that reason.
- A custom eval registered in `SC_OPTIONS['tce']['formevals']` runs
  `evaluateFieldValue()` on save and `deevaluateFieldValue()` for display in
  FormEngine, on both cores.
- `typo3 extension:setup` applies the `add` **and** `change` suggestions of
  the schema migrator and ignores its result (v13
  `PackageActivationService::updateDatabase()`, v14
  `PackageSetup::updateDatabaseSchemaForAllPackages()`).
  `DatabaseUpdatedPrerequisite` only adds tables and fields. In the usual
  deployment the schema therefore changes before `upgrade:run`, and no upgrade
  wizard can prepare data for a column type change.
- The PostgreSQL platform of DBAL emits `ALTER TABLE pages ALTER <column> TYPE
  NUMERIC(10, 7)`, without `USING`. TYPO3's own platform does not override it.

An in-place type change of the existing columns was probed with raw SQL on
PostgreSQL 15, MariaDB 10.11 and SQLite (the table copy DBAL performs):

| Stored text      | PostgreSQL, statement of the analyzer | MariaDB, strict (default) | MariaDB, not strict | SQLite                |
|------------------|---------------------------------------|---------------------------|---------------------|-----------------------|
| `52.520008`      | fails: no automatic cast              | `52.5200080`              | `52.5200080`        | `52.520008` (real)    |
| `''`             | fails                                 | fails (1292)              | `0.0000000`         | `''` stays text       |
| `52,52`          | fails                                 | fails (1292)              | `52.0000000`        | `52,52` stays text    |
| `abc`            | fails                                 | fails (1292)              | `0.0000000`         | `abc` stays text      |
| `52.12345678912` | fails                                 | `52.1234568`              | `52.1234568`        | `52.12345678912`      |

On PostgreSQL the statement fails even when every value is clean, and with
`USING` added it still fails on the column default (`NULL::character varying`)
until that is dropped, then on the first `''` or `52,52`. After a failed change
the column stays text, and a numeric comparison against it is an error on
PostgreSQL. Once the column is numeric, today's `= ''` comparison is an error
on PostgreSQL (`invalid input syntax for type numeric`).

## Goals / Non-Goals

**Goals:**

- One drawable rule, implemented three times from one table of cases.
- A migration that gives the same result on all four supported databases,
  whatever order `extension:setup` and `upgrade:run` run in.
- No value an editor or the geocoder stored is lost without being named.

**Non-Goals:**

- ACE-437 (the empty `geocode_status`) and retrying `failed` geocoding.
- Site settings of the map centre, which keep their own validation (ACE-769).
- A client-side validation in FormEngine beyond what the eval returns.

## Decisions

### 1. Column type and precision. Decision for the maintainer

| Option            | Precision           | Notes                                                                                                                 |
|-------------------|---------------------|-----------------------------------------------------------------------------------------------------------------------|
| `DECIMAL(10,7)`   | about 1 cm          | Exact. Nominatim answers with seven decimals. MariaDB/MySQL and PostgreSQL return `"52.5200080"`, SQLite a float.     |
| `DECIMAL(9,6)`    | about 11 cm         | Exact, one digit less than the geocoder delivers, so a geocoded value is rounded on every write.                      |
| `DOUBLE`          | about 15 digits     | Not exact, no scale, so the same input can render differently per database. Equality with `0` is still safe.          |

Recommended: `DECIMAL(10,7)` for both columns, as the issue proposes. The
values are exact on every database, the scale matches the geocoder, and three
integer digits cover the longitude range. The eval and the wizard round to
seven decimals before writing, because SQLite stores whatever it is given and
would otherwise keep more decimals than the other databases. A display that
strips the padded zeros (`deevaluateFieldValue()`) keeps the backend field
showing `52.520008`, not `52.5200080`.

### 2. Decimal comma and unreadable values. Decision for the maintainer

Options:

- **(a) Accept a decimal comma.** The eval and the wizard read a value with
  exactly one comma and no point as a decimal number. Anything else that does
  not parse is refused by the eval and set to "no coordinate" by the wizard,
  which reports it.
- **(b) Refuse the comma.** The eval refuses it, the wizard treats `52,52` as
  unreadable.
- **(c) Convert in the wizard, refuse in the field.**

Recommended: (a). A coordinate is at most 180 in magnitude, so a comma in it
cannot be a thousands separator and the reading is unambiguous. Editors on a
German backend type it, and today it is the one input that the field accepts,
the map script drops and the partner object misreads. (b) makes editors and
sites pay for an ambiguity that does not exist. (c) keeps two rules for one
value.

The wizard changes no `geocode_status`. A partner whose coordinate becomes
"no coordinate" leaves the map and is named in the wizard output with uid,
language and the stored value, so an editor can enter it again or set the
status to `open` for the geocoder. Resetting the status in the wizard was
rejected: for a `manually` located partner the geocoder would replace the
editor's place with the one of the address.

### 3. Migration route. Decision for the maintainer

- **(A) New columns and a copy wizard.** `ext_tables.sql` declares two new
  nullable `DECIMAL` columns and drops the old ones. `extension:setup` adds the
  new columns, which is safe in any order. An upgrade wizard reads the old
  columns, parses each value under decision 2 and writes the new ones, for
  every row (translations, workspace versions and deleted rows included, as it
  is a copy of a column). The old columns are left to the database analyzer,
  which renames them to `zzz_deleted_*` and later drops them. The wizard reads
  the `zzz_deleted_` name when the analyzer renamed them first, and reports
  nothing to do when neither exists.
- **(B) Change the type in place.** Same column names, the analyzer converts.
- **(C) In place, with a wizard that converts and alters on its own.**

Recommended: (A). The probe above rules out (B): it never succeeds on
PostgreSQL, fails on MariaDB/MySQL in strict mode for the very values that need
a decision, and in non-strict mode silently turns `''` into a real `0`, so a
partner with one coordinate missing would be drawn on the equator or the prime
meridian. (C) cannot run before `extension:setup` has tried (B) and, in
non-strict mode, already corrupted the data. (A) needs no platform specific SQL
and keeps the old values readable until the maintainer removes them.

The cost of (A) is new column names. Recommended names: `geocode_lat` and
`geocode_lng`, matching the `data-lat`/`data-lng` attributes. The model keeps
its property names through the Extbase mapping
(`Configuration/Extbase/Persistence/Classes.php`), so `{partner.geocodeLatitude}`
in a template keeps working and only its type changes. The wizard also renames
the two keys in `l10n_state` of every translation, or a translation an editor
detached would be synchronized again on the next save of its default record.

### 4. One drawable rule from one table of cases

The rule: a latitude and a longitude are present, the latitude is within
-90..90 and the longitude within -180..180, and they are not both zero.

- Query: `geocodeLatitude` and `geocodeLongitude` not `NULL` (QOM `equals()`
  with `null` renders `IS NULL` on both cores), each within its range, and not
  both equal to `0`. The range also excludes a `NaN` that a raw write could
  store on PostgreSQL, which sorts above every number there.
- Model: `?float` properties defaulting to `null`, `isDrawable()` with the same
  three conditions, `isShownOnMap()` unchanged on top of it. The docblocks that
  describe the disagreements go.
- Script: the range check is added to the existing checks. It stays, because
  an overridden partial or a page template can hand it any markup.

The cases (both present, one missing, `0`/`0` in several spellings, a single
zero on each axis, each range boundary and one beyond it) live in one JSON
fixture of the extension's tests, read by the unit test of the model, the
functional test of the repository and the JavaScript test. Rejected: three
hand-written lists, which is how the three rules drifted apart in the first
place.

### 5. The backend field stays an input

TCA `type => input`, `nullable => true`, `max => 20` as today, and two eval
classes,
one per axis, registered in `ext_localconf.php`: `evaluateFieldValue()` trims,
parses under decision 2, checks the range, rounds under decision 1 and returns
`null` for an empty value, and sets `$set = false` for a value it refuses.
`deevaluateFieldValue()` strips padded zeros. Both share one stateless parser
that the wizard and the command use as well. Rejected: `type => number` with
`format => decimal`, which rounds to two decimals on both cores. Rejected:
`nullable` alone, since an editor who clears the field with the null checkbox
still ticked sends `''`, which PostgreSQL refuses for a numeric column.
Whether the DataHandler stores a `null` returned by a custom eval has to be
checked on both cores first. If it does not, a `processDatamap_preProcessFieldArray`
hook turns `''` into `null` for the two columns instead.

### 6. Geocoding and the synchronization wizard

`GeocodeCommand` writes the parsed values under the new column names. Its
DataHandler write passes the eval, so it is rounded like an editor's input.
`SynchronizePartnerTranslationsUpgradeWizard` names the new columns. Its string
comparison still holds, since both sides of a comparison come from the same
column type of the same database.

### Relation to the other issues

- **ACE-562** (Fixed, `main` `23f5b4607`): this change builds on it and
  replaces its string rule. It keeps what ACE-562 settled: absence is the
  criterion, a single zero is a real coordinate, the coordinates are
  synchronized with their translations, the map shows its empty state.
- **ACE-709** (Done, branch `2`): left as it is. Branch `2` keeps `VARCHAR(20)`
  and the documented asymmetry, there is no backport.
- **ACE-437** (Open, `[3.x][2.x]`): left to a change of its own. It is not a
  dependency in either direction: the drawable rule reads coordinates only,
  and the status decides the geocoding queue and `findGeoLocated()`. Bundling
  it would tie its backport to `2` to a breaking change of `main`. Both add a
  wizard and edit the same `CREATE TABLE pages` block, so the second to land
  rebases.

## Risks / Trade-offs

- [Project code reads `geocode_latitude`/`geocode_longitude` by name, in
  TypoScript, a data processor, an import or a search index] → the `Breaking`
  entry names the old and new columns and the query that finds partner pages
  with a value the wizard dropped.
- [The analyzer drops the old columns before the wizard ran] → the values are
  gone. The `Breaking` entry says to run the wizard before removing unused
  fields, the usual order of a TYPO3 upgrade.
- [`getGeocodeLatitude()` returns `null`] → a project calling it with arithmetic
  gets a type error. Named in the `Breaking` entry.
- [MySQL 8 was not probed] → it behaves as MariaDB for strict conversion, and
  the CI matrix runs every test on it. Route (A) issues no conversion statement.
- [Seed and database templates] → the SQLite copy of `pages` is rebuilt, which
  is where the schema migration gave up halfway before. Rebuild both templates
  from nothing, as `docs/development/environment.md` describes.

## Migration Plan

1. `composer update`, then `typo3 extension:setup`: adds the new columns.
2. `typo3 upgrade:run`: the copy wizard fills them and reports what it
   converted and what it dropped. The synchronization wizard does not depend
   on its order.
3. Check the report, then remove the unused columns in the database analyzer.

Rollback before step 3: the previous release reads the old columns, which the
update left untouched.
