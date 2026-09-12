## Context

`packages/fgtclb/academic-contact4pages/Resources/Private/Templates/Contacts/List.html`
groups by role whenever `roles` is not empty (`:9-54`) and renders the
`Profile/Item` partial of `academic_persons` in three places (`:26-31` and
the two ungrouped loops). The partial paths are `5` for
`EXT:academic_persons/Resources/Private/Partials/` and `10` for this
extension's `Resources/Private/Partials/`, which the constants already point
at although the directory does not exist yet.

`ContactsController` is `final`, assigns `data`, `contacts`, `roles` and
`contactsWithoutRole`, and no `record`. `ContactRepository::findByPid()`
orders by `sorting` (`ContactRepository.php:73-74`), so the flat list already
has the editor's order. The FlexForm `Configuration/FlexForms/ContactsList.xml`
holds only `settings.showHiddenRecords` and has no Core13/Core14 split.

## Goals / Non-Goals

**Goals:**

- The grouping becomes an option without changing today's default output.
- A project changes the card by overriding one partial.

**Non-Goals:**

- Moving the grid wrapper (`col-12 col-md-6 …`) into the item partial. The
  projects override the card, not the grid.
- Any change to the page data processor.

## Decisions

### Checkbox plus TypoScript default

`settings.groupByRole` is a `check` field with `checkboxToggle`, default `1`,
and `plugin.tx_academiccontacts4pages.settings.groupByRole = 1` is set in
`Configuration/TypoScript/List/setup.typoscript`. A stored FlexForm without
the key then falls back to the TypoScript value, so existing content elements
keep grouping; a stored `0` still wins.

Rejected: a FlexForm default only. Existing records do not carry the key,
would read it as empty and lose the grouping.

### One item partial `Contacts/Item.html`

It receives `contact`, `role`, `profile`, `contract`, `settings`, `data` and
`grouped`. By default it renders `Profile/Item` with the arguments
`List.html` passes today, including `groupedProfiles: 'true'` in the grouped
branch, so the default markup does not change. In the flat branch it renders
the role name above the profile item when `role` is set.

Rejected: a layout select with shipped card designs. The designs differ per
project anyway, and one overridable partial plus the switch covers all four
projects that replace `List.html`.

### The record through the academic_base trait

The controller uses `GetCurrentContentRecordMethodTrait` of `academic_base`
(already a dependency) and assigns `record`, as the jobs and bite jobs
controllers do. The controller stays `final`.

### Template shape

The flat branch renders when `settings.groupByRole` is off or no contact has
a role. Both branches call the same item partial.

Guessed layout — a sketch, not a design:

```text
groupByRole = 0
+------------------+ +------------------+ +------------------+
| Dean             | | Contact person   | | Office           |
| [img] Dr. A. B.  | | [img] C. D.      | | [img] E. F.      |
| +49 ... | mail   | | +49 ... | mail   | | +49 ... | mail   |
+------------------+ +------------------+ +------------------+
```

## Risks / Trade-offs

- [A project's `List.html` override does not get the option] → The
  `Feature-` changelog entry says so and shows the item partial as the
  smaller override.
- [A project already ships a partial named `Contacts/Item.html` in a path
  above `10`] → Its partial would now be rendered for every contact. The
  changelog entry names the new partial so it can be checked.

## Open Questions

None.
