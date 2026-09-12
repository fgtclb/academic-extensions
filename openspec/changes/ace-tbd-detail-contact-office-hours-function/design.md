## Context

See `proposal.md` for the motivation. State on `main`:

- `Partials/Profile/PublicProfile/Contact.html` loops the contracts and
  renders e-mail, phone, postal addresses, location and room, each with an
  icon of `Configuration/Icons.php` (`academic-persons-envelope`, `-phone`,
  `-address`, `-room`). It renders no `officeHours`.
- `Partials/Profile/PublicProfile/Position.html` renders
  `{contract.position}` only.
- `Settings.yaml` `profile.details.position` is the map
  `{special: datasFromContracts}`.
- `Contract::getOfficeHours()` exists. The frontend editor field is
  `renderType: ckeditor` with the `html` validator, **but the backend TCA
  column `office_hours` is a plain `type => text` without an RTE** — the
  candidate assumed an RTE preset. Stored values are therefore HTML from the
  frontend editor, or plain text from the backend and imports.
- `FunctionType` has `functionName`, `functionNameMale` and
  `functionNameFemale`. The profile genders are `''`, `mr`, `ms` and
  `diverse`.
- `ProfileShowFieldsItems` already offers `contracts.officeHours` and
  `contracts.organisationalUnit`, but not `contracts.functionType`.
  `Partials/Profile/Contract/Field.html` renders scalar fields and a few
  relations; `ace-tbd-contract-field-partial-defects` fixes its relation
  handling.

## Goals / Non-Goals

**Goals:**

- The two rows every detail rewrite added, as configuration.

**Non-Goals:**

- Changing the backend TCA of `office_hours`.

## Decisions

### Office hours row: line breaks, then the core sanitizer

The row comes after location and room, with a new `academic-persons-clock`
icon registered like the others (currentColor provider, see
`docs/architecture/icons.md`) and the label `detail.officeHours`. The value is
rendered as `f:format.nl2br` followed by `f:sanitize.html`, both available on
v13 and v14. The editor stores CKEditor output without newlines between
blocks, so `nl2br` only affects plain-text values.

Rejected: `f:format.html`, which depends on a `lib.parseFunc_RTE` the site
package may not define. Also rejected: `f:format.raw`, which renders stored
markup unfiltered.

### Decided: office hours sit in the contact block

The office hours row is part of the detail contact block of each contract,
and not a public element of its own in `profile.structure`.

The ACE demo and the analysed projects that show office hours all put them
into the contact box of the contract, next to room and phone.
`PublicProfile/Contact.html` already loops over the contracts with one icon
per row.

### Position fields from Settings.yaml

`profile.details.position` gets `fields: [position]` in the shipped file.
The public profile normaliser accepts the list and keeps only the values
`position`, `functionType` and `organisationalUnit`; unknown values are
dropped. `Position.html` renders each configured value of a contract in its
own element, in order, inside the position line, and separates them with CSS.
With `ace-tbd-settings-per-field-merge` a project sets only this list.

Rejected: a new public element per field, which is too fine-grained for one
line and would need structure changes in every project layout.

### Decided: the organisational unit is allowed, the default stays `[position]`

`organisationalUnit` is an accepted value of
`profile.details.position.fields`, next to `position` and `functionType`.
The shipped default stays `[position]`.

Analysed projects render the unit name together with the function name, or
the unit together with the function type, in their contract line. An allowed
value that is not in the default costs one branch in `Position.html` and
changes no output.

### Gendered name in a shared partial

A partial `Profile/Contract/FunctionTypeName.html` switches on
`profile.gender` (`ms` female name, `mr` male name) and falls back to
`functionName` when the chosen name is empty. The detail position line and
`Field.html` both render it.

Rejected: a model getter. The contract would need its profile's gender, and
a method with an argument cannot be called from Fluid.

### Function type in "fields to show"

`ProfileShowFieldsItems` adds `contracts.functionType`, and `Field.html`
renders it through the shared partial. That depends on the relation handling
of `ace-tbd-contract-field-partial-defects`.

## Risks / Trade-offs

- [HTML office hours that contain newlines get an extra line break] →
  Accepted; covered by a test with CKEditor-shaped input.
- [Office hours appear on existing sites where data exists] → A visible
  change, announced in the `Feature-` changelog.
- [The sanitizer drops attributes a project relied on] → The default sanitizer
  build keeps links, emphasis and lists; the documentation names it.

## Open Questions

None.

Guessed layout — a sketch, not a design:

```text
Contact (GUESSED)
[@]  a.beispiel@ex.test
[T]  +49 6241 509-123
[#]  Building B, Room 1.02
[o]  Office hours: Tue 10-12, Thu 14-16   <- new
Professor - Dean of Studies (function)  <- position line
```
