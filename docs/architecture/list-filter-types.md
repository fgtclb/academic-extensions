# List filter types

The filter form of a list offers one select per category type of its group.
Which types it offers, and in which order, is the **filter types** setting:
a comma-separated list of type identifiers under one key,
`settings.filter.categoryTypes`. Today the program list and the program finder
read it; the partner and project lists do not yet.

What an integrator or editor sees is documented in the extension's
`Documentation/Configuration/`. This page is about how the value is resolved and
the rules that are easy to get wrong.

## Two levels, one key

| Level               | Where it is set                                                                                  |
|---------------------|--------------------------------------------------------------------------------------------------|
| The site            | Constant or site setting `plugin.tx_academicprograms.filter.categoryTypes`                       |
| One content element | FlexForm field `settings.filter.categoryTypes` of the `Program List` or `Program Finder` element |

The constant is mapped to `settings.filter.categoryTypes` in `setup.typoscript`,
and Extbase merges the FlexForm over it. An element that leaves its field empty
would therefore overwrite the site value with an empty string; the key is listed
in `plugin.tx_academicprograms.ignoreFlexFormSettingsIfEmpty`, so
`FrontendConfigurationManager` drops an empty field before the merge, on v13
and v14 alike. It drops `''` and `'0'` only, which is what the side by side
select stores for no selection. An element saved before the field existed has
no value for it at all and keeps the site value without that help.

One key for both levels was a decision, not an accident: most projects set the
list for the whole site, one sets different filters on different pages, and two
names for one concept would have to be kept apart in every template override.

## The resolver

`FGTCLB\CategoryTypes\Filter\FilterTypeResolver` turns the collection of the
list and the setting into a `FilterTypes` value: the offered identifiers, in
order, split into `visible` and `more` by an optional visible count. The program
list passes no count, so everything is `visible`; the split exists for the
partner, project and program lists to share once they offer a "more filters"
disclosure.

The collection is the one `CategoryRepository::findAllApplicable()` returns,
and that is where the rule comes from that is easiest to get wrong. It holds
**every** category of every type of the group, not only those of the listed
records; a category no listed record carries is marked disabled and rendered as
a disabled option. So:

| A type …                                         | Is offered |
|--------------------------------------------------|------------|
| with a category on a listed record               | yes        |
| with categories, none of them on a listed record | yes        |
| without any category                             | no         |
| no longer registered in the group                | no         |

That is what the list offered before the setting existed, and an empty setting
keeps it: every type with a category, in the registry order of the group.
Leaving out a type whose categories are all disabled would change the default
output of every list, which is a decision of its own.

The resolver is stateless, autowired and `@internal`. `ProgramController`
receives it through a `final` `inject*()` method rather than its constructor,
because the controller is not final and project subclasses call the
constructor.

The partial loops `filterTypes.visible` when `filterTypes` reaches it, and falls
back to the loop it had before when it does not: a subclass that overrides
`listAction()` without assigning it, or a project template that renders the
partial with its own arguments instead of `{_all}`, would otherwise lose every
filter on the update. The fallback ignores the setting, which is the one thing
such a project has to change to use it.

## What the setting does not restrict

The setting decides what the form **offers**, not what the list **accepts**.
A filter URL carries a uid list without the type of each category (see
[List filter URLs](list-filter-urls.md)), and the demand factory resolves it
against the whole group. A link that filters by a type the form does not offer
keeps working, and so does every link made before an element was changed.

## The items of the field

The FlexForm field gets its items from
`FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc`, which
offers the types of the group named in `itemsProcConfig.group`, so a type a
project adds is offered and one it removes is not. It is public through
`#[Autoconfigure(public: true)]`, because core resolves an `itemsProcFunc`
through `GeneralUtility::makeInstance()`. It dispatches no event: the list of
types is changed where types are registered, in `CategoryTypes.yaml`.

A stored identifier the group no longer has is no item, and a side by side
select drops a value that is not among its items silently — see
[Backend select items](backend-select-items.md#a-value-that-is-not-among-the-items-is-lost).
Here that is intended: the next save removes the type the project removed.
Until then the resolver ignores it.

## The program finder

The `Program Finder` element of `academic_programs` renders the same selects as
a form of its own, one per filter type, and posts them to the list plugin of
another page, see [List filter URLs](list-filter-urls.md#which-plugins). It
reads the same key at both levels, through the same
`ignoreFlexFormSettingsIfEmpty` entry — an entry in the extension block
`plugin.tx_academicprograms` applies to every plugin of the extension, as long
as no plugin block `plugin.tx_academicprograms_<plugin>` sets its own — and
the same resolver.

It differs in one place: with both levels empty it offers `degree,topic`, not
every type with a category. A finder is a compact entry, and a dozen selects is
no entry. The default is a constant of `ProgramController`, applied before the
resolver, so a site without a `topic` category gets the degree alone.

Its options come from `findAllApplicable()` over the programs in the finder's
own storage (`pages`, `recursive`), not over the storage of the list it
targets: `settings.listPid` names a page, which can carry several lists or
none. The documentation tells integrators to point both at the same storage.

It renders no form without a target it can link: `finderAction()` builds the
list URI itself and hands an empty string on, because a hidden, deleted or
access restricted page yields an empty URI without an exception, and a form with
an empty `action` posts to the page it is on, where nothing answers it. Nor does
it render one when none of its types has a category.

The finder submits no sorting. `DemandFactory::createDemandObject()` applies the
sorting of the element to a demand that carries none, so the target list keeps
its configured sorting - which also fixes the filter form of a list whose
sorting select is hidden. The selection replaces the list's preset categories,
as the list's own filter does.

A preselected category is a plain uid list in the FlexForm
(`settings.preselectedCategories`, a `category` field with `oneToMany`, which
FlexForms store inline). The controller turns it into one value per offered
type — the first of a type in the stored list wins, which is the one higher in
the category tree, because the tree element submits its checked nodes in tree
order; and a disabled one preselects nothing, because
a browser does not submit a disabled option and the visitor would search for
something other than what the select shows.

## Tests

| What                                         | Test                                                                                 |
|----------------------------------------------|--------------------------------------------------------------------------------------|
| The resolution rules and the split           | `typo3-category-types/Tests/Unit/Filter/FilterTypeResolverTest.php`                  |
| The items, and core getting the provider     | `typo3-category-types/Tests/{Unit,Functional}/Backend/FormEngine/…`                  |
| The field as FormEngine compiles it          | `academic-programs/Tests/Functional/Backend/FormEngine/FilterTypesFieldTest.php`     |
| Order, fallback, site set, unoffered filters | `academic-programs/Tests/Functional/Plugins/AcademicProgramsListFilterTypesTest.php` |
| The stored order after a save                | `academic-programs/Tests/Functional/Backend/FormEngine/FilterTypesFieldTest.php`     |
| The finder: default, order, preselection     | `academic-programs/Tests/Functional/Plugins/AcademicProgramsFinderTest.php`          |
| The finder: target URI, sorting, no form     | `academic-programs/Tests/Functional/Plugins/AcademicProgramsFinderTest.php`          |
| The finder renders outside the page cache    | `academic-programs/Tests/Functional/Plugins/AcademicProgramsFinderCachingTest.php`   |
| A filter without sorting keeps the element's | `academic-programs/Tests/Functional/Plugins/AcademicProgramsFilterUrlTest.php`       |
| Which sorting a demand gets                  | `academic-programs/Tests/Functional/Factory/DemandFactorySortingTest.php`            |
| The finder fields, and what a save stores    | `academic-programs/Tests/Functional/Backend/FormEngine/ProgramFinderFieldsTest.php`  |

## See also

- [List filter URLs](list-filter-urls.md) — the URL a filter submission
  redirects to, and why it carries no category type.
- [Backend select items](backend-select-items.md) — what an items provider is
  handed, and how to test a field by compiling the form.
- [TypoScript and site sets](typoscript-and-site-sets.md) — why the constant
  and the site setting share one default.
