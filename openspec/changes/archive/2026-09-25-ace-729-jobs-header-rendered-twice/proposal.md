## Why

The job list, job detail and new job form of `academic_jobs`
(`packages/fgtclb/academic-jobs`) and the job list of `academic_bite_jobs`
(`packages/fgtclb/academic-bite-jobs`) render the content element header in
their templates, through the header partial of EXT:fluid_styled_content,
while the content element layout renders it as well - the layouts of
EXT:fluid_styled_content and of `bk2k/bootstrap-package` both do. The same
four templates carry the line on this branch as on `main`, released since
2.1.0:

- With an explicit header layout (1 to 5), the header and the subheader
  appear twice.
- With the header layout "Default", the plugin emits an empty
  `<header></header>`: the partial falls back to a setting plugin settings do
  not carry.

A site whose content element layout renders no header relies on the header
of the plugin, so the line cannot simply be removed. Fixed on `main` by
ACE-729 and archived there as
`openspec/changes/archive/2026-09-25-ace-729-jobs-header-rendered-twice`; this
is its backport for 2.4.0.

## What Changes

- A setting per extension, "render the content element header in the
  plugin", off by default. Off: the plugins render no header; the layout
  renders it once. On: the plugins render it, with the heading level for
  "Default" mapped from the constant of EXT:fluid_styled_content.
- The same `Important` changelog entries as on `main`, in `2.4/`.

Behaviour is the same on TYPO3 v12 and v13. The site setting of
`academic_jobs` exists on v13 only, as every site setting on this branch;
the TypoScript constant works on both.

## Capabilities

### New Capabilities

- `academic-jobs/content-element-header`: who renders the header of the job
  plugins, and how often.
- `academic-bite-jobs/content-element-header`: the same for the job list.

### Modified Capabilities

None.

## Impact

- Templates `Job/List.html`, `Job/Show.html`, `Job/New.html`,
  `BiteJobs/List.html`; the TypoScript constants and setup of both
  extensions and the site settings definition of `academic_jobs`; their
  tests and documentation.
- A new trait of the testing helper package.
- No PHP, TCA or database change.

## Non-goals

- Changing the content element layout or `lib.contentElement`.
- The `record` view variable: it is a TYPO3 v14 requirement of the header
  partial, and this branch has no v14 support.
- The profile editing plugin of `academic_persons_edit`: its `ProfileEdit`
  layout renders the header partial unconditionally on this branch and shows
  the same double header. `main` replaced that editor (ACE-262), so there is
  nothing to backport; it needs a change of its own.
