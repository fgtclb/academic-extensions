## 1. Preparation

- [ ] 1.1 Read ACE-50, ACE-20 and ACE-474 in YouTrack and align the tasks with
  the acceptance criteria of ACE-50; note every difference in this file.
- [ ] 1.2 Confirm `ace-tbd-profile-query-constraint-event` (or its renamed
  successor) is merged, and verify the event names, the context type and the
  context-aware uid finders in the merged sources.

## 2. Profile field

- [ ] 2.1 Add `public_display` to the profile TCA and model with labels in
  English and German; check with `DefaultTcaSchema` on v13 and v14 which
  column core derives, and add a TCA functional test asserting the default
  `0` and `l10n_mode` `exclude`.

## 3. Plugin option and constraints

- [ ] 3.1 Add `settings.publicDisplayOnly` to the six FlexForm files, and
  assert the field in every data structure on v13 and v14 in a functional
  test.
- [ ] 3.2 Add the consent listener on the profile query event; add functional
  list and card tests with one consenting and one non-consenting profile
  (option on: one profile; option off: both), and show the option-on test
  fails without the listener.
- [ ] 3.3 Extend the listener to the contract query event and the uid
  lookups, without changing any `findByUids()` signature; add functional
  selected profiles and selected contracts tests, and one with hidden records
  shown, and show them fail without the listener.
- [ ] 3.4 Verify that the letter availability query applies the constraints
  of the profile query event; if it does not, make it apply them. Add a
  functional test where the only profile of one letter does not consent and
  the option is on, asserting that letter is not linked, and show it fails
  without the constraint.
- [ ] 3.5 Answer "page not found" in the detail action; add functional detail
  and listanddetail tests asserting the 404 for a non-consenting profile, and
  show them fail without the check.
- [ ] 3.6 Declare `plugin.tx_academicpersons.publicDisplay.required` (bool,
  default false) in `Configuration/Sets/Full/settings.definitions.yaml` and
  `Configuration/TypoScript/Default/constants.typoscript` with the same
  default, and map it in `setup.typoscript`; verify with
  `SiteSetDeliveryTest` for the site set and the static template.
- [ ] 3.7 Require consent in the listener and the detail action when either
  the site setting or the plugin option is on; add list and detail tests
  with the site setting on and the plugin option off (consent required) and
  both off (unchanged), and show the first fails while only the plugin
  option is read.

## 4. Documentation

- [ ] 4.1 Document the field, the option, the site setting and their OR, and
  the remaining gap for imports, in
  `Documentation/Configuration/General/Index.rst`.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-PublicDisplayConsent.rst`
  with the migration example for an existing consent column, the site
  setting, and links to the two follow-up changes; while either has not
  landed, name its gap there.
- [ ] 4.3 Add a section on opt-in visibility constraints to `docs/`, linked
  from `docs/architecture/Index.md`.
- [ ] 4.4 Propose the two follow-up changes for 3.0, consent in
  `academic_contacts4pages` and the consent toggle in the frontend editor,
  before this change is merged.

## 5. Issue and commit

- [ ] 5.1 Verify ACE-50 with a GET request.
- [ ] 5.2 Rename the change to `ace-50-public-display-consent` and verify
  `openspec validate` passes under the new name.
- [ ] 5.3 Commit as `[FEATURE] ACE-50: Require consent for public display` in
  TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres` for the new
  tests.
- [ ] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres` for the new
  tests.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
