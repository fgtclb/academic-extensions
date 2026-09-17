## Context

See `proposal.md` for the motivation. Verified on `main`:

- `AcademicPartner.html:19,21` reads `{partner.categories.allCategoriesByType}`,
  `AcademicProject.html:23,25` reads `{project.categories.allCategoriesByType}`.
- The variables come from the page data processors `partner-data` and
  `project-data`, which map the page record to the Extbase models through
  `PartnerFactory::get()` / `ProjectFactory::get()`. They are models, not
  arrays.
- `Partner` exposes `getAttributes()` (:220) and `getCategoryCollection()`
  (:234); `Project` exposes `getAttributes()` (:100) and
  `getCategoryCollection()` (:106). Neither has `getCategories()`, so Fluid
  resolves the path to `null` and the `f:if` skips the block.
- The list partials already read the working path
  (`Partials/Project/Item.html:29,31`, `{project.attributes.allCategoriesByType}`).
- The same lines are on branch `2` and in tag 2.3.4.
- Both templates end with
  `<f:cObject typoscriptObjectPath="styles.content.getContent"/>`
  (`AcademicPartner.html:56`, `AcademicProject.html:87`), defined only by the
  content-load sets of the two extensions.

## Goals / Non-Goals

**Goals:**

- The block renders with the markup it has today, on both page types.
- The page template tests pin it, so a later rename cannot hide it again.

**Non-Goals:**

- Any change to the model API or to the category collection.
- Any change to how the page content is rendered.

## Decisions

### Use the `attributes` accessor the list partials already use

Both templates switch to `{partner.attributes.allCategoriesByType}` and
`{project.attributes.allCategoriesByType}`. One template edit per extension,
no PHP.

Rejected: a `getCategories()` alias on both models. It adds public model API
to cover a template typo, and gives one collection a third name next to
`getAttributes()` and `getCategoryCollection()`.

### Test through the existing page template tests

`AcademicPartnerPageTemplateTest` and `AcademicProjectPageTemplateTest`
already render a page of the page type on a site package with
fluid_styled_content loaded. Their fixture gains a `sys_category` record of a
category type the extension registers and the `sys_category_record_mm` row
for the page; the new test asserts the category title and the type label.

Rejected: a unit test of the template path. It would pin the property name,
not the rendered block, which is exactly what went wrong.

### Decided: the content-load sets go in 3.0, through the sections change

All three content-load sets and the program categories partial are removed
in 3.0 as a breaking change, not deprecated for 4.0. For partners and
projects, taking the page templates off `styles.content.getContent` and
removing their content-load sets is owned by
`ace-tbd-page-templates-sections-subtitle`, which rewrites the same
templates into partials. This change therefore edits only the category lines
and leaves the content rendering as it is; it must not reintroduce or add a
`styles.content.getContent` call, and task 2.3 checks the diff for it.

### Decided: backport to branch `2` as a change of its own

The fix is backported to branch `2` in a separate change, derived from its
own file-level diff rather than cherry-picked. The same template lines are
verified on branch `2` and in tag 2.3.4, which four projects run; one of them
copies the page template only to get the block.

## Risks / Trade-offs

- [A site relied on the block being absent] → The `Important-` changelog entry
  names the visible change; such a site overrides the page template, as it
  would for any other markup change.
- [The type icon identifier `category_types.<group>.<type>` has no registered
  icon] → The core icon ViewHelper renders the default icon; the list
  partials have the same call today, so nothing new can fail.
- [The sections change rewrites the same templates] → It lands after this
  change and moves the category block into its partial.

## Migration Plan

None. The change is visible as soon as the extension is updated.

## Open Questions

None.
