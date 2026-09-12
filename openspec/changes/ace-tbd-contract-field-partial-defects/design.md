## Context

`Resources/Private/Partials/Profile/Contract/Field.html` renders one contract
field per call. It opens the row whenever `{contract.{fieldName}}` is truthy
(`:6`) and prints `contracts.{fieldName}` as the label (`:8`). The value
branches (`:10-54`) cover `position`, `room`, `officeHours`, `location`, the two
contact collections and `physicalAddresses`. There is no branch for
`organisationalUnit`, although `Classes/Backend/FormEngine/ProfileShowFieldsItems.php:99-100`
offers it. `locallang.xlf` has no `contracts.organisationalUnit` unit either
(the `contracts.*` keys at `:15-33` stop at `physicalAddresses`), so the row
renders as `<b>:</b>` with nothing after it. The candidate claimed the label was
printed. It is not, and that is the second half of this defect.

`Field.html:29` writes `href="tel:{item.phoneNumber}"`. The detail view does
the right thing already
(`Partials/Profile/PublicProfile/Contact.html:47-54`, `f:replace` of `' '` by
`''`).

Branch `2` carries the same `Field.html`.

## Goals / Non-Goals

**Goals:**

- Render every item `showFields` offers; none of them may produce an empty row.
- Keep the partial's arguments (`fieldName`, `contract`) unchanged, so project
  overrides of the callers keep working.

**Non-Goals:**

- Normalising anything beyond spaces in the `tel:` target (brackets, dashes,
  a leading `0`); the detail view does not do it either, and both must agree.
- Deciding per number whether the configured prefix applies.

## Decisions

### An own branch for the organisational unit

The unit renders `{contract.organisationalUnit.displayText}` and falls back to
`unitName` through an `f:if` on the display text. `displayText` comes first
because it is the field meant for output; `unitName` is what an import fills
reliably.

Rejected: removing `contracts.organisationalUnit` from the `showFields` items.
The field is useful, and one project removes it only because it renders
nothing.

### Add the missing label keys

`contracts.organisationalUnit` is added to `locallang.xlf` and
`de.locallang.xlf`, one line per source and target and indented with two
spaces, like the neighbouring units.

Rejected: reusing the TCA label of the column. The partial translates from
`locallang.xlf` for every other field, and a second source would have to be
special-cased in the label line.

### Build the `tel:` target as the detail view does

The same `f:replace(value: item.phoneNumber, search: ' ', replace: '')` call,
with the same comment. Two places, one rule. A shared partial for a single
attribute value is more indirection than the duplication costs.

Rejected: normalising the number on save. It changes stored data that editors
and importers write for readers, and it would not repair existing records.

### Decided: a site setting prefixes every `tel:` target

A site setting `plugin.tx_academicpersons.phoneNumbers.telPrefix` (string,
default empty) is declared in `Configuration/Sets/Full/settings.definitions.yaml`
and repeated with the same default in
`Configuration/TypoScript/Default/constants.typoscript`, and mapped to
`settings.phoneNumbers.telPrefix`. Every `tel:` target of the persons
templates, in `Contract/Field.html` and in `PublicProfile/Contact.html`, is
the prefix followed by the stored number, with the spaces of both removed.
The visible link text keeps the stored spelling, without the prefix.

The prefix is unconditional. It serves an installation that stores phone
numbers as extensions only, where every number needs the same leading part
to be dialable. The rule stays one rule for both partials: prefix, then
remove spaces. Rejected: prepending the prefix only when the number has no
leading `+` or `0`. That is a heuristic both partials would have to agree
on, and it guesses at the number format instead of leaving it to the
integrator. Rejected as well: leaving the prefix to project overrides of the
two partials, which is the copy this change wants to remove.

`academic_contacts4pages` renders `Contract/Field.html` with the settings of
its own plugin block, which today receives only `detailPid` from the persons
constants (`academic-contact4pages/Configuration/TypoScript/List/setup.typoscript`).
Its setup maps the prefix the same way, so the contacts for pages output
uses the same targets.

## Risks / Trade-offs

- An installation that styled the empty `<b>:</b>` row away sees the unit
  appear → intended; the row was never meant to be empty.
- A project override of `Field.html` keeps the old output → nothing breaks,
  the project simply does not profit until it drops its copy.
- An installation that mixes full numbers and extensions sets the prefix →
  its full numbers get the prefix as well. The documentation says the prefix
  is meant for installations that store extensions only, and that such an
  installation leaves it empty.

## Open Questions

None.
