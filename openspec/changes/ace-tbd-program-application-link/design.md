## Context

See `proposal.md` for the motivation. `Configuration/TCA/Overrides/pages.php`
adds `credit_points`, `job_profile`, `performance_scope` and `prerequisites`
through `TcaManipulator::addToPageTypesGeneralTab()` for page type 20, and
`ext_tables.sql` declares those four columns. The page template
`Resources/Private/Pages/AcademicProgram.html` has no layout and no sections;
candidate `programs-studyplan-03` (change
`ace-tbd-program-page-layout-and-sections`, proposed separately) plans a
`Program/Page/CallToAction` partial for exactly this link.

## Goals / Non-Goals

**Goals:**

- The same column name a project already uses, so its data stays where it
  is.
- No hand-written SQL.

**Non-Goals:**

- A card field `applicationLink` for a configurable facts list; that belongs
  to candidate `programs-studyplan-05` once it lands.

## Decisions

### Two columns with derived types

`application_link` (TCA `type => 'link'`, `allowedTypes => ['page', 'url']`)
and `application_link_label` (`type => 'input'`, `max => 60`) are added to the
program tab after `credit_points`. Neither gets an `ext_tables.sql` line:
`DefaultTcaSchema` derives both from the TCA (`case 'link'` and `case 'input'`
in the installed v13 core; the v14 derivation is confirmed by the schema test
of the tasks). Both stay translatable, so a translated program page can link a
translated application form.

Rejected: an inline table of several buttons with colours, as one project
has. It puts styling into data and serves one project; that project keeps its
relation or uses content elements.

### Model, page data and partial

`Program` and `ProgramData` get `getApplicationLink()` and
`getApplicationLinkLabel()`, `ProgramDataFactory` maps both, and the Extbase
mapping picks the model properties up from the column names. The page
template renders a new partial `Program/Page/CallToAction`: from its current
position after the facts list if `programs-studyplan-03` has not landed, from
that change's header section otherwise. The partial uses `f:link.typolink`
and the label `program.applicationLink.defaultLabel` when the label is empty.

Rejected: rendering the link inline in the page template. The partial is the
unit a project overrides, and `programs-studyplan-03` already plans it.

Guessed layout — a sketch, not a design:

```text
GUESSED  program page header
+------------------------------------------------------------+
| Bachelor of Arts                                           |
| Subtitle                             [ Apply now -> ]      |
+------------------------------------------------------------+
  empty label -> "Apply now"; empty link -> no button
```

## Risks / Trade-offs

- [A project defines the same column] → TCA of the later-loaded extension
  wins, and two `ext_tables.sql` definitions merge. The project that already
  has the column deletes its definition; the migration guide says so.
- [ace-demo stores the link in columns of its own] → One `UPDATE pages SET
  application_link = …` copies it; the statement lives in the migration guide.

## Migration Plan

Database compare adds the two columns. Projects with their own columns move
their data before removing them.

## Open Questions

None.
