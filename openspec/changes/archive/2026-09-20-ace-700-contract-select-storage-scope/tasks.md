## 1. Premises and tests first

- [x] 1.1 Re-check the premises of `design.md`, *Context*, on `main` and
      `origin/2`, and find out how page TSconfig reaches the handler for the
      FlexForm field `settings.selectedContracts`. Done on 2026-09-20: it
      arrives, by the flex path, and `$parameters['TSconfig']` is the content
      of `itemsProcFunc.` rather than its parent - `design.md` records both.
- [x] 1.2 Functional tests compiling the backend form of both fields - 1.4
      decided that over calling `ContractItems::itemsProcFunc()` - with a
      fixture of contracts on two sites' folders: with the setting only one
      site's contracts; without it all of them; with a depth, subfolders
      included. Record that the restricting tests fail on the unchanged code.
- [x] 1.3 A test for the referenced contract outside the restriction, for the
      single select and for the multiple FlexForm select.
- [x] 1.4 A test compiling the real backend form of both fields, so the
      TSconfig path in the documentation is the one FormEngine actually reads
      rather than the one this change believes it reads.

## 2. Implementation

- [x] 2.1 `Backend\FormEngine\ContractSelectScope` reads the setting from the
      itemsProcFunc parameters, resolves the page list with its depth and
      collects the referenced uids;
      `ContractRepository::getContractItemsForTcaItemsProcFunc()` keeps its
      signature and hands both to a new `findForBackendSelect()`. Quote the uid
      list with the query builder helper, or use Extbase `in()` on a non-empty
      list only.
- [x] 2.2 Order stays as today (`uid` from the repository, then by last and
      first name in PHP).
- [x] 2.3 Mutation: ignore the setting and watch 1.2 go red; drop the
      referenced-value rule and watch 1.3 go red; restore.

## 3. Definition of done

- [x] 3.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v13 and v14, each after its own `composerUpdate`.
- [x] 3.2 The setting documented in `academic_persons`' `Documentation/` with an
      example for both fields; `Documentation/Changelog/3.0/Feature-*.rst` there
      and in `academic_contacts4pages`;
      `docs/architecture/backend-select-items.md` for the handler contract
      behind it.
- [x] 3.3 Commit messages in TYPO3 Core format with the issue filed after
      implementation; the change archived as the last commit.
