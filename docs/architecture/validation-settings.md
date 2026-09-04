# Validation settings

One YAML file describes, per record type and per field, whether a field is
required, read only or disabled. It is the **single source of truth for both
editing contexts**: the TYPO3 backend FormEngine and the frontend edit form of
`EXT:academic_persons_edit`.

This page describes the `academic_persons` mechanism and the shared classes in
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
is the only place the sets are defined. The dual purpose is stated in the file
itself, above the `validations` key:

```yaml
# Validation configuration for frontend fluid forms and backend formengine configured using TCA adoption.
```

Until the integrator manual was written, that one comment was the only place in
the repository stating the coupling at all — see
[Documentation state](#documentation-state) below.

A set maps a property name to a list of flags. The shipped `profile` set is the
shortest example, and the one most often met:

```yaml
  profile:
    firstName:
      - disabled
      - required
    middleName:
      - disabled
    lastName:
      - disabled
```

Six sets exist, one per editable record type: `profile`, `contract`,
`emailAddress`, `phoneNumber`, `physicalAddress`, `profileInformation`.

The recognised flags, all matched case-insensitively
(`ValidationNormalizer::normalizeValidation()` in `academic_base`):

| Flag       | Effect                                                                  |
|------------|-------------------------------------------------------------------------|
| `required` | Adds `NotEmptyValidator`, and `required` + `minitems` to the TCA config |
| `disabled` | Field must not be edited. Forces `readOnly`, and cancels `required`     |
| `readonly` | Field is shown but not writable. Cancels `required`                     |
| `email`    | Adds `EmailAddressValidator`, TCA `type` and input type `email`         |
| `number`   | TCA `type` and input type `number` — no validator today                 |
| `date`     | Input type `date` only — the TCA column keeps its own `datetime` config |

Anything else in the list is ignored. There is no `url` flag yet; the source
carries a `@todo` for it.

## The shared classes in `academic_base`

Everything that has no persons knowledge lives in
`packages/fgtclb/academic-base/Classes/Settings/`, namespace
`FGTCLB\AcademicBase\Settings`, and is `@internal`:

| Class                                    | Role                                                                                               |
|------------------------------------------|----------------------------------------------------------------------------------------------------|
| `Validation`, `ValidationSet`            | The value objects. `#[Exclude]`d from the container, `__set_state()` for the cache                 |
| `ValidationNormalizer`                   | Flag list → `Validation`; `normalizeValidationSets()` for a whole `validations` map                |
| `SettingsFileLoader`                     | The package walk, the top-level `array_merge()` and the `cache.core` round trip                    |
| `TcaValidationMerger`                    | `toTcaTableConfig()` builds the `columns.<field>.config` fragment, `merge()` applies it to a table |
| `Exception\UnknownValidatorException`    | Raised by a validation engine for a class name that is not an Extbase validator                    |
| `Exception\UnsuitableValidatorException` | Raised by a validator handed a subject it is not built for                                         |

The ViewHelper `FGTCLB\AcademicBase\ViewHelpers\ValidationEnsureViewHelper`
sits next to them, declared in a template as
`xmlns:p="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`.

What stays in `academic_persons` is the persons shape: `AcademicPersonsSettings`
(the sets, the profile information types, the raw array),
`ProfileInformationType`, and `AcademicPersonsSettingsFactory`, which is now
glue — it hands the file path, the cache identifier and its `normalize()`
closure to the loader and delegates the `validations` map to the normaliser.
The validation *engine* — `AbstractFormDataValidator::processValidations()` in
`academic_persons_edit` — deliberately did not move: it is neutral in substance
but not yet in its types, and it is a second step.

No class aliases exist for the old `FGTCLB\AcademicPersons\Settings\Validation*`
and `FGTCLB\AcademicPersonsEdit\Exception\*ValidatorException` names. They were
`@internal`, and nothing outside the two persons extensions referenced them.

## Normalisation

`ValidationNormalizer::normalizeValidation()` turns each flag list into one
`Validation` value object:

```php
$readOnly = in_array('readonly', $validators, true);
$disabled = in_array('disabled', $validators, true);
$required = !$disabled && !$readOnly && in_array('required', $validators, true);
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
  shipped `profile` set produces no validators at all — all three of its entries
  are `disabled` — and why the `- required` on `firstName` has no effect.

`Validation::$fieldName` is the property name converted with
`GeneralUtility::camelCaseToLowerCaseUnderscored()`, i.e. the database column:
`firstName` becomes `first_name`. That is what lets one entry address both the
Extbase property and the TCA column.

## Consumer 1 — the TYPO3 backend FormEngine

`TcaValidationMerger::merge($tableTca, $validationSet)` returns the table array
with a `columns.<field>.config` fragment built from each `Validation::$tcaConfig`
merged in, and **every one of the six TCA files calls it** with the set it gets
from `AcademicPersonsSettings::getValidationSet()`:

| Set                  | TCA file                                                  |
|----------------------|-----------------------------------------------------------|
| `profile`            | `tx_academicpersons_domain_model_profile.php`             |
| `contract`           | `tx_academicpersons_domain_model_contract.php`            |
| `emailAddress`       | `tx_academicpersons_domain_model_email.php`               |
| `phoneNumber`        | `tx_academicpersons_domain_model_phone_number.php`        |
| `physicalAddress`    | `tx_academicpersons_domain_model_address.php`             |
| `profileInformation` | `tx_academicpersons_domain_model_profile_information.php` |

So marking a field `disabled` or `readonly` in the YAML makes it read only in the
backend record editor as well — by design, and for every backend user.

The merge is `ArrayUtility::mergeRecursiveWithOverrule()` for all six tables —
the contract table used `array_replace_recursive()` until ACE-501, which
produces the same result for these fragments. A missing set is a no-op, so a
table may be asked about a set nobody configured. All six call sites carry the
same `@todo`:

> MAIN TCA Files should be kept without dynamic calls, and following should be
> done in override files.

That is a structural note about *where* the call belongs — `Configuration/TCA/`
versus `Configuration/TCA/Overrides/` — not a doubt about the coupling itself.

## Consumer 2 — the frontend edit form

`AcademicPersonsSettings::getValidationSetWithFallback($identifier)` returns the
`ValidationSet`, and `EXT:academic_persons_edit` uses it in three places:

1. **Rendering.** The form partials under
   `Resources/Private/Partials/Profile/Forms/` map the flags straight onto the
   control: `disabled`, `readonly` and `required` attributes.
2. **Validation.** `required` contributes `NotEmptyValidator` to the
   `*FormDataValidator` of the matching argument.
3. **Transformation.** `disabled` and `readOnly` properties are never written to
   the model, whatever the request contains — see
   [Form data transformation](form-data-transformation.md), which is where that
   rule and the traps around it are documented.

An unknown identifier yields an empty `ValidationSet`, so every property falls
through as unconfigured.

## Overriding the settings in an installation

`SettingsFileLoader::loadMergedArray()` walks every **active package**, reads
`Configuration/AcademicPersons/Settings.yaml` if present, and folds them
together with `array_merge()`. To change a set:

- Ship the file in a site package that **depends on `academic_persons`**, so that
  the package is ordered after it — the last one loaded wins.
- Restate the **whole `validations` block**. `array_merge()` is shallow and
  `validations` is a top-level key, so redefining it replaces all six sets at
  once. There is no deep merge, and no syntax for removing a single flag from a
  single field.
- Flush the core cache afterwards; the normalised result is cached in
  `cache.core` under `AcademicPersons_Settings_v3` (the suffix was added when the
  graph's classes moved to `academic_base`, so a stale entry naming the old
  classes is never `require`d), as a `return <var_export>;`
  statement — which is why every object in the graph has a `__set_state()`.

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
| `url`                   | not understood                                    | validator only, no TCA                                |
| `number`                | TCA and input type, no validator                  | TCA and input type, no validator                      |
| `date`                  | input type only, TCA untouched                    | not understood                                        |

Two of those deserve emphasis because they are traps rather than gaps:

- `FieldTypeFromValidationViewHelper` returns **the first non-`required` list
  entry verbatim as the HTML `type` attribute**. An unknown keyword is therefore
  not ignored — `disabled` renders `<input type="disabled">`. It is the only
  place in either system where a typo produces output instead of silence.
- The jobs `Settings.yaml` carries a copy of the persons comment block, so it
  documents `disabled` and `readonly`, which do nothing there, and omits `url`
  and `number`, which it actually uses.

The divergence between its three readers is tracked as ACE-429. Adopting the
shared `academic_base` classes — which replaces the loader, the three readers
and the two duplicate exceptions, and turns the dead `getValidationsForTca()`
into a real TCA merge — is ACE-508, a behaviour change for jobs and therefore a
change of its own.

## Documentation state

Integrator-facing documentation was written on 2026-08-16 and now exists:

- `academic-persons/Documentation/Configuration/Validations/Index.rst` — the
  reference: sets, flags, the locked-by-default name fields, both consumers, and
  the override procedure.
- `academic-jobs/Documentation/Configuration/Validations/Index.rst` — the second
  implementation, documenting only what actually takes effect, with the
  `disabled`/`readonly` trap called out.
- `academic-persons-edit/Documentation/Configuration/General/Index.rst` — a
  *Which fields can be edited* section pointing at the persons manual, since that
  is where integrators meet the locked name fields.

Before that there was nothing: across the `Documentation/` trees of all twelve
packages there was not one hit for `Settings.yaml`, for the `validations` key, or
for `AcademicPersonsSettings`, and neither README mentioned it.

Cross-extension links are plain external URLs to docs.typo3.org. That is
deliberate — no FGTCLB extension registers an intersphinx inventory in its
`guides.xml`, and adding one would make the render depend on a sibling's
published inventory being reachable, which `--fail-on-log --fail-on-error` would
turn red.

Still open, in the YAML files themselves rather than in the manuals:

- The persons header calls it "Validation configuration for
  EXT:academic_persons_edit or custom implementation" — it omits the backend,
  which is half the point.
- Its inline flag list documents `required`, `email`, `number`, `date`,
  `disabled` and `readonly`; the frontend forms map every one of them.
- `@internal … will change until 2.1.0 release` is stale; the branch is
  `3.0.0-dev`.
- The jobs file's copied comment block is wrong for that extension, as above.

## See also

- [Form data transformation](form-data-transformation.md) — how the frontend edit
  form decides whether a submitted value reaches the model, and why a `disabled`
  property is discarded even when it was submitted.
- [Class design](class-design.md) — the value object conventions `Validation` and
  `ValidationSet` follow.
- `packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
  — the shipped sets.
- `packages/fgtclb/academic-base/Classes/Settings/` — `Validation`,
  `ValidationSet`, `ValidationNormalizer`, `SettingsFileLoader` and
  `TcaValidationMerger`, with their unit tests in
  `packages/fgtclb/academic-base/Tests/Unit/Settings/`.
- `packages/fgtclb/academic-persons/Classes/Settings/` —
  `AcademicPersonsSettingsFactory` and `AcademicPersonsSettings`.
