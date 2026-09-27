# The order of category types

The types of a category group are ordered by their `priority`, the highest
first. Types with the same priority keep the order they were declared in: the
load order of their packages, and within one `CategoryTypes.yaml` the order of
the list. No type this repository ships declares a priority, so every type has
`0` and the order is the declaration order until a project sets one.

How an integrator uses it is documented in the `For Developers` chapter of
`category_types`, section `The order of the types`. This page is about where the
order is decided and why there.

## One place decides: `CategoryTypeRegistry::attach()`

`attach()` sorts every group after it has added the types, and rebuilds the flat
list from the groups. Nothing else sorts types, and no consumer has to:

| Consumer                                             | Reads                                             |
|------------------------------------------------------|---------------------------------------------------|
| Facts of the program page, details element, card     | `CategoryCollection::getAllCategoriesByType()`    |
| Categories of a partner or project page and item     | `CategoryCollection::getAllCategoriesByType()`    |
| Filter selects of the program, partner, project list | `CategoryCollection::getAllCategoriesByType()`    |
| Page module category summary                         | `CategoryTypeRegistry::getGroupedCategoryTypes()` |
| A select of the types of one group (filter types)    | `CategoryTypeRegistry::getGroupedCategoryTypes()` |
| Type select of a category, type icons                | `CategoryTypeRegistry::getCategoryTypes()`        |

The category collection takes its type order from
`getCategoryTypeIdentifierByGroup()`, so it follows without a change.

Two other places were rejected:

- **The loader.** The registry is public API, and a test or a third party
  package can `attach()` without `CategoryTypeLoader`. Every way in passes
  through the registry: the YAML files, the cache entry and a direct call.
- **Every getter.** That repeats the sort on every call, while `attach()` runs
  once per request, when the registry is built.

## Stable, and no tiebreaker

`uasort()` is stable since PHP 8.0, which every supported PHP version is, so
equal priorities keep their attachment order without a second criterion. That
is what makes the change invisible where no priority is set: the order at `0`
is the order before, and `attachedTypesAreReturnedInAttachmentOrder` pins it.

Higher first follows the `priority` of tagged services in the TYPO3 service
container. With every default at `0`, one override moves one type to the front,
whatever the others declare. Lower first, the convention of `sorting` columns,
would need a negative value or an override of every other type for the same.

## The flat list

`getCategoryTypes()` lists the groups one after the other, in the order each
group was first attached, and each group in its own order. Before, it was the
attachment order across groups. The two differ when a type is added to a group
after a group that was first declared later already has types, for example a
type that a site package adds to the `programs` group while `academic_projects`,
loaded after `academic_programs`, declared its types in between. That type moves
from the end of the list into its group.

## The loader keeps its order

`CategoryTypeLoader::loadUncached()` still returns the types keyed by
`<group>.<identifier>` in declaration order. A `useExisting` override writes to
the existing key, so a changed type keeps its place there, and the registry
moves it only when the override sets a priority. A redeclaration without
`useExisting` falls back to priority `0`, like every key it leaves out.

The cache holds the flat list as the loader wrote it, and restoring it goes
through `attach()` as well, so the order does not depend on the order of a
cache entry — not even of one written before the types were sorted.

## A duplicate

`attach()` rejects a type whose identifier its group already holds. The types
before it in the same call stay attached, as they always did, and the flat list
is rebuilt in a `finally` block so that it includes them too.

## Where it is tested

| What                                                    | Test                                                                                        |
|---------------------------------------------------------|---------------------------------------------------------------------------------------------|
| The rule: highest first, stable, negative last, groups  | `typo3-category-types/Tests/Unit/Registry/CategoryTypeRegistryTest.php`                     |
| An override moves a type, uncached and from the cache   | `typo3-category-types/Tests/Unit/Loader/CategoryTypeLoaderTest.php`                         |
| Facts, list filter, page module summary, type select    | `academic-programs/Tests/Functional/CategoryTypes/CategoryTypePriorityTest.php`             |

## See also

- [Program facts](program-facts.md) — the facts follow the order when the field
  list names no types.
- [List filter types](list-filter-types.md) — a list offers its filters in this
  order when neither the element nor the site chooses them.
- [Page module category summary](page-module-category-summary.md) — one row per
  type, in this order.
