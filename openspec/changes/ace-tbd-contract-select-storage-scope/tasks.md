## 1. Premises and tests first

- [ ] 1.1 Re-check the premises of `design.md`, *Context*, on `main` and
      `origin/2`. Find out how page TSconfig reaches the handler for the
      FlexForm field `settings.selectedContracts`; if it does not arrive as
      `$parameters['TSconfig']['itemsProcFunc.']`, update this change
      (`/opsx:update`) before writing code.
- [ ] 1.2 Functional tests calling `ContractItems::itemsProcFunc()` with a
      fixture of contracts on two sites' folders: with the setting only one
      site's contracts; without it all of them; with a depth, subfolders
      included. Record that the restricting tests fail on the unchanged code.
- [ ] 1.3 A test for the referenced contract outside the restriction, for the
      single select and for the multiple FlexForm select.

## 2. Implementation

- [ ] 2.1 Read the setting in `ContractItems`, resolve the page list with its
      depth, and hand it to the repository; keep the currently referenced uids.
      Quote the uid list with the query builder helper, or use Extbase
      `in()` on a non-empty list only.
- [ ] 2.2 Order stays as today (by last and first name in PHP).
- [ ] 2.3 Mutation: ignore the setting and watch 1.2 go red; drop the
      referenced-value rule and watch 1.3 go red; restore.

## 3. Definition of done

- [ ] 3.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 3.2 The setting documented in `academic_persons`' `Documentation/` with an
      example for both fields; `Documentation/Changelog/3.0/Feature-*.rst`.
- [ ] 3.3 Commit messages in TYPO3 Core format with the issue filed after
      implementation; the change archived as the last commit.
