## Context

`f:translate` and `LocalizationUtility::translate()` read `_LOCAL_LANG` of the extension
name they are given. TYPO3 v13 builds `plugin.tx_` + the name, lowercased; v14 strips
underscores first. The templates passed the extension key, so v13 read
`plugin.tx_academic_<extension>`. ACE-739 changed the filter partials and the finder;
274 calls in 76 templates were left, plus PHP that translates itself: the three sorting
select view helpers, the job alerts, the select items trait of `academic_base` used by the
job form, the profile editor controller and its option service.

Measured before deciding, with the partner test and every casing on both versions (details
in `docs/architecture/label-overrides.md`):

- `AcademicPartners` reads the documented paths and the language file on v13 and v14.
- `academicpartners` reads the paths but loses the language file on both: the file is
  found through the camel case of the name.
- v13 applies the overrides of the name to a full `LLL:EXT:` key too, so the places with
  such keys - the editor's option service, help texts, section headings and country
  options, and the select items trait - are affected as well.
- v14 reads the plugin path only from an Extbase request handed to `translate()`. PHP that
  hands none loses the plugin override on v14 even with the right name.
- v13 keeps the labels of a language file, overrides included, for the whole request, so a
  correctly named translation leaks its overrides into later ones.

## Goals / Non-Goals

**Goals:**

- Every frontend label of the eight extensions reads the override of the extension and of
  the rendering plugin on v13 and v14, the plugin winning.
- A test per extension that proves each kind of call, and a check that keeps new
  templates from passing an underscored name.

**Non-Goals:**

- The page module template of `category_types`: backend, no `_LOCAL_LANG`. It is converted
  to `CategoryTypes` anyway so that the check needs no exception.
- Translation domains, which v13 does not know.

## Decisions

- **UpperCamelCase in every template**, by a mechanical rewrite of the `extensionName`
  values. Rejected: the lowercase name without underscores - it reads the paths, but loses
  the language file.
- **View helpers render the core `f:translate`** through the view helper invoker instead of
  calling `LocalizationUtility`: the core view helper hands the request on where v14 needs
  it and needs none on v13, so the view helpers carry no version switch. Their
  `extensionName` argument keeps its name; its default becomes UpperCamelCase.
- **Controllers and services build the argument list and spread it**, appending the request
  on v14 only. A direct fifth argument fails phpstan on v13, whose signature has four; the
  spread passes on both. A switch in the file, not a `Core13/Core14` split: it is two lines,
  and it goes with v13.
- **The select items trait of `academic_base` converts the key itself.** It is `@api` and
  documents an extension key; turning an underscored key into the extension name inside
  keeps every caller, including project code, working and fixes them all. A name without an
  underscore is kept, because the core conversion lowercases before it camel cases.
- **Every translation names its extension.** Without a name, `f:translate` on v13 takes the
  request's and v14 the extension key of an `LLL:EXT:` path first, so a help text of the
  profile editor pointing into the file of `academic_persons` read different paths on the
  two versions; in PHP, no name means no override at all. The editor's help texts, section
  headings and country options, the persons subline and two job labels now name theirs.
- **Tests shaped around the v13 leak**: on v13 a case proves its own call only as the first
  translation of its file in the request. The list fixtures hide the category filter, whose
  partial translates correctly since ACE-739, and render the sorting select from a partial
  of the test that holds the select alone. On v14 a lost or underscored name reads the same
  paths, so no case catches it there either; after the first translation of a file, the
  static check holds a call.
- **One unit test over all templates and classes** in `packages-dev/monorepo-shared`
  rather than a rule per extension: it checks the repository as a whole, like the
  `ext_emconf.php` key check beside it. It fails on a template translation without a name,
  and on an underscored name in a template or handed to `translate()` in PHP. The profile
  editor translates through one method that defaults to its name, so its many calls in
  PHP cannot diverge.
- **A translation domain reference of TYPO3 v14 in the settings is not supported**: a
  translation that passes an extension name makes v14 prefer it, so the help texts,
  section labels and the subline take an `LLL:EXT:` reference - the help texts the editor
  translates in PHP also literal text - as the documentation of the settings says.
- **The address types of the editor keep the name of academic_persons.** Their labels come
  from that extension's configuration and file; the spec names the exception.

## Risks / Trade-offs

- A site that put overrides under `plugin.tx_academic_<extension>` on v12/v13 loses them
  with the update. The `Important-` entries say to move them; ACE-739's entries, which said
  to copy the filter labels because other templates still read the old path, now say to
  move them too.
- Label keys the templates build from variables or from PHP are covered by kind, not one by
  one; the check covers the extension name of every call, not the key.
