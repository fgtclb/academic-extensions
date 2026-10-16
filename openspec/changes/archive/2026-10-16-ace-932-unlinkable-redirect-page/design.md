## Context

The job controller resolves the redirect page from the content element, the
site setting, the listeners of `AfterSaveJobEvent` and the deprecated
`listPid`. On this branch it decides in place whether the visitor returns to
the form, with `$returnsToForm`, and whether the confirmation is queued, with
`FlashMessageCreationMode::shouldBeCreated()`. `main` moved both into
`AfterSaveResolver::decide()` of `academic_base`, which this branch does not
have. For a redirect page it built the address with the Extbase `UriBuilder`
and threw the redirect.

Verified on TYPO3 12.4.45 and 13.4.36: `PageLinkBuilder` throws
`UnableToLinkException` for a page without a translation into a language with
strict fallback (1551621996) and for a page it does not find, which includes
a hidden or deleted one, one outside its publish dates and one the visitor
has no access to (1490987336). TYPO3 v13 also throws for a folder or a
spacer (1742757285), TYPO3 v12 has no such check and links a folder to its
slug. `ContentObjectRenderer::createUrl()` catches the exception and returns
`''` on both, `UriBuilder::buildFrontendUri()` passes it on, and
`redirectToUri('')` completes it with `GeneralUtility::locationHeaderUrl()` to
the script directory, the root of the site. Its scheme comes from the server
variables, on v12 from `HTTPS` and `reverseProxySSL`, on v13 also from
`X-Forwarded-Proto` of a trusted proxy, not from the request, and the
environment of the testing framework gave `http://www.acme.com/` for an
`https` site.

## Goals / Non-Goals

**Goals:** an unlinkable redirect page behaves as no redirect page, for every
source and every reason, and the integrator learns about it from the log.
The behaviour and the log message are the ones of `main`.

**Non-Goals:** linking another language, validating the page in the backend,
refusing a folder on TYPO3 v12.

## Decisions

- **The check is on the built address, not on the page record**, as on
  `main`. An empty address is what every reason ends in, so one comparison
  covers the strict fallback, `l18n_cfg`, hidden, deleted and missing pages,
  publish dates, access restrictions and the folder on v13. It also explains
  the one version difference without a switch: v12 builds an address for a
  folder, so the folder still receives the visitor there. Refusing a folder on v12 would need a check of the page
  record that repeats a rule of v13 core, for a case that never sent the
  visitor to the root.
- **The address is built before the confirmation is queued**, and an empty
  one sets the redirect page to none and `$returnsToForm` to true, so
  `shouldBeCreated()` decides again as for a form without a redirect page and
  the confirmation goes to the queue of the form. Keeping the first decision
  would suppress the confirmation in the conditional mode on the very page
  the visitor is shown, and keep it in the queue of the plugin, which the
  form does not render. This is the re-decision `main` makes by calling its
  resolver again, written with the two variables this branch decides with.
- **No resolver is backported.** `AfterSaveResolver` arrived on `main` with
  other changes, and two assignments do here what the second call does there.
- **The warning uses the PSR logger the controller already has**, at level
  warning, which the default log configuration of TYPO3 v12 and v13 writes to
  the log file. It names the redirect page, the form page, and the id and
  title of the site language, and lists the reasons TYPO3 has not to link a
  page, since the controller cannot tell which one applied. Its text is the
  one of `main`, so the 2.4 entry, which both branches share byte for byte,
  quotes it correctly. The `language` attribute of the request is a
  `SiteLanguage` on v12 as well.
- **Rejected: linking the default language of the page.** It ignores the
  strict fallback the site chose and shows the visitor a page in another
  language.

## Risks / Trade-offs

- [A site relied on the root as the destination, unlikely] → It now sees the
  form, and the log names the page to fix.
- [A folder behaves differently on v12 and v13] → The manual leaves the
  folder out, as a folder is no sensible redirect page on either version, and
  the test pins the behaviour of each version.
- [Building the address before the messages costs time] → None, it was built
  anyway for every redirect page.
