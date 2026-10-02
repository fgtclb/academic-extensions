## Context

See `proposal.md` for the motivation. The finder of
`ace-91-program-finder-element` renders its selects server side, disables an
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

- Subcategory matching beyond the list, see Risks.

## Decisions

### One data attribute with the categories of each program

The finder action adds `data-academic-programs-finder-programs` to its form:
a JSON list with one entry per program in storage, the uids of its categories,
restricted to the category types the finder offers. The uids of the programs
are left out. The module does not need them, and they differ between the two
page trees of the development seed that `LegacyDeliveryTest` compares. The
attribute names follow the `data-<extension>-*` rule of
`docs/development/frontend-assets.md`, and only the selects marked with
`data-academic-programs-finder-select` take part, so a select an override adds
for something else is left alone. The module computes per option how many
programs carry it together with every other current selection (AND between
types, as the list filter does), disables options with zero, and writes the
count of the full selection into a span inside the submit button, which leaves
an icon of an override in the button alone.

Rejected: a server round trip per change, through topwire or a JSON route.
It is the dependency the projects want to drop, and it needs a route and
caching decisions for a few kilobytes of data. Rejected as well: precomputing
every valid combination server side, which grows combinatorially with the
number of offered types.

### The programs come from the query the list uses

The uids come from the programs `ProgramRepository::findByDemand()` returns for
the finder's storage and settings, as `ModifyProgramListEvent` hands them back.
Hidden, access-restricted and out-of-storage programs therefore never appear in
the list, and a program a listener removes does not either. The count agrees
with what the list then shows as long as the finder and the list share the
storage and the field Include subcategories, the condition the options of the
finder already depend on. The list is ordered by program uid, so it does not
depend on the sorting of the element.

Rejected: a separate query on `sys_category_record_mm`. It would re-implement
the storage, recursion and visibility rules of the repository and drift from
them.

### The first module of the extension

- Source `Resources/Private/TypeScript/frontend/program-finder.ts`.
- Built output `Resources/Public/JavaScript/frontend/program-finder.js`,
  committed and checked by `checkJsBuildClean`.
- Specifier `@fgtclb/academic-programs/frontend/program-finder.js`, from a new
  `Configuration/JavaScriptModules.php` with the `core` dependency, shaped like
  the study plan's, unless `ace-tbd-program-list-results-in-place` landed
  first and added it. The specifier prefix is the same for both modules.
- The finder template loads it with `f:asset.module`, so it reaches only pages
  that carry a finder.
- The module exports its initialiser, so the jsdom test can run it on a
  fixture, and the start on `DOMContentLoaded` stays.

The count label is a pair of translated patterns, `finder.submit.count.one`
and `finder.submit.count.other` with `%d` for the number, handed over in two
data attributes, so the module carries no language of its own. One pattern
would read "Show 1 programs".

### Decided: narrowing plus the match count

The module disables impossible options, as ACE-91 asks, and also writes the
number of matching programs into the submit button, with the label as a
translated pattern. An option the server rendered disabled stays disabled, and
the option a select shows is never disabled, so a preselection whose categories
exclude each other can still be changed. The count falls out of the same
per-option computation over `data-academic-programs-finder-programs`, and the
pattern already comes from a data attribute. Without JavaScript the plain server
label of the button remains. The count mirrors the result count the listings
change adds to the list itself.

### Decided: narrowing disables, whatever the site hides

`settings.filter.hideDisabledOptions` decides which options the server renders,
and it leaves out the ones no program in storage carries. Narrowing in the
browser only disables an option the current selection excludes. Leaving it out
would change the length of a select while the visitor works through the form,
and `hidden` on an `<option>` is ignored by Safari, so it would mean removing
and inserting options again. An option the server rendered disabled stays
disabled, and the option a select shows is never disabled.

### Decided: the count is announced in a polite live region

The finder template renders a visually hidden element with `role="status"`
(`aria-live="polite"`), separate from the button, and the module writes the same
count sentence into it whenever the count changes. A count that changes with the
selection is a status message in the sense of WCAG 2.2 SC 4.1.3, and a text
change inside the button is read only when the button gets focus, so without the
region a screen reader user never learns the result of a change. The region
stays empty on the initial render, so loading the page announces nothing.

Guessed layout, a sketch rather than a design:

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
  types are sent, and a few hundred programs stay in the low kilobytes.
- [Semantics drift from the server filter] → Subcategory matching
  (`programs-studyplan-08`) landed first, with the finder field
  `settings.filter.includeSubcategories`. With that field on, the list has to
  list the ancestors of each assigned category as well, the way the finder
  enables a parent on the server. This change owns the adaptation and its
  test.
- [A screen reader announces every change] → The region is polite, so an
  announcement waits for the current speech and does not interrupt it.

## Open Questions

None.
