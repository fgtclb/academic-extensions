## Context

See `proposal.md` for the motivation. State on `main`, re-verified before
implementing:

- `Partials/Profile/List/AlphabetPagination.html` defines the letters a to z
  in an `f:variable`. Every letter except the active one is a link, the
  active one is a `span` without `aria-current`, the `nav` has no accessible
  name, and `justify-content-center` sits on the `ul`.
- `Templates/Profile/List.html` renders the navigation whenever
  `settings.alphabetPaginationEnabled` is set, without looking at
  `demand.profileList`, and passes the partial nothing but `demand`.
- `ProfileRepository::setFilters()` filters with
  `like('last_name', letter . '%')`, which the Extbase query parser renders
  as `LIKE`, or `ILIKE` on PostgreSQL - identically on v13.4 and v14.3.
  `resolveDemandForQuery()` returns the selection constraint for a manual
  selection without ever calling `setFilters()`, so the letter is ignored
  there.
- Since ACE-715 every list query also passes through
  `ModifyProfileQueryEvent`, whose listeners add constraints that narrow the
  list. The issues ACE-597 to ACE-599 were written before that event
  existed.
- `last_name` is `l10n_mode => exclude`: a translation carries the last
  name of its default language record, so the language decides which
  records are visible, never their initial.

## Goals / Non-Goals

**Goals:**

- A letter is a link exactly when selecting it yields a profile.
- The three existing issues stay separately committable.

**Non-Goals:**

- Changing the letter filter predicate.

## Decisions

### One statement over the list's own query

A new public repository method takes the demand, clears its letter on a
clone, and builds the list query through the same private steps as
`findByDemand()`: `ModifyProfileDemandEvent`, the demand settings, the
filters, and `ModifyProfileQueryEvent` with the same plugin context. The
Extbase query parser turns that into a query builder the way core's own
`count()` does - order reset, workspace restriction added - and the select
list is replaced by one conditional aggregate per letter:
`MAX(CASE WHEN last_name LIKE 'x%' THEN 1 ELSE 0 END)`. The result is a map
of the 26 letters to a boolean, assigned as `alphabetFilterLetters`.

`MAX()` makes the row duplicates of the `contracts` joins irrelevant, and
without `GROUP BY` the statement always returns exactly one row, so an empty
list yields every letter `false` rather than no row.

Rejected: computing letters from the rendered result, which is what one
project does - under an active letter the result holds only that letter.
Rejected: one `count()` per letter, 26 statements, roughly 2 times slower on
PostgreSQL and up to 15 times on MariaDB in the measurements recorded in
ACE-597; it stays the documented fallback
should the parser dependency below ever become a problem, since it uses
public API only and gives the same result. Rejected: a common table
expression around the list query. It is supported on every DBMS the branch
supports and was measured equally fast, but it needs the inner builder's
parameters copied onto the outer one, and the aggregate on the list query
itself is literally core's count query with another select list - which is
the strongest parity argument available.

The parser is `@internal` to Extbase. It is used exactly the way core's
`Typo3DbBackend::getObjectCountByQuery()` uses it, in one method, with the
same signature and semantics on v13.4 and v14.3 (compared on v13.4.34,
v13.4.35 and v14.3.7). It is the only source
of the list's language statement, visibility sub-selects, storage pids and
joins that does not duplicate them.

### The filter's own predicate, not a first character

Availability uses the predicate of the filter itself - `LIKE`, `ILIKE` on
PostgreSQL, against `last_name` - rather than a `DISTINCT` over the first
character. The two differ by collation: MariaDB with `utf8mb4_unicode_ci`
answers `'Özil' LIKE 'o%'` with true and lists the person under O, while
PostgreSQL's `ILIKE` does not fold umlauts and SQLite folds ASCII case only.
A first character normalised in PHP would disagree with the list on some
DBMS, and a link would lead to an empty page.

For the same reason neither the filter nor the availability switches to
`last_name_alpha`. That column is written only by
`DataHandlerHooks::setAlphaValuesForProfile()`, keeps umlauts, and would need
a backfill for rows written outside DataHandler.

### The letter set lives in PHP, with a fallback in the partial

The letters a to z move into a constant next to the availability they belong
to; they match the `StaticRangeMapper` of the route enhancers. The partial
iterates `alphabetFilterLetters`.

A project that overrides `List.html` but not the partial calls it with
`demand` alone. So the partial keeps its own a-to-z map for exactly that
case and renders every letter as a link, as today (ACE-598: templates that
do not pass the new variable keep today's output).

### No navigation for a manual selection

`List.html` renders the partial only when `demand.profileList` is empty, and
the controller computes no letters then (ACE-599). The repository method
still answers a demand with a selection - every letter `true`, which is what
the list does with a letter there - so a caller outside the plugin gets a
result that matches `findByDemand()`.

Rejected: a `displayCond` hiding the FlexForm checkbox when a selection is
set. It would be a FlexForm change in both `Core13/List.xml` and
`Core14/List.xml` for a field whose value is now simply without effect.

### `activeLetterResets` is a site setting

The option (ACE-718) is declared in the aggregate set's settings definitions
and in the constants with the default `0`, and mapped to `settings.alphabet`.
With the default the active letter is marked as current and not linked, as
today; with `1` it links back to the list without a letter. One analysed
project and the ACE demo want opposite behaviour, and the default keeps
today's output.

Rejected: a FlexForm field. It would have to be added to both FlexForm
variants, and it is a site-wide style decision.

### Markup

- The `nav` gets an `aria-label` from `locallang.xlf`, in English and German.
- The "A-Z" item is `active` and carries `aria-current="page"` while no
  letter is selected.
- The active letter is `li.page-item.active` with a `span.page-link` carrying
  `aria-current="page"`, or, when the option is on, a link back carrying
  `aria-current="page"` and a visually hidden "show all profiles" - without
  it a screen reader announces the current letter as a link and says nothing
  of where it leads.
- A letter without profiles is `li.page-item.disabled` with a
  `span.page-link` and a visually hidden "no profiles". No element with
  `href`: not focusable, not clickable, not crawled.

Rejected: `aria-disabled="true"` on the `span`. ARIA 1.2 allows the attribute
on widget roles only; on an element without a role it is ignored or flagged.
The hidden text says why the letter is not a link.

Rejected, reversing the earlier plan: moving `justify-content-center` from
the `ul` to the `nav`. The stated benefit was that a project could change the
alignment with CSS instead of an override. Bootstrap's utility classes are
`!important`, so a CSS override needs `!important` and a selector of its own
wherever the class sits - moving it gains nothing and changes markup for
every installation. The one project affected removes the class, and still
has to.

## Risks / Trade-offs

- [One extra statement per uncached rendering] → Only when the navigation is
  on and no selection is set. The list is cached with the page, and
  `profile_list_view` already flushes it on every profile save.
- [`ModifyProfileQueryEvent` and `ModifyProfileDemandEvent` are dispatched
  twice per rendering] → Documented for listener authors: a listener must
  answer both calls the same way, which a listener that only adds
  constraints does anyway.
- [A listener of `ModifyListProfilesEvent` that replaces the result is not
  reflected in the letters] → Documented in the method and the changelog.
- [In a workspace preview a profile deleted or hidden only in the workspace
  still enables its letter] → The same precision as the list's own
  pagination count, which is SQL only as well; live is exact. Documented.
- [DBMS comparison differences] → Parity tests compare every letter with the
  list's own result and run on SQLite and PostgreSQL locally and on the full
  DBMS matrix in CI.
- [Overrides of the partial show no availability] → Named in the changelog,
  with the variable to read.

## Differences from the issues

Recorded before implementing, as task 1.1 asks:

- ACE-597 predates `ModifyProfileQueryEvent` (ACE-715). The letter query goes
  through it as well, or a narrowing listener leaves letters enabled that
  lead to an empty list.
- ACE-597 prefers the common table expression shape; the aggregate on the
  list query is one of its accepted equivalents and is chosen here, see
  above.
- ACE-598 renders the "A-Z" item `active` while no letter is selected; this
  change follows the issue. Its markup has no reset option; the option is
  this change's addition from the analysis and was filed as ACE-718, with a
  commit of its own.
- ACE-599 offers hiding the FlexForm checkbox through a `displayCond` as an
  alternative; this change only stops rendering the navigation, see *No
  navigation for a manual selection*.
