# List filter types

The filter form of the partner list and map, both project lists and the program
list offers one select per category type of the list's group. Three settings
under `settings.filter` shape it, with the same names in all three extensions:

| Key                                   | Default | Read by                            |
|---------------------------------------|---------|------------------------------------|
| `settings.filter.categoryTypes`       | empty   | The resolver, for the list actions |
| `settings.filter.visibleCount`        | `0`     | The resolver, for the list actions |
| `settings.filter.hideDisabledOptions` | `0`     | The `DemandCategories` partials    |

Each is a constant `plugin.tx_academic<group>.filter.<key>`, mapped into
`settings.filter` in `setup.typoscript`, and on TYPO3 v13 a site setting of the
same path, declared with each extension's **aggregate** set: the partner list
and map, and both project elements, render the same form, and a set declares
settings only for itself. TYPO3 v12 has no site sets, so the constants are the
only way there. There is no FlexForm field: `main` adds one to the program
list, and a program finder; neither exists on this branch.

What an integrator sees is documented in the extension's
`Documentation/Configuration/`. This page is about how the values are resolved
and the rules that are easy to get wrong.

## The resolver

`FGTCLB\CategoryTypes\Filter\FilterTypeResolver` turns the category collection
of the list and the settings into a `FilterTypes` value: the offered
identifiers, in order, split into `visible` and `more` by the visible count.
`resolveFromSettings()` reads both keys from the plugin settings; a value
TypoScript cannot deliver counts as not set. It is stateless, autowired and
`@internal`, and the three list controllers receive it through a `final`
`inject*()` method rather than their constructor, because the controllers are
not final and project subclasses call the constructor.

The collection is the one `CategoryRepository::findAllApplicable()` returns: it
holds **every** category of every type of the group, and a category no listed
record carries is marked disabled. So:

| A type …                                         | Is offered |
|--------------------------------------------------|------------|
| with a category on a listed record               | yes        |
| with categories, none of them on a listed record | yes        |
| without any category                             | no         |
| not (or no longer) registered in the group       | no         |

An empty setting offers every type with a category, in the registry order of
the group - what the lists offered before the settings existed.

## The partials

The partials loop `filterTypes.visible`, and with a visible count the filters
after it go into a native `<details>` with a `<summary>` labelled
`filter.moreFilters`, as one `col-12` cell of the form's row holding a `row` of
its own. Without a count, or with one that covers every filter, the markup is
what it was before, which the list tests pin cell by cell. `<details>` gets
`open` while a filter inside it has a value - a category the editor preselected
included - found by a loop that sets a variable through `f:variable`, which
reaches the enclosing scope on Fluid 2 (v12) and Fluid 4 (v13).

Where `filterTypes` does not reach a partial - a subclass that overrides the
action without assigning it, or a project template that renders the partial
with its own arguments instead of `{_all}` - the partial falls back to the loop
it had before and offers every type with a category.

`hideDisabledOptions` is passed to `<ct:form.filterSelect>`. A filter whose
options are all left out still renders, with its "All" option only: which
categories are disabled changes with every filter the visitor sets, and filters
that come and go would move the form while the visitor narrows the list.

## The "All" option

The first option of every filter reads `sys_category.<group>.allOptions.<type>`
and falls back to the shared `sys_category.<group>.allOptions` through the
`default` argument of `f:translate`. No extension ships a label per type, so the
output is unchanged until a site adds one.

A site adds one through `_LOCAL_LANG` under `plugin.tx_academic<group>` or
`plugin.tx_academic<group>_<plugin>`. That works only because the filter
templates pass the extension name in **UpperCamelCase**:

| Template passes       | TYPO3 v12 and v13 read                  |
|-----------------------|-----------------------------------------|
| `'AcademicPartners'`  | `plugin.tx_academicpartners[_<plugin>]` |
| `'academic_partners'` | `plugin.tx_academic_partners` only      |

Both versions lowercase the name as given. Most other templates of the
extensions still pass the underscored name; that is ACE-740. A language file
override through `$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`
works too.

## What the settings do not restrict

The settings decide what the form **offers**, not what the list **accepts**. A
submitted filter of a type the form does not offer still filters the list.

## Tests

| What                                          | Test                                                                                                |
|-----------------------------------------------|-----------------------------------------------------------------------------------------------------|
| The resolution rules, the split, the settings | `typo3-category-types/Tests/Unit/Filter/FilterTypeResolverTest.php`                                 |
| Order, count, disclosure, labels, options     | `academic-<partners\|projects\|programs>/Tests/Functional/Plugins/Academic*ListFilterTest.php`      |
| The fallback, unoffered filters               | `academic-programs/Tests/Functional/Plugins/AcademicProgramsListFilterTest.php`                     |
| Reading the filters of a form                 | [`CategoryFilterFormAssertionTrait`](../testing/testing-helper.md#categoryfilterformassertiontrait) |

## See also

- [TypoScript and site sets](typoscript-and-site-sets.md) - why a constant and
  its site setting share one default, and what v12 sees.
- [Testing helper](../testing/testing-helper.md) - the trait the filter tests
  share.
