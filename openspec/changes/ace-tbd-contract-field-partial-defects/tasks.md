## 1. Tests first

- [ ] 1.1 Add the card fixture
  `Tests/Functional/Plugins/Fixtures/AcademicPersonsCardPlugin/cardPage_showFieldsContractRelations.csv`:
  two profiles, one contract each. One contract belongs to a unit with a display
  text, the other to a unit with only a unit name. One contract carries the
  phone number `+49 6241 509 123`. `showFields` is
  `contracts.organisationalUnit,contracts.phoneNumbers`.
- [ ] 1.2 Add tests to `AcademicPersonsCardPluginTest` asserting the label
  "Organisational unit", the display text, the unit name fallback,
  `href="tel:+496241509123"` and the unchanged link text. Run them against the
  unchanged partial and record that every assertion except the link text fails.
- [ ] 1.3 Add a selected-contracts test with the same phone number, so the
  second caller of the partial is covered, and record its failure the same way.

## 2. Implementation

- [ ] 2.1 Add the `organisationalUnit` branch to
  `Resources/Private/Partials/Profile/Contract/Field.html` (display text,
  falling back to unit name) and verify the tests of 1.2 pass.
- [ ] 2.2 Add `contracts.organisationalUnit` to `locallang.xlf` and
  `de.locallang.xlf` (one line, two-space indentation) and verify the label
  assertion passes.
- [ ] 2.3 Build the `tel:` target with `f:replace` as `Contact.html` does and
  verify the tests of 1.2 and 1.3 pass.
- [ ] 2.4 Break each change on purpose once more (drop the branch, restore
  the raw href) and watch the matching assertion go red; restore.

## 3. Phone link prefix

- [ ] 3.1 Add tests first: a card, a selected-contracts and a detail test with
  the prefix `+49 6241 509` and a stored number `123`, asserting
  `href="tel:+496241509123"` and the link text `123`, plus a
  contacts-for-pages rendering test with the same data. Record that they
  fail without the setting.
- [ ] 3.2 Declare `plugin.tx_academicpersons.phoneNumbers.telPrefix` (string,
  default empty) in `Configuration/Sets/Full/settings.definitions.yaml` and
  `Configuration/TypoScript/Default/constants.typoscript` with the same
  default, map it in `setup.typoscript`, and map it in the setup of
  `academic_contacts4pages` as `detailPid` is mapped. Verify with
  `SiteSetDeliveryTest` that it arrives with the site set and with the static
  template.
- [ ] 3.3 Verify whether the contacts processor of `academic_contacts4pages`
  renders the item with plugin settings that carry the prefix; map it there
  too, or name the gap in the pull request.
- [ ] 3.4 Prepend the prefix before removing spaces in `Field.html` and in
  `PublicProfile/Contact.html`; verify 3.1 and the tests of group 1 pass,
  then drop the prefix from one partial on purpose and watch its test go red;
  restore.

## 4. Documentation

- [ ] 4.1 Check `Documentation/` for a description of the `showFields` items
  and correct it if it lists the unit as unsupported. Verify with
  `checkRstRenderingAll`.
- [ ] 4.2 Document the prefix setting in the configuration chapter of
  `Documentation/`: it is unconditional, and meant for installations that
  store extensions only.
- [ ] 4.3 Add `Documentation/Changelog/3.0/Feature-PhoneLinkPrefix.rst` from
  `Build/Documentation/Templates/` for the setting. The two corrections of
  group 2 get no entry of their own: they ask nothing of an integrator.
  Record that in the pull request text.
- [ ] 4.4 Update `docs/architecture/typoscript-and-site-sets.md` if it
  enumerates the persons site settings; verify with `lintMarkdown -n`.

## 5. Backport

- [ ] 5.1 Backport the corrections of group 2 only: separate change on branch
  2 after a backport analysis (`docs/workflow/backporting.md`). Diff
  `Field.html` and both XLF files between the branches first; branch `2`
  indents XLF with tabs. The prefix setting is a feature and is not
  backported.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue in YouTrack, verify its key
  and rename the change to `ace-<NNN>-contract-field-partial-defects`.
- [ ] 6.2 Commit the corrections as
  `[BUGFIX] ACE-<NNN>: Render contract unit and tel: targets` and the
  setting as `[FEATURE] ACE-<NNN>: Add a prefix for phone link targets`, in
  TYPO3 Core format, so the bugfix commit can be backported on its own.

## 7. Definition of done

- [ ] 7.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 7.2 `composerUpdate`, then the same suites green with `-t 14`.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog updated as in group 4;
  `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 7.5 Anything left out is named in the pull request, with the reason.
- [ ] 7.6 Archive the change as the last commit of the pull request.
