## 1. Premises and tests first

- [x] 1.1 Re-measure every fact of `design.md`, *Context*, against the
      installed TYPO3 v12 tree rather than against the `main` change, and find
      out whether the FlexForm field receives the page TSconfig here.
- [x] 1.2 Port the two functional test classes and their fixtures, adapt them
      to v12, and record that the restricting tests fail on the unchanged code.
- [x] 1.3 Port the unit test of the resolver.
- [x] 1.4 Run both functional classes on TYPO3 v12 **and** v13, because the
      data structures are split per core version on this branch.

## 2. Implementation

- [x] 2.1 `ContractSelectScope` and `ContractSelectScopeResolver` as PHP 8.1
      classes; `getContractItemsForTcaItemsProcFunc()` keeps its signature and
      hands both inputs to a new `findForBackendSelect()`.
- [x] 2.2 The `TSconfig` parameter shape corrected wherever it is declared, and
      the `academic_base` `@api` trait corrected to pass that shape.
- [x] 2.3 Mutation: ignore the setting and watch 1.2 go red; drop the
      referenced-value rule and watch the data-loss guards go red; restore.

## 3. Definition of done

- [x] 3.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v12 and v13, each after its own `composerUpdate`.
- [x] 3.2 The setting documented in `academic_persons`' `Documentation/` with an
      example for both fields; `Documentation/Changelog/2.4/Feature-*.rst` there
      and in `academic_contacts4pages`;
      `docs/architecture/backend-select-items.md` written for this branch.
- [x] 3.3 Commit messages in TYPO3 Core format with the `main` change's issue;
      the change archived as the last commit.
