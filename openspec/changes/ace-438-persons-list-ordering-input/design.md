## Context

Measured on `2` `be2cd1678` in `packages/fgtclb/academic-persons`, against
TYPO3 12.4.45 and 13.4.36. The decisions were taken for `main` in the change
of the same name (#950, #959) and are carried over, this design records what
differs on this branch.

**Identical to `main` before #959.** `Classes/DemandValues/AbstractDemandValues.php`,
`Classes/Types/AbstractTypes.php`, `Classes/Tca/DemandValues.php` and the unit
tests `AbstractDemandValuesTest`, `AbstractTypesTest`, `DemandValuesTest` and
`TypesTest` are byte-identical, so the fixed parsers and their tests apply as
they are. `ProfileDemand::setSortByDirection()` (`:81-85`) stores any string,
as it did on `main`. `ProfileRepository::getOrderingsFromDemand()` (`:346-361`)
has the same strict allow list, and the uid fallback is appended with `+`
(`:313`).

**`GeneralUtility::trimExplode()` on v12.** 12.4.45 declares it without types
(`:947`) and casts the string with `(string)`. The splitting is the same as on
v13, so the parser behaves the same. An array handed to it raises "Array to
string conversion" instead of a `TypeError`, which only changes how the guard
test for an array fails before the fix.

**`ExtensionConfiguration::get()` on v12.** The same restore path as on v13:
a missing path is restored from `ext_conf_template.txt` through
`synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions(true)`, so the
two exceptions the guard catches mean an extension or a path the template does
not declare. An unwritable settings file throws `SettingsWriteException` from
`ConfigurationManager::writeLocalConfiguration()`, which stays uncaught, as on
`main`.

**Tests on v12.** A backend form is compiled with
`GeneralUtility::makeInstance(TcaDatabaseRecord::class)`, because the class is
not a public service on v12 (`CardFlexFormFieldsTest`). A repository test
builds `FrontendTypoScript` without its constructor, which takes two arguments
on v12 and four on v13 (`ProfileRepositoryVisibilityWindowTest`).
`AcademicPersonsListPluginTest::setUpFrontendRootPageForTestCase()` takes no
arguments and gains an optional list of constant files.

**Documentation.** The manual `Documentation/Configuration/General/Index.rst:93-96`
gives the group values as the default of `demand.allowedSortByValues`.
`docs/architecture/class-design.md:128-129` names both abstract classes with
`:17`, both are at `:19`. `docs/architecture/backend-select-items.md` has the
section "A handler appends to the declared items" (`:86`). `ProfileDemand` is
not `@api` on this branch.

## Goals / Non-Goals

**Goals:**

- No configured value list carries an item with an empty value, and no entry
  of it raises a PHP warning, on v12 and v13.
- The demand values read the extension configuration the way the types do.
- A sort direction that differs from `asc` or `desc` only in letter case or
  surrounding whitespace is applied, and an unknown one keeps the ordering.

**Non-Goals:**

- Anything beyond what #959 did on `main`.

## Decisions

### 1. Four commits in one pull request

The proposal, the ACE-438 fix, the ACE-439 fix and the archive, so each issue
is resolved by a commit of its own on this branch too. This is the shape
Stefan chose for `main`, with the proposal as a commit of its own because the
change is created on this branch.

### 2. The code and tests of `main`, adapted where `2` differs

The parsers, the guard and the setter are taken over unchanged, the tests with
the v12 differences above. The changelog entries go to `Changelog/2.4/` with
the labels of `main`, which are unused on this branch, and the ACE-439 entry
does not call `ProfileDemand` an API.

### 3. An unknown sort field keeps the uid order

As on `main`, decision 5 there, option (a).

## Risks / Trade-offs

- [A record was stored with a type that came from `=Label`] → It loses its
  item and the backend shows it as an invalid value. Accepted, as on `main`.
- [A listener relies on reading back an uppercase direction it wrote] → It
  now reads the lowercase form. Named in the `Important` entry.
- [The DBMS reaction to the empty identifier on v12] → Measured again on
  SQLite, PostgreSQL and MariaDB, task 2.4.

## Migration Plan

None. Only configurations that are broken today render differently.
