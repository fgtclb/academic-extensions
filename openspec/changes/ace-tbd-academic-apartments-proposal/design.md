## Context

`academic_jobs` carries its own settings stack:
`Classes/Loader/AcademicJobsSettingsLoader.php` (not marked `@internal`, and
it caches the loaded registry in a property),
`Classes/Registry/AcademicJobsSettingsRegistry.php`, the ViewHelpers
`Classes/ViewHelpers/Validation/{FieldTypeFromValidation,RequiredFromValidation}ViewHelper.php`
and `Configuration/AcademicJobs/Settings.yaml`. The submission pieces are
`Classes/SaveForm/FlashMessageCreationMode.php` (a backed enum that
`AfterSaveJobEvent` exposes), the partials
`Resources/Private/Partials/Job/Forms/{Checkbox,DateTime,Errors,FieldWrapper,Select,Textarea,Textfield,Upload}.html`,
and the save handling in `JobController::createAction()`, which resolves the
current page, the redirect page and the flash message mode around the
dispatch of `AfterSaveJobEvent`.

`academic_base` ships `Classes/Settings/{SettingsFileLoader,ValidationSet,Validation,ValidationNormalizer,TcaValidationMerger}.php`
(the loader is `@internal`) and `Classes/ViewHelpers/ValidationEnsureViewHelper.php`,
used by `academic_persons` and `academic_persons_edit`.
`docs/architecture/validation-settings.md` states that the two stacks share
no code, that the divergence of the three jobs readers is ACE-429, and that
adopting the shared classes is ACE-508, a behaviour change for jobs.

## Goals / Non-Goals

**Goals:**

- A second frontend form extension could reuse the submission pieces
  without copying them.
- Nothing observable changes for jobs, including project overrides.

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

`FlashMessageCreationMode` moves to a form namespace of `academic_base`. The
old name stays resolvable through a class alias map of `academic_jobs`,
because `AfterSaveJobEvent` hands it to listeners in project code. A test
proves that aliasing a backed enum resolves on PHP 8.2 to 8.5.

Rejected: leaving the enum in jobs and having base depend on it, which turns
the dependency direction around.

### Jobs keeps its partial names and delegates

The field partials move to `academic_base` below `Partials/Form/`. The jobs
partials `Job/Forms/*.html` remain as the names the jobs templates call,
each rendering its base counterpart with the same arguments. The jobs
TypoScript adds the base partial path at a lower key than its own.

Rejected: calling the base names from the jobs templates. Every project
override of `Job/Forms/*.html` would silently stop rendering.

### The redirect and flash message decision becomes a service

A stateless `final readonly` service in `academic_base` takes the current
page, the configured redirect page and the mode, and returns whether to
redirect and whether to create the flash message. The deprecated `listPid`
path stays in the jobs controller, since it is jobs configuration.

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
  override of a field partial.
- [An apartments extension means TER registration, a split repository and
  long-term maintenance] → Out of scope by decision; a condition of the
  proposal, not a task.

## Open Questions

None.
