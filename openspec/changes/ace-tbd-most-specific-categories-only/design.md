## Context

See `proposal.md` - Why. Verified on main:

- `academic-programs/Resources/Private/Partials/Program/Categories.html:17-34`
  and `Partials/Program/Item.html:36-38` print every assigned category.
- `typo3-category-types/Classes/Collection/CategoryCollection.php:63-83`
  groups the attached categories per type; it holds every category of the
  program and each `Category` knows its `parentId`
  (`Domain/Model/Category.php:19, 40`).
- `CategoryRepository::getCategoryRootline()`
  (`Domain/Repository/CategoryRepository.php:297`) walks up with one query
  per level; `Category::getParent()` queries as well.
- The facts rendering this builds on is `ace-tbd-program-facts-field-list`.

## Goals / Non-Goals

**Goals:**

- Hide an assigned ancestor without a query and without per-instance uids.

**Non-Goals:**

- Resolving ancestors that are not assigned to the program.
- Any change to filtering or to stored data.

## Decisions

### A collection method in category_types, no query

`CategoryCollection::getMostSpecificCategoriesByType(): array` returns the
same shape as `getAllCategoriesByType()`, without every category that is the
ancestor of another category of the same type in the collection. It builds a
uid-to-parent map of the attached categories once and walks each category's
parents through that map, stopping at a uid that is not attached, at a cycle
or at the root.

Rejected: the rootline query per category, which costs one query per level,
category and program - a list of 20 programs pays for it on every render.
Rejected: a `hide in frontend` column on `sys_category`, a schema change on a
table shared with news, partners and projects. Rejected: uid lists in
settings, not portable between instances (one project's
`academic_programs.detail.categories.blacklist`).

### Same type only

A parent of another type stands for another fact row; hiding it would drop a
whole fact. The walk therefore only considers ancestors whose type equals
the category's type.

### One boolean setting in academic_programs

`plugin.tx_academicprograms.facts.mostSpecificOnly` (bool, default false), in
the same `settings.definitions.yaml` and constants as the facts fields of
`ace-tbd-program-facts-field-list`, reaching the page through the
`program-data` processor option and the plugins through their settings. The
facts builder switches the collection method.

Rejected: `academicPrograms.facts.mostSpecificOnly` as the candidate
proposed; the repository's settings are keyed by the constant path.

### Decided: programs only

The switch is offered for programs only. Every workaround found in the
analysed projects hides a parent degree on programs, and no project asks for
the same on partner or project listings. Because the collection method lives
in `category_types`, extending the switch later costs one setting per
extension, once an installation asks for it.

## Risks / Trade-offs

- [An unassigned intermediate level is not bridged: with "Bachelor" and a
  grandchild assigned but not the child, both stay visible] → the degree
  hierarchies in the projects are two levels deep; a follow-up can resolve
  missing links with one batched query if a project needs it.
- [Editors may rely on the parent being shown] → off by default.

## Open Questions

None.
