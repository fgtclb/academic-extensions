# List plugin events

A project that wants a list plugin to show something else than it does has two
ways in: replace the controller, or listen. Replacing the controller means
subclassing it and re-registering the plugin, and it breaks on every upstream
change of a constructor or an action signature — `ace-demo` carried such a
subclass for years. Listening does not, so the list plugins dispatch events and
the controllers stay untouched.

This page is about the **shape** the events have and the rules that are easy to
get wrong. What each event carries is documented in the
`Documentation/` of the extension that ships it.

## The pair, per list

A list plugin dispatches **two** events, and they are two because they answer
two different questions:

| Event        | Dispatched                                              | A listener can                                               |
|--------------|---------------------------------------------------------|--------------------------------------------------------------|
| demand event | after the demand is built, before the query             | replace the demand, so the query itself is different         |
| list event   | after the query, before the view variables are assigned | replace the result and the categories, assign view variables |

`academic_partners` ships `ModifyPartnerDemandEvent` and
`ModifyPartnerListEvent`, dispatched in both `listAction()` and `mapAction()`;
`academic_projects` ships `ModifyProjectDemandEvent` and
`ModifyProjectListEvent`, dispatched in the one `listAction()` that serves both
project list plugins.

Neither event fires for a submission of the filter and sorting form: the plugin
answers the POST with a redirect before the demand event, and both events fire
on the GET request that follows, with the selection in the query string rather
than in the parsed body. See [List filter URLs](list-filter-urls.md).

`academic_persons` has the same pair in a different place.
`ModifyListProfilesEvent` is its list event and sits in the controller like
these; its *narrowing* point is `ModifyProfileQueryEvent`, which hangs in the
repository and hands the listener the Extbase query rather than the demand,
because four content elements share that one query. Both shapes are supported
API; which one an extension has follows from where its filtering lives, and
neither is to be converted into the other without a reason.

## Every event carries the plugin context

The context is `FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext`,
built from the request and the settings, and it is what makes one listener
serve several plugins:

```php
if ($event->getPluginControllerActionContext()->getPluginName() !== 'Map') {
    return;
}
```

The plugin name is the one the plugin was **registered** with — `List`, `Map`,
`ProjectList`, `ProjectListSingle` — not the content element type. The settings
on the context are the settings of the content element that is rendering, so a
listener can read a FlexForm field an editor filled in.

`academic_persons` has a second, older context class of its own under
`FGTCLB\AcademicPersons\Domain\Model\Dto\`, used by the events it shipped before
the shared one existed. New events take the `academic_base` one.

## Rules that are easy to get wrong

**A restriction the plugin must keep runs after the demand event.** The partner
map restricts the demand to partners that can be drawn, because a partner
without coordinates ends up at 0/0 instead of being left out (ACE-562). That
restriction is applied *after* the event, so a listener that hands back a fresh
demand — with the restriction off again — still gets a map without them. The
same holds for anything else a plugin guarantees rather than offers: dispatch
first, then enforce.

**A demand listener widens as easily as it narrows.** This is not the
constraint API of `academic_persons`, where the **conditions** a listener hands
out are `AND`-ed with the extension's own and can therefore only narrow. Here
the demand *is* the query: `setShowHiddenRecords(true)` shows hidden records to
every visitor, `setPages([])` drops the storage restriction the editor chose,
and `setSorting()` overrides the editor's ordering for every element at once.
Nothing guards those three, and two listeners that disagree are resolved by the
order they run in. What a listener cannot undo is what the repository pins
unconditionally: the page type, the enable fields other than `disabled`, and
the `uid` tiebreaker of the ordering. Say all of that where such an event is
documented;
[Database queries](database-queries.md#rule-3-in-an-open-query--constraints-an-extension-adds)
is the written rule this follows.

**A replaced demand starts from the defaults.** A listener that hands
`setDemand()` a demand it built itself, rather than mutating the one it was
given, drops everything the factory put there: the editor's `showHiddenRecords`
choice, the sorting, the project list's `activeState`, and — the one that
bites — the `filterCollection`, which is the category selection *the visitor
just submitted*. For projects `showSelected` travels with `pages` or it changes
their meaning: the repository reads `pages` as `uid IN (…)` when `showSelected`
is true and as `pid IN (…)` when it is not, so carrying one without the other
turns a single-selection element into a storage-folder restriction. Mutate
where you can; carry everything over where you cannot.

**A replaced result is rendered as it is.** `setPartners()` and
`setProjects()` take whatever query result a listener hands back, and the
repository's `setOrderings()` is not reapplied to it. A result a listener built
itself carries its own ordering, or the list is in whatever order the database
returns — which is not the same list twice on PostgreSQL.

**The categories are not recomputed after the list event.** They are computed
from the queried records, once, before the event. A listener that replaces the
result and wants the filter to match it sets the categories too, and builds
them the way the controller does —
`CategoryRepository::findAllApplicable($group, ...$narrowed->toArray())`, which
keeps every category of the group and marks the ones no record carries as
disabled options. `findByGroupAndUidList()` returns a bare list instead, so a
listener that reaches for it silently drops the disabled options the plugin
otherwise renders. Recomputing them after the event would run
`findAllApplicable()` a second time on every request that has no listener at
all.

## Testing them

The listener is the fixture: a small extension under
`Tests/Functional/Fixtures/Extensions/` registers one listener per event with
TYPO3's `#[AsEventListener]`, and every listener stays inert until a plugin
setting asks it for something. A test then includes the TypoScript file of the
behaviour it is about, and the same fixture serves every scenario of the class.

That the plugins render unchanged while nothing listens is not asserted in
those classes — it is what every *other* plugin test of the extension asserts,
because none of them loads the fixture.

## See also

- [Fixture extensions](../testing/fixture-extensions.md) — how the listener
  fixtures are discovered and loaded.
- [Dependency injection](dependency-injection.md) — why the listeners use
  TYPO3's `#[AsEventListener]` and never Symfony's.
- [Database queries](database-queries.md) — the ordering a demand produces, and
  what a listener must not take away.
