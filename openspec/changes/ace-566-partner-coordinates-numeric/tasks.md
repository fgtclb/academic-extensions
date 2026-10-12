## 1. Decisions and premises

- [ ] 1.1 Confirm the three decisions of `design.md` with the maintainer: column
  type and precision (1), decimal comma and unreadable values (2), migration
  route and column names (3). Record the answers in `design.md`. When one
  differs from the recommendation, revise `design.md`, the spec and these tasks
  with `/opsx:update` before any code. Done when `design.md` names the decided
  options.
- [ ] 1.2 Re-check every statement of `design.md` *Context* on `main` HEAD for
  TYPO3 v13 and v14: the file lines, the two drawable rules and the script
  rule, the DataHandler rounding, `extension:setup` applying `change`, the
  DataMapper skipping `NULL`, and the fixture count. Done when each is
  confirmed or corrected in `design.md`.
- [ ] 1.3 Check on both cores whether the DataHandler stores a `null` that a
  custom eval returns for an input field with `nullable => true`, with a
  throwaway functional test. Done when `design.md` decision 5 names the
  mechanism that turns an emptied field into `NULL`.

## 2. Tests first, shown red

- [ ] 2.1 One JSON fixture with the drawable cases of `design.md` decision 4,
  below `Tests/Fixtures/` of the extension. Done when the file lists every
  case with its expected result.
- [ ] 2.2 Unit test of `Partner::isDrawable()` over the fixture, with
  `?float` coordinates. Show it red on the unchanged model with the case
  "latitude set, longitude missing" (reported drawable today).
- [ ] 2.3 Functional test of the map query over the fixture, on SQLite and
  PostgreSQL. Show it red on the unchanged repository with the case "both
  coordinates `0.0`" (let through today).
- [ ] 2.4 Functional plugin test: a map whose only partner has `0.0`/`0.0`
  renders the empty state. Show it red on the unchanged repository.
- [ ] 2.5 JavaScript test of the map script over the fixture. Show it red on
  the unchanged script with a latitude of `91`.
- [ ] 2.6 Functional DataHandler tests of both fields: decimal point, decimal
  comma, empty (stored as `NULL`, on PostgreSQL too), out of range, text, more
  than seven decimals. Show each red on the unchanged TCA.
- [ ] 2.7 Functional tests of the copy wizard, with the old columns created by
  a fixture extension below `Tests/Functional/Fixtures/Extensions/`: a point
  value, a comma value, `''`, `NULL`, an unreadable value with its report
  line, a value out of range, a detached translation keeping its `l10n_state`,
  old columns renamed to `zzz_deleted_*`, no old columns at all. Show them red
  before the wizard exists.

## 3. Schema, TCA and model

- [ ] 3.1 `ext_tables.sql`: the new nullable columns under the decided type,
  the old columns removed. `PagesColumnDefaultsTest` stays green on SQLite and
  PostgreSQL.
- [ ] 3.2 TCA of both fields under decision 5, the `geocode` palette and the
  labels on the new names, the eval classes registered in `ext_localconf.php`
  and sharing one stateless parser. Tests of 2.6 green.
- [ ] 3.3 Extbase mapping of `geocodeLatitude`/`geocodeLongitude` to the new
  columns, `?float` properties and accessors, `isDrawable()` under decision 4
  with the disagreement docblock removed. Tests of 2.2 green.

## 4. Query, script and command

- [ ] 4.1 `PartnerRepository`: the numeric constraint for `findByDemand()` and
  `findGeoLocated()`, docblock without the asymmetry. Tests of 2.3 and 2.4
  green on SQLite, PostgreSQL and MariaDB.
- [ ] 4.2 `map.ts`: the range check, comments without the asymmetry, built
  file regenerated. Tests of 2.5 green, `checkJsBuildClean` green.
- [ ] 4.3 `GeocodeCommand`: writes the new columns through the DataHandler.
  `GeocodeCommandTest` reads the values as numbers, not as strings, and stays
  green on SQLite and PostgreSQL.
- [ ] 4.4 `SynchronizePartnerTranslationsUpgradeWizard` on the new columns, its
  tests and `PartnerCoordinateSynchronizationTest` green.

## 5. Upgrade wizard

- [ ] 5.1 The copy wizard under decisions 2 and 3, with a title and a
  description, reporting every converted and every dropped value with uid,
  language and stored text. Tests of 2.7 green on SQLite, PostgreSQL and
  MariaDB. `UpgradeWizardDescriptionTest` green.

## 6. Fixtures, seed and templates

- [ ] 6.1 Rewrite the 28 functional fixtures of the extension: the column
  names, and `""` as `\NULL`. Done when no fixture names an old column and the
  whole extension suite is green on PostgreSQL.
- [ ] 6.2 `Scenario.yaml` of `packages-dev/dev-site` on the new names, the
  legacy scenario regenerated, `--check` clean, `seedManifest` run for v13 and
  v14 and the manifests committed.
- [ ] 6.3 Rebuild both SQLite templates from nothing as
  `docs/development/environment.md` describes, so they carry the new columns
  and mark the new wizard done. `TemplateUpgradeWizardsTest` and the seed
  verification tests green.

## 7. Documentation

- [ ] 7.1 `Documentation/Changelog/3.0/Breaking-PartnerCoordinatesAreNumeric.rst`
  from `Build/Documentation/Templates/`: the new columns and their type, the
  getters returning `null`, the wizard and its report, removing the old
  columns only after the wizard ran, a query that lists partner pages whose
  value the wizard dropped. Rendered without a definition list.
- [ ] 7.2 Manual, `Configuration/Index.rst` "Which partners the map draws": the
  one rule, and what the coordinate fields accept. Developers chapter: the
  model types.
- [ ] 7.3 `docs/architecture/database-queries.md`: a value compared as a
  number is stored in a numeric column, and a type change goes through a new
  column and a copy wizard, with the probe results as the reason.
  `docs/development/frontend-assets.md` and
  `docs/architecture/list-plugin-events.md` updated where they describe the
  coordinates.

## 8. Definition of done

- [ ] 8.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit`, and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`), all green.
- [ ] 8.2 After `composerUpdate` for TYPO3 v14: the same suites, all green.
- [ ] 8.3 `testJs`, `lintTypescript`, `checkJsBuildClean`, `lintMarkdown -n`
  and `checkRstRenderingAll` green.
- [ ] 8.4 `docs/` and the extension's `Documentation/` updated, `README.md` and
  `CONTRIBUTING.md` still only summarize and link.
- [ ] 8.5 Every test of group 2 was shown red as stated there, and the pull
  request names how.
- [ ] 8.6 Commit as `[!!!][TASK] ACE-566: Store coordinates as numbers`
  in TYPO3 Core format with `Resolves: ACE-566`, no attribution of any kind.
- [ ] 8.7 Archive the change as the last commit of the same pull request.
