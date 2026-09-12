## Context

See `proposal.md` for the motivation. The finder of
`ace-tbd-program-finder-element` renders its selects server side, disables an
option no program in storage carries, and posts to the program list.

The repository discovers TypeScript sources per extension without any
configuration: `Build/extensions.mjs` finds `Resources/Private/TypeScript`
rather than listing it, and derives the import map prefix
`@fgtclb/<package directory>/frontend/` from the directory, the convention
every `Configuration/JavaScriptModules.php` follows. `academic_programs` has
neither a TypeScript directory nor an import map today.

## Goals / Non-Goals

**Goals:**

- Narrow with the data known when the page is rendered, without a request.
- Keep the module runnable by `testJs`: node strips types and does not
  transform them, so no `enum`, `namespace`, parameter properties or
  decorators.

**Non-Goals:**

- Mirroring subcategory matching before `programs-studyplan-08` lands; see
  Risks.

## Decisions

### One data attribute with a program-to-category map

The finder action adds `data-program-categories` to its form: a JSON object
mapping each program uid in storage to the uids of its categories, restricted
to the category types the finder offers. The module computes per option how
many programs carry it together with every other current selection (AND
between types, as the list filter does), disables options with zero, and
writes the count of the full selection into the submit button.

Rejected: a server round trip per change, through topwire or a JSON route.
It is the dependency the projects want to drop, and it needs a route and
caching decisions for a few kilobytes of data. Rejected as well: precomputing
every valid combination server side, which grows combinatorially with the
number of offered types.

### The map comes from the query the list uses

The uids come from the programs `ProgramRepository::findByDemand()` returns for
the finder's storage and settings. Hidden, access-restricted and out-of-storage
programs therefore never appear in the map, and the count agrees with what the
list then shows.

Rejected: a separate query on `sys_category_record_mm`. It would re-implement
the storage, recursion and visibility rules of the repository and drift from
them.

### The first module of the extension

- Source `Resources/Private/TypeScript/frontend/program-finder.ts`.
- Built output `Resources/Public/JavaScript/frontend/program-finder.js`,
  committed and checked by `checkJsBuildClean`.
- Specifier `@fgtclb/academic-programs/frontend/program-finder.js`, from a new
  `Configuration/JavaScriptModules.php` with the `core` dependency, shaped like
  the study plan's.
- The finder template loads it with `f:asset.module`, so it reaches only pages
  that carry a finder.
- The module exports its initialiser, so the jsdom test can run it on a
  fixture; the start on `DOMContentLoaded` stays.

The count label is a translated pattern handed over in a data attribute, so
the module carries no language of its own.

### Decided: narrowing plus the match count

The module disables impossible options, as ACE-91 asks, and also writes the
number of matching programs into the submit button, with the label as a
translated pattern. The count falls out of the same per-option computation
over `data-program-categories`, and the pattern already comes from a data
attribute. Without JavaScript the plain server label of the button remains.
The count mirrors the result count the listings change adds to the list
itself.

### Decided: the count is announced in a polite live region

The finder template renders a visually hidden element with `role="status"`
(`aria-live="polite"`), separate from the button, and the module writes the
same count sentence into it on every change. A count that changes with the
selection is a status message in the sense of WCAG 2.2 SC 4.1.3; a text
change inside the button is read only when the button gets focus, so without
the region a screen reader user never learns the result of a change. The
region stays empty on the initial render, so loading the page announces
nothing.

Guessed layout — a sketch, not a design:

```text
GUESSED  finder after selecting "Master"
+----------------------------------------------------------+
| Find your study program                                  |
| Degree   [ Master          v]   Interest [ All       v]  |
|                                   ( Robotics  disabled ) |
|                                  [ Show 4 programs -> ]  |
+----------------------------------------------------------+
```

## Risks / Trade-offs

- [Payload size for large program sets] → Only integer uids of the offered
  types are sent; a few hundred programs stay in the low kilobytes.
- [Semantics drift from the server filter] → When subcategory matching
  (`programs-studyplan-08`) lands, the map has to list the ancestors of each
  assigned category for the types where it is switched on. That change owns
  the adaptation and its test.
- [A screen reader announces every change] → The region is polite, so an
  announcement waits for the current speech and does not interrupt it.

## Open Questions

None.
