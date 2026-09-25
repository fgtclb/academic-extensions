## Context

See `proposal.md` for the motivation. On main:

- `Program/DemandCategories.html` and `Program/DemandSorting.html` submit the
  form of `Program/SortingAndFilters.html` (class
  `academic-programs-filtersorting`) with an inline
  `onchange="this.form.submit()"`; the form has no submit button.
- The list answers a POST of its demand with a `303` to the filter URL
  (`ace-723-list-filter-get-urls`), and renders uncached; the demand is not
  part of the cache hash.
- `Program/ItemList.html` renders the results; there is no wrapper that
  identifies one list element on a page with several.
- `academic_programs` ships no frontend module and no import map. The finder
  narrowing change `ace-tbd-finder-client-side-narrowing` plans the first one.
- Pull request #516 (ACE-91) replaced `#studyfinder-results` from a `fetch()`
  POST, removed the inline handlers without a replacement for a visitor
  without JavaScript, and did not update the address bar.

## Goals / Non-Goals

**Goals:**

- The same results and form as a reload of the filter URL, without the reload.
- A URL per selection, as today, in the address bar and in the history.
- Complete without JavaScript.

**Non-Goals:**

- The partner and project lists; a request per change avoided entirely.

## Decisions

### Submit the form as the browser would, and follow the redirect

The module sends the form data by `fetch()` POST to the form's action, as the
browser does. `fetch()` follows the `303`, so the response is the page of the
filter URL, and `response.url` is that URL with its cache hash. The server
stays the only place that normalises a selection and signs a URL.

Rejected: building the GET URL in the browser, which would duplicate
`createDemandArguments()` and cannot compute a cache hash; a JSON endpoint or
page type, which would duplicate routing, access and caching of the page.

### Replace the list element, found by its content element uid

The template marks the form and the results with the uid of the content
element (`data-academic-programs-list="<uid>"` on a wrapper). The module takes
the element with the same uid out of the response and replaces the form's
selects and the results region. The select that had focus gets it back.

Rejected: replacing the whole page body (loses scroll position and focus, and
touches other content elements); a fixed id as in #516 (two lists on a page).

### History

After an update the module calls `history.pushState()` with `response.url`
and remembers the uid in the state. `popstate` requests that URL with a GET
and replaces the list the same way. The initial entry gets the same state
through `replaceState()`.

### Failure falls back to a reload

A new change aborts a request still running (`AbortController`). A failed
request, or a response without the list element, leads to
`location.assign()` of the filter URL, or to a plain form submission when
there is none - the visitor gets the page, just not in place. That is also
what an override without the results region gets.

### No JavaScript, no inline handlers

The inline `onchange` handlers go; the form gets a submit button the module
hides with the `hidden` attribute when it takes over. A site with a strict
content security policy no longer has to allow inline event handlers for the
list.

### The announcement

A visually hidden `role="status"` element, empty on page load, receives
"<n> programs found" after an update; the count comes from an attribute of
the results region, the sentence from a translated label in a data attribute,
with a singular and a plural form.

### On by default, no setting

Every program list updates in place; there is no site setting to switch it
off. Without JavaScript nothing changes, and with it the list shows what the
reload showed, at the URL the reload had. A project that wants the reload
back overrides the form partial, which is also what keeps an existing
override on the reload. Decided by the maintainer on 2026-09-26.

Rejected: an opt-in site setting, off by default, which would add a setting,
its documentation and its tests for a behaviour no visitor can tell apart
from the reload except by its speed.

### Module and loading

`Resources/Private/TypeScript/frontend/program-list.ts`, free of `enum`,
`namespace`, parameter properties and decorators so `testJs` can run it,
loaded by `SortingAndFilters.html` through `f:asset.module`, and published
through `Configuration/JavaScriptModules.php`, which this change or the finder
narrowing change adds, whichever lands first.

## Risks / Trade-offs

- [A request per change] → The same server work as the reload it replaces,
  without the assets of a new page.
- [A project override of the form partials] → It keeps its inline handlers
  and reloads; a copy of the old results partial lacks the wrapper and falls
  back to a reload. The changelog says what to add.
- [Browser history grows with every change] → One entry per selection is what
  the reload produced as well.

## Migration Plan

None required. Projects with overrides of the list partials add the wrapper
and the submit button to get the in-place update; the `Important` changelog
entry names them.

## Open Questions

None.
