## Why

The job form of `academic_jobs` (`packages/fgtclb/academic-jobs`) has two
defects that hit every site without a redirect page. After a saved job it
redirects to an action of the job list plugin on the form page, so the visitor
gets an empty form without any word on the job (ACE-912). And the
confirmation is queued for the plugin while no shipped template renders that
queue, so the visitor never sees it, and a site that renders the queue later
shows it on a page where it makes no sense (ACE-919).

## What Changes

- Without a redirect page, or with the form page as the redirect page, the
  form sends the visitor back to the form page, to its plain URL without any
  argument of a plugin. The form shows the confirmation above itself, from a
  queue of its own that the shipped `Templates/Job/New.html` renders.
- With a redirect page, from the content element, the site setting, a listener
  of `AfterSaveJobEvent` or the deprecated `listPid`, the visitor is sent
  there as before, and the confirmation goes to the queue of the plugin as
  before. Nothing is shipped to render it there.
- The answer to a saved job is a `303 See Other` on TYPO3 v13 and v14 alike.
  No page is rendered for the submission any more. TYPO3 v13 used to render
  the whole page and send the redirect through `header()`.
- The mode "never" applies on the form page as well. The conditional mode
  keeps its meaning, so a failing notification mail is not reported when the
  visitor is sent to another page in that mode.
- A submission the validation rejects shows the field errors only. The
  generic error message of Extbase, which named a PHP method and stayed in the
  session on TYPO3 v13, is no longer queued.
- A job that could not be saved returns the visitor to the form with the
  message "Your job advert could not be created", instead of continuing with
  an empty uid.
- The FlexForm items `-1` and `0` of the message mode are relabelled to "Use
  the site setting" and "Suppress when a redirect page is set", in English
  and German, with the description of the site setting. Keys and values stay.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-jobs/new-job-form`: where the visitor goes after a saved job,
  where the confirmation is shown, what a rejected or failed submission
  shows, and the redirect page of 0 or below, which now returns to a working
  form.
- `academic-jobs/new-job-notification`: the warning about a mail that could
  not be sent is shown on the form when the visitor stays there.

## Impact

- `academic_jobs`: `JobController::createAction()`, `Templates/Job/New.html`,
  both `locallang.xlf` files, the constants label and the site set definition
  of the mode, the manual, the 2.4 changelog and the upgrade guides.
- `academic_base` (`packages/fgtclb/academic-base`): the internal
  `AfterSaveDecision` says whether the visitor returns to the form. Nothing of
  the public API changes.
- A project copy of `Templates/Job/New.html` has to render the new queue to
  show the confirmation.
- Script code that read the HTML of the answer to the submission on TYPO3 v13
  gets an empty `303` now.
- No database, TCA or dependency changes.

## Non-goals

- A content element or TypoScript object that renders the confirmation on a
  redirect page. The manual keeps the snippet for it.
- Changing the mode of the development seed, which still redirects with the
  mode "always". That is a follow-up.
- A configurable queue identifier.
- Branch `2`, which gets its own change from the backport analysis.
