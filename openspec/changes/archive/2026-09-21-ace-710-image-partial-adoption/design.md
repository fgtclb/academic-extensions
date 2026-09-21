## Context

Verified on `main`, the nine image calls:

- `academic-partners/Resources/Private/Partials/Partner/Item.html:46`
  (iterates `partner.media`, the doktype 40 page media, which is the partner
  logo), `Partials/Partnerships/List/Item.html:47`,
  `Partials/Partnerships/Teaser/Item.html:47`, `Pages/AcademicPartner.html:14`;
- `academic-programs/Resources/Private/Partials/Program/Item.html:47`,
  `Pages/AcademicProgram.html:13`;
- `academic-projects/Resources/Private/Partials/Project/Item.html:57`,
  `Pages/AcademicProject.html:18`;
- `academic-jobs/Resources/Private/Partials/Job/Item.html:72`
  (`job.image`).

List items use `card-img-top img-fluid`, page templates `img-fluid`. The
plugin views register their own partials at `partialRootPaths.0` with the
project constant at `.1` (partners, programs), at `.0` only (projects), or at
`.10` and `.20` (jobs). The page templates are rendered by `page.10`, whose
partial root paths the site's theme shares. The page objects of partners,
programs and projects add their own paths to it.

## Goals / Non-Goals

**Goals:**

- Every upstream list item and page template image goes through the shared
  partial of candidate `cross-cutting-01`.

**Non-Goals:**

- Changing the image source of any template.

## Decisions

### Preset per template

`card` for the program, project and job list items, `detail` for page
templates, `logo` for the partner list items and the partnership items, all
with crop variant `default`. Rejected: one image partial per extension, which
repeats the same markup five times.

### Decided: partner list items use `logo`

`Partner/Item.html` renders the first `partner.media` of the partner page,
which is the partner logo, exactly as both partnership item partials do. The
same image gets the same preset: `logo`, with no crop and SVG passed through.
This also matches `ace-tbd-named-crop-variants`, which keeps the partner page
media free-ratio (ACE-572). Rejected: `card`, as first proposed, which would
render one logo with two presets depending on the plugin.

### Decided: job list items use `card`

The job image is a generic single image field, even where editors use it for
an employer logo. The list item renders it with `card` and crop variant
`default`. Jobs get no named crop variants, so `default` stays free-ratio and
a logo is not cut, and the partial passes SVG through in every preset. A
project that wants `logo` for its jobs overrides `Job/Item.html` or the preset
section of the shared partial. Rejected: `logo` for all jobs, which would drop
the responsive sources for jobs that carry a photo.

### Partial root path keys below every project slot

The same rule as in `cross-cutting-01`: the academic_base path gets a key
below the lowest key of each plugin view. In `page.10`, the path gets a unique
negative key, so it can neither replace a theme path of the same key nor win
over a project path. Rejected: a high unique key like the jobs page paths use
(`1693651551`), because it would win over the project and theme partial paths
for `Academic/Image.html`.

### Added while implementing: academic_jobs registers the path in `page.10` too

The change first named the page objects of partners, programs and projects.
academic_jobs ships no page template, but it has been registering its own
partials in `page.10` all along, so a page object that renders `Job/Item` finds
that partial and would then fail on the `Academic/Image` it renders. It gets the
same negative key next to its existing one. Rejected: leaving it out, which
would make a working integration fail on an upgrade for no reason.

### Added while implementing: `Breaking-`, not `Important-`

`tasks.md` first named an `Important-` entry per extension. The markup changes
for every installation and a view with replaced partial root paths fails until
it lists the academic_base path, which is what `Breaking-` is for - and what
ACE-646 shipped for the same change in academic_persons.

### Added while implementing: the page objects register the path twice

A `PAGEVIEW` page object reads no `partialRootPaths` at all. It derives its
partial and layout root paths from `paths` by appending `Partials/` and
`Layouts/` (`PageViewContentObject::render()`), and all three extensions ship a
`paths` entry for exactly that integration. Registering under `partialRootPaths`
alone left a `PAGEVIEW` page dying on `Academic/Image`, so `paths` carries
`EXT:academic_base/Resources/Private/` under the same negative key, and each page
template test renders its page on both page object shapes.

### Verified: a project override works in academic_projects too

`plugin.tx_academicprojects.view.partialRootPaths` has one key, `0`, and it *is*
the project constant, so a project that sets it names a directory holding only
its override. The extension's own partials stay resolvable all the same:
`ActionController::addDefaultPathToPaths()` prepends
`EXT:academic_projects/Resources/Private/Partials/` when the configured paths do
not list it, at the lowest precedence. All four extensions therefore get the same
`projectOverrideOfTheSharedImagePartialWins` test.

## Risks / Trade-offs

- [The theme or a project already uses the same negative key in `page.10`] →
  Each extension uses a negative key of its own, the Breaking entries name it,
  and the page template tests carry a theme path at `0` and a project path at
  `1` and assert both survive.
- [Project CSS selects the image tag directly] → Breaking entries per
  extension.

## Open Questions

None.
