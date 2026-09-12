## Context

Verified on `main`:

- `Header/All` is rendered by `academic-jobs/Resources/Private/Templates/Job/{List,Show,New}.html`,
  `academic-bite-jobs/.../BiteJobs/List.html` and the study plan content
  element. The thirteen templates of this change do not render it.
- The contacts `List.html` and the partners `PartnershipsList.html` and
  `PartnershipsTeaser.html` already pass `data.header_layout` and
  `data.subheader` to their own role heading partials (`Profile/Header`,
  `Partner/Header`), with the role name as the heading text. Those partials
  have a default case, so the layout "Hidden" does not hide a role heading.
- `Header/All` is a partial of EXT:fluid_styled_content. Of the five
  extensions, none requires `typo3/cms-fluid-styled-content` and none has its
  partial path. academic_jobs requires it and registers it at
  `partialRootPaths.0`. The existing partial path keys are `0`/`1` (persons,
  partners, programs), `0` (projects) and `5`/`10` (contacts4pages).
- On TYPO3 v14 the header partial renders through `record`
  (`GetCurrentContentRecordMethodTrait`, academic_base, `@api`, ACE-270); v13
  reads `data`. Every controller of this change already assigns `data`. The
  persons and contacts controllers are final, the partners, programs and
  projects controllers are not.

## Goals / Non-Goals

**Goals:**

- The same header markup as a standard content element of the site.

**Non-Goals:**

- Changing the jobs, bite jobs or study plan templates.

## Decisions

### The core header partial, as in academic_jobs

Each template renders `<f:render partial="Header/All" arguments="{_all}"/>`
first. Each action assigns `record` through the trait next to `data`, in
`ProfileController` (five actions, including the early returns of
`selectedProfilesAction()` and `selectedContractsAction()`),
`PartnerController`, `ProgramController`, `DetailsController`,
`ProjectController` and `ContactsController`. No version switch is needed. The
extensions require `typo3/cms-fluid-styled-content` and register its partial
path. Rejected: a copy of the header partial in academic_base, which forks
core markup that differs between v13 and v14. Rejected: a
`settings.renderHeader` switch, which duplicates the header layout "Hidden".

### The header partial path below every project slot

Same rule as the image partial of `cross-cutting-01`: a negative key, so a
project can still override `Header/All` through its constant slot.

### Role headings drop the subheader

With the content element header above, passing `data.subheader` to every role
heading prints the subheader once per role. Rejected: keeping both, which
repeats it.

### Decided: a Breaking changelog entry, in 3.0

The header ships in 3.0 and is announced as a `Breaking-` entry per extension,
not as `Important-`. A header appearing where none rendered before changes the
visible output of every page with such a plugin, and a project that renders a
header outside the plugin template shows it twice, so an integrator has to act
on it. The entry shows the markup before and after and the two migrations:
drop the own header, or set the header layout "Hidden". A project that adds
the header in a template override is not affected, because its override
replaces the upstream template. 3.0.0 is unreleased, which makes it the point
for the change; deferring to 4.0 was rejected.

### What a visitor sees

Guessed layout — a sketch, not a design:

```text
+ tt_content (academicpersons_list) ------+
| <h2>Our professors</h2>  <- Header/All  |
| A B C ... Z                              |
| [card] [card] [card]                     |
+------------------------------------------+
```

## Risks / Trade-offs

- [Projects that render their own header show two] → Breaking changelog per
  extension with the markup before and after, and the migration.
- [A plugin wrapper already renders a header in some project] → Named in the
  Breaking entries; the upstream precedent (academic_jobs) renders none.
- [Themes that do not load fluid_styled_content] → The new requirement makes
  it explicit; the Breaking entries name it.

## Open Questions

None.
