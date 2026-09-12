## Context

See `proposal.md` for the motivation. State on `main`:

- `Partials/Profile/List/AlphabetPagination.html` defines the letters a to z
  in an `f:variable`. Every letter except the active one is a link, the
  active one is a `span`, and `justify-content-center` is hard-wired on the
  `ul`.
- `Templates/Profile/List.html` renders the navigation whenever
  `settings.alphabetPaginationEnabled` is set, without looking at
  `demand.profileList`.
- `ProfileRepository::setFilters()` filters with
  `like('last_name', letter . '%')`. `applyDemandForQuery()` returns early
  for a manual selection, so the letter is ignored there.
- The profile table also has `last_name_alpha`, a read-only one-character
  column, which is the default `groupBy`.

## Goals / Non-Goals

**Goals:**

- A letter is a link exactly when selecting it yields a profile.
- The three existing issues stay separately committable.

**Non-Goals:**

- Changing the letter filter predicate.

## Decisions

### One query that reuses the list constraints

A new repository method builds the list query from the same demand with the
letter cleared. It reuses `applyDemandSettings()` and `setFilters()`, converts
the query with the core Extbase query parser into a DBAL query builder,
selects the distinct lower-cased first character of `last_name`, and orders
it (ACE-349 rule). The controller assigns the result as
`alphabetFilterLetters`, a map of letter to availability.

Rejected: computing letters from the rendered result, which is what one
project does. Under an active letter the result holds only that letter. Also
rejected: one count query per letter, which is 26 queries.

### The first character of `last_name`, not `last_name_alpha`

Availability must use the rule the filter uses, or a link can lead to an
empty page. The filter reads `last_name`, so the availability does too.

### Decided: both keep `last_name`

Neither the letter filter nor the availability switches to
`last_name_alpha`.

That column is written only by `DataHandlerHooks::setAlphaValuesForProfile()`
(lowercased first character, umlauts kept as they are). Switching would
change which letter a name starting with an umlaut falls under wherever
`LIKE` compares collation-insensitively, and it would need a backfill for
rows written outside DataHandler. A bugfix should not change the filter
predicate.

### Letter set moves to PHP

The a-to-z list moves from the partial into the controller, next to the
availability it belongs to. The partial iterates `alphabetFilterLetters`.

### No navigation for a manual selection

`List.html` renders the partial only when `demand.profileList` is empty, and
the controller skips the letter query in that case (ACE-599).

### `activeLetterResets` is a site setting

The option is declared in the aggregate set's settings definitions and in the
constants with the default `0`, and mapped to `settings.alphabet`.

Rejected: a FlexForm field. It would have to be added to both
`Core13/List.xml` and `Core14/List.xml`, and it is a site-wide style decision.

### Decided: both behaviours of the active letter, through the setting

Both behaviours are offered: with `activeLetterResets` at its default `0`,
the active letter is marked as current and not linked, as today; with `1`,
it links back to the list without a letter.

One analysed project and the ACE demo want opposite behaviour. The default
keeps today's output for the cost of one condition in
`AlphabetPagination.html`.

### Markup

The `nav` gets `d-flex justify-content-center` and a BEM class, and the `ul`
keeps `pagination`. Disabled letters render as `li.page-item.disabled` with a
`span.page-link` and `aria-disabled="true"`; the active letter carries
`aria-current="page"`. That is an `Important-` changelog entry for project
CSS.

## Risks / Trade-offs

- [One extra query per uncached rendering] → The list is cached with the
  page; the query is `DISTINCT` over one indexed-width expression.
- [DBMS comparison differences, where PostgreSQL compares case-sensitively
  unless the core expression builder adapts it] → Run the functional tests on
  PostgreSQL as well as SQLite, with mixed-case last names.
- [Overrides of the partial show no availability] → Named in the changelog.

## Open Questions

None.

Guessed layout — a sketch, not a design:

```text
<nav aria-label="Filter by last name">
[A-Z] [A] [B] (C) [D] ... [W] (X) (Y) [Z]     ( ) = disabled, no link
                 ^ active: aria-current, resets when activeLetterResets=1
```
