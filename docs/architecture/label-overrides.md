# Label overrides

A site changes a label of an extension without copying a template: in TypoScript,
under `plugin.tx_<extension>._LOCAL_LANG` for every plugin of the extension, or under
`plugin.tx_<extension>_<plugin>._LOCAL_LANG` for one of them. The path of the plugin
wins. Which path the core reads is decided by the **extension name** a translation
passes, and TYPO3 v13 and v14 derive the path from it differently. Every frontend label
of the academic extensions reads the documented path on both core versions since
ACE-740; this page is what keeps it that way.

Each extension lists its labels, and the path of each of its content elements, in the
**Labels** page of its configuration chapter.

## The extension name decides the path

Measured with the functional label tests of every extension and, for the filter forms,
in both development instances (ACE-739):

| A translation passes  | TYPO3 v13 reads                          | TYPO3 v14 reads                          | `locallang.xlf` found |
|-----------------------|------------------------------------------|------------------------------------------|-----------------------|
| `'AcademicPartners'`  | `plugin.tx_academicpartners[_<plugin>]`  | `plugin.tx_academicpartners[_<plugin>]`  | yes                   |
| `'academic_partners'` | `plugin.tx_academic_partners` only       | `plugin.tx_academicpartners[_<plugin>]`  | yes                   |
| `'academicpartners'`  | `plugin.tx_academicpartners[_<plugin>]`  | `plugin.tx_academicpartners[_<plugin>]`  | **no**                |

v13 builds `tx_` and the name as given, lowercased; v14's
`LanguageService::loadTypoScriptLabelsFromExtension()` strips the underscores first.
Both find the language file through `GeneralUtility::camelCaseToLowerCaseUnderscored()`
of the name, so a name without the camel case points at an extension
`academicpartners` that does not exist, and the label renders empty. **UpperCamelCase is
the only spelling that works everywhere.** A full `LLL:EXT:` key finds its file itself,
but reads the overrides of the name passed with it all the same - on v13 too, keyed by
the id of the label in that file.

`f:translate` without an extension name picks one itself, and the two versions pick
differently. v13 takes the name of the Extbase request, and the extension key of an
`LLL:EXT:` path only outside a plugin. v14 takes the extension key of an `LLL:EXT:` path
first, inside a plugin too, and the name of the request only for a short key. A full
reference into another extension's file - the help texts of the profile editor point into
the file of `academic_persons` - therefore read the overrides of the editor on v13 and
those of `academic_persons` on v14. **Every translation names its extension**, which
`TranslationExtensionNameTest` checks.

## Where each version finds the plugin path

**v13** reads `_LOCAL_LANG` through the Extbase configuration manager, which caches the
configuration per lowercased extension name and plugin name. The Extbase bootstrap has
filled the entry of the rendering plugin, `_LOCAL_LANG` of `plugin.tx_<ext>_<plugin>`
included, so a translation with the plugin's own extension name finds it. A translation
with another extension's name - the profile editor translating the address types of
`academic_persons` - misses that entry and reads the path of that extension only.

**v14** takes the plugin name from the Extbase request that `LocalizationUtility::
translate()` is handed. `f:translate` hands it on; PHP that calls `translate()` itself
does not, and falls back to `$GLOBALS['TYPO3_REQUEST']`, which is no Extbase request - so
the override of the plugin is lost there, and only the one of the extension applies. v13
has no parameter for the request at all. With another extension's name, v14 reads that
extension's path and its path for the plugin of the request:
`plugin.tx_academicpersons_profileediting` for the address types of the editor.

That is why the PHP places pass it in one of two ways:

- **A view helper renders the core `f:translate`** through
  `$this->renderingContext->getViewHelperInvoker()->invoke(TranslateViewHelper::class, …)`
  instead of calling `LocalizationUtility` - the three sorting select view helpers. The
  core view helper does the right thing on each version, and no switch is needed.
- **A controller or a service builds the argument list** and appends the request on v14
  only, then spreads it: `LocalizationUtility::translate(...$parameters)`. One call fits
  both signatures and phpstan accepts it on both; a direct fifth argument fails phpstan on
  v13. `JobController::translateAlert()`, the profile editor's `ProfileController::
  translate()` and `ProfileFieldOptionsService`, and the select items trait of
  `academic_base` do this, each with a `@todo` to drop the switch with v13. A translation
  in PHP names its extension as well: without one, neither version reads any
  `_LOCAL_LANG` - the help texts and the country options of the editor did not.

The page templates of partners, projects and programs and the study plan content element
are not rendered by a plugin: a site sets their labels under the path of the extension.

## One translation with the right name leaks to the others on v13

v13 keeps one `LanguageService` per locale and language file for the whole request, and
writes the overrides into it only when a translation finds some. Once one translation has
read the right path, every later translation of the same file shows those overrides,
whatever name it passes. Before ACE-740, an override on v13 therefore reached some labels
of a page and not others, depending on the order they rendered in - after the ACE-739
filter form, the partner items showed it, the sorting labels above the form did not.

A functional test of a label override is subject to the same rule: on v13 it proves its
own call only when that call is the first translation of its file in the request. The
label tests hide the category filter of the lists, which translates correctly since
ACE-739, and render the sorting select from a partial of their own, without the labels
of the shipped one, so that the view helper's default name is what the options are read
with. On v14 an underscored or lost extension name reads the same paths - the underscores
are stripped, a short key falls back to the name of the request - so no case can catch
that there either. After the first translation of a file, `TranslationExtensionNameTest`
holds a call.

## A category type without a label

The label of a category type is `sys_category.<group>.<identifier>`, and the language
files only carry the types the extension ships. Every template that names a type hands
that label to `ct:categoryTypeTitle` of `category_types` (ACE-806), which renders it when
it is not empty and the title the type is registered with otherwise:

```html
{f:translate(key: 'sys_category.partners.{type}', extensionName: 'AcademicPartners')
    -> ct:categoryTypeTitle(group: 'partners', identifier: type)}
```

The order is therefore the label set for the plugin, the label set for the extension,
the label of the language file, and the registered title. The lookup itself is not
touched, which is why the title follows it rather than a view helper replacing
`f:translate`. An empty label counts as none: a site that sets a label to an empty
string gets the title. The shipped partner and project types on v13 are the exception,
see below.

**The title is not the `default` of `f:translate`.** That was the first design, and it
broke the overrides of shipped types on v13. Fluid evaluates an argument before the view
helper runs, so the title was resolved first, with `LanguageService::sL()`. v13's `sL()`
caches a resolved label in the runtime cache for the whole request, keyed by the locale
and the reference alone. The shipped titles of partners and projects are
`LLL:EXT:academic_<ext>/Resources/Private/Language/locallang.xlf:sys_category.<group>.<type>`,
the very reference `LocalizationUtility::translate()` hands `sL()` after it applied the
`_LOCAL_LANG` overrides, so it got the cached label without them. The label tests of
both extensions caught it, in the cases that override a category type label. Programs
was not affected only because its titles point to `locallang_be.xlf`. Resolving the
title only after the lookup found nothing keeps any title out of the way of a label.

The same cache decides what a blanked label of such a type shows on v13: the lookup
leaves the blanked label in the cache under the reference that is the title, so the title
is empty too and the type stays unlabelled. On v14, and for programs, the title is shown.
`CategoryTypeTitleTest` of partners pins both, with a test per core version.

The view helper resolves the title with `sL()` for the site language of the request, for
the same reason the page module summary does: `f:translate` answers an empty string for
a literal title, see
[The page module category summary](page-module-category-summary.md#labels-come-from-the-registry-not-from-a-key-convention).
The content and the title are escaped once, by the view helper.

## How it is tested

- **One functional test per extension**, `*LabelOverrideTest`, renders each kind of
  translation - inline, tag, over several lines, inside the argument of another view
  helper, a key from a variable or from PHP, a full `LLL:` reference, translated by a view
  helper, a controller or a service - through the chain: the label of the language file,
  the override of the extension, the override of the plugin, and the plugin winning over
  the extension. On v13 every override case failed before the change.
- **[`TranslationExtensionNameTest`](../testing/unit-tests.md#the-extension-name-of-translations)**
  fails on a template translation that passes an underscored extension name or none at
  all, and on a `translate()` call in PHP that passes an underscored one. The profile
  editor translates through one method that defaults to its name, so its calls cannot
  diverge.
- **`CategoryTypeTitleTest`** of programs, partners and projects registers a type with a
  translated title through a fixture extension and renders every place that names it, in
  English and German, with a label of the extension and of a plugin winning over the
  title. The fixture also retitles a shipped type, so the test sees that its label still
  wins. `CategoryTypeTitleViewHelperTest` of `category_types` covers a literal title, an
  `LLL:` title, an unknown type and the single escaping.
- **`aLiteralHelpTextOfTheFormsIsShownAsItIs`** of the editor's label test, with the fixture
  extension `test_literal_helptext`, shows that a help text configured as literal text
  rather than as a label reference is shown as it is in the document and contact forms.

## Language file overrides

Replacing the label in the language file itself works on every path, under the key of
each core version: `$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on v13,
`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on v14, which renamed it
(Breaking-107436) and does not read the old key.

## Not covered

- The page module summary of `category_types` renders in the backend, where no
  `_LOCAL_LANG` applies. Its template passes `CategoryTypes` for consistency; the
  language file is the same.
- Translation domains (`domain:`), which v14 recommends over `extensionName`, do not exist
  on v13. For the same reason, a translation domain reference of v14,
  `LLL:my_sitepackage.messages:key`, does not resolve in the settings that take a label
  reference - the help texts and document sections of the profile editor, the subline of
  the public profile: the translation passes the extension name, which v14 then prefers.
  Those settings take an `LLL:EXT:` reference; only the help texts the editor translates
  in PHP, those of its document and contact forms, also take literal text.

## See also

- [List filter types](list-filter-types.md) - the per-type "All" label of the filters,
  the first templates that were changed
- [Unit tests](../testing/unit-tests.md#the-extension-name-of-translations)
- [The page module category summary](page-module-category-summary.md) - the registered
  title in the backend
- [Core version aware code](core-version-aware-code.md)
