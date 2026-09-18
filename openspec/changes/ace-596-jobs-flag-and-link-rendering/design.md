## Context

See `proposal.md` for the motivation. Measured on `origin/2` before the fix,
against `main` at the commit before the fix landed there:

- `Partials/Job/Information.html`, `Partials/Job/Item.html`,
  `Templates/Job/Show.html`, `Templates/Job/List.html` and
  `Documentation/Templates/Override/Index.rst` are **byte-identical** on both
  branches, so the production patch carries over literally.
- `locallang.xlf` and `de.locallang.xlf` are identical after stripping leading
  whitespace: the only difference is that this branch indents with tabs.
- `Tests/Functional/Plugins/AcademicJobsListAndDetailPluginTest.php` does not
  exist here. The extension has a `Tests/Functional/Plugins/` tree, but only
  for the new-job form.
- Both branches register `academicjobs_list` and `academicjobs_detail` as
  dedicated content types, so the fixture rows are the same.

## Goals / Non-Goals

**Goals:**

- Both partials render every item of their list correctly; each stays the
  override point of its view.
- The behaviour is the same on TYPO3 v12 and v13.

**Non-Goals:**

- A field list or formatter setting.

## Decisions

### The patch is taken over unchanged

Every Fluid construct the fix uses exists on both core versions of this
branch: `f:if`/`f:then`/`f:else if` and `f:comment` are Fluid 2 features, and
`Link\TypolinkViewHelper` on TYPO3 12.4.45 registers `parameter` as its
required argument and renders its children as the link text, exactly as on
v13. No version switch is needed, and none is added.

### The new label units follow this branch's indentation

The three keys `jobs.internationalsWelcome`, `jobs.alumniRecommend` and
`jobs.linkText` are written with tabs, so each file stays internally
consistent. Their text is identical to `main`.

### The test class is written here rather than dropped

`docs/workflow/backporting.md` names three ways out when a test has no home on
this branch. This backport takes the third: the list and detail plugin test is
written for this branch, with a baseline, the flag and link assertions, a
German rendering and a job that has neither flag nor link. It is deliberately
narrower than the class on `main`, which also covers the job type filter,
hidden records, header layouts and the contact block.

## Risks / Trade-offs

- [The link text changes from the stored URL to a label] → Named in the
  `Important-` changelog entry; a site that wants the URL overrides the
  label or the partial.
- [The list view changes as well as the detail view] → Both partials are
  named in the `Important-` changelog entry.

## Migration Plan

None.

## Open Questions

None.
