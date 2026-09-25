## Why

Five projects want a compact entry into the program list: a few selects, for
example in a home page hero, that open the list pre-filtered.
`academic_programs` (`packages/fgtclb/academic-programs`) ships only the list
and the details element. Three projects, the ACE demo among them, therefore
each register the same content element `academicprograms_programfinder` in
the upstream plugin namespace, each with a controller, FlexForm, template and
TSconfig of their own. A fourth has an element of its own, and a fifth plans
one. ACE-91 asks for the finder upstream, with the Bachelor degree
preselected.

## What Changes

- New content element "Program finder" (`academicprograms_programfinder`) in
  the `academic` group. It is hidden by default like the other elements and
  enabled by a new component set `fgtclb/academic-programs-program-finder`,
  which the aggregate set includes.
- Settings: the target list page (required), the category types offered and
  their order, and preselected categories. The types use the key of the
  program list's filter types: an empty field falls back to the site-wide
  filter types of the program list, and to degree, then topic, when that is
  empty as well.
- The options are the categories of the programs in the element's storage; an
  option no program carries is disabled.
- Submitting opens the target page with the selection applied to its program
  list, in the argument shape the list accepts today.
- **BREAKING** for installations that register
  `academicprograms_programfinder` themselves, or declare a `finderAction()`
  in a class extending or replacing the program controller: their
  registration, loaded after this one, collides with it - it replaces or
  duplicates the type item, replaces the data structure and, for the same
  controller class, the finder action; a project `Program/Finder.html` is
  rendered by the upstream action - until they remove it. Their stored
  elements and `settings.listPid` values keep working.
- A program list keeps its configured sorting when a filter submission
  carries none: the finder submits none, and neither does the filter of a
  list whose sorting select is hidden, which fell back to title ascending.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-finder`: the program finder element as editors
  configure it and visitors use it.

### Modified Capabilities

- `academic-programs/program-list-sorting`: a filter that carries no sorting
  keeps the sorting configured for the list element.

## Impact

- TCA, FlexForm, plugin configuration, a controller action, a template,
  labels, page TSconfig and a site set in `academic_programs`.
- The static page TSconfig and TypoScript registrations for installations
  without site sets.
- Builds on the category type items provider, the filter type resolver and
  the site-wide filter type setting of `ace-736-program-list-filter-types`
  (candidate `programs-studyplan-09`, merged).

## Non-goals

- Narrowing the options without a reload; that is
  `ace-tbd-finder-client-side-narrowing`.
- Subcategory matching; candidate `programs-studyplan-08` adds its switch to
  the list and the finder.
- Speaking URLs for the target list (ACE-623, listings family).
- Migrating the element one project built under a name of its own; the
  integrator migration guide describes it.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-10`). Four of the six analysed projects carry their own
code for this today, and a fifth plans the same.

Implements part of ACE-91: the finder element. The rest of ACE-91 - options
narrowed without a reload - is `ace-tbd-finder-client-side-narrowing`, so
ACE-91 stays open after this change.
