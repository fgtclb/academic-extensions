## Context

`packages/fgtclb/academic-contact4pages/Resources/Private/Templates/Contacts/List.html`
groups by role whenever `roles` is not empty (`:7-55`) and renders the
`Profile/Item` partial of `academic_persons` in three places (`:23-32` and
the two ungrouped loops). The partial paths are `5` for
`EXT:academic_persons/Resources/Private/Partials/` and `10` for this
extension's `Resources/Private/Partials/`, which the constants already point
at although the directory does not exist yet.

`ContactsController` is `final`, assigns `data`, `contacts`, `roles` and
`contactsWithoutRole`, and no `record`. `ContactRepository::findByPid()`
orders by `sorting` and `uid` (`ContactRepository.php:83-86`), so the flat
list already has the editor's order. The FlexForm
`Configuration/FlexForms/ContactsList.xml` holds only
`settings.showHiddenRecords` and has no Core13/Core14 split.

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

Rejected: an inverted flag (`settings.flatList`, default `0`), which would
keep the grouping without any TypoScript. The element cannot render without
the shipped setup anyway - it also brings the `academic_persons` partial path
`Profile/Item` is resolved from - and "Group by role", on by default, is the
wording an editor reads directly. The changelog entry names the line for an
installation that replaces the shipped setup with a copy of its own.

### One item partial `Contacts/Item.html`

It receives `contact`, `role`, `profile`, `contract`, `settings`, `data` and
`grouped`. By default it renders `Profile/Item` with the arguments
`List.html` passes today, including `groupedProfiles: 'true'` in the grouped
branch, so the default markup does not change. In the flat branch it renders
the role name above the profile item when `role` is set, in a `p` with the
class `academic-contacts4pages__role` — a new element with a class of the
extension's block, following `docs/architecture/overridable-partials.md`.
The contacts without a role below the groups take the flat branch without a
role, so they render exactly as before.

A project that overrides the constant `partialRootPath` (key `10`) with a
directory of its own — as the 3.0 changelog tells it to for the persons
partials — does not lose the shipped `Contacts/Item.html`: Extbase's
`ActionController::addDefaultPathToPaths()` puts
`EXT:academic_contacts4pages/Resources/Private/Partials/` as the
lowest-priority path whenever the configured paths do not contain it. The
existing test `anOverriddenProfilePartialReachesThisPluginToo` does exactly
that and keeps rendering all cards, so it pins the behaviour on both core
versions.

Rejected: a layout select with shipped card designs. The designs differ per
project anyway, and one overridable partial plus the switch covers all four
projects that replace `List.html`.

### The record through the academic_base trait

The controller uses `GetCurrentContentRecordMethodTrait` of `academic_base`
(already a dependency) and assigns `record`, as the jobs and bite jobs
controllers do. The controller stays `final`.

The element renders inside `lib.contentElement`, whose `Default` layout
already renders the header. A list template that renders `Header/All` as well
therefore only makes sense where the site package takes the header out of the
element's layout; otherwise the header renders twice (found in review). The
documentation says so, and names the `settings.defaultHeaderType` mapping the
partial needs for the header layout "Default", which plugin settings lack.

### Changelog placement

The `Feature-` entry lives in `Documentation/Changelog/2.4/` on `main` and on
branch `2` alike (maintainer decision): the option first ships with 2.4, and
3.0 contains it. The `Important-` entry about `record` is `main` only and
lives in `3.0/`.

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
