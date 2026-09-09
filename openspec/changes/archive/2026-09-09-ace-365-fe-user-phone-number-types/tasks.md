## 1. Telephone-only contract bugfix

- [x] 1.1 Add focused functional coverage for profile creation and update when `fe_users.telephone` is the only contract value, run it against the unchanged implementation to record the expected failure, and verify the assertions cover the complete resulting record set.
- [x] 1.2 Replace both `fe_users.phone` guards with `fe_users.telephone` and verify the focused creation/update tests pass on TYPO3 v12 and v13.

## 2. Configurable imported phone-number types

- [x] 2.0 Add the `uid` tiebreaker to `PhoneNumberRepository::findByContractIncludingHidden()`, which the record match depends on and which this branch does not carry yet.
- [x] 2.1 Add the `profile.feuser.telephoneNumberType` and `profile.feuser.faxNumberType` options with default `business`, their English/German labels, and matching values in both tracked development-instance configurations; verify the resulting configuration arrays use the intended nested path.
- [x] 2.2 Add a stateless internal resolver used by synchronisation that validates configured values against the available phone-number types and falls back to `''`; add unit coverage for defaults, independent values, custom valid values, invalid values, and a missing `business` type, then prove a validation test fails without the resolver behaviour and passes with it.
- [x] 2.3 Refactor imported phone-number synchronisation to separate source field, type, and identifier; preserve selectable existing types, correct only exact legacy-invalid values, and verify focused functional tests fail before and pass after the change.
- [x] 2.4 Add canonical `telephone:fe_users:<uid>` matching plus the self-healing `phone:fe_users:<uid>` fallback, retain `fax:fe_users:<uid>`, and verify repeated synchronisation creates no duplicate while canonical/legacy collisions are not deleted or merged.
- [x] 2.5 Update the existing create/update assert CSV fixtures for the new default types and identifiers and verify the affected functional test classes pass for TYPO3 v12 and v13.

## 3. Documentation

- [x] 3.1 Document the two options, defaults, validation, and fallback in the extension configuration reference and correct the documented default phone-number type list; verify the option names match the implementation exactly.
- [x] 3.2 Add the 2.4 `Feature-ConfigurableFrontendUserPhoneNumberTypes.rst` changelog covering stored values, identifiers, the runtime repair, valid-value preservation, and unchanged fax contract membership, plus the `Important-` entry for the changed identifier; verify both are included by the changelog glob.
- [x] 3.3 Add the frontend-user contact import behaviour and migration decisions to `docs/` and link the section from its architecture index; verify there is no duplicated README or CONTRIBUTING content.

## 4. Definition of done and user-controlled commits

- [x] 4.1 Inspect the complete diff, confirm only ACE-365 and its OpenSpec artifacts are present, and hand the uncommitted state plus all intermediate test results to the user.
- [x] 4.2 User runs `composerUpdate`, `phpstan`, `unit`, and `functional` separately for TYPO3 v12 and v13, plus `lintPhp`, `cgl -n`, `lintMarkdown -n`, RST rendering, and the synchronisation tests on SQLite and PostgreSQL; record every result before declaring the change complete.
- [x] 4.3 After the user reports successful final tests and explicitly approves commits, create the TYPO3 Core-style commit `[FEATURE] ACE-545: Configure imported phone types`, carrying the telephone-only contract fix with it, and with no unrelated files.
- [x] 4.4 Archive the OpenSpec change as the final commit of the pull request, verify the delta is folded into `openspec/specs/`, and do not push without a separate explicit user request.
