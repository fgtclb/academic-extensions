# Plugin view event

Every plugin of seven academic extensions dispatches one event when it
renders, `FGTCLB\AcademicBase\Event\ModifyPluginViewEvent`, so a project adds a
variable for its templates with a listener instead of a subclass, a copy or an
XCLASS of the controller. It is the same event for every plugin; a listener
tells them apart by the context. Up to 2.4 there were five events of this kind,
one per action and only in two extensions: `ModifyJobControllerNewActionViewEvent`
of `academic_jobs` and the list, detail, selected profiles and selected
contracts events of `academic_persons`. They are removed, not deprecated.

This page is about how the event is dispatched and what a new action has to
do. What a listener may rely on is documented on the extension points page of
`academic_base`, which lists the event.

## Who dispatches it

Eighteen actions in eight controllers of seven extensions: `academic_bite_jobs`,
`academic_contacts4pages`, `academic_jobs`, `academic_partners`,
`academic_persons`, `academic_programs` and `academic_projects`. Three of them
have a second rendering path, an early return for an empty or not found state:
the job detail without a job, and the selected profiles and selected contracts
elements without a selection. Each of those paths dispatches as well.

Not covered, on purpose:

- `academic_study_plan` renders through a data processor, not an Extbase
  action, and TypoScript `dataProcessing` already takes processors of a
  project.
- `academic_persons_edit` edits a profile through forms that need events on the
  data they write, not on the view.
- The persons detail action answers a missing profile with a page not found
  response; it renders no view, so it dispatches nothing.

## How it is dispatched

Through the trait
`FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait`, which
is `@internal`. Its one method, `dispatchModifyPluginViewEvent()`, takes the
plugin action context, the view and the event dispatcher from the action, and
dispatches the event with the context and the view. It returns the dispatched
event, which no action needs today. It reads nothing of the controller itself,
as no trait of the extensions does, see [Class design](class-design.md#traits).

- **Called once on every path that renders**, after the action assigned its own
  variables, so a listener sees the view as the template will.
- **Before a variable a listener must not replace.** The job form assigns
  `validations` after the event, so a listener cannot take the configured
  validations off the form.
- **Not hidden in an override of `htmlResponse()`.** That would run after every
  assignment, the protected ones included. Nor would it protect the event
  from a project subclass of one of the controllers that are not `final` yet:
  a subclass that overrides `htmlResponse()` or an action without calling the
  parent drops the dispatch either way.
- **One context per rendering.** Every action builds its context once, before
  its first event and after its settings are settled, and hands the same object
  to every event it dispatches: the demand and list events of the partner,
  program and project lists and of the program finder, the query and page title
  events of the persons plugins, the write event of the profile editor, and this
  one. A listener that follows a rendering through its events gets one context
  (ACE-767). The persons list therefore decides before its query that a letter
  switches the pagination off, and the persons actions hand their deprecated
  persons context to this event as well. It implements the `academic_base`
  interface the event declares (ACE-747).

The partner, program and project lists and the program finder have demand and
list events of their own, see [List plugin events](list-plugin-events.md). The
view event comes after both.

## What a listener cannot do

The event carries no data of an extension. Changing which records a plugin
shows belongs in a demand or query event before the query; a listener of the
view event that assigns another value for a variable the action assigned
replaces it in the view, and only there. The persons list keeps paginating the
queried profiles, and the persons detail keeps the page title of the queried
profile. The `Breaking-` entries of `academic_jobs` and
`academic_persons` say where each setter of the removed events went.

A listener of a removed event raises no error. TYPO3 registers a listener's
event as a class name, from the parameter type or the attribute, without
loading the class, and PHP checks the parameter type only when the method is
called, which it never is. PHPStan reports the parameter as an unknown class.

## The test

`academic-base/Tests/Functional/Plugins/ModifyPluginViewEventTest.php` renders
every plugin and action, each on a page of its own, and the three early
returns, and asserts exactly one dispatch with the extension, plugin and action
name. Each row also names a text the page shows on that path, and a variable
the action assigns last, which the listener has to find assigned already. The
fixture extension `test_plugin_view_event` records the calls and the assigned
variables, and assigns `probe`, which its template overrides of the persons
list, the partner map and the job form print. The same class shows that a
listener replaces a variable the action assigned, `data`, that it cannot
replace the validations of the job form, and that a listener of a removed
event is registered and not called.

A new action that renders a view gets a row in that data provider. Removing
the dispatch from any path turns its row red, and so does moving it in front
of the action's own assignments.

A second listener of the fixture records the context of every event that carries
one. The test renders every plugin that dispatches more than the view event: the
persons list with and without a letter, the persons detail, card, selected
profiles and selected contracts, the partner list and map, the program list and
finder, and the project list. For each it asserts the sequence of events and
that all of them received the same context object. Another test asserts that
under a letter every event of the persons list sees the pagination switched off.
A context built a second time anywhere in such an action turns its row red.

## See also

- [Class design](class-design.md#extension-points) — the rules every event
  follows, and the page that lists them.
- [List plugin events](list-plugin-events.md) — the demand and list events of
  the partner, program and project lists, and the plugin context.
- [Fixture extensions](../testing/fixture-extensions.md) — how
  `test_plugin_view_event` is discovered and loaded.
