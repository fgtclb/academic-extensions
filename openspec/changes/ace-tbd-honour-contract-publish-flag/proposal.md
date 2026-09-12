## Why

Every contract of `academic_persons` (`packages/fgtclb/academic-persons`)
carries a toggle labelled "Show this contract online?", with the help text
"Control whether this employment contract and its contact details are shown on
the public profile." No public view reads it. Four projects write the flag in
their importers anyway, one inverted its default with an upgrade wizard of its
own, and one hides the field as meaningless. The field promises a behaviour the
extension does not have.

## What Changes

- **BREAKING** The public views honour the flag from 3.0 on. A new site
  setting controls it; it is on by default and stays available as the off
  switch that restores the previous output.
- With the setting on, an unpublished contract is left out wherever a profile's
  contracts are shown: the list, list-and-detail, card and selected-profiles
  plugins, and the position and contact blocks of the detail view.
- With the setting on, the selected-contracts plugin leaves out an unpublished
  contract, even when an editor selected it.
- The upgrade documentation gives one documented `UPDATE` statement that marks
  every existing contract as published, for installations that want to keep
  showing their contracts without reviewing every record first. No upgrade
  wizard is added.
- New contracts stay unpublished by default. The integrator and developer
  documentation shows how a project changes that default in its own site
  package, through `ext_tables.sql` and a TCA override, and which records
  such a default does not reach.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/contract-publish-flag`: when a contract is shown on the
  public site, depending on its publish flag and the integrator's setting.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the contract selection introduced by the contract
  display policy change (a hard dependency), the selected-contracts action,
  one site setting in the site set and the static template, labels, and a
  `Breaking-*.rst` with the upgrade statement.
- `academic_persons_edit` (`packages/fgtclb/academic-persons-edit`) keeps
  writing the flag; its toggle now has an effect while the setting is on.
- Every installation upgrading to 3.0: contracts that were never marked for
  online display disappear from the public views until the statement is run,
  the flags are reviewed, or the setting is switched off.
- No schema change, no change to the database default.

## Non-goals

- Honouring the flag in `academic_contacts4pages`
  (`packages/fgtclb/academic-contact4pages`), which renders a contact's
  contract directly.
- Honouring it in the backend contract selectors (the existing `@todo` in
  `ContractItems.php`).
- Hiding a profile whose contracts are all unpublished; profile-level consent
  is a separate candidate.
- Changing the default of new contracts upstream: the column, TCA, model and
  frontend editor defaults stay unpublished. It is a decision per project,
  and only documented.
- An upgrade wizard: every wizard is a TYPO3 v15 blocker call site
  (ACE-294), and none is added while v13 is supported.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-04`). Four of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-honour-contract-publish-flag` when the issue is filed after
implementation.
