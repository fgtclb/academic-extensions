# Validation settings

One YAML file describes the profile of `academic_persons`: which fields exist,
in which order, with which control, and whether each is required, read only or
disabled. It is the **single source of truth for both editing contexts**: the
TYPO3 backend FormEngine and the editing frontend of `EXT:academic_persons_edit`.

This page describes the `academic_persons` graph and the shared classes in
`academic_base` it is built on since ACE-501. **`academic_jobs` reads its own,
much smaller file through the same classes** since ACE-508, with the same flags
and the same merge, see
[The jobs settings](#the-jobs-settings-in-academic_jobs) at the end.

That is why the file ships in **`academic_persons`** and not in the edit
extension. `academic_persons` owns the domain models and their TCA, so it must
own the configuration that drives the backend forms; the frontend edit extension
is the *second* consumer of the same data, not its owner. An installation that
does not have `academic_persons_edit` installed still gets the backend half.

## The file

`packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
is the only place the graph is defined — there is no second file in the edit
extension, which `SettingsSourceTest` pins. Since ACE-503 it
has four top-level maps for the profile, since ACE-720 a fifth for the
frontend user synchronisation, and since ACE-758 a sixth for the fields a
synchronisation owns:

| Map                | Holds                                                                                                                                                                       |
|--------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `profile`          | The public detail layout (`structure`, `details`) **and** every editable profile property: `section`, `fieldType`, `renderType`, `validators`, `helptext`, `characterLimit` |
| `special`          | The components that are not one property: the composed `title`, the `image`, the `skipSync` and the `hidden` switch                                                         |
| `contracts`        | `fields` of the contract form, and `contactSections` — `physicalAddresses`, `emailAddresses`, `phoneNumbers` — with their own `fields`                                      |
| `documentSections` | The sortable lists: the seven profile information types and `contracts`, each with `label`, `type`, `fieldName`, `rowFields`, `actions`, `validators`, `helptext`           |
| `frontendUserSync` | Which `fe_users` column feeds which profile and contract property, see [Frontend-user contact import](frontend-user-contact-import.md)                                      |
| `managedFields`    | Per record type, the fields that are read-only on the records a synchronisation wrote, see [Managed fields](#managed-fields-a-lock-per-record)                              |

The shipped `profile` map is the shortest example of a field, and the one most
often met:

```yaml
profile:
  firstName:
    section: information
    fieldType: input
    renderType: text
    validators:
      - readonly
      - disabled
```

The previous shape — a `profileInformationsTypes` map generating the seven
inline columns of the profile TCA, and a `validations` map with one flag list
per record type — is not read as such any more. The seven relations are now
declared by `tx_academicpersons_domain_model_profile.php` itself (an override
that dropped an entry used to lose a backend column), and the flags sit on the
field they apply to. The Breaking entry
`Breaking-SectionBasedAcademicPersonsSettings.rst` of `academic-persons`
documents the migration.

### The legacy overlay (ACE-504, transitional)

A site package that still ships the old shape is not ignored:
`AcademicPersonsSettingsFactory::get()` hands the merged array to
`LegacySettingsMigrator::migrate()` before `normalize()` builds the graph. The
migrator maps `validations.<set>.<property>` onto the `validators` of the field
with that key or `propertyName` in the set's target map, and the
`profileInformation` set onto the `validators` map of every timeline section;
`profileInformationsTypes.<id>` refines the `label` of the document section
with that key. The rule is *overlay, not replace*: a legacy set
decides the six flags the old shape knew (`required`, `readonly`, `disabled`,
`email`, `number` and, since 2.4, `frontendreadonly`) for every field of its
target — an unlisted field loses them, exactly as it was unconfigured before,
which is what made the 2.x manual's example unlock the name fields by not
listing them — and the flags it could not express (`url`, `date`, `tel`,
`textarea`, `html`) stay as shipped. Two things
are lossy and end up in the migration's `notes`: an eighth type under
`profileInformationsTypes` is reported, not created — it would need a profile
relation and a TCA column — and a `type` or `fieldName` under such a key is
reported, not applied. Since ACE-503 the seven profile relations, and the
record type each of them selects, are declared by
`Configuration/TCA/tx_academicpersons_domain_model_profile.php` rather than
generated from the settings, so applying a legacy rename would move the editing
frontend alone and leave the backend column and the editor addressing different
record types; `migrateProfileInformationTypes()` keeps the values that match
the TCA. The legacy keys never reach `raw`.

`type` and `fieldName` are deliberately **not** overlaid, and that is the second
lossy case. Since ACE-503 the seven profile relations, and the record type each
of them selects, are declared by
`Configuration/TCA/tx_academicpersons_domain_model_profile.php` rather than
generated from the settings, so applying a legacy rename would move the editing
frontend alone and leave the backend column and the editor addressing different
record types. `migrateProfileInformationTypes()` therefore keeps the values that
match the TCA and reports the divergent one per key in `notes`, next to the
eighth-type note. A `type` or `fieldName` written directly
into the new `documentSections` map *is* read — the settings graph has no TCA to
compare it against, so that divergence is the integrator's, and the manual says
so in `Configuration/Sections/Index.rst`,
`Configuration/Validations/Index.rst` and the `Upgrade/` chapter of
`academic-persons`.

Attribution is per package: the factory walks
`SettingsFileLoader::loadPackageArrays()` — the per-package view the loader
gained for this, keyed by package key in loading order — and the migrator logs
**one `LogLevel::WARNING` per package and legacy key** through
`LoggerAwareInterface`, on the cache miss that builds the graph. It is never an
`E_USER_DEPRECATED`: both phpunit suites run with `failOnDeprecation`, so a
fixture shipping the old shape would turn every functional test red. A
contract field whose property 3.0 removed, `contracts.fields.publish` of a
copied map (ACE-775), is left out of the graph by the factory with a warning
of the same kind, rather than stopping the TCA build with an exception. The
console command `academic:persons:settings:migrate` (`MigrateSettingsCommand`)
prints the migrated four maps per legacy package and exits with 1 when one
exists; it deliberately has no `--write` — the file belongs to a version
controlled site package. `Report\LegacySettingsStatus` names the packages in
EXT:reports; it is registered by a compiler pass in `Services.php` only when
that extension is active, because `ExtensionManagementUtility::isLoaded()` is
not usable while the container is built. Its test asks `StatusRegistry`
directly: the class aggregating the providers differs between the core
versions (`Report\Status\Status` on v13, `Service\StatusService` on v14),
while the registry, the interface and `Status` itself are identical. The
legacy layer — the migrator, the overlay call, and the legacy halves of the
command and the status — is removed in 4.0; the `--delta` mode and the
override entries of the status stay, see
[Finding what an override changes](#finding-what-an-override-changes).

`LegacySettingsMigratorTest` covers the mapping against the shipped file,
`LegacySettingsOverlayTest` pins that a legacy `readonly` state reaches the TCA
`readOnly`, and `MigrateSettingsCommandTest` that the printed YAML parses to the
array the graph was built from.

The recognised flags, all matched case-insensitively
(`ValidationNormalizer::normalizeValidation()` in `academic_base`):

| Flag               | Effect                                                                                    |
|--------------------|-------------------------------------------------------------------------------------------|
| `required`         | Adds `NotEmptyValidator`, and `required` + `minitems` to the TCA config                   |
| `disabled`         | Field must not be edited. Forces `readOnly`, and cancels `required`                       |
| `readonly`         | Field is shown but not writable. Cancels `required`                                       |
| `frontendreadonly` | Like `readonly` for the frontend, and TCA untouched: the backend keeps `required` as well |
| `email`            | Adds `EmailAddressValidator`, TCA `type` and input type `email`                           |
| `number`           | TCA `type` and input type `number` — no validator                                         |
| `url`              | Adds `UrlValidator` and input type `url` — TCA untouched                                  |
| `date`             | Input type `date` only — the TCA column keeps its own `datetime` config                   |
| `tel`              | Input type `tel` only — no format enforced, TCA untouched                                 |
| `textarea`         | Input type `textarea` only — TCA untouched                                                |
| `html`             | Input type `textarea` and `Validation::isRichText()` — TCA untouched                      |

An unknown flag is kept in `Validation::$flags` and has no other effect.

"Input type" is what the *settings graph* calls a field, not what the browser
gets. The profile editor maps `date` to a plain text control, because the
native `<input type="date">` follows the locale of the browser rather than the
one of the site and a date picker is deliberately not shipped yet — see
[The two contract dates are a text control](profile-editing-contract.md#the-two-contract-dates-are-a-text-control).
Nothing about that reaches this normalizer: the flag stays `date`, and so does
the input type it derives.

## The shared classes in `academic_base`

Everything that has no persons knowledge lives in
`packages/fgtclb/academic-base/Classes/Settings/`, namespace
`FGTCLB\AcademicBase\Settings`, and is `@internal`:

| Class                                    | Role                                                                                                                           |
|------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| `Validation`, `ValidationSet`            | The value objects. `#[Exclude]`d from the container, `__set_state()` for the cache                                             |
| `ValidationNormalizer`                   | Flag list → `Validation`; `normalizeValidationSets()` for a whole map of flat sets                                             |
| `SettingsFileLoader`                     | The package walk, the recursive merge and the `cache.core` round trip; `loadPackageArrays()` is the per-package view           |
| `TcaValidationMerger`                    | `toTcaTableConfig()` builds the `columns.<field>.config` fragment, `merge()` applies it to a table                             |
| `Exception\UnknownValidatorException`    | Raised by a validation engine for a class name that is not an Extbase validator                                                |
| `Exception\UnsuitableValidatorException` | Raised by a validator handed a subject it is not built for                                                                     |

The ViewHelper `FGTCLB\AcademicBase\ViewHelpers\ValidationEnsureViewHelper`
sits next to them, declared in a template as
`xmlns:p="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`.

Its callers are the partials `Job/Forms/FieldWrapper.html` and
`Job/Forms/Textfield.html` of `academic_jobs` (ACE-508). Its earlier callers,
the `Partials/Profile/Forms/` templates of `academic_persons_edit`, went with the
editing rewrite of 3.0. The class is `@internal`, but its tag name and arguments
are public API from the moment a shipped template calls it: the extension points
page of `academic_base` makes the ViewHelpers a template calls part of what a
site package may override, and the Breaking entry of ACE-508 tells a site
package to call it in place of the removed jobs ViewHelpers.

`Validation` carries, beyond the flags' effects, the normalised `flags` list
itself and a `characterLimit` (ACE-503). `normalizeValidation()` takes the
optional `fieldName` (the column, when it differs from the underscored
identifier), `renderType` (the frontend control the flags start from — a
`select` is a `select` input type without any flag saying so) and
`characterLimit`. None of the three reach the TCA fragment.

The validation *engine* — `AbstractFormDataValidator::processValidationSet()`
in `academic_persons_edit` — deliberately did not move: it is neutral in
substance but not yet in its types, and it is a second step.

No class aliases exist for the old `FGTCLB\AcademicPersons\Settings\Validation*`
and `FGTCLB\AcademicPersonsEdit\Exception\*ValidatorException` names. They were
`@internal`, and nothing outside the two persons extensions referenced them.

## The persons graph

`AcademicPersonsSettingsFactory::normalize()` turns the merged array into
`AcademicPersonsSettings`, a graph of value objects under
`packages/fgtclb/academic-persons/Classes/Settings/`, all `#[Exclude]`d and all
with a `__set_state()`:

| Object                                            | Built from                                               | Carries                                                                                                                                                                                                    |
|---------------------------------------------------|----------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `PublicProfileSettings`                           | `profile.structure`, `profile.details`                   | The layout columns and the per-element property lists, maps and label references. A contract block adds `contracts` and `onlyValid`, and `position` its ordered `fields`                                   |
| `ProfileSection` → `ProfileField`                 | every other `profile` entry, grouped by `section`        | `propertyName`, `fieldName`, `fieldType`, `renderType`, `Validation`, `position`, `helptext`, `custom` for a project field                                                                                 |
| `SpecialField`                                    | `special`                                                | `type`, `renderType`, composed `fieldIdentifiers`, renderer `settings` (the image's `ratio`); `hasDirectProfileProperty()` is true for `skipSync` and `hidden`                                             |
| `ContractField`                                   | `contracts.fields`                                       | as a profile field, plus `optionSource`, `helptext`, `autocomplete`                                                                                                                                        |
| `ContractContactSection` → `ContractContactField` | `contracts.contactSections`                              | as a profile field, plus `autocomplete` and `helptext`; the section carries the `ValidationSet`                                                                                                            |
| `DocumentSection`                                 | `documentSections`, `contracts` completing its own entry | `label`, `type`, `fieldName`, `readOnly`, `rowFields`, `actions`, `helptexts` (keyed like `validators`), `ValidationSet`; `allowsAction()`, `getAllowedActions()`, `allowsCreate()`, `allowsDragSorting()` |
| `ManagedFieldsSettings`                           | `managedFields`                                          | per record type the managed property and column, resolved through the fields above, plus `problems` and `assertValid()`, see [Managed fields](#managed-fields-a-lock-per-record)                           |

Four details of the normalisation are easy to get wrong:

- **A field's key is not always its property.** The contact sections need
  unique keys across three record types that all have a `type` column, so
  `emailAddressType` declares `propertyName: type`, and `emailAddress` declares
  `propertyName: email`. The validation sets are keyed by **property name**,
  which is what `ObjectAccess` and the ViewHelper resolve; the `fields` maps of
  the sections are keyed by the settings key. `getProfileField()`,
  `getContractField()` and `getContractContactField()` accept either.
- **Document validators speak the editor's language.** `from`, `to` and
  `description` are aliases of the `yearStart`, `yearEnd` and `bodytext`
  properties (`DOCUMENT_PROPERTY_ALIASES`); `year` is the `year` property and
  column. A document field accepts the plain flag list or a map with
  `validators`, `<flag>: true` entries and an `editor` block — `type: ckeditor`
  implies `html` and carries the `limit`, `type: textarea` implies `textarea`.
- **The `contracts` document section is two lines.** It declares `type:
  contracts` and takes label, relation, row fields and actions from the
  top-level `contracts` map (`array_replace`), and its validation set is the
  contract fields'. `DocumentSection::isContractSection()` is what the TCA
  fragment builder branches on.
- **A project field names its column itself.** A profile entry with
  `custom: true` is a column a site package added (ACE-764). Its property name
  is its key, whatever `propertyName` says, and its `fieldName` is taken as
  written, empty when the entry names none. The entry is kept either way, so
  the column check below can name the mistake. The factory reads no TCA.

`fieldType` and `renderType` are **frontend metadata only**. The normaliser
never derives a TCA `type` from them; the six TCA files declare their column
types and the settings listener overlays `readOnly` and `required` (plus the
`email` and `number` types, as before). `SettingsValidationOverridesTest` pins
that the `website` column stays `input` under a `combinedLink` render type and
that the rich text columns keep their `enableRichtext`.

Nothing is logged when an entry is dropped: a profile field without a
`section`, `fieldType` or `renderType`, a document section without `label`,
`type` or `fieldName`, a `special` entry whose `type` is not `special`. The
`isValid()` of each value object is the gate, and `SectionSettingsTest` pins
what each accepts.

**Everything a consumer needs is on the graph** — every help text, the image's
crop ratio, the row fields and actions — so nothing reads the raw array to
render a form. `raw` keeps the merged array on the settings object, after the
legacy overlay and therefore without the legacy keys: ACE-504 reports and
prints a site package override from it, and `MigrateSettingsCommandTest`
compares it with what the command prints. A consumer reaching for `raw[...]`
for anything else is a value object missing a property, and the property is
the fix.

## Normalisation

`ValidationNormalizer::normalizeValidation()` turns each flag list into one
`Validation` value object:

```php
$readOnly = in_array('readonly', $flags, true);
$disabled = in_array('disabled', $flags, true);
$frontendReadOnly = in_array('frontendreadonly', $flags, true);
$backendRequired = !$disabled && !$readOnly && in_array('required', $flags, true);
$required = $backendRequired && !$frontendReadOnly;
...
if ($disabled) {
    // @todo Investigate how to handle that for the backend / TCA FormEngine, therefore switch to
    //       readOnly for now
    $readOnly = true;
}
$tcaConfig['readOnly'] = $readOnly;
...
if ($frontendReadOnly) {
    $readOnly = true;
}
```

Three consequences worth knowing:

- **`disabled` implies `readOnly`.** FormEngine has no per-field notion matching
  the HTML `disabled` attribute, so `disabled` is expressed to the backend as
  `readOnly`. The `@todo` records that this mapping is provisional, not that the
  backend coupling is unintended. For anything the normaliser produces,
  `disabled` therefore never occurs without `readOnly`.
- **`disabled` and `readonly` cancel `required`.** A field the user cannot edit
  cannot be demanded of them, so no validator is generated. This is why the
  three shipped name fields produce no validators at all — all three are
  `readonly` and `disabled`.
- **`frontendreadonly` is set after the TCA fragment.** `Validation::$readOnly`
  and `$required` are what the frontend reads, `$tcaConfig` is what the backend
  reads, and the flag changes only the first two. A `required` beside it still
  gives the TCA `required` and `minitems`, because a backend editor can
  correct the value an owner cannot. `readonly` or `disabled` beside it still
  lock the TCA column. Rejected: making `readonly` itself frontend-only, which
  would unlock the backend of every installation that relies on the shared
  lock. A record an owner creates in the frontend is therefore stored without
  such a field, and the record editor asks for it the next time the record is
  saved there.

`Validation::$fieldName` is the property name converted with
`GeneralUtility::camelCaseToLowerCaseUnderscored()`, i.e. the database column,
unless the settings entry names a `fieldName`: `firstName` becomes
`first_name`, `emailAddress` with `propertyName: email` becomes `email`. That
is what lets one entry address the Extbase property and the TCA column.

`AcademicPersonsSettings` answers validation questions per section, and never
falls back from one to another:

| Accessor                                                    | Returns                                                                                |
|-------------------------------------------------------------|----------------------------------------------------------------------------------------|
| `getProfileValidationSet(?$section)`                        | One section's set, or all sections folded (a later section wins per property)          |
| `getProfileUpdateValidationSet()`                           | All sections plus the direct special fields (`skipSync`, `hidden`)                     |
| `getProfileValidationSetForFields($ids, $section)`          | The named fields of one section, keyed by property                                     |
| `getContractContactValidationSet($section)`, `…ForFields()` | One contact section's set                                                              |
| `getDocumentValidationSet($id)`                             | One document section's validation set, by settings key                                 |
| `getDocumentSectionByType($type)`                           | One document section, by the record type its rows carry                                |
| `getDocumentValidationTcaTypesConfig()`                     | `types.<type>.columnsOverrides` for the profile information table, contracts excluded  |

Every one of them returns an empty `ValidationSet` carrying the requested
identifier for an unknown section.

## Consumer 1 — the TYPO3 backend FormEngine

`ApplySettingsToTca` of `academic_persons` applies the graph to the TCA of the
six person tables, as a listener of `AfterTcaCompilationEvent` with the
identifier `academic-persons/apply-settings-to-tca`. For five tables it calls
`TcaValidationMerger::merge($tableTca, $validationSet)`, which merges a
`columns.<field>.config` fragment built from each `Validation::$tcaConfig`. The
sixth gets a `types` fragment:

| Table                                                 | Section                                    | Merge                                                                                |
|-------------------------------------------------------|--------------------------------------------|--------------------------------------------------------------------------------------|
| `tx_academicpersons_domain_model_profile`             | `profile` + `special.skipSync`             | `merge($tca, <the update set without the disabled column>)`                          |
| `tx_academicpersons_domain_model_contract`            | `contracts.fields`                         | `merge($tca, $settings->getDocumentValidationSet('contracts'))`                      |
| `tx_academicpersons_domain_model_email`               | `contracts.contactSections.emailAddresses` | `merge($tca, $settings->getContractContactValidationSet('emailAddresses'))`          |
| `tx_academicpersons_domain_model_phone_number`        | `…phoneNumbers`                            | `merge($tca, $settings->getContractContactValidationSet('phoneNumbers'))`            |
| `tx_academicpersons_domain_model_address`             | `…physicalAddresses`                       | `merge($tca, $settings->getContractContactValidationSet('physicalAddresses'))`       |
| `tx_academicpersons_domain_model_profile_information` | the timeline `documentSections`            | `mergeRecursiveWithOverrule($tca, $settings->getDocumentValidationTcaTypesConfig())` |

The profile information table is one table with a `type` column shared by the
seven timeline types, so a section's flags land in the `columnsOverrides` of
its record type and never on the column: a required title of publications does
not make the title of a lecture required. `ProfileInformationTcaTest` and
`EditSettingsIsolationTest` pin both halves — the override reaches the type,
the `range` of the year columns is untouched.

So marking a field `disabled` or `readonly` in the YAML makes it read only in the
backend record editor as well — by design, and for every backend user.
`frontendreadonly` is the lock that stays out of the TCA, for a field that
owners must not change and backend editors must be able to correct.

There is one exception. `special.hidden` is the owner's visibility switch of the
profile editor and writes the table's `disabled` enable column. Its flags decide
whether *owners* may show or hide a profile, and an installation sets them
exactly when editors are meant to decide instead. The listener therefore leaves
the validation of that column out of the merge, and the backend checkbox stays
writable whatever the YAML says.

### Project fields and the column check

A project field is merged like any other field once its column may be used, so a
`required` project field is required in the backend form as well.
`ProjectProfileFieldCheck` decides that, for the listener here and for the
editor, see
[Form data transformation](form-data-transformation.md#project-fields). It
refuses a column in this order: an entry without a `fieldName`, a key that is a property of the
shipped `Profile` model, a column the TCA does not have, a system column (`uid`,
`pid`, the `t3ver_*` columns and every column the `ctrl` section names for the
core), a column of a `Profile` property, a TCA type other than `input`, `text`,
`email`, `link`, `number` and `check`, the renderers `select` and
`combinedLink`, a renderer that does not fit the type (`checkbox` takes a
`check` column and nothing else does, `ckeditor` takes a `text` column), an
`email` or `link` column without the `email` or `url` validator, and a `link`
column whose `allowedTypes` leave out `url`. The DataHandler checks those two
types itself, empties an invalid value and reports an error, which would come
after the Extbase write of the same request. A project field with the `url`
validator also refuses every value that is not an `http` or `https` address,
because the validator lets a `t3://` link through, which the DataHandler
resolves and may refuse. The shipped model is the class itself, not an XCLASS of
it, so a project that maps its column in its model XCLASS can declare it.

A refused column gets nothing of the settings, and the listener raises an
`E_USER_DEPRECATED` notice naming the field, the column and the reason. Not an
exception, because a typo would take down the backend, the frontend and the
install tool with the TCA, until the cache is flushed from the command line.
Not a log entry alone, because a typo would go unnoticed. The deprecation level
is the one a TYPO3 installation routes to a log of its own and that the test
suites of this repository and of most projects turn into a failure. It is not a
real deprecation, and the message says what is wrong. The compiled TCA is
cached, so the notice comes once per cache build.

A regular field whose column the TCA does not have stays silently out of the
merge. Only a declared project field raises the notice.

The tests assert the notice by collecting it with an error handler of their own
around the listener call. PHPUnit 11.5 counts a deprecation it was told to
expect as a deprecation of the test all the same, unless the whole test carries
`#[IgnoreDeprecations]`, which would hide an unexpected one as well. A notice
raised while the test instance compiles its TCA cannot be asserted at all: the
deprecations of `setUp()` are dropped before the test runs, and still fail the
run. The editor's own check is therefore tested with a fixture listener ordered
after the settings that removes the column, which the settings listener cannot
see.

### Why a listener after the overrides

Until ACE-763 the six TCA files merged the graph themselves, at the end of
`Configuration/TCA/`, and carried a `@todo` that the call belonged somewhere
else. Two things were wrong with that place:

- **Every `Configuration/TCA/Overrides` file ran after the merge.** A site
  package that replaced a column wholesale, to change its label or its rich
  text configuration, dropped `required` and `readOnly` of that column without
  a word. One analysed project replaces five profile columns that way.
- **The graph was built while the TCA was incomplete.** A column a project adds
  in its overrides did not exist yet, so nothing that reads the TCA could check
  a settings entry against it. Project columns editable in the frontend editor
  need exactly that check.

`AfterTcaCompilationEvent` is the one place after all overrides, after the TCA
migration and preparation, and before the compiled TCA is cached. The TCA
cache and the `TcaSchema` cache hold the merged result: `BootCompletedEvent`
would come after both are written, and `BeforeTcaOverridesEvent` comes before
any project column exists.

Consequences, each pinned by `SettingsAfterTcaOverridesTest`:

- **The settings win over a TCA override of the keys they set.** Every fragment
  writes `readOnly` and `required`, also when they are false, so a site package
  that sets either on a configured column in its overrides loses it. It sets
  them in `Settings.yaml` instead, or orders an `AfterTcaCompilationEvent`
  listener of its own after `academic-persons/apply-settings-to-tca`.
- **A settings field whose column the TCA does not have adds nothing.** The
  merge in the TCA files created a `columns.<field>.config` fragment without a
  `type`, and the TCA migration then stopped the whole TCA build with
  `Missing "type" in TCA of field`.
- **The listener adds the `email[subst]` soft reference** to every `email`
  column of the five tables with flags on their columns. The core adds it
  while it prepares the TCA, which is before the event, and the e-mail column
  of the contact records only becomes an `email` column through the settings.
- **It is ordered after `content-blocks-tca`**, the TCA listener of
  EXT:content_blocks. That listener runs in `BeforeTcaOverridesEvent` in
  content_blocks 1.6.5 (TYPO3 v13) and 2.4.10 (TYPO3 v14) and so is earlier
  anyway. Should it move to this event, the settings still come last. An
  `after` that names no listener of the event is dropped by the core's
  dependency ordering, so the ordering needs no dependency on that extension.
- **The cached state is what counts.** The test reads the `tca_base` and the
  `TcaSchema` entries of the `core` cache the bootstrap wrote, not only
  `$GLOBALS['TCA']`.

The install tool builds the TCA with the listeners of extensions, through the
same container as a request. Its TCA checks do not: the checks for TCA
migrations and for TCA in `ext_tables.php` use
`TcaFactory::createNotMigrated()`, which stops after the overrides and
dispatches no `AfterTcaCompilationEvent`.
They used to see the settings flags and now do not, which changes nothing they
report, because no TCA migration touches `required`, `readOnly`, `minitems` or
the `email` and `number` types.

The identifier `academic-persons/apply-settings-to-tca` is public API, listed
on the extension points page of `academic_base`. The class is not.
`SettingsAfterTcaOverridesTest` pins the identifier with a listener ordered
after it. The core runs a listener with fewer orderings later, so that fixture
listener is also ordered after `content-blocks-tca` like the persons listener,
which leaves the ordering after the persons identifier as the only reason it
runs last.

## Consumer 2 — the frontend edit form

`EXT:academic_persons_edit` reads the typed sets in three places:

1. **Rendering.** `ProfileSectionProvider` and `ProfileDocumentSectionProvider`
   turn the typed graph into the view model the partials below
   `Resources/Private/Partials/Profile/` render, and the `Validation` object of
   a field travels with it: `readOnly` and `disabled` decide whether an edit
   control is rendered at all, `required` renders the marker and the
   `required` attribute. The document and contact forms carry the same flags in
   the JSON their form endpoints answer with.
2. **Validation.** `AbstractFormDataValidator::processValidationSet($subject,
   $set)` runs the `validatorClassNames` of every property of the set. Each
   concrete validator resolves its section: the contact validators their
   contact section, the contract validator `getDocumentValidationSet('contracts')`,
   the profile validator `getProfileUpdateValidationSet()`, and the profile
   information validator the set of the section its record type belongs to — so a publication is never validated by the
   lecture section.
3. **Transformation.** `disabled` and `readOnly` properties, `frontendreadonly`
   ones included, are never written to the model, whatever the request contains — see
   [Form data transformation](form-data-transformation.md), which is where that
   rule and the traps around it are documented.

A project field goes through the same three steps. Its view carries the stored
value, since the model has no property for it, and its value is written through
the DataHandler after the Extbase write, see
[Form data transformation](form-data-transformation.md#project-fields).

`ProfileDocumentSectionProvider` maps the settings key of a section
(`pressMedia`) to the record type (`press_media`) through
`getDocumentSection($key)->type`, which is what the removed
`ProfileInformationType` did before.

## Managed fields: a lock per record

The flags above lock a field of **every** record of a table. That is right for
a value no editor may change, and wrong for one a synchronisation owns on some
records only: the global `readonly` also locked the e-mail address an editor
added by hand. `managedFields` (ACE-758) is the lock per record, and it is kept
apart from the flags on purpose.

- **It lives in its own map.** `managedFields.<record type>` lists fields of
  `profile`, `contracts`, `emailAddresses`, `phoneNumbers` and
  `physicalAddresses` by their settings key or property. The factory resolves
  each entry through the field of its own record type, so `type` means the
  type of that contact list only, and keeps property and column in
  `ManagedFieldsSettings`. A field the settings do not configure cannot be
  managed: the graph is cached on its own, apart from the TCA, so it cannot
  look at TCA columns.
- **It never reaches the TCA.** `ManagedFieldResolver` (`Classes/Profile/`)
  answers per row: a record is a candidate when it has an `import_identifier`,
  is a default-language row, and its profile has `skip_sync = 0`. The profile
  of a contract or a contact is read with the deleted restriction only, the
  way the synchronisation reads it, so a hidden profile stays locked. A
  profile that cannot be found locks nothing.
- **FormEngine applies it.** The data provider
  `Backend/FormEngine/ManagedFieldsReadOnly` is registered for
  `tcaDatabaseRecord` after `TcaColumnsProcessFieldDescriptions` and before
  `TcaFlexPrepare`, the same position on v13 and v14. It sets
  `config.readOnly` and appends a note naming the import identifier to the
  already translated description. Inline children run the same data group, so
  the contracts and contacts inside a profile form are covered as well.
- **The frontend editor applies it too** (ACE-760). The resolver has a
  second entry point that takes the domain model the editor writes and
  answers property names, and `ManagedRecordLocks` of `academic_persons_edit`
  turns them into read-only fields with a "Synchronised" marker and into the
  row actions the synchronisation takes away: a row with a managed field
  cannot be deleted, and offers no edit once every editable field of it is
  managed. A submitted value for a managed field is ignored, as one for a
  `readonly` field is. In a translated site language a managed field whose
  column all languages share stays locked, a translated one is editable as
  in the backend, and the delete is still decided on the default-language
  record, which is the row it removes. See
  [The profile editing contract](profile-editing-contract.md#fields-and-rows-the-synchronisation-owns).
- **DataHandler is untouched.** The lock applies to the backend form and the
  frontend editor only. The synchronisation, an import or a script keep
  writing the fields, and a DataHandler hook would have had to tell them apart
  from an editor.
- **Copies and workspaces.** `import_identifier` has no
  `setToDefaultOnCopy`, so a copy of a synchronised record is locked as well
  (ACE-759). The parent rows are read live, without a workspace overlay, so a
  draft `skip_sync` unlocks the contracts and contacts of a profile only once
  it is published.
- **Translations are left alone.** The synchronisation writes default-language
  rows only, and a translated `position` or `title` is editorial content no
  source delivers. The `l10n_mode: exclude` columns are read only on a
  translation through core anyway.

A mistake in the map follows the rule of `frontendUserSync`: the factory
records it in `problems` instead of throwing, because a typo must not break
the TCA, and the resolver throws it (1790536034) when a person record form is
compiled. `ManagedFieldResolverTest` (unit and functional),
`ManagedFieldsReadOnlyTest` and, for the editor,
`AcademicPersonsEditManagedFieldsTest` pin the rules above.

The two locks combine. The shipped name fields carry `readonly` and
`disabled`, which already lock them on every record, so a project that wants
them locked on synchronised profiles only replaces those flags, typically with
`frontendreadonly`, and names the fields in `managedFields`.

## Overriding the settings in an installation

`SettingsFileLoader::loadMergedArray()` walks every **active package**, reads
`Configuration/AcademicPersons/Settings.yaml` if present, and folds the files
onto each other **recursively**. To change a section:

- Ship the file in a site package that **depends on `academic_persons`**, so that
  the package is ordered after it — the later one wins per key.
- State **only the keys that differ**. A map is merged key by key at any depth,
  so a file that names `profile.title.validators` changes that one list and
  leaves the layout, the other fields and their flags as shipped.
- Flush the core cache afterwards; the normalised graph is cached in
  `cache.core` under `AcademicPersons_Settings_v3` — the identifier ACE-501
  introduced when the classes moved to `academic_base`; ACE-503 keeps it,
  because nothing was released in between — as a `return <var_export>;`
  statement, which is why every object in the graph has a `__set_state()`.

Four rules decide what the merge does with a value, and they hold at every
depth:

| The later file has        | Result                                                          |
|---------------------------|-----------------------------------------------------------------|
| a map                     | merged key by key with the earlier map                          |
| a list                    | replaces the earlier list as a whole, an empty one included     |
| a value of another type   | replaces the earlier value                                      |
| `null` (`~`)              | removes the key, as if no package had configured it             |

A list replaces rather than combines because a flag list has no identity to
merge by: a project that could only add entries could never drop `required`
from `[required]`. A YAML map whose keys happen to be `0 … n-1` is a PHP list
and is replaced too — nothing in the shipped file has that shape — and an empty
map, `{}`, parses to the same empty array as an empty sequence, so it clears a
map.

The key order of a merged map is the order of the later file when that file
names **every** key of the earlier one, which is what keeps a reordered full
copy rendering in its own order; a file that names only some keys leaves the
earlier order alone and its new keys are appended. Order is display order for
the `profile` entries, `special.<component>.fields`, `contracts.fields`,
`contracts.contactSections` and `documentSections`.

That trigger is deliberate and it is sharp: a reordered copy decides the order
until upstream adds an entry it does not name, and from that release on the
upstream order applies again. Always the earlier order
(`array_replace_recursive()`) would have taken the order from every reordered
copy at once, and always the later order would move a single overridden key to
the front; a project that depends on its order names the new key when it
updates.

**An entry is no longer removed by leaving it out — at any depth.** A copy of a
top-level map used to drop everything it did not list; it now changes nothing
but what it names, and a key left off a restated entry is inherited as well.
The 3.0 recipe for unlocking the profile names — the whole `profile` map
restated, with the `validators` key left off the three name fields — therefore
inherits the shipped `[readonly, disabled]` and keeps them locked. An entry is
removed with `~` and a flag list emptied with `[]`:

```yaml
profile:
  middleName: ~
  firstName:
    validators: []
```

### Finding what an override changes

`Settings\SettingsOverrideComparator` (ACE-724) compares every package's file
with the merge of the packages loaded before it — folded with
`SettingsFileLoader::merge()`, so it cannot disagree with the runtime — and
returns one `SettingsOverride` per package after the first: the delta, the
entries the package removes with `~`, and the entries a copied map leaves out.
It has two consumers:
`academic:persons:settings:migrate --delta` prints them, and
`Report\LegacySettingsStatus` adds one status per package that removes or
omits something — a notice when it omits, an info when it only removes.

- **The delta is exact.** Merged onto the earlier packages it gives the array
  the package's own file gives, **key order included**; every test of the
  comparator asserts that. An entry equal to the earlier one is dropped, a `~`
  is kept only where an earlier package has the key, a map against a non-map is
  kept whole, and so is a map whose delta would be a list (integer keys). The
  order rule of the merge makes one case expensive: a map that names every
  earlier key decides the order, so where dropping its unchanged keys would
  change the merged order — it sorts them differently, or places a new key
  among them — the delta restates every key that map names. A field that gained
  `validators` between two shipped keys is therefore printed whole. A partial
  map decides nothing, and its new keys are their own delta.
- **An omission is a heuristic, and says so.** Below the top level, a map is a
  copy when the package restates at least two of its earlier entries
  unchanged, or when it sits inside a copy — a copied map used to replace
  everything below it, so a cut-down `fields` map inside a restated `contracts`
  is a copy whatever it restates. "Unchanged" is a subset in any key order: a
  restated field that lacks a key upstream added after the copy was made, or
  sorts its keys differently, still counts — that drift is what the report is
  for. Every earlier entry a copy does not name is reported, and a single
  restated entry does not make a copy. The rule errs both ways. A copy that
  restates at most one entry of a map unchanged and sits in no other copy is
  missed — `contracts.fields` cut down to the position alone. A delta written
  verbosely, restating two unchanged keys next to its change, is taken for a
  copy and gets a notice for its siblings, although `--delta` still shrinks it
  to the change; the report then names more, never less. Relative thresholds
  were tried and missed exactly the copies that leave out the most. The top
  level is never a copy, because the top-level merge never removed an unnamed
  top-level map. Omissions are never turned into `~` automatically: the entry
  may be one the copy meant to drop or one upstream added after the copy was
  made, and only the integrator can tell — writing `~` for all of them would
  freeze the copy against every later upstream addition, which is the drift
  the merge ended.

There is no TypoScript and no site-set path — the site sets do not expose
validations.

Because both consumers read the same data, an override changes the backend and
the frontend together. Re-enabling the profile name fields for the frontend edit
form therefore also makes those columns writable again in the backend record
editor. That is usually what is wanted, but it is worth being deliberate about.

## The jobs settings, in `academic_jobs`

`academic_jobs` ships `Configuration/AcademicJobs/Settings.yaml` with one flat
set, `validations.job`, a flag list per property of the job. Until ACE-508 it
had a loader, a registry and two ViewHelpers of its own, with a top-level
`array_merge()` and three readers that understood three keyword sets (ACE-429).
They are gone, and the file is read by the shared classes:

| Piece                                           | What it does                                                                                                        |
|-------------------------------------------------|---------------------------------------------------------------------------------------------------------------------|
| `Settings\AcademicJobsSettingsFactory`          | `SettingsFileLoader::load()` with the cache identifier `AcademicJobs_Settings_v3`, `normalizeValidationSets()`      |
| `Settings\AcademicJobsSettings`                 | The sets by identifier, `getValidationSet()` answers an empty set for an unknown one. A service through the factory |
| `Domain\Validator\JobValidator`                 | Runs the `validatorClassNames` of the `job` set, skipping a field the job has no gettable property for              |
| `Job/Forms/FieldWrapper.html`, `Textfield.html` | `p:validationEnsure`, then `required` for the asterisk and `inputType` for the input                                |
| `EventListener\ApplySettingsToTca`              | `academic-jobs/apply-settings-to-tca`, the `job` set merged into the job table after the overrides                  |

The listener is the persons one in small: one table, `TcaValidationMerger::merge()`
over the fields the table has a column for, the `email[subst]` soft reference
for a column the `email` flag creates, and `after: 'content-blocks-tca'`. Its
consequences are the persons ones listed in
[Why a listener after the overrides](#why-a-listener-after-the-overrides): the
settings win over a TCA override of `required` and `readOnly`, and a site
package that has to differ orders a listener after the identifier.
`JobValidationSettingsAfterTcaOverridesTest` pins each of them.

Three things differ from persons on purpose:

- **The form does not lock a field.** `readonly` and `disabled` cancel
  `required` and lock the backend column, while the new-job form keeps offering
  the field and stores what is submitted. The form only creates jobs, so it has
  no stored value a lock could protect and no transformation guard either. What
  the lock protects is the submitted value, against later backend edits.
- **The shipped file uses `tel` for the phone.** With the TCA merge, `number`
  would turn `contact_phone` into a number column, and saving a record would
  store `+49 30 123` as `49`.
- **ACE-429 is half open.** `required` still accepts `0` for the job type and
  the employment type, both `int` properties defaulting to `0`, because
  `NotEmptyValidator` treats `0` as a value. `JobValidatorTest` pins it.

## Documentation state

Integrator-facing documentation exists:

- `academic-persons/Documentation/Configuration/Sections/Index.rst` — the four
  maps, the field shape, the document sections and the override procedure.
- `academic-persons/Documentation/Configuration/Validations/Index.rst` — the
  flags, the character limits, the locked-by-default name fields and both
  consumers.
- `academic-persons/Documentation/Configuration/ManagedFields/Index.rst`:
  the `managedFields` map, which records it locks and how it combines with the
  flags.
- `academic-jobs/Documentation/Configuration/Validations/Index.rst`: the jobs
  flags in the form, the check of a submitted job and the backend, the merge
  per field and the listener identifier.
- `academic-persons-edit/Documentation/Configuration/General/Index.rst` — a
  *Which fields can be edited* section pointing at the persons manual, since that
  is where integrators meet the locked name fields.
- `academic-persons-edit/Documentation/Configuration/Settings/Index.rst`: the
  *Project fields* section, how a site package makes a column of its own
  editable.

Cross-extension links are plain external URLs to docs.typo3.org. That is
deliberate — no FGTCLB extension registers an intersphinx inventory in its
`guides.xml`, and adding one would make the render depend on a sibling's
published inventory being reachable, which `--fail-on-log --fail-on-error` would
turn red.

## See also

- [Form data transformation](form-data-transformation.md) — how the frontend edit
  form decides whether a submitted value reaches the model, and why a `disabled`
  property is discarded even when it was submitted.
- [Class design](class-design.md) — the value object conventions `Validation` and
  `ValidationSet` follow.
- `packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
  — the shipped graph, with the format documented in its header.
- `packages/fgtclb/academic-base/Classes/Settings/` — `Validation`,
  `ValidationSet`, `ValidationNormalizer`, `SettingsFileLoader` and
  `TcaValidationMerger`, with their unit tests in
  `packages/fgtclb/academic-base/Tests/Unit/Settings/`.
- `packages/fgtclb/academic-persons/Classes/Settings/` —
  `AcademicPersonsSettingsFactory`, `AcademicPersonsSettings` and the value
  objects of the graph, with their unit tests in
  `packages/fgtclb/academic-persons/Tests/Unit/Settings/` and the TCA
  functional tests in `packages/fgtclb/academic-persons/Tests/Functional/Tca/`.
