## 1. Shared date primitives — `academic_base`

- [x] 1.1 Add `FGTCLB\AcademicBase\Date\DateDisplay`, `DateGranularity`,
      `DateCompletionEdge`, `DateCompletion` and `DateFieldSettings` as
      `#[Exclude]`d value objects with `__set_state()`.
- [x] 1.2 Add the stateless `LocalizedDateFormatter` and `DateValueParser`.
- [x] 1.3 Unit tests for all seven. The formatter is shown to fail by asserting
      `de-DE` output before the skeleton mapping exists; the parser by feeding
      it `2019-02-31`, which a naive `createFromFormat()` reads as 3 March.

## 2. The record — `academic_persons`

- [x] 2.1 `ext_tables.sql`: `year`, `year_start`, `year_end` become `date`,
      `date_start`, `date_end`, `date DEFAULT NULL`.
- [x] 2.2 `ProfileInformation`: `?\DateTime $date`, `$dateStart`, `$dateEnd`
      with their accessors.
- [x] 2.3 TCA: the three columns become `dbType: date` / `type: datetime` /
      `format: date` / `nullable: true`; the palette and the seven types follow.
- [x] 2.4 XLF labels renamed, `en` and `de`, on one line each.
- [x] 2.5 Extend `ProfileInformationRepositoryTest` and the model unit test to
      the new types; both are shown to fail against the old columns first.

## 3. The settings vocabulary — `academic_persons`

- [x] 3.1 `Validation` carries a `DateFieldSettings`; `ValidationNormalizer`
      reads the `date:` block of a field and the `dates:` map of a section.
- [x] 3.2 Row field and validator key `year` becomes `date`; the aliases `from`
      and `to` address `dateStart` and `dateEnd`; `LegacySettingsMigrator`
      follows.
- [x] 3.3 The shipped `Settings.yaml` declares the seven sections' dates and
      the two contract dates, with the header comment updated.
- [x] 3.4 Unit tests of the factory, the normalizer and the migrator extended;
      each new assertion shown to fail against the old vocabulary.

## 4. The public profile — `academic_persons`

- [x] 4.1 A ViewHelper formats a timeline date for the site locale and the
      section's display configuration, resolved from the record type.
- [x] 4.2 `TimelineItem.html` renders through it; its comment is rewritten.
- [x] 4.3 Functional test: the same entry rendered on two site languages, and
      a section that publishes the year alone.

## 5. The editor — `academic_persons_edit`

- [x] 5.1 The endpoint parses, completes, validates and answers a date by the
      field's granularity, and formats a display value by its display
      configuration. The `dd.mm.yyyy` placeholder constant goes.
- [x] 5.2 `Control.html`, `Prototypes.html` and the document row partials
      render the native control and carry the granularity; `YearValue.html`
      becomes `DateValue.html`.
- [x] 5.3 `field-clone.ts` and `documents.ts` map the granularity to the input
      type and carry the renamed row values; the built artifacts are rebuilt.
- [x] 5.4 The editor shows the "only the year is published" hint where display
      is narrower than input.
- [x] 5.5 The contract dates `validFrom` / `validTo` become native controls.
- [x] 5.6 Functional and JavaScript tests rewritten: the type assertion that
      pins `text` today, the both-formats round trip, the impossible date, the
      required empty date, the completion rules.

## 6. The migration starting point — `academic_persons`

- [x] 6.1 `AbstractMigrateProfileInformationDatesUpgradeWizard`, `abstract
      readonly`, unregistered, with the completion edges as constructor
      arguments.
- [x] 6.2 A functional test registers a subclass of it and proves both edges,
      the "only where empty" guard and the repeatability.

## 7. `academic_jobs`

- [x] 7.1 `Job/Forms/DateTime.html` prefills `Y-m-d` and drops
      `data-render="datepicker"`.
- [x] 7.2 `Item.html` and `Information.html` render a job date for the site
      locale.
- [x] 7.3 Functional test: a job carrying a date is offered for editing with
      that date, shown to fail against the `d.m.Y` prefill.

## 8. Seed and instances

- [x] 8.1 `Scenario.yaml`: the 21 timeline records and their language variants
      carry dates. `ScenarioLegacy.yaml` is not affected.
- [x] 8.2 The ten functional CSV fixtures carry dates.
- [x] 8.3 `seedManifest` rewritten for core 13 and core 14, both committed.
- [x] 8.4 `core-13/` and `core-14/` rebuilt from nothing and their SQLite
      templates committed — a schema change cannot be migrated into an
      existing instance.

## 9. Documentation

- [x] 9.1 `academic-persons`: `Breaking-*.rst` for the columns and the
      vocabulary, `Important-*.rst` for the migration starting point with a
      developer/integrator section, `Feature-*.rst` for the display
      configuration. `Important-ProfileInformationYearRange.rst` is removed —
      it describes columns 3.0.0 will not have.
- [x] 9.2 `academic-persons-edit`: `Breaking-*.rst` for the native control and
      the two contract dates, `Feature-*.rst` for the input granularity.
- [x] 9.3 `academic-jobs`: `Important-*.rst` for the repaired prefill and the
      localised display.
- [x] 9.4 `academic-base`: `Feature-*.rst` for the shared primitives.
- [x] 9.5 The three manuals: the persons `Configuration/Sections` and
      `Validations` chapters, the edit `ProfileEditing` and
      `Configuration/Settings` chapters, the persons `Upgrade` chapter.
- [x] 9.6 `docs/architecture/validation-settings.md`,
      `docs/architecture/profile-editing-contract.md` — the deferral is
      resolved, and both say so — plus a new `docs/architecture/dates.md`
      linked from `docs/architecture/Index.md`.

## 10. Definition of done

- [x] 10.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for core 13 and core 14 each.
- [x] 10.2 `lintTypescript`, `typecheckJs`, `testJs`, `checkJsBuildClean` and
      `lintMarkdown -n` green.
- [x] 10.3 `checkRstRenderingAll` green.
- [x] 10.4 Every new behaviour has a test, and each was shown to fail without
      the change.
- [x] 10.5 Commit message in TYPO3 Core format with the verified `ACE-552`
      reference; the change archived as the last commit of the pull request.
