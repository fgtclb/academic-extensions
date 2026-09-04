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
identical in all six (`ProfileFactory.php`, `ContractFactory.php`,
`AddressFactory.php`, `ProfileInformationFactory.php`, `EmailFactory.php`,
`PhoneNumberFactory.php`):

```php
private function mayApplyProperty(ValidationSet $validationSet, ProfileFormData $form, string $propertyName): bool
{
    $validation = $validationSet->get($propertyName);
    if ($validation !== null && ($validation->readOnly || $validation->disabled)) {
        // ReadOnly or disabled: keep existing persisted data and ignore the submitted value.
        return false;
    }
    return $form->shouldApplyProperty($propertyName);
}
```

So a value is written only when **both** hold:

1. the property is not configured `readOnly` or `disabled`, and
2. an override was registered for it — `shouldApplyProperty()` is
   `hasPropertyOverride()`, nothing else.

The order matters and is deliberate: the validation configuration wins over
everything, including an override. A PSR-14 listener that replaces an override
before the transformation runs cannot write a property the configuration locks.

## Rule 1 — `disabled` and `readOnly` protect persisted data

This is not a validation rule that leaked into the wrong layer. It is the last
of three places that refuse a locked property, and the only one that cannot be
bypassed by a hand-built request:

| Layer                               | What it does                                                                 |
|-------------------------------------|------------------------------------------------------------------------------|
| The rendered control                | A locked field renders as text, without an edit button                       |
| `ProfileUpdateValidationService`    | Whitelists the payload keys against the settings graph, dropping locked ones |
| `mayApplyProperty()` in the factory | Refuses the write even when an override was registered anyway                |

A request that posts a locked property is answered `422` with the error code
`invalid_profile_data` and the message `Unknown profile property "…"` — a
locked property is not part of the editable set, so it is refused the way an
invented property name is. It never reaches the factory. The guard is what
makes that a policy rather than a coincidence of the caller.

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

## The shipped defaults, and the trap they set

`packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
is the only place the profile validations are defined, and it locks the three
name fields:

```yaml
  firstName:
    validators:
      - disabled
      - required
  middleName:
    validators:
      - disabled
  lastName:
    validators:
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
> It is neither: the field renders read-only, the payload key is refused by the
> whitelist with `invalid_profile_data`, and rule 1 would discard it even if it
> got through. **When a
> transformation test needs an editable property, use one that the shipped set
> does not lock** — `website` and `websiteTitle` are the ones the factory tests
> settled on.

### `- required` on `firstName` is inert

`ValidationNormalizer::normalizeValidation()` in `academic-base` (which
`AcademicPersonsSettingsFactory` delegates to) computes:

```php
$required = !$disabled && !$readOnly && in_array('required', $flags, true);
...
if ($disabled) {
    // @todo Investigate how to handle that for the backend / TCA FormEngine, therefore switch to
    //       readOnly for now
    $readOnly = true;
}
```

So `disabled` cancels `required` and additionally forces `readOnly`. For the
shipped set that means no validator is produced for any of the three properties,
which is correct — a field the user cannot edit cannot be required of them. The
`- required` entry on `firstName` is a leftover with no effect.

`disabled` therefore always implies `readOnly` for anything the normaliser
produces, so the `||` in rule 1 only distinguishes the two for a `Validation`
built by hand.

**The same configuration also drives the TYPO3 backend**, deliberately: the TCA
files merge the set of their own section in through `TcaValidationMerger`, so a
locked field is read-only in the record editor as well. That coupling — and the
reason the settings ship in `academic_persons` rather than in the edit
extension — is documented in [Validation settings](validation-settings.md).

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
- `packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
  — the shipped sets, and the only file that defines them.
