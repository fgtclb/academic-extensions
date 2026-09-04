# Validation settings

One YAML file describes the profile of `academic_persons`: which fields exist,
in which order, with which control, and whether each is required, read only or
disabled. It is the **single source of truth for both editing contexts**: the
TYPO3 backend FormEngine and the editing frontend of `EXT:academic_persons_edit`.

This page describes the `academic_persons` graph and the shared classes in
`academic_base` it is built on since ACE-501. **`academic_jobs` ships a second,
unrelated implementation** of the same idea, with its own file, its own loader
and a different keyword vocabulary — see
[The second implementation](#the-second-implementation-in-academic_jobs) at the
end. The two share no code yet; moving jobs onto the shared classes is ACE-508.

That is why the file ships in **`academic_persons`** and not in the edit
extension. `academic_persons` owns the domain models and their TCA, so it must
own the configuration that drives the backend forms; the frontend edit extension
is the *second* consumer of the same data, not its owner. An installation that
does not have `academic_persons_edit` installed still gets the backend half.

## The file

`packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
is the only place the graph is defined — there is no second file in the edit
extension, which `SettingsSourceTest` pins. Since ACE-503 it
has four top-level maps:

| Map                | Holds                                                                                                                                                                       |
|--------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `profile`          | The public detail layout (`structure`, `details`) **and** every editable profile property: `section`, `fieldType`, `renderType`, `validators`, `helptext`, `characterLimit` |
| `special`          | The components that are not one property: the composed `title`, the `image`, the `skipSync` switch                                                                          |
| `contracts`        | `fields` of the contract form, and `contactSections` — `physicalAddresses`, `emailAddresses`, `phoneNumbers` — with their own `fields`                                      |
| `documentSections` | The sortable lists: the seven profile information types and `contracts`, each with `label`, `type`, `fieldName`, `rowFields`, `actions`, `validators`, `helptext`           |

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
per record type — is not read any more. The seven relations are now declared by
`tx_academicpersons_domain_model_profile.php` itself (an override that dropped
an entry used to lose a backend column), and the flags sit on the field they
apply to. The Breaking entry `Breaking-SectionBasedAcademicPersonsSettings.rst`
of `academic-persons` documents the migration; the runtime overlay for a
site package still shipping the old shape is ACE-504.

The recognised flags, all matched case-insensitively
(`ValidationNormalizer::normalizeValidation()` in `academic_base`):

| Flag       | Effect                                                                  |
|------------|-------------------------------------------------------------------------|
| `required` | Adds `NotEmptyValidator`, and `required` + `minitems` to the TCA config |
| `disabled` | Field must not be edited. Forces `readOnly`, and cancels `required`     |
| `readonly` | Field is shown but not writable. Cancels `required`                     |
| `email`    | Adds `EmailAddressValidator`, TCA `type` and input type `email`         |
| `number`   | TCA `type` and input type `number` — no validator                       |
| `url`      | Adds `UrlValidator` and input type `url` — TCA untouched                |
| `date`     | Input type `date` only — the TCA column keeps its own `datetime` config |
| `tel`      | Input type `tel` only — no format enforced, TCA untouched               |
| `textarea` | Input type `textarea` only — TCA untouched                              |
| `html`     | Input type `textarea` and `Validation::isRichText()` — TCA untouched    |

An unknown flag is kept in `Validation::$flags` and has no other effect.

## The shared classes in `academic_base`

Everything that has no persons knowledge lives in
`packages/fgtclb/academic-base/Classes/Settings/`, namespace
`FGTCLB\AcademicBase\Settings`, and is `@internal`:

| Class                                    | Role                                                                                               |
|------------------------------------------|----------------------------------------------------------------------------------------------------|
| `Validation`, `ValidationSet`            | The value objects. `#[Exclude]`d from the container, `__set_state()` for the cache                 |
| `ValidationNormalizer`                   | Flag list → `Validation`; `normalizeValidationSets()` for a whole map of flat sets                 |
| `SettingsFileLoader`                     | The package walk, the top-level `array_merge()` and the `cache.core` round trip                    |
| `TcaValidationMerger`                    | `toTcaTableConfig()` builds the `columns.<field>.config` fragment, `merge()` applies it to a table |
| `Exception\UnknownValidatorException`    | Raised by a validation engine for a class name that is not an Extbase validator                    |
| `Exception\UnsuitableValidatorException` | Raised by a validator handed a subject it is not built for                                         |

The ViewHelper `FGTCLB\AcademicBase\ViewHelpers\ValidationEnsureViewHelper`
sits next to them, declared in a template as
`xmlns:p="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`.

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
| `PublicProfileSettings`                           | `profile.structure`, `profile.details`                   | The layout columns and the per-element property lists, maps and label references                                                                                                                           |
| `ProfileSection` → `ProfileField`                 | every other `profile` entry, grouped by `section`        | `propertyName`, `fieldName`, `fieldType`, `renderType`, `Validation`, `position`, `helptext`                                                                                                               |
| `SpecialField`                                    | `special`                                                | `type`, `renderType`, composed `fieldIdentifiers`, renderer `settings` (the image's `ratio`); `hasDirectProfileProperty()` is true only for `skipSync`                                                     |
| `ContractField`                                   | `contracts.fields`                                       | as a profile field, plus `optionSource`, `helptext`, `autocomplete`                                                                                                                                        |
| `ContractContactSection` → `ContractContactField` | `contracts.contactSections`                              | as a profile field, plus `autocomplete` and `helptext`; the section carries the `ValidationSet`                                                                                                            |
| `DocumentSection`                                 | `documentSections`, `contracts` completing its own entry | `label`, `type`, `fieldName`, `readOnly`, `rowFields`, `actions`, `helptexts` (keyed like `validators`), `ValidationSet`; `allowsAction()`, `getAllowedActions()`, `allowsCreate()`, `allowsDragSorting()` |

Three details of the normalisation are easy to get wrong:

- **A field's key is not always its property.** The contact sections need
  unique keys across three record types that all have a `type` column, so
  `emailAddressType` declares `propertyName: type`, and `emailAddress` declares
  `propertyName: email`. The validation sets are keyed by **property name**,
  which is what `ObjectAccess` and the ViewHelper resolve; the `fields` maps of
  the sections are keyed by the settings key. `getProfileField()`,
  `getContractField()` and `getContractContactField()` accept either.
- **Document validators speak the editor's language.** `from`, `to` and
  `description` are aliases of the `dateStart`, `dateEnd` and `bodytext`
  properties (`DOCUMENT_PROPERTY_ALIASES`); `date` is the `date` property and
  column of ACE-502. A document field accepts the plain flag list or a map with
  `validators`, `<flag>: true` entries and an `editor` block — `type: ckeditor`
  implies `html` and carries the `limit`, `type: textarea` implies `textarea`.
- **The `contracts` document section is two lines.** It declares `type:
  contracts` and takes label, relation, row fields and actions from the
  top-level `contracts` map (`array_replace`), and its validation set is the
  contract fields'. `DocumentSection::isContractSection()` is what the TCA
  fragment builder branches on.

`fieldType` and `renderType` are **frontend metadata only**. The normaliser
never derives a TCA `type` from them; the six TCA files declare their column
types and the settings overlay `readOnly` and `required` (plus the `email` and
`number` types, as before). `SettingsValidationOverridesTest` pins that the
`website` column stays `input` under a `combinedLink` render type and that the
rich text columns keep their `enableRichtext`.

Nothing is logged when an entry is dropped: a profile field without a
`section`, `fieldType` or `renderType`, a document section without `label`,
`type` or `fieldName`, a `special` entry whose `type` is not `special`. The
`isValid()` of each value object is the gate, and `SectionSettingsTest` pins
what each accepts.

**Everything a consumer needs is on the graph** — every help text, the image's
crop ratio, the row fields and actions — so nothing reads the raw array to
render a form. `raw` keeps the merged array on the settings object for ACE-504,
which reports the legacy keys of a site package override from it; a consumer
reaching for `raw[...]` for anything else is a value object missing a property,
and the property is the fix.

## Normalisation

`ValidationNormalizer::normalizeValidation()` turns each flag list into one
`Validation` value object:

```php
$readOnly = in_array('readonly', $flags, true);
$disabled = in_array('disabled', $flags, true);
$required = !$disabled && !$readOnly && in_array('required', $flags, true);
...
if ($disabled) {
    // @todo Investigate how to handle that for the backend / TCA FormEngine, therefore switch to
    //       readOnly for now
    $readOnly = true;
}
```

Two consequences worth knowing:

- **`disabled` implies `readOnly`.** FormEngine has no per-field notion matching
  the HTML `disabled` attribute, so `disabled` is expressed to the backend as
  `readOnly`. The `@todo` records that this mapping is provisional, not that the
  backend coupling is unintended. For anything produced by this factory,
  `disabled` therefore never occurs without `readOnly`.
- **`disabled` and `readonly` cancel `required`.** A field the user cannot edit
  cannot be demanded of them, so no validator is generated. This is why the
  three shipped name fields produce no validators at all — all three are
  `readonly` and `disabled`.

`Validation::$fieldName` is the property name converted with
`GeneralUtility::camelCaseToLowerCaseUnderscored()`, i.e. the database column,
unless the settings entry names a `fieldName`: `firstName` becomes
`first_name`, `emailAddress` with `propertyName: email` becomes `email`. That
is what lets one entry address the Extbase property and the TCA column.

`AcademicPersonsSettings` answers validation questions per section, and never
falls back from one to another:

| Accessor                                                                 | Returns                                                                                |
|--------------------------------------------------------------------------|----------------------------------------------------------------------------------------|
| `getProfileValidationSet(?$section)`                                     | One section's set, or all sections folded (a later section wins per property)          |
| `getProfileUpdateValidationSet()`                                        | All sections plus the direct special fields (`skipSync`) — what the profile TCA merges |
| `getProfileValidationSetForFields($ids, $section)`                       | The named fields of one section, keyed by property                                     |
| `getContractContactValidationSet($section)`, `…ForFields()`              | One contact section's set                                                              |
| `getDocumentValidationSet($id)`, `getDocumentValidationSetByType($type)` | One document section's set, by settings key or by record type                          |
| `getDocumentValidationTcaTypesConfig()`                                  | `types.<type>.columnsOverrides` for the profile information table, contracts excluded  |

Every one of them returns an empty `ValidationSet` carrying the requested
identifier for an unknown section.

## Consumer 1 — the TYPO3 backend FormEngine

`TcaValidationMerger::merge($tableTca, $validationSet)` returns the table array
with a `columns.<field>.config` fragment built from each `Validation::$tcaConfig`
merged in, and **five of the six TCA files call it** with the set of their own
section; the sixth merges a `types` fragment:

| Section                                    | TCA file                                                  | Call                                                                                 |
|--------------------------------------------|-----------------------------------------------------------|--------------------------------------------------------------------------------------|
| `profile` + `special.skipSync`             | `tx_academicpersons_domain_model_profile.php`             | `merge($tca, $settings->getProfileUpdateValidationSet())`                            |
| `contracts.fields`                         | `tx_academicpersons_domain_model_contract.php`            | `merge($tca, $settings->getDocumentValidationSet('contracts'))`                      |
| `contracts.contactSections.emailAddresses` | `tx_academicpersons_domain_model_email.php`               | `merge($tca, $settings->getContractContactValidationSet('emailAddresses'))`          |
| `…phoneNumbers`                            | `tx_academicpersons_domain_model_phone_number.php`        | `merge($tca, $settings->getContractContactValidationSet('phoneNumbers'))`            |
| `…physicalAddresses`                       | `tx_academicpersons_domain_model_address.php`             | `merge($tca, $settings->getContractContactValidationSet('physicalAddresses'))`       |
| the timeline `documentSections`            | `tx_academicpersons_domain_model_profile_information.php` | `mergeRecursiveWithOverrule($tca, $settings->getDocumentValidationTcaTypesConfig())` |

The profile information table is one table with a `type` column shared by the
seven timeline types, so a section's flags land in the `columnsOverrides` of
its record type and never on the column: a required title of publications does
not make the title of a lecture required. `ProfileInformationTcaTest` and
`EditSettingsIsolationTest` pin both halves — the override reaches the type,
the native `datetime` configuration of the `date` columns is untouched.

So marking a field `disabled` or `readonly` in the YAML makes it read only in the
backend record editor as well — by design, and for every backend user.

The merge is `ArrayUtility::mergeRecursiveWithOverrule()` for all six tables. A
missing section is a no-op, so a table may be asked about a section nobody
configured. All six call sites carry the same `@todo`:

> MAIN TCA Files should be kept without dynamic calls, and following should be
> done in override files.

That is a structural note about *where* the call belongs — `Configuration/TCA/`
versus `Configuration/TCA/Overrides/` — not a doubt about the coupling itself.

## Consumer 2 — the frontend edit form

`EXT:academic_persons_edit` reads the typed sets in three places:

1. **Rendering.** The form partials under
   `Resources/Private/Partials/Profile/Forms/` map the flags straight onto the
   control through `p:validationEnsure`: `disabled`, `readonly` and `required`
   attributes. The controllers assign the `validations` of the section's set.
2. **Validation.** `AbstractFormDataValidator::processValidationSet($subject,
   $set)` runs the `validatorClassNames` of every property of the set. Each
   concrete validator resolves its section: the contact validators their
   contact section, the contract validator `getDocumentValidationSet('contracts')`,
   the profile validator `getProfileUpdateValidationSet()`, and the profile
   information validator `getDocumentValidationSetByType()` with the record
   type the form data carries — so a publication is never validated by the
   lecture section.
3. **Transformation.** `disabled` and `readOnly` properties are never written to
   the model, whatever the request contains — see
   [Form data transformation](form-data-transformation.md), which is where that
   rule and the traps around it are documented.

`ProfileInformationController::newAction()` maps the settings key of a section
(`pressMedia`) to the record type (`press_media`) through
`getDocumentSection($key)->type`, which is what the removed
`ProfileInformationType` did before.

## Overriding the settings in an installation

`SettingsFileLoader::loadMergedArray()` walks every **active package**, reads
`Configuration/AcademicPersons/Settings.yaml` if present, and folds them
together with `array_merge()`. To change a section:

- Ship the file in a site package that **depends on `academic_persons`**, so that
  the package is ordered after it — the last one loaded wins.
- Restate the **whole top-level map** the change belongs to. `array_merge()` is
  shallow, so redefining `profile` replaces the layout keys and every field at
  once, and redefining `documentSections` replaces all eight sections. There is
  no deep merge, and no syntax for removing a single flag from a single field.
- Flush the core cache afterwards; the normalised graph is cached in
  `cache.core` under `AcademicPersons_Settings_v3` — the identifier ACE-501
  introduced when the classes moved to `academic_base`; ACE-503 keeps it,
  because nothing was released in between — as a `return <var_export>;`
  statement, which is why every object in the graph has a `__set_state()`.

There is no TypoScript and no site-set path — the site sets do not expose
validations.

Because both consumers read the same data, an override changes the backend and
the frontend together. Re-enabling the profile name fields for the frontend edit
form therefore also makes those columns writable again in the backend record
editor. That is usually what is wanted, but it is worth being deliberate about.

## The second implementation, in `academic_jobs`

`academic_jobs` has its own `Configuration/AcademicJobs/Settings.yaml`, read by
`AcademicJobsSettingsLoader` into `AcademicJobsSettingsRegistry`. The package
walk and the shallow `array_merge()` are the same algorithm, so the override
rules match — but nothing else does, and the two systems share no code.

|                         | `academic_persons`                                | `academic_jobs`                                       |
|-------------------------|---------------------------------------------------|-------------------------------------------------------|
| Normalisation           | once, at load, into `Validation` objects          | none — three readers reinterpret the raw array        |
| Keyword case            | lowercased before matching                        | case sensitive                                        |
| Backend TCA             | merged by all six TCA files                       | `getValidationsForTca()` exists but **has no caller** |
| Frontend rendering      | `disabled` / `readonly` / `required` / input type | required asterisk and input type only                 |
| Transformation guard    | yes — locked properties are never written         | none                                                  |
| `disabled` / `readonly` | supported                                         | **understood by none of the three readers**           |
| `url`                   | validator and input type, no TCA                  | validator only, no TCA                                |
| `number`                | TCA and input type, no validator                  | TCA and input type, no validator                      |
| `date`                  | input type only, TCA untouched                    | not understood                                        |

Two of those deserve emphasis because they are traps rather than gaps:

- `FieldTypeFromValidationViewHelper` returns **the first non-`required` list
  entry verbatim as the HTML `type` attribute**. An unknown keyword is therefore
  not ignored — `disabled` renders `<input type="disabled">`. It is the only
  place in either system where a typo produces output instead of silence.
- The jobs `Settings.yaml` carries a copy of the old persons comment block, so it
  documents `disabled` and `readonly`, which do nothing there, and omits `url`
  and `number`, which it actually uses.

The divergence between its three readers is tracked as ACE-429. Adopting the
shared `academic_base` classes — which replaces the loader, the three readers
and the two duplicate exceptions, and turns the dead `getValidationsForTca()`
into a real TCA merge — is ACE-508, a behaviour change for jobs and therefore a
change of its own.

## Documentation state

Integrator-facing documentation exists:

- `academic-persons/Documentation/Configuration/Sections/Index.rst` — the four
  maps, the field shape, the document sections and the override procedure.
- `academic-persons/Documentation/Configuration/Validations/Index.rst` — the
  flags, the character limits, the locked-by-default name fields and both
  consumers.
- `academic-jobs/Documentation/Configuration/Validations/Index.rst` — the second
  implementation, documenting only what actually takes effect, with the
  `disabled`/`readonly` trap called out.
- `academic-persons-edit/Documentation/Configuration/General/Index.rst` — a
  *Which fields can be edited* section pointing at the persons manual, since that
  is where integrators meet the locked name fields.

Cross-extension links are plain external URLs to docs.typo3.org. That is
deliberate — no FGTCLB extension registers an intersphinx inventory in its
`guides.xml`, and adding one would make the render depend on a sibling's
published inventory being reachable, which `--fail-on-log --fail-on-error` would
turn red.

Still open: the jobs file's copied comment block is wrong for that extension, as
above.

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
  `AcademicPersonsSettingsFactory`, `AcademicPersonsSettings` and the eight
  value objects of the graph, with their unit tests in
  `packages/fgtclb/academic-persons/Tests/Unit/Settings/` and the TCA
  functional tests in `packages/fgtclb/academic-persons/Tests/Functional/Tca/`.
