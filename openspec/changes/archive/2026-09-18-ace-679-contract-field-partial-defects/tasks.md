## 1. Tests first

- [x] 1.1 Add the fixture
  `Tests/Functional/Plugins/Fixtures/AcademicPersonsContractFieldRendering/selectedContractsPage.csv`:
  three contracts, one belonging to a unit with a display text, one to a unit
  with only a unit name, one to no unit. One contract carries the phone number
  `+49 6241 509 123`. `showFields` is
  `contracts.organisationalUnit,contracts.phoneNumbers`.
- [x] 1.2 Add `Tests/Functional/Plugins/AcademicPersonsContractFieldRenderingTest`,
  rendering the shipped templates through the selected contracts plugin in the
  shape this branch's plugin tests have, asserting the label
  "Organisational Unit", the display text, the unit name fallback, the absent
  row of the contract without a unit, `href="tel:+496241509123"` and the
  unchanged link text. Record that every assertion except the link text fails.
- [x] 1.3 Add a detail plugin test for the same phone number, so the second
  template that builds a `tel:` target is covered, and record its failure the
  same way.

## 2. Implementation

- [x] 2.1 Add the `organisationalUnit` branch to
  `Resources/Private/Partials/Profile/Contract/Field.html` (display text,
  falling back to unit name) and verify the tests of 1.2 pass.
- [x] 2.2 Add `contracts.organisationalUnit` to `locallang.xlf` and
  `de.locallang.xlf`, indented with tabs like their neighbours, and verify the
  label assertion passes.
- [x] 2.3 Strip the spaces of the `tel:` target with `f:replace` in
  `Field.html` and in `Resources/Private/Templates/Profile/Detail.html`, and
  verify the tests of 1.2 and 1.3 pass.
- [x] 2.4 Break each change on purpose (drop the branch, restore each raw
  href) and watch the matching assertion go red; restore.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Important-*.rst` for the two
  corrections. Verify with `checkRstRenderingAll`.
- [x] 3.2 Check `Documentation/` for a description of the `showFields` items
  and correct it if it lists the unit as unsupported.

## 4. Definition of done

- [x] 4.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 12`.
- [x] 4.2 `composerUpdate`, then the same suites green with `-t 13`.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 `Documentation/` changelog updated as in group 3; `README.md` and
  `CONTRIBUTING.md` still only summarise.
- [x] 4.5 Anything left out is named in the pull request, with the reason.
- [x] 4.6 Archive the change as the last commit of the pull request.
