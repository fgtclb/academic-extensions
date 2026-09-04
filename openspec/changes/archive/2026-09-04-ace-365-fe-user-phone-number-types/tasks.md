## 1. Telephone-only contract bugfix

- [x] 1.1 Add focused functional coverage for profile creation and update when `fe_users.telephone` is the only contract value, run it against the unchanged implementation to record the expected failure, and verify the assertions cover the complete resulting record set.
- [x] 1.2 Replace both `fe_users.phone` guards with `fe_users.telephone` and verify the focused creation/update tests pass on TYPO3 v13 and v14.

## 2. Configurable imported phone-number types

- [x] 2.1 Add the `profile.fe_users.telephoneNumberType` and `profile.fe_users.faxNumberType` options with default `business`, their English/German labels, and matching values in both tracked development-instance configurations; verify the resulting configuration arrays use the intended nested path.
- [x] 2.2 Add a stateless internal resolver shared by synchronisation and migration that validates configured values against the available phone-number types and falls back to `''`; add unit coverage for defaults, independent values, custom valid values, invalid values, and a missing `business` type, then prove a validation test fails without the resolver behaviour and passes with it.
- [x] 2.3 Refactor imported phone-number synchronisation to separate source field, type, and identifier; preserve selectable existing types, correct only exact legacy-invalid values, and verify focused functional tests fail before and pass after the change.
- [x] 2.4 Add canonical `telephone:fe_users:<uid>` matching plus the self-healing `phone:fe_users:<uid>` fallback, retain `fax:fe_users:<uid>`, and verify repeated synchronisation creates no duplicate while canonical/legacy collisions are not deleted or merged.
- [x] 2.5 Update the existing create/update assert CSV fixtures for the new default types and identifiers and verify the affected functional test classes pass for TYPO3 v13 and v14.

## 3. Upgrade wizard

- [x] 3.1 Add the TYPO3 v13/v14-compatible `MigrateImportedPhoneNumberTypesUpgradeWizard` with `DatabaseUpdatedPrerequisite`, unrestricted ordered reads, exact PHP identifier validation, and row-local QueryBuilder updates; verify its registration and prerequisites in a functional test.
- [x] 3.2 Add migration fixtures and functional tests covering legacy telephone/fax types, identifier normalization, valid and manual records, hidden/deleted records, canonical collisions, honest `updateNecessary()`, and idempotence; prove the migration assertions fail without the wizard behaviour and pass after implementation.
- [x] 3.3 Run the focused wizard test on SQLite during implementation and leave the MariaDB, MySQL, and PostgreSQL runs for the user's final test pass.

## 4. Documentation

- [x] 4.1 Document the two options, defaults, validation, and fallback in the extension configuration reference and correct the documented default phone-number type list; verify the option names match the implementation exactly.
- [x] 4.2 Add the 3.0 `Feature-ConfigurableFrontendUserPhoneNumberTypes.rst` changelog covering stored values, identifiers, runtime fallback, wizard, valid-value preservation, and unchanged fax contract membership; verify it is included by the changelog glob.
- [x] 4.3 Add the frontend-user contact import behaviour and migration decisions to `docs/` and link the section from its architecture index; verify there is no duplicated README or CONTRIBUTING content.

## 5. Definition of done and user-controlled commits

- [x] 5.1 Inspect the complete diff, confirm only ACE-365 and its OpenSpec artifacts are present, and hand the uncommitted state plus all intermediate test results to the user.
- [x] 5.2 User runs `composerUpdate`, `phpstan`, `unit`, and `functional` separately for TYPO3 v13 and v14, plus `lintPhp`, `cgl -n`, `lintMarkdown -n`, RST rendering, and the wizard tests on SQLite, MariaDB, MySQL, and PostgreSQL; record every result before declaring the change complete.
- [x] 5.3 After the user reports successful final tests and explicitly approves commits, create the three TYPO3 Core-style commits `[BUGFIX] ACE-365: Preserve telephone-only contracts`, `[FEATURE] ACE-365: Configure imported phone number types`, and `[FEATURE] ACE-365: Migrate imported phone number types`, with no unrelated files.
- [x] 5.4 Archive the OpenSpec change as the final commit of the pull request, verify the delta is folded into `openspec/specs/`, and do not push without a separate explicit user request.
