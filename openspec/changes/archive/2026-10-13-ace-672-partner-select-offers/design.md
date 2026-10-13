## Context

Measured on `2` `52171fbea` in `packages/fgtclb/academic-partners`, against
TYPO3 12.4.45 and 13.4.36. The decisions were taken for `main` in the change of
the same name (#950, #961) and are carried over, this design records what
differs on this branch.

**The handler.** `Classes/Backend/FormEngine/PartnerItems.php` is the one of
`main` apart from the item icon (`:32`, the partnership record icon). It builds
`Typo3QuerySettings` through `GeneralUtility::makeInstance()`, sets only
`setRespectStoragePage(false)` and carries the same two `@todo` lines.
`PartnerRepositoryFindAllTest` is byte-identical to `main` before #961.

**The core default per version.** `Typo3QuerySettings::__construct()` on
13.4.36 switches `ignoreEnableFields` on for a backend request. On 12.4.45 it
does so only when the deprecated Extbase feature toggle
`ignoreAllEnableFieldsInBe` is on, which is off by default.
`Typo3DbQueryParser::getBackendConstraintStatement()` is the same on both: with
the flag on, `BackendUtility::BEenableFields()` is skipped and only the soft
delete clause remains.

**The unchanged save on v12.** Measured with a scratch test: a partnership
whose partner is hidden, starts in the future or has expired compiles with
`databaseRow['partner'] = []`. `TcaSelectItems::addData()` runs
`processSelectFieldValue()` before `addInvalidItemsFromDatabase()`, so the
select gets a `[ MISSING LABEL ("15") ]` item that is not selected,
`SelectSingleElement` selects by the row value, the browser submits the first
entry `0`, and the save stores `partner = 0`. The same fixture on 13.4.36 keeps
all three partners.

**The tests.** `PartnerSelectOrderTest` pins the split with
`partnersAreOfferedInAlphabeticalOrderOnCore13()` (`not-core-12`) and
`...OnCore12()` (`not-core-13`), and with
`hiddenPartnersAreOfferedOnCore13AsBefore()` and
`hiddenPartnersAreNotOfferedOnCore12AsBefore()`. On this branch `not-core-13`
means v12 only. `TcaDatabaseRecord` is not a public service on v12, so the
form is compiled with the group from `GeneralUtility::makeInstance()`.

**The documentation.** `docs/architecture/backend-select-items.md` has no
section about enable fields, and its table says `[ INVALID VALUE ]` for a
`selectSingle` value missing from the items. `TcaSelectItems` uses
`labels.noMatchingLabel`, which is `MISSING LABEL` on 12.4.45 and 13.4.36.

## Goals / Non-Goals

**Goals:**

- The handler states which partners it offers, the same on v12 and v13.
- A partnership keeps its partner on an unchanged save on v12.
- Tests that fail when the handler stops stating it.

**Non-Goals:**

- Any change to `PartnerRepository`.
- A marker for hidden or timed partners in the item label.

## Decisions

### 1. Offer every partner page that is not deleted

Decision A of `main`, carried over. `setIgnoreEnableFields(true)` on the
settings the handler builds, a comment in place of the two `@todo` lines. On
v12 this is a behaviour change: hidden and timed partners appear in the select,
which is what keeps them on save.

### 2. The handler decides, not the repository

As on `main`: in a frontend request an ignore flag with an empty list drops
every enable field, so `findAll()` stays neutral.

### 3. Tests

- `PartnerItemsTest` calls the handler without any request. Red on v12 and v13
  before the change.
- `PartnerSelectVisibilityTest` compiles the form for hidden, future and
  expired partners and saves a partnership with each of them unchanged. Red on
  v12 before the change, which is the data loss. Green on v13 before the change
  and shown red there by forcing the flag off in the handler.
- `PartnerSelectOrderTest`: each version split pair becomes one shared test,
  and the class docblock loses its paragraph about the split. The shared order
  test is red on v12 before the change.
- `PartnerRepositoryFindAllTest`: the rename of ACE-671, shown red with the
  flag set inside `findAll()`.

The scenario "Hidden partners keep the visibility they had, per core
version" keeps its name, because a modified requirement has to carry every
scenario of the spec by name, and only its outcome changes: v12 offers them
too.

### 4. Changelog

`Documentation/Changelog/2.4/Important-PartnerSelectOffersHiddenPartners.rst`:
v12 starts to offer hidden and timed partners and keeps such a partner on save,
v13 does not change, and what the frontend renders for such a partnership.

### 5. Commit tag

`[BUGFIX]` on this branch, because it ends the loss of data on v12.

## Risks / Trade-offs

- [An editor on v12 picks a hidden or expired partner without noticing] → The
  `Important` entry says so, and v13 has offered them all along.

## Migration Plan

None. A partner already lost to a save on v12 cannot be restored by this
change.
