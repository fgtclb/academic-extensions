# Form data transformation

How `EXT:academic_persons_edit` decides whether a submitted value reaches the
domain model. The rules are small; the reason to write them down is that the
mechanism is not the Extbase one it looks like, and the shipped configuration
locks three properties in a way that reads as a defect the first time you meet
it.

The code is `packages/fgtclb/academic-persons-edit/Classes/Domain/Factory/` —
six factories, one private setter per property — plus
`Classes/Domain/Model/Dto/AbstractFormData.php` and, for the profile itself,
`Classes/Service/ProfileUpdateValidationService.php`.

## Where the values come from

There is no Extbase form. The editing frontend posts JSON to the actions of
`ProfileController`, and the payload carries only what changed:

```json
{ "profile": 123, "data": { "website": "https://example.org", "websiteTitle": "" } }
```

`ProfileUpdatePayloadParser` turns that into a `ProfileUpdatePayload`,
`ProfileUpdateRequestService::validate()` checks method, content type, the
`X-Requested-With` header, the login and the ownership of the profile, and
`ProfileUpdateValidationService` then builds the `*FormData` object from the
**persisted** record and registers exactly the keys of `data` on it as
*property overrides*.

That last step is the whole mechanism: an override is the record that a
property was submitted.

## The decision, in order

Every factory setter is guarded by the same private helper, `mayApplyProperty()`,
in all six (`ProfileFactory.php`, `ContractFactory.php`, `AddressFactory.php`,
`ProfileInformationFactory.php`, `EmailFactory.php`, `PhoneNumberFactory.php`).
The five whose records carry an import identifier also take the properties the
synchronisation manages on the record (ACE-760), which `updateFromFormData()`
receives from the controller:

```php
private function mayApplyProperty(ValidationSet $validationSet, ProfileFormData $form, string $propertyName, array $managedProperties): bool
{
    if (in_array($propertyName, $managedProperties, true)) {
        return false;
    }
    $validation = $validationSet->get($propertyName);
    if ($validation !== null && ($validation->readOnly || $validation->disabled)) {
        // ReadOnly or disabled: keep existing persisted data and ignore the submitted value.
        return false;
    }
    return $form->shouldApplyProperty($propertyName);
}
```

So a value is written only when **all three** hold:

1. the synchronisation does not manage the property on this record,
2. the property is not configured `readOnly` or `disabled`, and
3. an override was registered for it. `shouldApplyProperty()` is
   `hasPropertyOverride()`, nothing else.

The order matters and is deliberate: the validation configuration wins over
everything, including an override. Even a value that reached the store by
mistake cannot write a property the configuration locks.

## The listener before the write

`BeforeProfileEditingWriteEvent` of `academic_persons_edit` is dispatched once
for every write of the editor, fifteen endpoints in all: the profile, its two
switches, the image upload and removal, and the create, update, visibility,
delete and sort endpoints of documents and of contacts. A listener refuses the
write, or replaces the values it stores. `ProfileEditingAction` names the
write, and `carriesFields()` says whether it has values to replace: the
profile, the synchronisation switch, and the creation and update of a document
or contact. The integrator side, with examples, is the
[developer chapter of `academic_persons_edit`](../../packages/fgtclb/academic-persons-edit/Documentation/Developers/Index.rst).

Three decisions shape it:

- **It is dispatched after every check of the request, and right before the
  factory or writer call.** Authentication, ownership, the action allow-list,
  the locks of the synchronisation on the row, the normalisation and the
  validation all come first, so a listener sees only a write that would
  otherwise be stored. The sort by a complete order checks the order before the
  dispatch for that reason, which `reorderDocumentRecords()` did on its own
  before.
- **Replaced values take the path of submitted ones.** The fields a listener
  sees are the submitted values in their JSON shape, reduced to the keys the
  normalisation accepted, so a locked field the browser sent is not among
  them. When a listener changed them, the controller runs the same
  normalisation and validation on the new values that it ran on the submitted
  ones: `createFormData()` and the validator for the profile,
  `normalizeAndValidateDocumentFields()` and
  `normalizeAndValidateContractContactFields()` for the rest. A locked field is
  dropped again, an invalid value is a `422`, and rich text runs through the
  sanitiser. A listener never touches the override store, so the factories
  only ever read normalised values from it.
- **A refusal is `422` `write_refused`** with the listener's reason as the
  message, the error shape the editor already shows as text. The event is
  stoppable and a refusal stops it.

Three alternatives were rejected:

- Dispatching before the validation. A listener would get raw payloads and
  could copy unsanitised input into a rich text field.
- One event class per endpoint. A listener refusing "any write of X" would
  register for each of them, and would miss the class a new endpoint adds.
- Opening the `final` controller for inheritance. It changes with every
  feature of the editor.

## Rule 1 — `disabled` and `readOnly` protect persisted data

This is not a validation rule that leaked into the wrong layer. It is the last
of three places that refuse a locked property, and the only one that cannot be
bypassed by a hand-built request:

| Layer                               | What it does                                                                        |
|-------------------------------------|-------------------------------------------------------------------------------------|
| The rendered control                | A locked field renders as text or read-only, without an edit button                 |
| The endpoint                        | Checks the payload keys against the settings graph and drops the locked ones        |
| `mayApplyProperty()` in the factory | Refuses the write even when an override was registered anyway                       |

A request that posts a locked property is answered as if it had left the
property out: the stored value stays, the other properties of the request are
written, and the `data` of the profile endpoint's answer does not name it. That
holds for the profile, document and contact endpoints alike, and for a field
managed on the record as for `readonly`, `frontendreadonly` and `disabled`. A
key the settings graph does not have, neither as a property nor as a
[project field](#project-fields), is still
refused, with `422`,
`invalid_profile_data` and `Unknown profile property "…"` on the profile
endpoint and "This field cannot be changed." on the other two.

Until ACE-760 the endpoints refused a locked property with `422` as well. The
editor sends every field of an open contract or contact when it saves, the
locked ones included, so a record with a locked field could not be saved from
the browser at all. The two switches with an endpoint of their own, the
synchronisation switch and the profile visibility, still refuse: their one
value is the whole request, and ignoring it would answer a success for nothing.
The guard in the factory is what makes the rule a policy rather than a
coincidence of the caller.

## Rule 2 — only what the payload carried

`AbstractFormData::shouldApplyProperty()` is one line:

```php
final public function shouldApplyProperty(string $propertyName): bool
{
    return $this->hasPropertyOverride($propertyName);
}
```

Before the editing rewrite (ACE-262) it also asked the *request* whether the
property had been submitted, through `wasPropertySentInRequest()` and the
argument name that `AbstractFormDataConverter` put on the object. Both are gone
with the Extbase form flow they served: the JSON endpoints know exactly which
keys arrived, so the object no longer has to re-derive it from the request, and
a form data object built for rendering carries no overrides at all and therefore
writes nothing when it is handed to a factory by mistake.

What the rule prevents is unchanged, and it is why it exists: the `*FormData`
objects have empty-string defaults, so a factory that wrote every property would
clear every field the request did not mention. See
`packages/fgtclb/academic-persons-edit/Documentation/Changelog/2.4/Important-FormDataTransformationOnlyMapsSubmittedFields.rst`
for the entry that introduced it.

## Project fields

A site package can add a column to the profile table and declare it in the
settings with `custom: true` (ACE-764). No property of `Profile` or of
`ProfileFormData` carries it, so it takes its own path through the same steps:

| Step            | Regular property                               | Project field                                                                   |
|-----------------|------------------------------------------------|---------------------------------------------------------------------------------|
| Payload key     | A property of `ProfileFormData`                | Its identifier, which `AcademicPersonsSettings::getCustomProfileFields()` knows |
| Registered as   | `setPropertyOverride()`                        | `ProfileFormData::setCustomValue()`, an override plus the name                  |
| Validation      | `ProfileFormDataValidator`, one pass           | The same pass: it admits a property with a custom value                         |
| Written by      | `ProfileFactory`, then `persistAll()`          | `ProjectProfileFields::writeValues()`, a DataHandler datamap, after that        |
| Row written     | The row Extbase resolved                       | The row `LocalizedProfileUidResolver` resolves for the site language            |
| Answered        | The submitted value                            | The value read back after the write                                             |

Because the value is an override, rule 1 and rule 2 hold unchanged, the answer
of the request names it, and a listener of the write event can replace it like
any other field. `ProfileFactory` has no setter for it and never writes it.

The DataHandler write runs as the synthetic admin user of
`DataHandlerExecutionContext::runAsLiveBackendUser()`, marked
`ProfileWriteCorrelation::Internal`, so the backend save announcement of
ACE-725 stays out of it. It happens between `persistAll()` and the
`AfterProfileUpdateEvent` the editor dispatches, so the translation
synchronisation reads the new value. A frontend request in a workspace preview
is refused with `409` before anything is written, as the visibility switch is.

On a translation, the DataHandler drops a column with `l10n_mode: exclude` from
the data of the row, and one with `allowLanguageSynchronization` while the
`l10n_state` of the row takes it from the default language. Neither is an error,
so the write would answer a success and store nothing. `writeValues()` therefore
sends an excluded column to the default-language record, from which the same run
carries it into the translations, and marks a synchronised column `custom` in the
`l10n_state` of the translation. The answer carries the values read back after
the write, so a `max` of the column or a hook shows in it.

The two writes are not one transaction. A value the DataHandler refuses although
it passed the validation, through a hook of the installation for example, is
answered with `500` and logged, and the Extbase part of the request stays
stored. The column check keeps the known cases out of that path: the
DataHandler validates an `email` and a `link` column itself, so such a column
needs the validator that refuses an invalid value first, a `link` column has to
allow web addresses, and a project field with the `url` validator takes nothing
but an `http` or `https` address.

An update made in a translation is not announced, as for every other field of
the editor. A shared column written to the default-language record from there
is no exception: the DataHandler carries it into the translations in the same
run, and the synchronisation has nothing left to do.

Which column a project field may use is decided in one place,
`ProjectProfileFieldCheck` of `academic_persons`. The TCA listener asks it for
every project field while the TCA is compiled and raises an `E_USER_DEPRECATED`
notice for a refused one. The editor asks it again against the TCA schema of the
request: for every project field when it renders the page, which then fails with
`InvalidProjectProfileFieldException`, and for the submitted ones when it writes,
before their values are validated, which answers `500` and
`invalid_project_field`. It checks again after a listener of the write event
replaced the fields. The second check is not redundant: a listener ordered after
the settings can still remove or change the column, and a production
installation does not log deprecations.

A submitted key is looked up among the project fields first. The form data
object has a property of its own for the names of the project values, and
`_hasProperty()` is `property_exists()`, so a project field named like it would
otherwise be taken for a property of the profile and never written.

Rejected, as recorded in the change: a magic `__get()`/`__set()` on the form data
object, which would hide a typo from phpstan and from the key check that refuses
an unknown payload. A `QueryBuilder` update of the column, which writes no
history, calls no hook and bypasses the workspace handling. An XCLASS of the form
data object and its factory, the 2.x way, which one project used and which is
fatal on 3.0.

## The shipped defaults, and the trap they set

`packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
is the only place the profile validations are defined, and it locks the three
name fields:

```yaml
  firstName:
    validators:
      - readonly
      - disabled
  middleName:
    validators:
      - readonly
      - disabled
  lastName:
    validators:
      - readonly
      - disabled
```

**They are locked on purpose.** The commit that did it, `7c9ae9a0c`, says so:
*"the name fields of the profile were set not only to required, but also to
disabled as the fields should not be overwritten in the frontend."* They are
typically owned elsewhere — an Active Directory or LDAP backed `fe_user`,
synchronised into the profile — which is also why `skipSync` exists.

> **The trap.** A test, a script or a `curl` that posts
> `{"data": {"firstName": "…"}}` and then asserts the record changed will find
> it unchanged, and it is very easy to read that as "the endpoint is broken".
> It is neither: the field renders read-only, the endpoint drops the payload
> key and answers a success, and rule 1 would discard it even if it got
> through. **When a
> transformation test needs an editable property, use one that the shipped set
> does not lock** — `website` and `websiteTitle` are the ones the factory tests
> settled on.

### `disabled` cancels `required`, and always implies `readonly`

`ValidationNormalizer::normalizeValidation()` in `academic-base` (which
`AcademicPersonsSettingsFactory` delegates to) computes:

```php
$frontendReadOnly = in_array('frontendreadonly', $flags, true);
$backendRequired = !$disabled && !$readOnly && in_array('required', $flags, true);
$required = $backendRequired && !$frontendReadOnly;
...
if ($disabled) {
    // @todo Investigate how to handle that for the backend / TCA FormEngine, therefore switch to
    //       readOnly for now
    $readOnly = true;
}
```

So `disabled` cancels `required` and additionally forces `readOnly` — the
`readonly` the three name fields spell out is therefore redundant, and kept
because it says what is meant. For the shipped set no validator is produced for
any of the three properties, which is correct: a field the user cannot edit
cannot be required of them. A `required` added beside `disabled` would have no
effect either.

`disabled` therefore always implies `readOnly` for anything the normaliser
produces, so the `||` in rule 1 only distinguishes the two for a `Validation`
built by hand.

`frontendreadonly` sets `readOnly` as well, so rule 1 protects such a
property exactly like a `readonly` one. The difference is on the backend
side only: the flag leaves the TCA fragment alone, and a backend editor can
still change the value. See
[Validation settings](validation-settings.md#normalisation).

**The same configuration also drives the TYPO3 backend**, deliberately: a
listener of the compiled TCA merges the set of each section into its table
through `TcaValidationMerger`, so a field locked with `readonly` or `disabled`
is read-only in the record editor as well. That coupling — and the reason the
settings ship in `academic_persons` rather than in the edit extension — is
documented in
[Validation settings](validation-settings.md).

## Overriding the set in an instance

An installation that wants the names editable ships its own `Settings.yaml`.
The mechanism is described in
[Validation settings](validation-settings.md#overriding-the-settings-in-an-installation).
Note that it changes the backend record editor at the same time.

## See also

- [Validation settings](validation-settings.md) — the shared configuration behind
  rule 1, why it lives in `academic_persons`, and how it drives the backend
  FormEngine as well.
- [Class design](class-design.md) — the DTO and data object conventions these
  form data objects follow.
- [Dependency injection](dependency-injection.md) — how the factories and the
  settings service are wired.
- [Functional tests](../testing/functional-tests.md) — the JSON endpoint test
  pattern that exercises this path end to end.
- `packages/fgtclb/academic-persons-edit/Documentation/ProfileEditing/Index.rst`
  — the integrator-facing description of the endpoints and their payloads.
- `packages/fgtclb/academic-persons-edit/Documentation/Developers/Index.rst`:
  the write event, as integrators read it.
- `packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
  — the shipped sets, and the only file that defines them.
