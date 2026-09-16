## 1. Main

- [x] 1.1 Confirm `main` still ships CKEditor 5 with the `link` toolbar item in
  `academic-persons-edit/Resources/Private/TypeScript/frontend/profile/rich-text.ts`
  and the sanitiser allowing `a[href]`; nothing else changes on `main`.
  Confirmed: the toolbar carries `link` with `allowedProtocols` http, https,
  mailto and tel, and `ProfileRichTextSanitizerBuilder` permits an `a` tag whose
  `href` is restricted to those four schemes plus local targets.

## 2. Branch 2

- [x] 2.1 Backport: separate change on branch 2 after a backport analysis
  (`docs/workflow/backporting.md`), starting from branch `2`'s own
  `AGENTS.md` and `docs/`. Written there as
  `ace-668-ckeditor4-link-and-line-breaks`, with a delta spec of its own —
  the behaviour exists on that branch, so it is specified there rather than
  skipped as it is here.
- [x] 2.2 In that change, add a functional test that saves a body with
  `<a href="https://example.org">` and asserts the stored and rendered link,
  and one each with a `javascript:` and a `data:` href asserting that
  neither is rendered as a link, on TYPO3 v12 and v13; record the results on
  the unchanged code. Recorded green on the unchanged code: the storing path
  was never the defect, and the rendering already refuses both dangerous
  protocols, so nothing had to be reported to the TYPO3 Security Team and the
  change did not grow.
- [x] 2.3 Add the `links` group, the document language and the writer rules,
  rebuild with `buildJs`, check with `checkJsBuildClean`.
- [x] 2.4 Add a 2.4 changelog entry of `academic_persons_edit` on branch `2`.
- [ ] 2.5 Verify the toolbar and the stored markup by hand in the v12 and v13
  development instances of branch `2`, and record the result in the pull
  request. It cannot be automated there: that branch has seven node suites and
  no `testJs`.

## 3. File the issue

- [x] 3.1 Filed as ACE-668 (Bug, Version 2.4.0), the change renamed after it,
  and committed in TYPO3 Core format. The subject is
  `[BUGFIX] ACE-668: Add a link button to the editor` — the drafted wording
  shortened to stay within the 52 character limit.

## 4. Definition of done

- [x] 4.1 On `main`: `lintMarkdown -n` green; no PHP, TypeScript or
  documentation source changes, so the PHP gates and
  `checkRstRenderingAll` are unaffected.
- [x] 4.2 On branch `2`: `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green for TYPO3 v12 and v13, each after its own
  `composerUpdate`, plus `buildJs`, `typecheckJs`, `lintTypescript` and
  `checkRstRenderingSingle academic-persons-edit`, with the extension's
  `Documentation/Changelog/2.4/` entry written.
- [ ] 4.3 Archive the change as the last commit of the pull request on `main`.
