## Context

See `proposal.md` for the motivation. On main:

- `Program/DemandCategories.html` and `Program/DemandSorting.html` submit the
  form of `Program/SortingAndFilters.html` (class
  `academic-programs-filtersorting`) with an inline
  `onchange="this.form.submit()"`, and the form has no submit button.
- The list answers a POST of its demand with a `303` to the filter URL
  (`ace-723-list-filter-get-urls`), and renders uncached. The demand is not
  part of the cache hash.
- `Program/ItemList.html` renders the results. There is no wrapper that
  identifies one list element on a page with several.
- `academic_programs` ships one frontend module, the finder module of
  `ace-91-finder-client-side-narrowing`, and the import map it added.
- Pull request #516 (ACE-91) replaced `#studyfinder-results` from a `fetch()`
  POST, removed the inline handlers without a replacement for a visitor
  without JavaScript, and did not update the address bar.

## Goals / Non-Goals

**Goals:**

- The same results and form as a reload of the filter URL, without the reload.
- A URL per selection, as today, in the address bar and in the history.
- Complete without JavaScript.

**Non-Goals:**

- The partner and project lists.
- Avoiding a request per change entirely.

## Decisions

### Submit the form as the browser would, and follow the redirect

The module sends the form data by `fetch()` POST to the form's action, as the
browser does. `fetch()` follows the `303`, so the response is the page of the
filter URL, and `response.url` is that URL with its cache hash. The server
stays the only place that normalises a selection and signs a URL.

Rejected: building the GET URL in the browser, which would duplicate
`createDemandArguments()` and cannot compute a cache hash, and a JSON endpoint
or page type, which would duplicate routing, access and caching of the page.

### Replace the list element, found by its content element uid

`Program/List.html` wraps the list in an element carrying the uid of the
content element (`data-academic-programs-list="<uid>"`). Inside it, one region
(`data-academic-programs-list-content`) holds the form, the active filters,
the result count and the results, and carries the number of programs it shows.
The module takes each list of the page out of the response by its uid and
replaces its region as a whole. The select that had the focus gets it back,
found by its name, and a "More filters" disclosure the visitor opened stays
open.

Every list of the page is replaced, not only the one the visitor changed. All
program lists are the same plugin and read their demand from the same
arguments, `tx_academicprograms_programlist[demand]`, so the filter URL filters
every list of the page, and a reload of it shows them all filtered. Replacing
only the changed list would show a state no URL has, and back and forward,
which replace every list, would show the same entry in two ways. A list per
selection would need the demand scoped per content element on the server,
which is a change of its own. A list that hides its filter and its sorting
reads the same arguments and is replaced as well. Only the changed list
announces its number, and on back and forward only the first list replaced,
so a screen reader hears one sentence.

One region rather than the selects and the results separately: the active
filters and the result count change with the selection too, and the active
filters are not rendered at all without a selection, so a region per part
would need placeholders for parts that may be missing.

Rejected: replacing the whole page body (loses scroll position and focus, and
touches other content elements), and a fixed id as in #516 (two lists on a
page).

### The form is found by the region, not by its class

The module drives the form inside the content region, and a form outside it
that carries `data-academic-programs-list-form` or whose selects carry
`data-academic-programs-list-select`. It does not look the form up by its
class, as the rule of `docs/development/frontend-assets.md` says. The region
covers an override of `Program/SortingAndFilters.html` written before this
change, whose form has the class but not the attribute, and the attributes
cover a form outside the region, see "Overrides".

### History

After an update the module calls `history.pushState()` with `response.url`
and marks the entry in the state as its own (`academicProgramsList: true`).
`popstate` on a marked entry requests that URL with a GET and replaces every
list of the page with the ones of the response. The initial entry gets the
same mark through `replaceState()`. An entry with the URL already shown, which
a fragment link adds, requests nothing.

### Failure falls back to a reload

A new change aborts a request still running (`AbortController`). There is
one request at a time for the page, since all lists share one address bar. A
failed request, an error status, or a response without the changed list
leads to a plain form submission (`form.submit()`), which posts the same data
again and lands on the filter URL. The visitor gets the page, just not in
place. Going back or forward to an entry whose page cannot be requested
reloads it.

`location.assign()` of the filter URL, as first planned, was dropped: the
submission reaches the same page, works when there is no response URL at all,
and is the one navigation the test harness can observe with its target.

### No JavaScript, no inline handlers

The inline `onchange` handlers go. The form gets a submit button in a column
the module hides with the `hidden` attribute when it takes over. The column
rather than the button, because a theme rule that gives `.btn` a `display`
beats the user agent rule behind `hidden`, and the hidden column leaves no
gap in the grid. A site with a strict
content security policy no longer has to allow inline event handlers for the
list.

### Overrides

- A select that keeps an inline `onchange` handler, from an override of a
  filter partial, is left to that handler. The decision is per select, not per
  form: with one of the two filter partials overridden, the selects of the
  other one carry no handler and are driven as usual. The module hides the
  submit button either way.
- An override of `Program/List.html` without the wrapper or the region: the
  form carries `data-academic-programs-list-form`, and the module submits it on
  a change and reloads the page, as the inline handlers did.
- An override of `Program/SortingAndFilters.html` from before: its form has no
  button and does not load the module, while the new filter partials have no
  inline handlers any more. `Program/List.html` therefore loads the module as
  well, and the form sits in the region, so it is updated in place.
- An override of both: neither loads the module, and the form is outside any
  region and unmarked. The filter partials therefore load the module too, and
  their selects carry `data-academic-programs-list-select`, so the module
  submits that form on a change.

### The announcement

A visually hidden `role="status"` element, empty on page load, receives
"<n> programs found" after an update. The number comes from
`data-academic-programs-list-total` of the content region, the sentence from
the labels the result count already uses (`list.resultCount.singular` and
`.plural`), in `data-academic-programs-list-count-one` and `-count-other` of
the wrapper, named like the patterns of the program finder. The status
element sits outside the region, so it is the same element before and after
the update, which a screen reader needs to hear the change.

A sorting change that keeps the number writes the same sentence again, which
some screen readers do not repeat. Forcing a repeat (clearing the element and
writing it a moment later) was left out: it depends on the screen reader and
was not verified with one.

### On by default, no setting

Every program list updates in place, and there is no site setting to switch it
off. Without JavaScript nothing changes, and with it the list shows what the
reload showed, at the URL the reload had. A project that wants the reload
back overrides `Program/DemandCategories.html` and `Program/DemandSorting.html`
with the inline handlers, which is also what keeps an existing override of
them on the reload. Decided by the maintainer on 2026-09-26.

Rejected: an opt-in site setting, off by default, which would add a setting,
its documentation and its tests for a behaviour no visitor can tell apart
from the reload except by its speed.

### Module and loading

`Resources/Private/TypeScript/frontend/program-list.ts`, free of `enum`,
`namespace`, parameter properties and decorators so `testJs` can run it,
loaded by `List.html`, `SortingAndFilters.html`, `DemandCategories.html` and
`DemandSorting.html` through `f:asset.module` whenever the form renders (the
asset collector loads it once), and published
through `Configuration/JavaScriptModules.php`, which the finder narrowing
change added.

### The test harness

jsdom performs no navigation to another document: `form.submit()`,
`location.reload()` and `location.assign()` only report "not implemented".
The harness models `form.submit()` as a count on the form and records
reloads from the jsdom console, and its request double gains the response
URL of a followed redirect and the abort signal.

## Risks / Trade-offs

- [A request per change] → The same server work as the reload it replaces,
  without the assets of a new page.
- [A project override of the list templates] → It keeps its inline handlers
  and reloads, or falls back to a reload without the region, see
  "Overrides". The changelog says what to add.
- [Browser history grows with every change] → One entry per selection is what
  the reload produced as well.

## Migration Plan

None required. Projects with overrides of the list partials add the wrapper
and the submit button to get the in-place update. The `Important` changelog
entry names them.

## Open Questions

None.
