## Context

The first workspace edit of a record versions its workspace aware inline
children in the same `DataHandler` command (`copyRecord_processRelation()`).
`copyRecord_raw()` records every new version in `copyMappingArray`, live uid
to version uid, and `remapListedDBRecords()` rewrites every relation field of
the new versions through that mapping: a `select` with `foreign_table` and a
`group`. The pointer of a child to its parent is such a field in ten places, so
the child version ends up pointing at the parent's version row, while every
other relation of a version names live uids. Publishing writes the version
into the live child and deletes the parent's version row.

What the published child holds depends on where the workspace is published
from. The workspace module publishes as a user in the workspace, and
`version_swap_processFields()` of the workspaces extension queues
`updateInlineForeignFieldSorting()`, which writes the live parent into the
swapped children through `writeForeignField()`. `workspace:autopublish` runs
as a user of the live workspace, and that repair does not happen. From the
module, a page and the role of its contacts or partnerships edited in the same
workspace still lose the page. The code path is the same on 13.4.36 and 14.3.9
(read and measured), and v12.4.45 has the same code (read only).

A second edit does not repair a lost pointer: the child no longer belongs to
the live parent, so it is not versioned with it again.

Measured with a probe that edits each parent in a workspace and publishes from
live, then copies and translates it, on both cores with identical results.
Published from the workspace module, every row is intact except the page of a
page contact or partnership whose role was edited in the same workspace:

| Child pointer                                   | Type                    | Published from live |
|-------------------------------------------------|-------------------------|---------------------|
| `address`, `email`, `phone_number` → `contract` | `select`, foreign_table | deleted version     |
| `contract.profile`                              | `select`, foreign_table | deleted version     |
| `contract.organisational_unit`                  | `select`, foreign_table | deleted version     |
| `profile_information.profile`                   | `select`, foreign_table | deleted version     |
| `contact.role`, `partnership.role`              | `select`, foreign_table | deleted version     |
| `contact.page`, `partnership.page`              | `group`, `pages`        | deleted version     |
| `contact.contract`                              | `select`, no table      | live parent         |
| `module.semester`, `semester.content_element`   | `passthrough`           | live parent         |

## Goals / Non-Goals

**Goals:**

- Each of the ten pointers keeps the live parent through a publish, on the
  first and on every later edit.
- Copy and translation keep giving the children the copied or translated
  parent.
- A new inline relation with a relation typed pointer cannot reintroduce the
  defect unnoticed.

**Non-Goals:**

- Repairing rows that are broken already (see proposal).
- Branch `2`.

## Decisions

### `dontRemapTablesOnCopy` with the parent table on each pointer

`remapListedDBRecords_procDBRefs()` skips every table listed in the field's
`dontRemapTablesOnCopy` (honoured on v13 and v14). The pointer is still
written by the parent's own inline relation: `remapListedDBRecords_procInline()`
calls `RelationHandler::writeForeignField()`, which writes the live parent uid
for a version (`useLiveParentIds`), the copied parent uid for a copy, and the
translated parent uid for a translation. The probe shows all three with the
setting in place, for every one of the ten pointers, and the second edit then
versions the children again.

Rejected:

- `passthrough` pointers. They are no relations to TYPO3 then, which removes
  the field from the forms where it is shown and the rows from the reference
  index.
- A hook that repairs the versions after the command, as ACE-481 does for the
  address selections. That selection is not written by an inline relation, so
  it needs one. The pointers are, so a hook would only undo what core should
  not have done.

### A TCA test per extension, through a shared trait

A trait in `packages-dev/testing-helper` collects every inline relation of
the loaded TCA whose child table belongs to the extension, and asserts that a
pointer typed `select` with `foreign_table`, or `group`, lists the parent table
in `dontRemapTablesOnCopy`. Each extension's test names the relations it
expects, so a relation that disappears from the discovery fails as well. The
four extensions with inline children use it: `academic_persons`,
`academic_contacts4pages`, `academic_partners` and `academic_study_plan`.

Rejected: one test over all extensions. No package loads every extension
for a test except the seed verification, and the check belongs next to the
TCA it checks.

### Behaviour tests per relation

Per extension, a data provided test edits each parent in a workspace,
publishes the whole workspace from the workspace module and from live, and
asserts the live child still points at the live parent, and a second test
copies each parent. A frontend render proves what a visitor sees for the
relations a plugin shows: the profile detail (contracts and their addresses),
the contacts list (page and role) and the partnerships of a page. The address
selection test of ACE-481 that asserted the stored selection only now asserts
the rendered page as well.

## Risks / Trade-offs

- [A copy of a child alone, without its parent, could have relied on the
  remap] → No: the remap only maps uids that were copied in the same command,
  so a child copied alone kept the parent before and keeps it now.
- [A future core changes `writeForeignField()`] → The behaviour tests run on
  both cores in every pull request and the nightly run.
- [A child translated before its parent in one command map] → With the
  setting it keeps the default language parent, before it was moved to the
  translated parent by the remap. Core already creates a stray translation of
  the translation in that order, and the backend never builds such a command
  map for these tables, so the order is left as it is.
- [Installations already hold broken rows] → The changelog entries give the
  queries to find them.
- [Tests that publish from one place only] → `publishWorkspace()` of the
  testing helper takes the place, and every publish test runs from both.

## Migration Plan

None: no schema change, and stored values keep their meaning.
