## Context

Since ACE-508, `academic_jobs` reads `Configuration/AcademicJobs/Settings.yaml`
through the shared settings classes of `academic_base`
(`Classes/Settings/{SettingsFileLoader,ValidationSet,Validation,ValidationNormalizer,TcaValidationMerger}.php`,
all `@internal`), and its form partials resolve a field with
`Classes/ViewHelpers/ValidationEnsureViewHelper.php` of `academic_base`. The
jobs-only loader, registry and validation ViewHelpers are gone. The submission
pieces are `Classes/SaveForm/FlashMessageCreationMode.php` (a backed enum that
`AfterSaveJobEvent` exposes), the partials
`Resources/Private/Partials/Job/Forms/{Checkbox,DateTime,Errors,FieldWrapper,Select,Textarea,Textfield,Upload}.html`,
and the save handling in `JobController::createAction()`, which resolves the
current page, the redirect page and the flash message mode around the
dispatch of `AfterSaveJobEvent`. `docs/architecture/validation-settings.md`
describes how jobs reads its settings, and that the ACE-429 problem with `0`
for the two selects is still open.

## Goals / Non-Goals

**Goals:**

- A second frontend form extension could reuse the submission pieces
  without copying them.
- Nothing observable changes for jobs, including project overrides, apart
  from the `disabled` state of a select described below.

**Non-Goals:**

- The settings layer (ACE-508).
- Designing `academic_apartments`.

## Decisions

### Split the prerequisite in two

The settings layer is ACE-508 and specifies behaviour of its own. This change
plans only the move of the submission pieces, which is behaviour neutral.

Rejected: one change for both, as the candidate proposed. It mixes a
behaviour change with a move, and a red jobs test could not tell which half
broke it.

### The enum moves with a deprecated alias

`FlashMessageCreationMode` moves to `FGTCLB\AcademicBase\Form`. The old name
stays resolvable through `Migrations/Code/ClassAliasMap.php` of
`academic_jobs`, because `AfterSaveJobEvent` hands it to listeners in project
code. The map is the TYPO3 one, named in `extra.typo3/class-alias-loader` of
the composer manifest, which composer mode and classic mode both read. The
alias names the same enum, not a copy, so a case of the old name passes the
typed setter of the event. A unit test proves both on PHP 8.2 and 8.5, the ends
of the unit matrix.

The map has to ship with the package. `/Migrations` was marked `export-ignore`
in the `.gitattributes` of `academic_jobs`, which would have left the map out
of every composer installation from the split repository. The line goes, and a
test of `packages-dev/monorepo-shared` fails for any package whose declared map
its archive leaves out.

`typo3/class-alias-loader` is not added to the requirements of
`academic_jobs`: `typo3/cms-core` requires it on both core versions, and the
dependency test of the extension expects every requirement to be an extension
of `ext_emconf.php`.

Rejected: leaving the enum in jobs and having base depend on it, which turns
the dependency direction around.

### Jobs keeps its partial names and delegates

The field partials move to `academic_base` below `Partials/Academic/Form/`,
next to the shared `Academic/Image` partial. A bare `Form/` folder would collide
with the partials of a theme: the bootstrap package ships `Partials/Form/`, and
the jobs views list theme paths above the base path. The jobs partials
`Job/Forms/*.html` remain as the names the jobs templates call, each rendering
its base counterpart with `objectName: 'job'` and `extensionName:
'AcademicJobs'`, which keep the ids, labels and placeholders as they were.

A base field partial renders its wrapper through the argument
`fieldWrapperPartial`, and the jobs partials pass `Job/Forms/FieldWrapper`.
Had the base partials rendered the base wrapper directly, a project override
of `Job/Forms/FieldWrapper.html` would have stopped applying to every field.

The base partial path needs no TypoScript change: both jobs views list it
already, at `-1` in the plugin view and at `-1758484804` in `page.10`, for the
shared image partial.

Rejected: calling the base names from the jobs templates. Every project
override of `Job/Forms/*.html` would silently stop rendering.

### The redirect and flash message decision becomes a service

A stateless `final readonly` service in `academic_base`, `AfterSaveResolver`,
takes the current page, the redirect page and the mode, and returns an
`AfterSaveDecision` with the page to redirect to and whether to create the
flash message. The deprecated `listPid` path stays in the jobs controller,
since it is jobs configuration.

The service also reads the redirect page and the mode from the plugin
settings, the two private methods of the jobs controller before. A second form
extension would otherwise copy them, and they are the larger half of the
logic. They keep their behaviour exactly, including the difference between
the two settings: an integer redirect page of the plugin decides alone, while
a mode the plugin setting does not name falls back to TypoScript.

The decision keeps the disagreement ACE-435 describes: the form redirects to
any page id it is given, while the mode counts `0` and below as no redirect
page. Fixing it is a behaviour change of its own, on `main` and `2`, and the
decision now sits in one method for every form.

### The shared select honours `disabled`

The jobs select partial read a `disabled` argument of its own, which the
shipped template never passed, while every other field partial reads
`element.disabled`. The shared select reads `element.disabled`. Copying the
defect into a partial meant for every form was rejected. It changes the output
only for a project copy of `Job/Properties/Job.html` that sets `disabled` on a
select element, which now renders disabled, or passes `disabled` as an argument
of its own, which no longer does. The `Important` changelog entry of
`academic_jobs` names both.

### Decided: ACE-508 lands before the move

ACE-508 (moving `academic_jobs` onto the shared validation settings of
`academic_base`) is merged first, and the submission pieces move after it.
`Forms/FieldWrapper.html` and `Forms/Textfield.html` call the jobs-only
validation ViewHelpers; moved before ACE-508, the base partials would either
depend on `academic_jobs`, inverting the dependency, or be rewritten a second
time.

### Decided: no apartments extension upstream for now

A housing exchange is not in scope for the academic extension family now.
Only the behaviour neutral move of the submission pieces proceeds; the
conditions of `proposal.md` stay recorded for a later revisit.

Only one project asks for it, and a new extension means TER registration, a
split repository and long-term maintenance. The move is worth doing without
it, because it removes a second copy of the form machinery for any later
form extension.

## Risks / Trade-offs

- [The move touches the jobs form that projects override] → The existing
  jobs functional tests must pass unchanged, and a new test pins a project
  override of a field partial and of the field wrapper.
- [An apartments extension means TER registration, a split repository and
  long-term maintenance] → Out of scope by decision; a condition of the
  proposal, not a task.

## Open Questions

None.
