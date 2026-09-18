## Context

`Resources/Private/Partials/Profile/Contract/Field.html` is **byte-identical**
to the file on `main`. It opens the row whenever `{contract.{fieldName}}` is
truthy (`:6`) and prints `contracts.{fieldName}` as the label (`:8`). The value
branches cover `position`, `room`, `officeHours`, `location`, the two contact
collections and `physicalAddresses`. There is no branch for
`organisationalUnit`, although
`Classes/Backend/FormEngine/ProfileShowFieldsItems.php` offers it, and
`locallang.xlf` has no `contracts.organisationalUnit` unit either - so the row
renders as `<b>:</b>` with nothing after it.

`Field.html:29` writes `href="tel:{item.phoneNumber}"`, and so does
`Resources/Private/Templates/Profile/Detail.html:73`. On `main` the detail view
is a different, rebuilt template that strips the spaces already; here both
places carry the defect.

## Goals / Non-Goals

**Goals:**

- Render every item `showFields` offers; none of them may produce an empty row.
- Keep the partial's arguments (`fieldName`, `contract`) unchanged, so project
  overrides of the callers keep working.
- One rule for the `tel:` target in both places that build one.

**Non-Goals:**

- The phone link target prefix of the `main` change - a feature, not a defect.
- Normalising anything beyond spaces in the `tel:` target (brackets, dashes, a
  leading `0`).

## Decisions

### An own branch for the organisational unit

The unit renders `{contract.organisationalUnit.displayText}` and falls back to
`unitName` through an `f:if` on the display text. `displayText` comes first
because it is the field meant for output; `unitName` is what an import fills
reliably. Both properties exist on the model of this branch.

Rejected: removing `contracts.organisationalUnit` from the `showFields` items.
The field is useful, and one project removes it only because it renders
nothing.

### Add the missing label keys

`contracts.organisationalUnit` is added to `locallang.xlf` and
`de.locallang.xlf`. This branch indents its XLF with **tabs**, so the units are
written by hand rather than copied from `main`.

Rejected: reusing the TCA label of the column. The partial translates from
`locallang.xlf` for every other field, and a second source would have to be
special-cased in the label line.

### Strip the spaces of the `tel:` target in both templates

`f:replace(value: …, search: ' ', replace: '')`, with the same comment in both
places. A shared partial for a single attribute value is more indirection than
the duplication costs - the same reasoning the `main` change made for its two
places.

Rejected: normalising the number on save. It changes stored data that editors
and importers write for readers, and it would not repair existing records.

### A test class of its own for the shipped templates

`AcademicPersonsSelectedContractsPluginTest` loads `EXT:test_plugin_templates`,
whose simplified templates carry no contract fields at all, and this branch has
no card plugin test and no `FrontendPluginRenderingTrait`. The new class
therefore renders the shipped templates through the selected contracts plugin,
in the shape this branch's own plugin tests have (`InternalRequest`,
`writeSiteConfiguration()`).

`academicpersons_selectedcontracts` is a dedicated CType on v12 and v13 here,
and both `Configuration/FlexForms/Core12/SelectedContracts.xml` and its `Core13`
counterpart declare `settings.showFields`, so one fixture serves both core
versions.

## Risks / Trade-offs

- An installation that styled the empty `<b>:</b>` row away sees the unit
  appear → intended; the row was never meant to be empty.
- A project override of `Field.html` or of `Profile/Detail.html` keeps the old
  output → nothing breaks, the project simply does not profit until it drops
  its copy.

## Open Questions

None.
