## Context

See `proposal.md` for the motivation. `Configuration/TCA/Overrides/pages.php`
adds `credit_points`, `job_profile`, `performance_scope` and `prerequisites`
through `TcaManipulator::addToPageTypesGeneralTab()` for page type 20, and
`ext_tables.sql` declares those four columns. The page template
`Resources/Private/Pages/AcademicProgram.html` renders inside the site layout
since ACE-726 (candidate `programs-studyplan-03`), and its section `Main`
renders the partials `Program/Page/Header`, `Media`, `Facts` and `Content`.
The header and the media share a reversed flex column, so the header partial
is one element.

## Goals / Non-Goals

**Goals:**

- The same column name a project already uses, so its data stays where it
  is.
- No hand-written SQL.

**Non-Goals:**

- A card field `applicationLink` for the configurable facts list of
  `programs-studyplan-05`, which landed as ACE-733. A fact is a label and a
  value, and a link does not fit it.

## Decisions

### Two columns with derived types

`application_link` (TCA `type => 'link'`, `allowedTypes => ['page', 'url']`)
and `application_link_label` (`type => 'input'`, `max => 60`) are added to the
program tab after `credit_points`. Neither gets an `ext_tables.sql` line:
`DefaultTcaSchema` derives both from the TCA. It does so in `case 'link'` and
`case 'input'` on v13 and for `LinkFieldType` and `InputFieldType` on v14, as a
text column and a `varchar(60)`, both `NOT NULL DEFAULT ''`. Both stay
translatable, so a translated program page can link a translated application
form.

Rejected: an inline table of several buttons with colours, as one project
has. It puts styling into data and serves one project. That project keeps its
relation or uses content elements.

### Model, page data and partial

`Program` and `ProgramData` get `getApplicationLink()` and
`getApplicationLinkLabel()`, `ProgramDataFactory` maps both, and the Extbase
mapping of `Program` names both columns next to the four existing ones. The
section `Main` renders a new partial `Program/Page/CallToAction` right after
the header and the media, before the facts. It is a sibling of the header
rather than part of it, so a project that overrides `Program/Page/Header`
keeps the link, and the reversed flex column keeps its two children.

The partial resolves the link to a URL first and renders nothing when that
URL is empty, the way the header treats the link back to the list: a target
page that is hidden, deleted or untranslated in a language without a fallback
leaves no bare label behind. The link itself is rendered with
`f:link.typolink`, so a target or a title the editor picked in the link
browser stays. The label is the editor's, or `page.applicationLink.defaultLabel`
when it is empty, a key next to `page.backToList` of the header rather than in
the `program.<field>` namespace the fact labels are built from. The partial
reads nothing but `program`, so a list item override renders it as well. Its
element is therefore the block `academic-programs-application`, not an element
of `academic-programs-detail`.

Rejected: rendering the link inline in the page template, or inside the
header partial. The partial is the unit a project overrides, and the header is
one a project already overrides for other reasons.

The order of the section `Main`:

```text
+------------------------------------------------------------+
| image                                                      |
| Back to all programs                                       |
| Bachelor of Arts                                           |
| Subtitle                                                   |
+------------------------------------------------------------+
  [ Apply now ]          no link, or a target that cannot be
                         linked: nothing
  facts
  content elements
```

## Risks / Trade-offs

- [A project defines the same column] → TCA of the later-loaded extension
  wins, and two `ext_tables.sql` definitions merge. The project that already
  has the column deletes its definition. The changelog entry says so.
- [A project stores the link in columns of its own] → One `UPDATE pages SET
  application_link = …` copies it. The statement lives in the changelog
  entry, since the integrator migration guide is a change of its own that has
  not landed.

## Migration Plan

Database compare adds the two columns. Projects with their own columns move
their data before removing them.

## Open Questions

None.
