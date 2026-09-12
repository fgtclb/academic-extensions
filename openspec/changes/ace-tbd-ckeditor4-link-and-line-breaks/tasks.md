## 1. Main

- [ ] 1.1 Confirm `main` still ships CKEditor 5 with the `link` toolbar item in
  `academic-persons-edit/Resources/Private/TypeScript/frontend/profile/rich-text.ts`
  and the sanitiser allowing `a[href]`; nothing else changes on `main`.

## 2. Branch 2

- [ ] 2.1 Backport: separate change on branch 2 after a backport analysis
  (`docs/workflow/backporting.md`), starting from branch `2`'s own
  `AGENTS.md` and `docs/`.
- [ ] 2.2 In that change, add a functional test that saves a body with
  `<a href="https://example.org">` and asserts the stored and rendered link,
  and one each with a `javascript:` and a `data:` href asserting that
  neither is rendered as a link, on TYPO3 v12 and v13; record the results on
  the unchanged code. If either href is rendered, stop and report it
  privately to the TYPO3 Security Team instead of widening this change.
- [ ] 2.3 Add the `links` group, the document language and the writer rules,
  rebuild with `buildJs`, check with `checkJsBuildClean`, and verify the
  toolbar and the stored HTML by hand in the v12 and v13 development
  instances of branch `2`; record the result in the pull request.
- [ ] 2.4 Add a 2.4 changelog entry of `academic_persons_edit` on branch `2`.

## 3. File the issue

- [ ] 3.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-ckeditor4-link-and-line-breaks`, and commit as
  `[BUGFIX] ACE-<NNN>: Offer links in the CKEditor 4 toolbar` in TYPO3 Core
  format.

## 4. Definition of done

- [ ] 4.1 On `main`: `lintMarkdown -n` green; no PHP, TypeScript or
  documentation source changes, so the PHP gates and
  `checkRstRenderingAll` are unaffected and stated as such.
- [ ] 4.2 On branch `2`: `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12 and v13, each after its own `composerUpdate`,
  plus `lintMarkdown -n` and `checkRstRenderingAll`, with the extension's
  `Documentation/` changelog updated.
- [ ] 4.3 Archive the change as the last commit of the pull request on `main`.
