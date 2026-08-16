# Form data transformation

How `EXT:academic_persons_edit` decides whether a submitted value reaches the
domain model. The rules are small; the reason to write them down is that two of
them look like defects when you meet them for the first time, and both have
already been misread once.

The code is `packages/fgtclb/academic-persons-edit/Classes/Domain/Factory/` —
six factories, one private setter per property — plus
`Classes/Domain/Model/Dto/AbstractFormData.php`.

## The decision, in order

Every factory setter is guarded by the same private helper, `mayApplyProperty()`,
identical in all six (`ProfileFactory.php:63`, `ContractFactory.php:56`,
`AddressFactory.php:58`, `ProfileInformationFactory.php:49`,
`EmailFactory.php:39`, `PhoneNumberFactory.php:39`):

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
2. it was either sent in the request, or registered as an override —
   `shouldApplyProperty()` is `wasPropertySentInRequest() || hasPropertyOverride()`.

The order matters and is deliberate: the validation configuration wins over
everything, including an override. A listener cannot write a property the
configuration locks.

## Rule 1 — `disabled` and `readOnly` protect persisted data

This is not a validation rule that leaked into the wrong layer. It is the
server-side half of a policy whose client-side half is the rendered form.

The guard arrived with `dfabb2943` (`[TASK] ACE-33: Refactor configuration
handling`), whose comment states the motive directly: *ignore value to prevent
empty existing persisted data*. `63609482d` later hoisted it into
`mayApplyProperty()` and added the request detection of rule 2, keeping the
precedence explicit — *"readOnly/disabled validation configuration keeps
precedence to protect persisted data"*.

### What the browser does, and where the guard is actually load-bearing

All five form partials under `Resources/Private/Partials/Profile/Forms/` render
the flags onto the control, e.g. `Textfield.html:36-38`:

```html
disabled="{validation.disabled ? 'disabled' : ''}"
required="{validation.required ? 'required' : ''}"
readonly="{validation.readOnly ? 'readonly' : ''}"
```

The empty branch is safe: Fluid drops a registered tag attribute whose value is
`''`, so `disabled=""` — which *would* disable the control — never reaches the
HTML.

What the server then receives differs per control type, and this is the part
worth knowing:

| Control                               | Marked `disabled`                        | Marked `readOnly`                              |
|---------------------------------------|------------------------------------------|------------------------------------------------|
| `f:form.textfield`, `f:form.textarea` | Not submitted at all — the key is absent | Submitted, with the value it was rendered with |
| `f:form.select`, `f:form.checkbox`    | **Submitted as `''`** — see below        | Submitted, with the value it was rendered with |

The select and checkbox case is the one that bites. Both view helpers emit a
companion hidden field so that an empty selection still reaches the server, and
that hidden field is **not** disabled — see
`renderHiddenFieldForEmptyValue()` in
`cms-fluid/Classes/ViewHelpers/Form/AbstractFormFieldViewHelper.php`, whose last
statement is:

```php
return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="" />';
```

So for a disabled select or checkbox the property *does* arrive, with an empty
value, and `wasPropertySentInRequest()` returns `true`. Without rule 1 the stored
value would be overwritten with `''` on every save. **That is the data loss the
guard exists to prevent**, and no amount of request-level detection would catch
it, because as far as the request is concerned the field was submitted.

For a disabled text field the guard is belt-and-braces: a browser omits the key,
so rule 2 would already skip it. It still matters, because a hand-built or
forged POST can carry the key anyway.

## Rule 2 — only what the request carried

`AbstractFormData::wasPropertySentInRequest()` (`AbstractFormData.php:81-93`)
reads exactly one source:

```php
$arguments = $this->getActionRequestArguments();   // $request->getAttribute('extbase')->getArguments()
$argumentName = $this->getArgumentName();          // set by AbstractFormDataConverter
...
return array_key_exists($propertyName, $namespacedArguments);
```

Never `getParsedBody()`. The arguments come from `RequestBuilder`, already
stripped of the plugin namespace and keyed by action argument name, and the
argument name is `$argument->getName()`, configured onto the type converter by
`AbstractActionController::initializeAction()`.

This exists because before `63609482d` the factories wrote **every** property on
every request, so a field that was not part of a form was overwritten with the
empty DTO default. See
`packages/fgtclb/academic-persons-edit/Documentation/Changelog/2.4/Important-FormDataTransformationOnlyMapsSubmittedFields.rst`.

## The shipped defaults, and the trap they set

`packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml:80-87`
is the only place the `profile` set is defined:

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

**The three name fields are locked on purpose.** The commit that did it,
`7c9ae9a0c`, says so: *"the name fields of the profile were set not only to
required, but also to disabled as the fields should not be overwritten in the
frontend."* They are typically owned elsewhere — an Active Directory or LDAP
backed `fe_user`, synchronised into the profile — which is also why
`skipSync` exists.

> **The trap.** A test, a script or a `curl` that posts `profileFormData[firstName]`
> and then asserts the record changed will find it unchanged, and it is very easy
> to read that as "the form is broken" or "the request detection does not work".
> It is neither. No browser sends that key, because the input is rendered
> `disabled`; the value only arrives because it was posted by hand, and rule 1
> then discards it exactly as intended. This has already been misdiagnosed once,
> while writing
> `packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileFormSubmissionTest.php`
> — whose first version asserted on the name fields for this reason. **When a
> transformation test needs an editable property, use one that the shipped set
> does not lock** — `website` and `websiteTitle` are the ones that test settled on.

### `- required` on `firstName` is inert

`AcademicPersonsSettingsFactory::normalizeValidations()` (`:99-109`) computes:

```php
$required = !$disabled && !$readOnly && in_array('required', $validators, true);
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

`disabled` therefore always implies `readOnly` for anything this factory
produces, so the `||` in rule 1 only distinguishes the two for a `Validation`
built by hand.

**The same configuration also drives the TYPO3 backend**, deliberately: all six
TCA tables merge in `getValidationTcaTableConfig()`, so a locked field is read
only in the record editor as well. That coupling — and the reason the settings
ship in `academic_persons` rather than in the edit extension — is documented in
[Validation settings](validation-settings.md).

## Overriding the set in an instance

An installation that wants the names editable ships its own `Settings.yaml`. The
mechanism has sharp edges — a shallow merge that replaces all six sets at once —
and is described in
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
- [Functional tests](../testing/functional-tests.md) — running the plugin
  rendering tests that exercise this path end to end.
- `packages/fgtclb/academic-persons-edit/Documentation/Changelog/2.4/Important-FormDataTransformationOnlyMapsSubmittedFields.rst`
  — the integrator-facing entry for rule 2.
- `packages/fgtclb/academic-persons/Configuration/AcademicPersons/Settings.yaml`
  — the shipped sets, and the only file that defines them.
