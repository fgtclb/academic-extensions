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
| `Classes/Form/AfterSaveDecision.php`        | data object, internal | The page to redirect to, and whether to queue the message |
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

`decide()` redirects whenever a redirect page is given, while the message
follows the mode, which counts a page id of `0` or below as no redirect page.
Only a listener can hand in such a page id, and the form then redirects and
queues the message. ACE-435 tracks that disagreement. It is kept in one place,
`decide()`, so fixing it changes every form at once.

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
