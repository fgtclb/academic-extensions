# Frontend forms

`academic_jobs` is the one academic extension whose visitors submit a record
through an Extbase form, the new-job form. The generic parts of that form live
in `academic_base`, so a second form extension builds on them instead of
copying the jobs extension (ACE-797). Only the parts that are not about jobs
moved. The controller, the validator and the settings of the form stay in
`academic_jobs`.

## What `academic_base` provides

| Piece                                       | Kind                  | What it does                                              |
|---------------------------------------------|-----------------------|-----------------------------------------------------------|
| `Classes/Form/FlashMessageCreationMode.php` | backed enum, `@api`   | Whether a save queues its confirmation as a flash message |
| `Classes/Form/AfterSaveResolver.php`        | service, internal     | Reads redirect page and mode, decides after the save      |
| `Classes/Form/AfterSaveDecision.php`        | data object, internal | Back to the form or to a page, and whether to queue       |
| `Resources/Private/Partials/Academic/Form/` | Fluid partials        | Six field partials, the field wrapper and the error alert |

The service and the data object are internal to the academic extensions. The
enum is public because `AfterSaveJobEvent` hands it to project listeners, and it
is listed on the extension points page of `academic_base`. Its old name,
`FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode`, still resolves through
`Migrations/Code/ClassAliasMap.php` of `academic_jobs` and is deprecated. The
alias names the same enum, not a copy, so a case of the old name is accepted by
the typed setter of the event.

## The save, step by step

`AfterSaveResolver` reads `settings.redirectPageId` and
`settings.flashMessageCreationMode` of the content element first, and the
TypoScript fallbacks `settings.saveForm.fallbackRedirectPageId` and
`settings.saveForm.fallbackFlashMessageCreationMode` otherwise. The two settings
fall back differently, which is the behaviour the jobs controller had and is
kept:

- A redirect page that is an integer in the plugin setting decides alone. `0`
  there means "no redirect page", and the TypoScript fallback is not read.
- A mode the plugin setting does not name, `3` for example, falls back to the
  TypoScript setting, and then to the conditional mode.

The controller resolves both before it dispatches its after-save event, so a
listener can replace them, and calls `decide()` with what the listeners left.
The jobs controller applies the deprecated `listPid` redirect in between,
because that is jobs configuration.

`decide()` counts a page id of `0` or below as no redirect page, for the
redirect and for the message alike, as the mode does. Only a listener can hand
in such a page id, and the form then behaves as for no redirect page: it
returns to the form page, where the message of the mode is shown. It used to
redirect to that page id while the mode queued the message for the form page
(ACE-435). The page id is normalised in `decide()`, not in the setter of the
event, so a later listener still reads what an earlier one set, and in the jobs
controller after the `listPid` fallback, which therefore never takes over a
`0`.

## Back to the form, or to a page

`decide()` sets `returnsToForm` without a redirect page and for a redirect page
that is the current page. `redirectPageId` keeps the page all the same, so a
controller reads it only when the flag is false. Setting it to `null` for the
current page would have made the flag redundant, but lost the information that
a page was configured. The jobs controller maps the decision to two things
(ACE-912, ACE-919):

| `returnsToForm` | Redirect                                | Queue of the confirmation                          |
|-----------------|-----------------------------------------|----------------------------------------------------|
| `true`          | The form page, without any plugin query | `academic_jobs.new_job_form.confirmation`          |
| `false`         | `redirectPageId`                        | `extbase.flashmessages.tx_academicjobs_newjobform` |

The first queue is a constant of the controller,
`JobController::CONFIRMATION_QUEUE_IDENTIFIER`, and `Templates/Job/New.html`
renders it with `f:flashMessages` inside the content element. The literal is
repeated in the template, Fluid cannot read the constant, and the functional
tests hold both together. The form is a `USER_INT`, so the rendered queue never
reaches the page cache. The second queue is the default queue of the plugin,
which no shipped template renders. A redirect page renders it in an uncached
part, which the manual shows. The form does not render it, so a confirmation
left in the session by a redirect page never turns up on the form. Rendering
the default queue in `New.html` was rejected for that reason, and because
Extbase queued its own error message there.

That error message, "An error occurred while trying to call ...", is switched
off: `getErrorFlashMessage()` of the controller returns `false`. TYPO3 v13
stored it in the session, v14 does not, so on v13 it turned up next to the
confirmation of the next saved job.

Both redirects are thrown as `PropagateResponseException`, as the list filters
do, see [List filter URLs](list-filter-urls.md#thrown-not-returned). The
answer is a `303` without a page on v13 and v14. Before, the controller
returned `redirect('list')` without a redirect page. Extbase built that link
for the `List` plugin, the only plugin offering `list`, so the form page got
`tx_academicjobs_list` arguments, no plugin on it took them, and the form
rendered empty. It did not throw `InvalidActionNameException` 1313855175.

The exception skips what `Extbase\Core\Bootstrap` does after an action. The
controller therefore does both steps itself before it throws, in the same
order. It calls `persistAll()` first, so a listener of `AfterSaveJobEvent`
that changes the job or another record through a repository without
persisting it keeps its change. Then it calls
`CacheService::clearCachesOfRegisteredPageIds()`: the persistence registered
the saved records, and the call flushes the page caches tagged with them and
the pages the storage folder names in `TCEMAIN.clearCacheCmd`. The
`AfterRequestDispatchedEvent` is skipped as well, nothing in the extensions
listens to it.

A job that has no uid after `persistAll()` queues "Job not created" for the
form and leaves through the same exit, before the event and the mail. A
request for the create action without a job returns to the form through it
too, without a message.

## The field partials

A field partial of `academic_base` takes the field as `element`, the name of
the form object as `objectName`, the extension of its labels as
`extensionName`, and optionally the partial that wraps it as
`fieldWrapperPartial`. The id of a field is `<objectName>.<identifier>`, its
label `create.<objectName>.<identifier>.label`, its placeholder
`edit.<objectName>.<identifier>.placeholder`, and the error alert reads
`create.<objectName>.incorrectValues`, all from the given extension. The
arguments of each partial are described in its `f:comment`.

The jobs partials below `Job/Forms/` keep their names and render the base
partial of the same name, with `objectName: 'job'`, `extensionName:
'AcademicJobs'` and `fieldWrapperPartial: 'Job/Forms/FieldWrapper'`. That last
argument is the reason the wrapper is a parameter at all: a project that
overrides `Job/Forms/FieldWrapper.html` changes every field of the form, and it
still does. Had the base field partials rendered the base wrapper directly, that
override would have silently stopped applying to all twenty fields.

Calling the base partials from the jobs templates was rejected for the same
reason: every project copy of a `Job/Forms/*.html` partial would have stopped
rendering. A copy made before the move keeps working, because it renders the
same arguments and the same wrapper name as before.

The base partial path is registered in both jobs views already, at `-1` in the
plugin view and at `-1758484804` in `page.10`, for the shared image partial. See
[Shared partials](shared-partials.md#the-root-path-key--1).

The select reads `element.disabled` like every other field partial. The jobs
select read a `disabled` argument of its own, which the shipped template never
passed. A project copy of `Job/Properties/Job.html` that sets `disabled` on a
select element now gets a disabled select, and one that passed `disabled` as an
argument of its own next to the element now gets an editable one.

## The class alias map has to ship

`Migrations/Code/ClassAliasMap.php` only works where it is installed. Composer
installs a split package from the archive of its split repository, which leaves
out every path its `.gitattributes` marks `export-ignore`. Several packages
mark `/Migrations` that way, and `academic_jobs` did too until ACE-797.
`ClassAliasMapExportTest` of `packages-dev/monorepo-shared` fails for a package
whose declared map its archive would leave out. A test in
this repository cannot see the loss otherwise: the packages are installed from
their directories here.

## A second form extension

A second form extension, a housing exchange for example, is not planned. The
decision recorded for it is that it is revisited only when all of the following
hold:

- The maintainer decides that it belongs to the academic extensions.
- The settings of `academic_jobs` read through the shared classes of
  `academic_base` (ACE-508) and the pieces on this page are shared (ACE-797).
  Both are done.
- The job list is paginated, so a list plugin has a model to follow. That is
  done as well (ACE-256).
- The model fields of the project that asked for it are taken over, not its
  code, which targets APIs `main` no longer has.
- The extension key, the split repository and who maintains it are settled.

Such an extension uses the enum, the resolver and the partials of this page,
and passes its own `objectName` and `extensionName`. It needs its own
after-save event, and its own wrapper partial name if projects are to override
its wrapper.

## Tests

- `academic-base/Tests/Unit/Form/` covers the enum, every settings combination
  of the resolver and the decision for every mode.
- `academic-jobs/Tests/Unit/ClassAliasMapTest.php` resolves the old enum name
  and changes the mode of an `AfterSaveJobEvent` through it.
- `academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormRedirectPageTest.php`
  sends a job without a redirect page, with a listener that hands in `0` or
  `-1`, and with the form page as the redirect page, and asserts the `303` to
  the plain form URL and the confirmation in the form, once. It covers the
  redirect page of the element, the site setting, a listener and `listPid`
  with its deprecation, the modes "never" and the conditional one, a
  confirmation left on an unrendered redirect page, a change a listener does
  not persist itself, a request without a job, and the cache clearing, on
  v13 and v14.
- `academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormRejectedSubmissionTest.php`
  asserts that a rejected submission queues no error message of Extbase, also
  not into the session for the confirmation that follows.
- `academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormPartialOverrideTest.php`
  renders the form with a project copy of `Job/Forms/Textfield.html` and with
  one of `Job/Forms/FieldWrapper.html`, made from the files as they were before
  the move, and asserts that every field is rendered through the override.
- The rest of the jobs form tests passed unchanged across the move: rendering,
  validation, flags, upload, labels and the messages after a save.

## See also

- [Shared partials](shared-partials.md): the other partial of `academic_base`
  and the root path key it is registered with.
- [Overridable partials](overridable-partials.md): how a template is cut into
  partials a project overrides one at a time.
- [Validation settings](validation-settings.md): the settings the field
  partials read for the required mark and the input type.
- [Class design](class-design.md#extension-points): what is public API.
