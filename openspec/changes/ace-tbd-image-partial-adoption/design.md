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

## Risks / Trade-offs

- [The theme or a project already uses the same negative key in `page.10`] →
  The Important entries name the key; the development instances with the
  Bootstrap Package theme are checked in task 2.3.
- [Project CSS selects the image tag directly] → Important entries per
  extension.

## Open Questions

None.
