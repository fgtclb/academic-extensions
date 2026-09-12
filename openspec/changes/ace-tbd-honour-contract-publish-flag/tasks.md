## 1. Prerequisite

- [ ] 1.1 Confirm that `ace-tbd-contract-display-policy` (or its renamed
  successor) is merged on `main`. This change builds on its contract
  selection and must not start before it.

## 2. Site setting

- [ ] 2.1 Declare `plugin.tx_academicpersons.contracts.respectPublishFlag`
  (bool, default true) in `Configuration/Sets/Full/settings.definitions.yaml`
  and `Configuration/TypoScript/Default/constants.typoscript` with the same
  default, and map it in `setup.typoscript`. Verify with the existing
  `SiteSetDeliveryTest` that the setting arrives in the plugin settings with the
  site set and with the static template.

## 3. Tests first

- [ ] 3.1 Add a detail fixture: one profile, one published and one unpublished
  contract, each with its own e-mail address. Add tests for the setting at its
  default (published address only) and switched off (both addresses). Record
  that the default test fails against the unchanged selection.
- [ ] 3.2 Add the same pair for the list and card plugins, and record the
  failure of the default test.
- [ ] 3.3 Add a selected-contracts test with one unpublished selected contract
  and the setting at its default, and record its failure.
- [ ] 3.4 Add a unit test of the contract selection covering the flag, both
  ways.

## 4. Implementation

- [ ] 4.1 Add the publish rule to the contract selection and its value object;
  verify 3.1, 3.2 and 3.4 pass.
- [ ] 4.2 Apply the rule to the selected contracts after
  `ModifySelectedContractsEvent`; verify 3.3 passes.
- [ ] 4.3 Remove the rule on purpose and watch 3.1 to 3.3 go red; restore.
- [ ] 4.4 Check the existing persons functional fixtures for contracts with
  `publish = 0` that now disappear from rendered output, and adjust each
  fixture or its assertion deliberately, naming each one in the pull request.

## 5. Documentation

- [ ] 5.1 Add `Documentation/Changelog/3.0/Breaking-ContractPublishFlag.rst`
  from `Build/Documentation/Templates/`: the new default, the affected
  installations, the documented `UPDATE` statement and the off switch, with the
  data-loss warning in its first paragraph.
- [ ] 5.2 Document the setting and the statement in the configuration and
  upgrade chapters of `Documentation/`; verify with `checkRstRenderingAll`.
- [ ] 5.3 Add an integrator and developer section to `Documentation/` on
  making new contracts published by default in a project: the column
  definition in the site package's `ext_tables.sql` and the TCA `default` in
  an override, and that contracts created through Extbase (frontend editor,
  importers persisting the model) keep the model's `false`. Verify the
  `ext_tables.sql` override on v13 and v14 with the database analyser of a
  functional test instance before writing it down.
- [ ] 5.4 Update `docs/architecture/typoscript-and-site-sets.md` if it
  enumerates the persons site settings; verify with `lintMarkdown -n`.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue in YouTrack, verify its key
  and rename the change to `ace-<NNN>-honour-contract-publish-flag`.
- [ ] 6.2 Commit as `[!!!][FEATURE] ACE-<NNN>: Honour the contract publish flag`
  in TYPO3 Core format.

## 7. Definition of done

- [ ] 7.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 7.2 `composerUpdate`, then the same suites green with `-t 14`.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog updated as in group 5;
  `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 7.5 No backport: a behaviour change on a maintenance line. State it in
  the pull request, together with anything else left out.
- [ ] 7.6 Archive the change as the last commit of the pull request.
