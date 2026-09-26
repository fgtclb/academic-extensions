# Label overrides

A site changes a label of an extension without copying a template: in TypoScript,
under `plugin.tx_<extension>._LOCAL_LANG` for every plugin of the extension, or under
`plugin.tx_<extension>_<plugin>._LOCAL_LANG` for one of them. The path of the plugin
wins. Which path the core reads is decided by the **extension name** a translation
passes. Every frontend label of the academic extensions reads the documented path since
ACE-740; this page is what keeps it that way.

Each extension lists its labels, and the path of each of its content elements, in the
**Labels** page of its configuration chapter.

## The extension name decides the path

Measured with the functional label tests of every extension on TYPO3 v12 and v13:

| A translation passes  | TYPO3 v12 and v13 read                  | `locallang.xlf` found |
|-----------------------|-----------------------------------------|-----------------------|
| `'AcademicPartners'`  | `plugin.tx_academicpartners[_<plugin>]` | yes                   |
| `'academic_partners'` | `plugin.tx_academic_partners` only      | yes                   |
| `'academicpartners'`  | `plugin.tx_academicpartners[_<plugin>]` | **no**                |

Both versions build `tx_` and the name as given, lowercased, and find the language file
through `GeneralUtility::camelCaseToLowerCaseUnderscored()` of the name, so a name without
the camel case points at an extension `academicpartners` that does not exist, and the label
renders empty. **UpperCamelCase is the only spelling that works.** A full `LLL:EXT:` key
finds its file itself, but reads the overrides of the name passed with it all the same,
keyed by the id of the label in that file. TYPO3 v14, on branch `main`, strips the
underscores itself; the templates are the same there.

`f:translate` without an extension name takes the one of the Extbase request, which is
UpperCamelCase. Outside a plugin it has none, and falls back to the extension key of an
`LLL:EXT:` path - underscored again. TYPO3 v14, on branch `main`, takes that key first,
inside a plugin too. **Every translation names its extension**, which
`TranslationExtensionNameTest` checks. A translation in PHP without a name reads no override
at all.

## Where the plugin path comes from

Both versions read `_LOCAL_LANG` through the Extbase configuration manager, which caches
the configuration per lowercased extension name and plugin name. The Extbase bootstrap has
filled the entry of the rendering plugin, `_LOCAL_LANG` of `plugin.tx_<ext>_<plugin>`
included, so a translation with the plugin's own extension name finds it. A translation
with another extension's name misses that entry and reads the path of that extension only;
the profile editor's `detail.since` and `detail.till` of academic_persons are such
translations.

The page templates of partners, projects and programs and the study plan content element
are not rendered by a plugin: a site sets their labels under the path of the extension.

The profile editor translated the options of its selects with `persons_edit`, which is no
spelling of the extension at all, and read their overrides from `plugin.tx_persons_edit`.
It passes `AcademicPersonsEdit` now, as its flash messages do.

## One translation with the right name leaks to the others

The core keeps the labels of a language file, overrides included, for the whole request,
and writes the overrides in only when a translation finds some. Once one translation has
read the right path, every later translation of the same file shows those overrides,
whatever name it passes. Before ACE-740, an override therefore reached some labels of a
page and not others, depending on the order they rendered in.

A functional test of a label override is subject to the same rule: it proves its own call
only when that call is the first translation of its file in the request. The label tests
hide the category filter of the lists, which translates correctly since ACE-739, and
render the sorting select from a partial of their own, without the labels of the shipped
one, so that the view helper's default name is what the options are read with. The cases
after the first translation of a file are held by `TranslationExtensionNameTest`.

## How it is tested

- **One functional test per extension**, `*LabelOverrideTest`, renders each kind of
  translation - inline, tag, over several lines, inside the argument of another view
  helper, a key from a variable, a full `LLL:` reference, translated by a view helper or a
  controller - through the chain: the label of the language file, the override of the
  extension, the override of the plugin, and the plugin winning over the extension. On
  v12 and v13 every override case failed before the change.
- **[`TranslationExtensionNameTest`](../testing/unit-tests.md#the-extension-name-of-translations)**
  fails on a template translation that passes an underscored extension name or none at
  all, and on a `translate()` call in PHP that passes an underscored one, which covers the
  calls the functional tests do not render or that come later.

## Language file overrides

Replacing the label in the language file itself works on every path, through
`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`.

## Not covered

- The page module summary of `category_types` renders in the backend, where no
  `_LOCAL_LANG` applies. Its template passes `CategoryTypes` for consistency; the
  language file is the same.
- The messages of a rejected image upload in the profile editor come from the file upload
  converter of academic_base, which translates them without an extension name: no
  `_LOCAL_LANG` reaches them, a language file override does.

## See also

- [List filter types](list-filter-types.md) - the per-type "All" label of the filters,
  the first templates that were changed
- [Unit tests](../testing/unit-tests.md#the-extension-name-of-translations)
- [Core version aware code](core-version-aware-code.md)
