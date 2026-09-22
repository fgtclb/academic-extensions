## Context

The current files:

- `Resources/Private/Partials/Profile/Item.html` is one block:
  - the detail URI computation (`:6-36`);
  - the name, built as `{profile.firstName} {profile.middleName} {profile.lastName}`
    and handed to `Profile/Header` or `Profile/SectionHeader` (`:46`, `:58`);
  - the contracts (`:69-72`);
  - the image (`:75-102`): the shown-fields switch, and a render of the shared
    `Academic/Image` partial of `academic_base` with the `card` preset.

  Re-verified against `main` at `a38a1b1c7`. The analysis of 2026-09-12 called
  the image "unsized" and put it at `:75-88`; ACE-646 and ACE-710 have moved it
  to the shared partial since. That changes nothing about the split - the block
  becomes `Profile/Item/Image.html` either way - but the new partial passes the
  preset and the placeholder on rather than holding `f:image` itself.
- `Templates/Profile/List.html:16-28` and
  `Partials/Profile/List/ItemList.html:9-65` hard-wire the group header, the
  grid, the pagination and the empty state (`:68`).
- `Card.html` and `SelectedProfiles.html` repeat the same row and column
  markup around `Profile/Item`. `SelectedContracts.html` does the same per
  contract.

`academic_contacts4pages` renders `Profile/Header` and `Profile/Item`
(`academic-contact4pages/Resources/Private/Templates/Contacts/List.html:12`,
`:24`, `:44`, `:62`) with `profile`, `contract`, `settings`, `data` and
`groupedProfiles`. Its `partialRootPaths` are `-1` for the shared partials of
`academic_base`, `5` for the persons partials and `10` for its own
(`academic-contact4pages/Configuration/TypoScript/List/setup.typoscript:16-22`).
An override registered for `plugin.tx_academicpersons` therefore does not
reach it on its own.

It builds its own grid around the items (`:10`, `:22`, `:40`, `:42`, `:58`,
`:60`) and has no empty state at all, so the list partials of this change do not
reach it either. That is a deliberate boundary, not an oversight: its grid is
grouped by role, and it is documented on both sides.

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
`ItemList` (grouped and ungrouped), `Card.html`, `SelectedProfiles.html` **and**
`SelectedContracts.html`.

The original decision left the last one out, with its own loop, because it
iterates contracts. That would put the row and column classes in two files and
make an override of the grid reach three of the four elements while both carry
the same class. The partial takes an optional `contracts` argument instead and
iterates those when it is given some, handing each item its one contract; every
other caller passes `profiles` and is unaffected.

### The result count gets the total counted for it

`ItemList` passes `count` (the profiles rendered below, one page of a paginated
list) **and** `total` (what the list found). The total is counted with
`f:count()` rather than taken from the paginator because
`AbstractPaginator::getTotalAmountOfItems()` is `abstract protected` on v13 and
on v14 alike (`cms-core/Classes/Pagination/AbstractPaginator.php:102`), so Fluid
cannot reach it: `{paginator.totalAmountOfItems}` renders an empty string. The
paginator is passed as well, for its page number and page count, which are
public.

### The result count ships empty

`ResultCount` receives the number of profiles and renders nothing. Printing a
count by default would change every list. As an empty hook it removes one
project's template copy.

### Class names

Every new partial **that renders an element of its own** gets a stable class of
the `academic-persons-` family, next to the existing classes. No class is
removed; project stylesheets rely on `card`, `card-title` and `card-img-top`.

The block a class names is the block its element sits **in**, which is not the
partial's folder for two of them: `Profile/List/Items` and
`Profile/List/EmptyState` render inside the list, the card, the selected
profiles and the selected contracts alike, so they are blocks of their own,
`academic-persons-grid` and `academic-persons-empty-state`. A class is a
published contract from the release on, so this is decided now rather than in a
`Breaking-*.rst` later.

Four partials render no element and therefore get no class:
`Profile/Item/DetailLink` and `Profile/Item/Name` render text,
`Profile/Item/Contracts` delegates to `Profile/Contract/Item`, which owns the
list element and its classes, and `Profile/List/ResultCount` renders nothing.
Wrapping any of them would be a markup change, which is what the first
requirement forbids.

Where the class belongs on a heading a shared partial renders, it is passed
through that partial's existing `positionClass` argument - `card-title` becomes
`academic-persons-item__name card-title`, and the group header's empty
`class=""` becomes `academic-persons-list__group-header`. That keeps the change
to an attribute value.

### Decided: a value partial renders raw text

`Profile/Item/Name` and `Profile/Item/DetailLink` produce a value that the item
hands to `Profile/Header` or `Profile/SectionHeader` as an argument, and those
escape it once when they write it into their heading. A partial escapes its own
object accessors, so rendering the name through one and handing the result on
escapes it twice: `O'Neill` reaches the browser as `O&#039;Neill`.

Both partials therefore render their values through `f:format.raw`, say so in
their own comment, and are documented as text partials. (The markup of the
double escape is `O&amp;#039;Neill`; `O&#039;Neill` is the correct single one.)

Rejected: decoding the extra layer in the item.
`f:format.htmlentitiesDecode` maps `keepQuotes` to `ENT_NOQUOTES` or
`ENT_COMPAT` and cannot reach `ENT_QUOTES`, while Fluid's `EscapingNode`
escapes with `ENT_QUOTES` - the apostrophe survives the decode. Verified in
`cms-fluid/Classes/ViewHelpers/Format/HtmlentitiesDecodeViewHelper.php:72` and
`typo3fluid/fluid/src/Core/Parser/SyntaxTree/EscapingNode.php:46`.

Rejected: letting the header partials take pre-rendered markup. `Profile/Header`
renders eleven heading variants and `Profile/SectionHeader` ten,
`academic_contacts4pages` renders both through partial root paths of its own,
and the group header passes a database value that has to stay escaped.
(`academic_persons_edit` registers the persons partials at key `0` but ships its
own `Profile/Header.html` at key `10`, so it renders that one — it is not a
caller of these.)

The contract has a second half: such a partial may only be rendered as an
argument of something that escapes it, never in an output position, or the
stored profile values reach the browser unescaped. Both partials and the
`Templates` chapter of the extension say so.

### The title in the name

`Profile/Item/Name` joins `title`, `firstName`, `middleName` and `lastName`,
skipping empty parts. It is one of the three output changes - the classes and
the separator of a skipped part are the others - and the only one an editor
asked for: it is taken from the overrides of two projects. It gets an
`Important-*.rst`.

### Decided: the title is part of the name by default

The academic title is rendered by default, announced in the
`Important-*.rst`, and not behind an opt-in setting.

The detail headline already renders it (`Settings.yaml`
`profile.details.headline: [title, firstName, middleName, lastName]`), and
so does the alternative text of the detail image, so the list is the
inconsistent view. Two analysed projects override the item only to add the
title.

### Decided: card and selected profiles use the grid and the empty state

`Card.html` and `SelectedProfiles.html` render `Profile/List/Items` and
`Profile/List/EmptyState`, and not the group header.

The original decision left the empty state out, on the premise that neither
template has one. That premise is false: `Card.html:24-26` and
`SelectedProfiles.html:24-26` both render `<p>{f:translate(key:
'list.noProfilesFound')}</p>`, the same paragraph the list renders, and
`SelectedContracts.html:25-27` renders it with the contracts label. Keeping
three copies of it while shipping an empty-state partial would mean an
integrator's override reaches one of four elements.

`EmptyState` therefore takes an optional `key`, defaulting to the profiles
label; `SelectedContracts.html` passes the contracts one. The rendered markup
is the same paragraph plus the new BEM class.

Neither template groups its profiles, so the group header stays out of both.

## Risks / Trade-offs

- An item renders six partials where it rendered two. Fluid compiles partials,
  but each render still builds a variable provider, so a long list pays for it.
  Measured against the suite it is not visible; a list of some hundred profiles
  is where it would be. Accepted: the alternative is the copies this change
  exists to remove.
- `Profile/Contract/Item` receives `{_all}` and the variable in it that held the
  detail address is named `detailLink` now, not `detailUri`. Nothing shipped
  read it; a project copy of that partial could have. Named in the changelog.

- A project overrides `Profile/Item` and a new sub-partial at once. → The
  sub-partial is never rendered. The documentation says to drop the item
  copy.
- Contacts for pages misses an override. → Documented. The functional test
  registers the fixture path for both plugins.
- Title duplication for projects that already add it. → They drop their
  override; the `Important-*.rst` names this case.

## Open Questions

None.
