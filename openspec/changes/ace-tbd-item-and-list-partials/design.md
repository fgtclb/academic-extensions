## Context

The current files:

- `Resources/Private/Partials/Profile/Item.html` is one block:
  - the detail URI computation (`:6-36`);
  - the name, built as `{profile.firstName} {profile.middleName} {profile.lastName}`
    and handed to `Profile/Header` or `Profile/SectionHeader` (`:46`, `:58`);
  - the contracts (`:69-72`);
  - the unsized image (`:75-88`).
- `Templates/Profile/List.html:16-28` and
  `Partials/Profile/List/ItemList.html:9-65` hard-wire the group header, the
  grid, the pagination and the empty state (`:68`).
- `Card.html` and `SelectedProfiles.html` repeat the same row and column
  markup around `Profile/Item`. `SelectedContracts.html` does the same per
  contract.

`academic_contacts4pages` renders `Profile/Header` and `Profile/Item`
(`academic-contact4pages/Resources/Private/Templates/Contacts/List.html:12`,
`:24`, `:44`, `:62`) with `profile`, `contract`, `settings`, `data` and
`groupedProfiles`. Its `partialRootPaths` are `5` for the persons partials and
`10` for its own (`academic-contact4pages/Configuration/TypoScript/List/setup.typoscript:10-13`).
An override registered for `plugin.tx_academicpersons` therefore does not
reach it on its own.

## Goals / Non-Goals

**Goals:**

- Overriding one aspect of an item means overriding one file of a few lines.
- The same default markup, except for the title.

**Non-Goals:**

- Moving the persons templates to a different layout framework.

## Decisions

### Entry partials delegate, their arguments stay

`Profile/Item` renders `Profile/Item/{DetailLink,Name,Image,Contracts}`.
`Profile/List/ItemList` renders
`Profile/List/{GroupHeader,Items,ResultCount,EmptyState}`. `Pagination` and
`AlphabetPagination` keep their files; they only gain BEM classes. Existing
overrides of an entry partial keep working, they just do not profit.

Rejected: Fluid sections inside the existing files. An override still has to
copy the whole file.

### The detail link partial renders the URI only

`Profile/Item/DetailLink` returns the URI and is used inline through
`f:variable`. The header partials keep receiving `link`. An optional
`detailPid` argument wins over `settings.detailPid`, so a caller without plugin
settings (a project's own template, contacts for pages) can link.

Rejected: the partial rendering the whole `<a>`. The link is placed inside the
header partials' heading elements, which vary by layout.

### `Profile/List/Items` carries the grid

The item grid with its Bootstrap column classes lives in one partial, used by
`ItemList` (grouped and ungrouped), `Card.html` and `SelectedProfiles.html`.
`SelectedContracts.html` keeps its own loop, because it iterates contracts.

### The result count ships empty

`ResultCount` receives the number of profiles and renders nothing. Printing a
count by default would change every list. As an empty hook it removes one
project's template copy.

### Class names

Every new partial wraps its output in an `academic-persons-item__<part>` or
`academic-persons-list__<part>` class, next to the existing classes. No class is
removed; project stylesheets rely on `card`, `card-title` and `card-img-top`.

### The title in the name

`Profile/Item/Name` joins `title`, `firstName`, `middleName` and `lastName`,
skipping empty parts. This is the one output change, taken from the overrides
of two projects. It gets an `Important-*.rst`.

### Decided: the title is part of the name by default

The academic title is rendered by default, announced in the
`Important-*.rst`, and not behind an opt-in setting.

The detail headline already renders it (`Settings.yaml`
`profile.details.headline: [title, firstName, middleName, lastName]`), and
so does the alternative text of the detail image, so the list is the
inconsistent view. Two analysed projects override the item only to add the
title.

### Decided: card and selected profiles use only the grid partial

`Card.html` and `SelectedProfiles.html` render `Profile/List/Items`, and not
the group header or the empty state partials.

Neither template groups its profiles or has an empty state today; both loop
over `{profiles}` straight into `Profile/Item` (`Card.html:10`,
`SelectedProfiles.html:10`). Adding the other partials would change their
output without any project asking for it.

## Risks / Trade-offs

- A project overrides `Profile/Item` and a new sub-partial at once. → The
  sub-partial is never rendered. The documentation says to drop the item
  copy.
- Contacts for pages misses an override. → Documented. The functional test
  registers the fixture path for both plugins.
- Title duplication for projects that already add it. → They drop their
  override; the `Important-*.rst` names this case.

## Open Questions

None.
