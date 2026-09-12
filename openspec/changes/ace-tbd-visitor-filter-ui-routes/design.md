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
- `ace-tbd-visitor-filter-demand-query` provides the demand properties
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

Rejected: a GET form, as the candidate proposed. A browser-built GET URL
carries no cHash, so TYPO3 renders it uncached, or rejects it where cHash
validation is enforced. The Extbase hidden fields would also end up in the
URL. Also rejected: an inline `onchange` that navigates to precomputed option
URLs, as one project does, which fails without JavaScript and under a strict
CSP.

### Slug columns on both filter records

`slug` is added to both tables (`type => slug`, generated from
`function_name` or `unit_name`, `eval => uniqueInSite`). The routes map it
with `PersistedAliasMapper`. For a record without a slug the mapper cannot
generate, so the URI builder falls back to query parameters.

Rejected: `unique_name` of the organisational unit, which is not guaranteed
URL safe and does not exist on function types. Also rejected: the uid without
an aspect, which yields `/function/12?cHash=...`.

### Decided: a console command fills existing slugs, no upgrade wizard

A console command in the `academic:persons:*` namespace, registered in
`Services.yaml` like the existing persons commands, fills the slug of every
function type and organisational unit whose slug is empty. It generates each
slug with the core `SlugHelper` from the field's TCA, so it produces what a
save would, including `uniqueInSite`, and it leaves existing slugs untouched.
Running it twice changes nothing. The upgrade chapter and the changelog tell
integrators to run it once.

Without a migration the mapper falls back to query parameters for every
existing record until someone saves it, which may never happen. An upgrade
wizard is not added while the branch supports TYPO3 v13: each wizard is a
call site of the v15-blocking `Install\Attribute\UpgradeWizard` API
(ACE-294), and the same rule applies to every change of this round. A console
command is not such a call site. It is revisited with ACE-294.

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
  `ace-tbd-list-view-modes`, placed after the filter segments and before the
  page or letter.

That is eighteen explicit routes per enhancer. The filter key segments are
`LocaleModifier` aspects (for example `function` and `funktion`, `unit` and
`einheit`). Every variable gets explicit `requirements` of `[^/]+`, which
also keeps the two-segment filter routes apart from `/{profile_name}`.
Pagination is off under a letter today; the follow-up change that allows it,
decided with `ace-tbd-list-links-keep-state`, extends this route set with the
letter and page combinations.

### Decided: a letter combined with a filter is routed

Each of the three filter routes gets one explicit variant with `{letter}`,
without a page variant.

The one production filter among the analysed projects routes exactly a
filter segment followed by an optional letter. With pagination off under a
letter this is one route per filter route. It is explicit rather than an
optional segment, because Symfony omits only trailing defaults (ACE-623).

### Decided: the view mode segment combines with the filter routes

The `/view-mode/{viewMode}` route of `ace-tbd-list-view-modes` (a
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

## Risks / Trade-offs

- [Existing records have no slug until the command runs] → Their URL uses
  query parameters and still works; the upgrade chapter names the command.
- [Symfony omits only trailing defaults (ACE-623)] → Explicit routes per
  combination instead of optional segments; a routing test per route.
- [One more request per filter submission] → The redirect target is cached,
  and the POST itself is cheap.
- [Upgrade wizards are a TYPO3 v15 blocker (ACE-294)] → No wizard is added;
  the console command carries the migration.
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
