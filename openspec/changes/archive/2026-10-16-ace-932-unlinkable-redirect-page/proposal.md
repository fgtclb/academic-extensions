## Why

The job form of `academic_jobs` (`packages/fgtclb/academic-jobs`) sends the
visitor to the root of the site after a saved job when the redirect page
cannot be linked in the language of the visitor, for example a page without a
translation into a language with strict fallback, the default of a new site
language. Nothing is logged, and a queued confirmation waits in the session
for a page that may never render it (ACE-932, found by the test of ACE-931).

This backports the change `ace-932-unlinkable-redirect-page` of `main`
(`openspec/changes/archive/2026-10-16-ace-932-unlinkable-redirect-page/`
there), re-derived for TYPO3 v12 and v13.

## What Changes

- A redirect page the form cannot link in the language of the visitor counts
  as no redirect page: the visitor returns to the page of the form, which
  shows the confirmation as the message mode decides for a form without a
  redirect page.
- The job controller logs a warning that names the redirect page, the page of
  the form and the language.
- This holds for every source of the redirect page: the content element, the
  site setting, a listener of the after-save event and the deprecated list
  page, and for every reason TYPO3 does not link a page, among them a hidden,
  deleted or missing page, a page outside its publish dates and a page the
  visitor has no access to.
- The configuration chapter of the manual, the upgrade guide from 2.3 and a
  2.4 `Important` entry describe it. Nothing is breaking: a redirect page that
  can be linked works as before.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-jobs/new-job-form`: a requirement is added that a redirect page
  the form cannot link returns the visitor to the form, with a warning in the
  log. The requirement that a saved job returns the visitor to the form names
  the unlinkable redirect page as a case, and the requirement that a redirect
  page receives the visitor is narrowed to a page that can be linked in the
  language of the visitor. Their scenarios hold unchanged.

## Impact

- `academic_jobs`: the create action of the job controller, a new functional
  test with a fixture of its own, the deprecation helper of the form tests
  moved into their shared trait, the configuration chapter and the template
  chapter of the manual, the upgrade guide `From23To24` and the 2.4 entry
  `Important-JobFormReturnsWhenRedirectPageCannotBeLinked.rst`, the last two
  byte-identical with `main`.
- `docs/testing/functional-tests.md`. This branch has no architecture page on
  the frontend forms, so the decision is recorded in `design.md` only.
- TYPO3 v12 and v13 behave the same for a missing translation, a hidden, a
  deleted and a missing page. They differ for a folder: v13 links none and the
  form returns to itself, v12 links it and the form sends the visitor there,
  as before.

## Non-goals

- Linking the default language of the redirect page. It ignores the strict
  fallback the site chose and sends the visitor to a page in another language.
- A check of the redirect page in the backend when the content element is
  saved. The page can lose its translation or be hidden later, and a listener
  or the site setting names pages the element does not know.
- Refusing a folder on TYPO3 v12. Core links it there, and the check sits on
  the address core builds, not on the page record.
- Other links of the extensions that TYPO3 cannot build. They render as no
  link, which is the intended behaviour of a link in a page.
