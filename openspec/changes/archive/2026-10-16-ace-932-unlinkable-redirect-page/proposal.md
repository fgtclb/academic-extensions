## Why

The job form of `academic_jobs` (`packages/fgtclb/academic-jobs`) sends the
visitor to the root of the site after a saved job when the redirect page
cannot be linked in the language of the visitor, for example a page without a
translation into a language with strict fallback, the default of a new site
language. Nothing is logged, and a queued confirmation waits in the session
for a page that may never render it (ACE-932, found by the test of ACE-931).

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
  deleted or missing page.
- The integrator manual, the upgrade guide from 2.3 and a 2.4 `Important`
  entry describe it. Nothing is breaking: a redirect page that can be linked
  works as before.

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
  moved into their shared trait, the integrator manual of the job form, the
  upgrade guide `From23To24` and the 2.4 entry
  `Important-JobFormReturnsWhenRedirectPageCannotBeLinked.rst`.
- `academic_base` (`packages/fgtclb/academic-base`) is not changed, the
  controller asks its after-save resolver again.
- `docs/architecture/frontend-forms.md` and `docs/testing/functional-tests.md`.
- TYPO3 v13 and v14 behave the same. Before the fix, v13 built the address of
  the start page from the server environment and v14 from the request.

## Non-goals

- Linking the default language of the redirect page. It ignores the strict
  fallback the site chose and sends the visitor to a page in another language.
- A check of the redirect page in the backend when the content element is
  saved. The page can lose its translation or be hidden later, and a listener
  or the site setting names pages the element does not know.
- Other links of the extensions that TYPO3 cannot build. They render as no
  link, which is the intended behaviour of a link in a page.
