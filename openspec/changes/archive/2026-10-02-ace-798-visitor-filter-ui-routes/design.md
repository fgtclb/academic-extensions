## Context

See `proposal.md` for the motivation. State on `main`:

- `Configuration/Routes/List.yaml` (`ProfileListPlugin`) and
  `ListAndDetail.yaml` (`ProfileListAndDetailPlugin`) route
  `{localized_page}-{page}` and `/{letter}`, and ListAndDetail also routes
  `/{profile_name}` through a `PersistedAliasMapper` on the profile `slug`.
- **The candidate assumed a slug on the filter records. There is none:** the
  TCA of `tx_academicpersons_domain_model_function_type` has
  `function_name`, `function_name_female`, `function_name_male` and
  `import_identifier`, and `tx_academicpersons_domain_model_organisational_unit`
  has `unit_name`, `unique_name`, `display_text`, `long_text` and
  `import_identifier`. Neither has a `slug` column.
- `ace-779-visitor-filter-demand-query` provides the demand properties
  `functionTypeFilter` and `organisationalUnitFilter` and the view variable
  `filterOptions`.
- Route enhancers are never loaded automatically, and an aspect makes a path
  variable greedy unless `requirements` pin it.

## Goals / Non-Goals

**Goals:**

- A filter that works without JavaScript and under a strict CSP, and a
  cacheable filtered list URL.

**Non-Goals:**

- A page variant under an active letter.

## Decisions

### POST to a filter action, redirect to the list URL

The partial `Profile/List/Filter.html` renders an Extbase form that posts to a
new non-cacheable `filter` action of the list and listanddetail plugins. The
action validates the choices against `filterOptions` and answers with a 303
redirect to the list URL, which the URI builder generates with a cHash or as
a routed URL.

The list keeps its demand in the cHash, so the redirect signs whatever it
carries. Every carried value is therefore limited first: the filters to their
options, the view mode to the allowed modes, the letter to the letters of the
navigation, and the page is dropped. A hand-made submission gets no signed URL
the list would not link itself.

The redirect is thrown as a `PropagateResponseException`. A returned redirect
reaches the browser on TYPO3 v13 only through `header()`, while the page
renders all the same.

Rejected: a GET form, as the candidate proposed. A browser-built GET URL
carries no cHash, so TYPO3 renders it uncached, or rejects it where cHash
validation is enforced. The Extbase hidden fields would also end up in the
URL. Also rejected: an inline `onchange` that navigates to precomputed option
URLs, as one project does, which fails without JavaScript and under a strict
CSP.

### Slug columns on both filter records

`slug` is added to both tables (`type => slug`, generated from
`function_name` or `unit_name`, `eval => unique`). It is not an exclude
field: the DataHandler generates the slug of a new record only when the user
may write the field.

The routes map it with `PersonsFilterSlugMapper`, a `PersistedAliasMapper`
that returns no segment for an empty slug or one with a slash. The core mapper
returns such a slug as it is stored, and Symfony's URL generator then throws an
`InvalidParameterException` for the requirement of the route, which
`PageRouter` does not catch. Without a segment the route is skipped and the URI
builder falls back to query parameters.

The frontend user synchronisation creates function types and units through
Extbase, which knows no slug, so it generates the slug itself right after it
persisted the record.

Each translation has a slug of its own, generated from its translated name,
so a German list reads `/funktion/professorin`. `unique` applies per
language, and the mapper resolves a slug in the language of the request and
its fallbacks.

Changed from `uniqueInSite` during implementation: with `uniqueInSite` the
mapper keeps only records stored inside the site that resolves the URL
(`SiteAccessorTrait::filterContainedInSite()`), while generation does not
check the site. A list whose records live in a folder of another site would
link to a URL that answers 404. `unique` makes the slug unambiguous across the
installation, so the mapper never has to choose.

Rejected: `unique_name` of the organisational unit, which is not guaranteed
URL safe and does not exist on function types. Also rejected: the uid without
an aspect, which yields `/function/12?cHash=...`.

### Decided: a repeatable upgrade wizard fills existing slugs

A repeatable upgrade wizard, `academicPersons_fillFilterSlugs`, fills the slug
of every live function type and organisational unit whose slug is empty. It
writes through the DataHandler with an empty slug, which makes the DataHandler
generate the slug from the name with the field's TCA, so it produces exactly
what a save would, including `unique`. Existing slugs are untouched. It
requires the database update first.

It is repeatable because a record can lose its slug after the upgrade: a
workspace version made before it is published with an empty slug, and an
import that writes the table directly leaves it empty. The wizard is offered
again whenever a live record has no slug, and changes nothing otherwise.

The first draft chose a console command, because every wizard is a call site
of the v15-blocking `Install\Attribute\UpgradeWizard` API (ACE-294). The
maintainer decided for the wizard: the upgrade module is where an integrator
looks after an update, and the call site is one more for ACE-294 to migrate.

Rejected: filling slugs only on save, which leaves the fallback in place
indefinitely.

### Route set

Both enhancers get these routes:

- function type;
- organisational unit;
- both filters;
- each of these with `{localized_page}-{page}`;
- each of these with `{letter}`, without a page variant;
- each of the nine above with the `/view-mode/{viewMode}` segment of
  `ace-735-list-view-modes`, placed after the filter segments and before the
  page or letter.

That is eighteen explicit routes per enhancer. The filter key segments are
`LocaleModifier` aspects (for example `function` and `funktion`, `unit` and
`einheit`). Every variable gets explicit `requirements` of `[^/]+`.
`/{profile_name}` of ListAndDetail has no requirement and matches any path. It
stays apart from the filter routes because its mapper finds no profile for a
path like `function/professor`, and the matcher then tries the next route.
Pagination is off under a letter today; the follow-up change that allows it,
decided with `ace-734-list-links-keep-state`, extends this route set with the
letter and page combinations.

### Decided: a letter combined with a filter is routed

Each of the three filter routes gets one explicit variant with `{letter}`,
without a page variant.

The one production filter among the analysed projects routes exactly a
filter segment followed by an optional letter. With pagination off under a
letter this is one route per filter route. It is explicit rather than an
optional segment, because Symfony omits only trailing defaults (ACE-623).

### Decided: the view mode segment combines with the filter routes

The `/view-mode/{viewMode}` route of `ace-735-list-view-modes` (a
`StaticValueMapper` on the shipped modes) extends this route set: every
filter route, with page or letter or alone, also exists with the view mode
segment. Whichever of the two changes lands second adds these combinations
and their routing tests.

A visitor who switched the list to the table and then filters it keeps the
table, and that list needs a URL like every other. Leaving the combinations
out would fall back to query parameters exactly where both features are in
use.

### Extend the shipped enhancer key

The documentation shows extending `routeEnhancers.ProfileListPlugin` in the
site configuration. In one project, a second enhancer on the same plugin is
what broke its routing.

### The lists flush when a filter record changes

The options of the form are read when the list is cached. A DataHandler run
that writes a function type or an organisational unit, a save, a hide, a
delete, a restore, a copy, a move or a translation, flushes the
`profile_list_view` tag once, from the datamap and from the command map.
Records the frontend user synchronisation creates through Extbase flush
nothing, as the profiles it writes do not.

## Risks / Trade-offs

- [Existing records have no slug until the wizard runs] → Their URL uses
  query parameters and still works. The upgrade chapter names the wizard.
- [Symfony omits only trailing defaults (ACE-623)] → Explicit routes per
  combination instead of optional segments; a routing test per route.
- [One more request per filter submission] → The redirect target is cached,
  and the POST itself is cheap.
- [Upgrade wizards are a TYPO3 v15 blocker (ACE-294)] → One more call site
  for ACE-294, accepted for the upgrade module.
- [Eighteen routes per enhancer] → Each gets a generation and a resolution
  test; the number follows from ACE-623, not from a choice.

## Open Questions

None.

Guessed layout — a sketch, not a design:

```text
+------------------------------------------------------------+
| Function [ all functions      v]  Unit [ all units     v]  |
|                                              [ Filter ]    |
+------------------------------------------------------------+
[A-Z] [A] [B] ...
```
