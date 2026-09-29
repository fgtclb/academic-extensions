## Context

See `proposal.md` - Why. Verified on main:

- Since `ace-733-program-facts-field-list`, the program page, the details
  element and the program card print their categories through
  `academic-programs/Classes/Service/ProgramFactsBuilder.php`, which takes
  every assigned category of a type from `getAllCategoriesByType()`, and
  `Resources/Private/Partials/Program/Facts/Item.html` prints each of them;
  `Partials/Program/Categories.html` is removed.
- `typo3-category-types/Classes/Collection/CategoryCollection.php:63-83`
  groups the attached categories per type; it holds every category of the
  program and each `Category` knows its `parentId`
  (`Domain/Model/Category.php:19, 40`).
- `CategoryRepository::getCategoryRootline()`
  (`Domain/Repository/CategoryRepository.php:297`) walks up with one query
  per level; `Category::getParent()` queries as well.
- The facts rendering this builds on is `ace-733-program-facts-field-list`.

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
ancestor of another category of the same type in the collection. The
collection is keyed by uid already, so it walks each category's parents
through its own entries, stopping at a uid that is not attached, at the root
or at a uid it has already passed.

A parent chain that leads back to the category it started from is a cycle, in
which every category is the ancestor of every other one. Hiding them for that
would leave the whole fact empty, so a walk that returns to its start hides
nothing. A category below a cycle still hides the cycle.

The walk reads `Category::getParentId()`, the parent of the overlaid row. The
core copies `parent` into a translation as it is
(`DataHandler::copyRecord_processManyToMany()` only localizes references with
`localizeReferencesAtParentLocalization`, which `sys_category.parent` does not
set), so a translation points at the default language parent, whose uid is the
uid the collection holds. An editor who changes a translation's parent changes
the hierarchy for that language.

Rejected: the rootline query per category, which costs one query per level,
category and program - a list of 20 programs pays for it on every render.
Rejected: a `hide in frontend` column on `sys_category`, a schema change on a
table shared with news, partners and projects. Rejected: uid lists in
settings, not portable between instances (one project's
`academic_programs.detail.categories.blacklist`).

### Same type only

A parent of another type stands for another fact row, and hiding it would
drop a whole fact. The walk therefore only hides ancestors whose type equals
the category's type. It still passes a parent of another type, so an ancestor
of the same type above it is found.

### One boolean setting in academic_programs

`plugin.tx_academicprograms.facts.mostSpecificOnly` (bool, default false), in
the same `settings.definitions.yaml` and constants as the facts fields of
`ace-733-program-facts-field-list`. It reaches the page through the
`program-data` processor option `factsMostSpecificOnly`, the details element
through `settings.facts.mostSpecificOnly`, and the card through the new
argument `mostSpecificOnly` of `<ace:program.facts>`, which
`Partials/Program/Item.html` hands `settings.facts.mostSpecificOnly`.
`ProgramFactsBuilder::build()` takes it as an optional fourth argument and
switches the collection method.

The card reads the facts switch rather than a `card.` setting of its own, so a
program shows the same categories in all three places. An override of
`Program/Item.html` that calls the view helper without the argument keeps
showing every category on the card, which the changelog says.

`category_types` gets a Feature changelog of its own for the new public method
of the `@api` collection.

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
