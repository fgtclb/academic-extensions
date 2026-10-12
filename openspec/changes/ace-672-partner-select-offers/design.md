## Context

See `proposal.md` for the motivation. Read on `main` at `e75f86b95`, against
the installed TYPO3 13.4.35 and 14.3.7, on 2026-10-12. Every point is a
premise to measure again before code is written.

- `PartnerItems::itemsProcFunc()` (`academic-partners/Classes/Backend/FormEngine/PartnerItems.php:16-36`)
  builds `Typo3QuerySettings` through `GeneralUtility::makeInstance()`, sets
  only `setRespectStoragePage(false)` and hands them to
  `PartnerRepository::findAll()` (`PartnerRepository.php:39-55`), which sets
  no enable field option either. The two `@todo` lines are at `:20-21`.
- `Typo3QuerySettings::__construct()` (`:98-102` on both cores) switches
  `ignoreEnableFields` on when `$GLOBALS['TYPO3_REQUEST']` is a backend
  request, and only then.
- Outside a frontend request `Typo3DbQueryParser::getVisibilityConstraintStatement()`
  takes the backend branch, and `getBackendConstraintStatement()` reads only
  the flag: with it on, `BackendUtility::BEenableFields()` is skipped
  entirely and only `deleted = 0` remains. `BEenableFields()` covers
  `disabled`, `starttime` and `endtime`, never `fe_group`. The list
  `enableFieldsToBeIgnored` is not read in that branch.
- So on v13 and v14 the select offers hidden partners and partners outside
  their start or end time, and it would not if the handler ran without a
  backend request.
- Core's own `foreign_table` selects apply `DeletedRestriction` and
  `WorkspaceRestriction` only (`AbstractItemProvider.php:610-612` on v13,
  `:652-654` on v14). They offer hidden and timed records too.
- The second `@todo` names a publish property. No column, TCA field or model
  property of that name exists in `academic-partners`.
- `PartnerSelectOrderTest::hiddenPartnersAreOfferedAsBefore()` (`:132`) pins
  the hidden partner through the compiled form, with a backend request.
  `PartnerRepositoryFindAllTest::hiddenPartnersAreNotReturned()` (`:58-64`)
  asserts the opposite without any request (ACE-671).
- `docs/architecture/backend-select-items.md`, "A value that is not among the
  items is lost": a `selectSingle` without `foreign_table` does not restore a
  stored value that the handler did not offer, so the next save writes the
  relation away.

## Goals / Non-Goals

**Goals:**

- The handler states which partners it offers, independent of the global
  request.
- A test that fails when the handler stops stating it.
- A test of `findAll()` whose name says what it measures.

**Non-Goals:**

- A marker for hidden or timed partners in the item label.
- Any change to `PartnerRepository`.
- The `null` check on the result in the handler and the nullable return type
  of `findAll()`. Both are item 5 of ACE-443, which
  `ace-434-coverage-round-leftovers` (pipeline #130, after this one) takes
  together with the baseline entry and a `Breaking` entry. This change leaves
  the loop of the handler as it is, so the two diffs do not overlap.

## Decisions

### 1. Offer every partner page that is not deleted (decision for the maintainer)

- **A. Every partner page that is not deleted.** Nothing changes for an
  editor on v13 and v14. No cost.
- **B. Visible partner pages only.** Hidden and timed partners disappear from
  the select, a behaviour change with an `Important` entry. The handler has to
  put the stored value back into the items, as `ContractSelectScopeResolver`
  does, or every save of a partnership with a hidden partner drops it.
- **C. Hidden partners, but not timed ones.** Partners outside their start or
  end time disappear. The backend branch of Extbase cannot express it, because
  it ignores `enableFieldsToBeIgnored`, so it needs a query of its own, and the
  stored value has to be put back as in B.

**Recommended: A.** It is what core's record selects do, it lets an editor
prepare a partnership before the partner page goes live, and it keeps a
stored value among the items, so no save can lose it. B and C answer a real
concern, an editor picking a partner that is not public, but the cheaper
answer to that concern is the label marker named as a non-goal.

Confirm with the maintainer before writing code. If he chooses B or C, the
delta spec and this design change first, through `/opsx:update`.

### 2. The handler decides, not the repository

`setIgnoreEnableFields(true)` goes on the settings the handler already
builds. `findAll()` stays as it is.

Rejected: setting the flag inside `findAll()`. It is a public method, and in a
frontend request an ignore flag with an empty list drops every enable field
(`getFrontendConstraintStatement()`), so a frontend caller would receive
hidden partners. The question "which partners does the select offer" belongs
to the select.

`enableFieldsToBeIgnored` stays empty. Naming the fields would read as if it
limited anything, and in the backend branch it does not. A short comment in
place of the two `@todo` lines says what is offered and why.

### 3. Tests

- A new test calls the handler without any global request and asserts that a
  hidden partner, a partner before its start time and a partner after its
  end time are among the items, and a deleted one is not. It is red today on
  v13 and v14, because without a backend request the core default stays off.
  This is the red proof of the change.
- The compiled form test gets the same three cases and the unchanged save of
  a partnership whose partner is hidden: the compiled row still holds that
  partner, and after the save the record refers to it. These are green before
  the change, since FormEngine runs below a backend request. They are shown
  red by forcing the flag off in the handler for one run.
- The timed partners go into a fixture of their own, so the order fixture of
  ACE-669, whose titles are anti-correlated with `uid` and `sorting`, stays as
  it is.
- `hiddenPartnersAreOfferedAsBefore()` is renamed to `hiddenPartnersAreOffered()`,
  and its docblock names the decision instead of the open question.

### 4. ACE-671: name the context, do not move the test

`hiddenPartnersAreNotReturned()` becomes
`hiddenPartnersAreNotReturnedOutsideABackendRequest()`, and its docblock says
that it measures the repository default for a caller outside the backend,
and that the select's guarantee is tested where the select is built. It also
guards decision 2: if the flag moved into `findAll()`, this test turns red.

Rejected: setting a backend request in it and asserting the hidden partner.
That measures a core default, not code of this extension, and duplicates the
compiled form test. Deleting it was rejected for the reason above.

### 5. Changelog

`Documentation/Changelog/3.0/Important-PartnerSelectOffersHiddenPartners.rst`:
which partners the select offers, and that an editor cannot yet tell hidden
ones apart. Nothing changes on v13 or v14, but 3.0 is the upgrade target of
installations that ran 2.x on TYPO3 v12, which did not offer them. The 2.x
changelog files are not edited on this branch.

### 6. Branch `2` (decision for the maintainer)

The handler and the test of ACE-671 are the same on `2`, the spec is not:
`openspec/specs/academic-partners/partner-selection/spec.md` there pins
"hidden partners keep the visibility they had, per core version", with
`hiddenPartnersAreOfferedOnCore13AsBefore()` (`not-core-12`) and
`hiddenPartnersAreNotOfferedOnCore12AsBefore()` (`not-core-13`).

- **Align v12 (recommended).** v12 starts to offer hidden and timed partners,
  the two version split tests become one, and `Changelog/2.4/` gets an
  `Important` entry. The reason beyond consistency is the trap of decision 1:
  on v12 a partnership whose partner is hidden or expired probably loses it on
  the next unchanged save. That was not measured, the backport measures it on
  v12 first.
- **Close the branch `2` part as won't fix.** The split is documented by the
  spec there. Acceptable only if the measurement shows no data loss on v12.

## Risks / Trade-offs

- [An editor picks a hidden or expired partner without noticing] → It is the
  current behaviour on v13 and v14. The `Important` entry says it, and the
  label marker is the follow-up. The implementation reads what the partner
  page and the partnership list render for such a partnership and states it
  in the entry, without changing it.
- [The new handler test passes for a different reason] → It sets no global
  request at all, which is the one context where the core default stays off,
  so only the handler's own flag can make it pass.
- [Workspaces] → In an offline workspace `getBackendConstraintStatement()`
  skips `BEenableFields()` already, so nothing changes there.

## Migration Plan

None. No stored value and no configuration changes.
