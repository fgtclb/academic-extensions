## 1. Tests first

- [x] 1.1 Record the current output: add list (grouped and ungrouped), card,
  selected-profiles and selected-contracts assertions on classes, links and
  text that the split must not change, and verify they pass before any
  template is touched.
- [x] 1.2 Add a functional fixture extension, following
  `docs/testing/fixture-extensions.md`, that overrides single partials with
  marker text: `Profile/Item/Name.html`, `Profile/List/EmptyState.html` and
  `Profile/List/ResultCount.html` in one partial path, `Profile/List/Items.html`
  in a second one so the grid override can be had on its own, plus a card
  template that passes a detail page. Register the paths for the persons plugins
  and for `academic_contacts4pages`.
- [x] 1.3 Add tests that the list, card, selected-profiles, selected-contracts
  and contacts-for-pages output show the name marker, and that an empty list
  shows the empty state marker. Record that they fail before the split.
- [x] 1.4 Add a test that a profile with a title renders "title first last",
  and record its failure.
- [x] 1.5 Add a test that renders `Profile/Item` with `detailPid` passed and
  no plugin settings, and assert the link targets that page. Record its
  failure.

## 2. Implementation

- [x] 2.1 Split `Profile/Item.html` into `Profile/Item/{DetailLink,Name,Image,Contracts}.html`
  and verify 1.1 and the name and title tests pass.
- [x] 2.2 Split `Profile/List/ItemList.html` into
  `Profile/List/{GroupHeader,Items,ResultCount,EmptyState}.html`, use
  `Profile/List/Items` and `Profile/List/EmptyState` in `Card.html`,
  `SelectedProfiles.html` and `SelectedContracts.html`, and verify 1.1 and 1.3
  pass.
- [x] 2.3 Add the BEM classes to every new partial and to `Pagination.html`
  and `AlphabetPagination.html`, keeping every existing class; verify 1.1.
- [x] 2.4 Delete the fixture's override files on purpose and watch 1.3 go
  red; restore.

## 3. Documentation

- [x] 3.1 Document the partial structure and the contacts-for-pages path note
  in `Documentation/Templates/`; verify with `checkRstRenderingAll`.
- [x] 3.2 Add `Documentation/Changelog/3.0/Important-ProfileItemAndListPartials.rst`
  from `Build/Documentation/Templates/`: the new partials, the title in the
  name, and the advice to drop full copies.
- [x] 3.3 Add the fixture extension to `docs/testing/fixture-extensions.md`
  if that page lists them; verify with `lintMarkdown -n`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, verify its key
  and rename the change to `ace-716-item-and-list-partials`.
- [x] 4.2 Commit as `[TASK] ACE-716: Split profile item and list partials`
  in TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 5.2 `composerUpdate`, then the same suites green with `-t 14`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog updated as in group 3;
  `README.md` and `CONTRIBUTING.md` still only summarise.
- [x] 5.5 No backport: template refactoring on a maintenance line causes merge
  conflicts for projects that override the files. State it in the pull
  request, together with anything else left out.
- [ ] 5.6 Archive the change as the last commit of the pull request.
