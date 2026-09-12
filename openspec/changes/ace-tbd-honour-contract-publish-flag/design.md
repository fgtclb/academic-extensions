## Context

The flag and what the code does with it today:

- The flag is `tx_academicpersons_domain_model_contract.publish`,
  `smallint NOT NULL DEFAULT 0` (`ext_tables.sql:38`), `Contract::$publish =
  false` (`Classes/Domain/Model/Contract.php:32`).
- It is not an enable column: the TCA `enablecolumns` carry only `disabled`
  (`Configuration/TCA/tx_academicpersons_domain_model_contract.php:32-34`).
- Outside the model it is read nowhere but in `academic_persons_edit`, and in a
  `@todo` in `Classes/Backend/FormEngine/ContractItems.php:92`.

Every public view reads `profile.contracts` directly today. The contract display
policy change (`ace-tbd-contract-display-policy`) routes all of them through
one contract selection. This change depends on it, and is a small addition to
that selection.

Site settings of `plugin.tx_academicpersons` are declared in
`Configuration/Sets/Full/settings.definitions.yaml` and repeated with the same
default in `Configuration/TypoScript/Default/constants.typoscript`. They reach
the plugin settings through `setup.typoscript`, as `detailPid` does.

## Goals / Non-Goals

**Goals:**

- One rule, applied in one place, for every view that shows a profile's
  contracts.
- The flag means what its label says from 3.0 on, with one switch to restore
  the previous output.

**Non-Goals:**

- Making `publish` an enable column. That would hide unpublished contracts from
  the editing frontend as well, which lists them so they can be published.

## Decisions

### A site setting, not a plugin option

`plugin.tx_academicpersons.contracts.respectPublishFlag` (bool, default
`true`) is mapped to `settings.contracts.respectPublishFlag`. It reaches the
contract selection as one more property of its selection value object.

The flag describes what the data means for the whole site. A FlexForm option
would let a list and the detail view of the same site disagree about the same
contract.

### Filter in the contract selection, including selected contracts

The contract selection drops unpublished contracts when the setting is on.
`selectedContractsAction()` runs the selected contracts through the same rule
before sorting them, after `ModifySelectedContractsEvent`.

The candidate put an SQL condition into `ContractRepository::findByUids()`.
That is rejected. It needs a new parameter on a public repository method, and a
changed signature fatally breaks every subclass or XCLASS that overrides it —
one of the analysed projects has such an override. The selected-contracts
plugin has no pagination and no count, so filtering after the query changes
nothing a visitor sees.

### Decided: the flag is honoured by default in 3.0

The setting defaults to `true` in 3.0, as a `[!!!]` change, and stays
available as the off switch. The earlier draft proposed an opt-in for 3.x and
a default flip in 4.0.

3.0 is not tagged yet. Four of the six analysed projects already force the
flag to published in their importers or with a migration of their own, so the
flip costs them nothing, while a flip in 4.0 would make every installation
migrate the same data a second time. The other option, an opt-in only with no
flip ever, leaves the field's label wrong for good.

### Decided: a documented statement, no upgrade wizard

Existing contracts are published by one `UPDATE` statement documented in the
`Breaking-*.rst` and the upgrade chapter, not by an upgrade wizard:

```sql
UPDATE tx_academicpersons_domain_model_contract SET publish = 1 WHERE publish = 0;
```

It covers every language and workspace row, because it has no other
condition.

The earlier draft added a confirmable wizard. No new upgrade wizard is added
while the branch supports TYPO3 v13: each wizard is a call site of the
v15-blocking `Install\Attribute\UpgradeWizard` API (ACE-294), and the same rule
applies to every change of this round. It is revisited with ACE-294.

Rejected:

- Flipping the column default. Existing rows would be unaffected, new imports
  would change, and nothing would be gained over the statement.
- A migration that runs on its own at runtime. Publishing a contract changes
  what a site shows, which must stay the integrator's decision.

### Decided: no upstream default change for new contracts

New contracts stay unpublished by default: the column default in
`ext_tables.sql`, the TCA `default`, `Contract::$publish` and the
`ContractFormData::$publish` of the frontend editor are not changed. The
integrator and developer documentation shows how a project makes new
contracts published by default in its own site package: a column definition
with `DEFAULT '1'` in the package's `ext_tables.sql`, and the matching TCA
`default` in a TCA override.

Whether new contracts are published by default is a decision per project.
One analysed project inverted exactly these defaults, others rely on the
flag being set deliberately. Changing them upstream would take the decision
away from everyone.

The documentation also names what the two project-level defaults do not
reach: records created through Extbase, such as contracts written by the
frontend editor or an importer that persists `Contract` objects, carry the
value of the model property, not the column default. A project that wants
those published as well sets the flag where it creates them.

## Risks / Trade-offs

- An installation upgrades without reading the changelog, and the contact data
  of every contract nobody ever ticked disappears. → The `Breaking-*.rst` says
  so in its first paragraph, with the statement and the off switch, and the
  setting's label names both.
- The statement publishes a contract an editor deliberately left
  unpublished. → Until now the flag had no effect, so leaving it at 0 was not
  a decision the site could observe. An integrator who needs that distinction
  reviews the flags instead of running the statement.
- The statement is not tested by the suite. → It has no condition beyond the
  flag itself; the documentation shows it verbatim.

## Migration Plan

1. Before or right after the upgrade, decide per installation: review the
   flags, run the documented statement, or switch the setting off.
2. Upgrade and flush caches.

To roll back, switch the setting off. The data needs no restoring.

## Open Questions

None.
