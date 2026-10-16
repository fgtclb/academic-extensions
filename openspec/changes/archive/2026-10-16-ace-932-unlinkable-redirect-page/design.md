## Context

The job controller resolves the redirect page from the content element, the
site setting, the listeners of `AfterSaveJobEvent` and the deprecated
`listPid`, and asks `AfterSaveResolver::decide()` of `academic_base` whether
the visitor returns to the form and whether the confirmation is queued. For a
redirect page it built the address with the Extbase `UriBuilder` and threw the
redirect.

Verified on TYPO3 13.4.35 and 14.3.9 (and 12.4.45 for branch `2`):
`PageLinkBuilder` throws `UnableToLinkException` for a page without a
translation into a language with strict fallback (1551621996), for a page it
does not find, which includes a hidden or deleted one, one outside its publish
dates and one restricted to other users (1490987336), and on v13
and v14 for a folder or a spacer (1742757285). `ContentObjectRenderer::createUrl()`
catches it and returns `''`, `UriBuilder::buildFrontendUri()` passes it on,
and `redirectToUri('')` completes it with `GeneralUtility::locationHeaderUrl()`
to the script directory, the root of the site.

## Goals / Non-Goals

**Goals:** an unlinkable redirect page behaves as no redirect page, for every
source and every reason, and the integrator learns about it from the log.

**Non-Goals:** linking another language, validating the page in the backend.

## Decisions

- **The check is on the built address, not on the page record.** An empty
  address is what every reason ends in, so one comparison covers the strict
  fallback, `l18n_cfg`, hidden, deleted, missing and folder pages, and any
  reason a later core adds. Checking the record would repeat the rules of
  `PageLinkBuilder` and miss some of them.
- **The address is built before the confirmation is queued**, and an empty
  one makes the controller call `decide()` again with no redirect page. The
  decision then holds what a form without a redirect page would do: the
  confirmation goes to the queue of the form, and the conditional mode
  queues it. Keeping the first decision would suppress the confirmation in
  that mode on the very page the visitor is shown, and keep it in the queue
  of the plugin, which the form does not render. This follows the ACE-912 and
  ACE-919 decisions: the mode applies to where the visitor actually ends up.
- **No change to `academic_base`.** Asking the resolver again with `null` is
  enough, a new flag or method on `AfterSaveDecision` would only serve this
  one caller.
- **The warning uses the PSR logger the controller already has**, as the
  failed notification mail does (ACE-772), at level warning, which the default
  log configuration of TYPO3 writes to the log file. It names the redirect
  page, the form page, and the id and title of the site language, and lists
  the reasons TYPO3 has not to link a page, since the controller cannot tell
  which one applied.
- **Rejected: linking the default language of the page.** It ignores the
  strict fallback the site chose and shows the visitor a page in another
  language.

## Risks / Trade-offs

- A site that relied on the root as the destination, unlikely, now sees the
  form. The log names the page to fix.
- Building the address before the messages costs nothing extra, it was built
  anyway for every redirect page.
