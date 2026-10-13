## Context

Backport of `ace-430-partnerships-per-language` on `main` (archived there as
`2026-10-13-ace-430-partnerships-per-language`), with the read side of ACE-484
(`cb0cef0e2` on `main`) for the page contacts, which never reached this
branch. Derived from the backport analysis and measured on `origin/2` at
`18162f208` against TYPO3 12.4.45 and 13.4.36.

- `PartnershipRepository::findByPid()` and `PartnershipProcessor` are
  byte-identical to `main` before its change. `PartnerController` differs in
  style (promoted `protected` properties, not final) but builds the role map
  with the same loop twice. The partnership list and teaser templates differ
  in markup and compare `{partnership.role} == {role}` the same way.
- `ContactRepository::findByPid()` forces `OVERLAYS_ON`, lifts
  `respectSysLanguage` and matches `page = <uid>`, and handles the plugin
  option `$showHidden` through `setIgnoreEnableFields(true)` with
  `setEnableFieldsToBeIgnored(['disabled'])`. It uses neither
  `HiddenRecordsFetcher` (ACE-857, `main` only) nor `HiddenRecordsQueryTrait`.
  `ContactRepositoryFindByPidTest` pins the duplicates in four tests.
- The page contact templates compare the role by uid already.
- TYPO3 v12 behaves like v13 where it matters: `Typo3DbBackend` overlays with
  `OVERLAYS_MIXED` internally (`:540` on 12.4.45), the data mapper resolves a
  relation in the language of its parent record when `respectSysLanguage` is
  lifted (`:418`), and it looks a relation up in the identity map by the plain
  uid (`DataMapper.php:685`), as v13 does at `:742`. Measured: on v12 the
  tests ported from `main` fail before the change exactly where they fail on
  v13, and a translated partnership after an untranslated one names the
  default language role.
- In a frontend request whose visibility includes hidden content, the Extbase
  query of both repositories returns hidden records, on v12 too (the preview
  tests pass before the change).

## Goals / Non-Goals

**Goals:**

- One row per partnership and per contact and language, the same on v12 and
  v13, and the role groups of the partnership list and teaser complete.

**Non-Goals:**

- The DataHandler hook of ACE-484, any TCA or schema change, an upgrade
  wizard.

## Decisions

### 1. The pre-query of `main`, in both repositories

Both repositories resolve the matching uids per language first, with the rule
of `main` (records in language "all", in the default language only default
records, in a translated language its own records plus the default records
without a translation there, the lowest uid winning), and fetch exactly those
uids with `respectSysLanguage` lifted and the overlay pinned to
`OVERLAYS_MIXED`, keeping `sorting`, `uid`, and matching `uid = 0` for an
empty list. `PartnershipRepository` takes the file of `main` as it is.

### 2. The pre-query follows the preview, for the contacts too

The pre-query applies `DeletedRestriction`, the `WorkspaceRestriction` of the
current workspace and `HiddenRestriction`, the last one unless the visibility
of the context includes hidden content. For the contacts it is also left out
when the plugin option shows hidden records, as on `main`. `main`'s
`ContactRepository` leaves the preview out and therefore hides hidden contacts
in a preview since ACE-484. Porting that would bring the same regression to
this branch, so the contacts here follow the preview, and `main` has the
follow-up ACE-905. The Extbase part of the contacts stays as this branch has it.

Rejected: `HiddenRecordsFetcher` of `main` and folding the hidden translation
handling of ACE-857 into this change. Out of scope here.

### 3. The role groups compare the role by uid

As on `main`: the templates compare `role.uid`, and `PartnershipRoles`
collects the roles for the controller and the processor, keeping the role in
the visitor's language for the heading. `PartnerController` is not final on
this branch, so a site package may extend it and call its constructor. It
therefore gets the service through a final `injectPartnershipRoles()`, as it
gets the `FilterTypeResolver`, and its constructor stays as it is
(`docs/architecture/class-design.md`). The processor has no container
instance and keeps `GeneralUtility::makeInstance()`.

### 4. Tests

The tests of `main` are ported. A visible record with a hidden translation
on the page is added for both repositories: without the `hidden` filter the
pre-query would pick the hidden translation and the record would vanish in
that language. The version pair about the identity map
becomes one test without a group, since v12 and v13 behave the same. The four
pinned contact tests are replaced by the language tests of `main`. The preview
tests build the TypoScript of the simulated request without its constructor,
whose signature differs between v12 and v13.

## Risks / Trade-offs

- [A copy and a proper translation of one partnership coexist] → The copy
  decides the position, and the core overlay picks one of the two without an
  order. Both carry the same partner and role.
- [v12 and v13 name the default language role after an untranslated
  partnership] → Core behaviour, older than this change, pinned in a test and
  named in the changelog.

## Migration Plan

None. The duplicated rows stay in the database and stop rendering twice.
