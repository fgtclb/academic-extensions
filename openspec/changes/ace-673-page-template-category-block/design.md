## Context

See `proposal.md` for the motivation. Verified on this branch at `6c325b252`:

- `AcademicPartner.html:19,21` reads `{partner.categories.allCategoriesByType}`,
  `AcademicProject.html:23,25` reads `{project.categories.allCategoriesByType}`.
- `Partner::getAttributes()` (:182) and `Project::getAttributes()` (:100)
  exist; neither model has `getCategories()`. The list and teaser partials of
  both extensions already read `{…attributes.allCategoriesByType}`.
- `AcademicPartner.html` is identical to `main` before the fix.
  `AcademicProject.html` differs from it only in the rich text lines of
  ACE-676, which are already backported; the category lines are the same.
- The partner and project category types (`region`, `partner_type`,
  `collaboration_type`; `competence_field`, `department`, `funding_partner`)
  and their labels are registered here as on `main`. `main` only adds
  `inlineIcon: true` to each type, which the template does not depend on.
- The fixture of `AcademicPartnerPageTemplateTest` is identical to `main`
  before the fix. `AcademicProjectPageTemplateTest` and its fixture were
  rewritten on this branch by the ACE-676 backport (a link target page 11 and
  rich text columns), which is the state `main` reached after merging both
  changes.

## Goals / Non-Goals

**Goals:**

- The block renders with the markup it has today, on both page types, on
  TYPO3 v12 and v13.
- The page template tests pin it, so a later rename cannot hide it again.

**Non-Goals:**

- Any change to the model API or to the category collection.
- Any change to how the page content is rendered.

## Decisions

### Use the `attributes` accessor the list partials already use

Both templates switch to `{partner.attributes.allCategoriesByType}` and
`{project.attributes.allCategoriesByType}`, as on `main`. One template edit
per extension, no PHP.

Rejected: a `getCategories()` alias on both models, for the reasons given on
`main`.

### Merge the category fixture into the ACE-676 fixture of the project test

The project page fixture gains the storage folder, three `sys_category`
records and the MM rows while keeping the rich text columns of ACE-676. Page
11 stays the link target and doubles as the page without categories, which is
exactly the fixture `main` carries after both changes. The partner test and
fixture take the `main` diff unchanged.

Rejected: a second fixture file for the category tests. It would duplicate
the site setup for no gain and diverge from `main` without a reason.

## Risks / Trade-offs

- [A site relied on the block being absent] → The `Important-` changelog entry
  names the visible change; such a site overrides the page template.
- [The type icon identifier has no registered icon] → The core icon
  ViewHelper renders the default icon; the list partials have the same call
  today.

## Migration Plan

None. The change is visible as soon as the extension is updated.

## Open Questions

None.
