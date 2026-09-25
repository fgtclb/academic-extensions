## Context

See `proposal.md` for the motivation. On main:

- `Configuration/TCA/Overrides/tt_content.php` registers only
  `academicprograms_programlist` and `academicprograms_programdetails`,
  through `TcaManipulator::addContentElementPlugin()` and
  `addContentElementPluginFlexForm()`, which absorb the v13/v14 differences
  (ACE-293).
- `ext_localconf.php` configures `ProgramList` with `listAction` uncached, and
  `ProgramController` has only `listAction()`.
- The list accepts
  `tx_academicprograms_programlist[demand][filterCollection][<type>]`, a
  single uid or a list (`CategoryFilterNormalizer`, ACE-357).
- `Configuration/page.tsconfig` hides every element of the extension, and
  each component set's page TSconfig re-enables its own.
- Three projects, the ACE demo among them, already store
  `academicprograms_programfinder` records with `settings.listPid` in their
  FlexForm.
- The category types of the programs group include `degree` and `topic`
  (`Configuration/CategoryTypes.yaml`).

## Goals / Non-Goals

**Goals:**

- The three projects' records render with the upstream element without a
  data migration.
- The finder submits exactly what the list's own filter submits.

**Non-Goals:**

- A JavaScript dependency; the element is complete without one.

## Decisions

### Adopt the name the projects already store

CType `academicprograms_programfinder`, plugin `ProgramFinder`, FlexForm key
`settings.listPid`. Stored records of those three projects migrate for free;
the price is that their own registration collides with this one until they
delete it (see Risks).

Rejected: a new name, which would need a record migration in three projects.

### Decided: adopt `academicprograms_programfinder`

Upstream adopts the CType `academicprograms_programfinder` with plugin
`ProgramFinder` and `settings.listPid`, and ships a Breaking changelog entry
that names what the projects with their own registration delete. Their
stored records then render with the upstream element without a migration,
while a new name would need a record migration in three projects. The
upgrade configuration check reports an XCLASS of the controller, not a
project registration of the content type; a finding for that would be a
follow-up.

### A non-cacheable `finderAction()` on `ProgramController`

`finderAction()` builds the demand from the element's storage (`pages`,
`recursive`) through `DemandFactory`, queries through
`ProgramRepository::findByDemand()`, and collects the options with
`CategoryRepository::findAllApplicable('programs', …)`, as the list does. It
is registered uncached like `listAction()`: which options are available
depends on program pages elsewhere in the tree, and no cache tag ties the
finder's page to them, so a cached finder would keep offering stale options.

Rejected: a teaser mode of the list plugin, which is ace-demo's switch on the
CType inside `List.html` and puts two outputs into one template. Rejected: a
separate controller, which duplicates the collaborators the program
controller already has.

### The form targets the list plugin

`Templates/Program/Finder.html` renders `f:form` with
`actionUri="{listUri}"`, `extensionName="AcademicPrograms"` and
`pluginName="ProgramList"` - the latter two set the field name prefix - and
one select per type named `demand[filterCollection][<type>]`, with the method
the list's own filter form uses. The shape is pinned by a test, because the
finder now depends on it.

`finderAction()` builds `listUri` itself (`uriFor('list', …, 'ProgramList')`
on `settings.listPid`) instead of letting the form do it: a page that cannot
be linked - hidden, deleted, access restricted - yields an empty URI without
an exception, and a form with an empty `action` posts to the page it is on.
An empty URI renders no form, and so does a finder whose types have no
category.

The finder submits no sorting. `DemandFactory::createDemandObject()` now
applies the element's `settings.sorting` to a demand that carries none, not
only to a request without any demand, so the target list keeps its sorting.
The same gap hit the filter form of a list whose sorting select is hidden;
both get an `Important` changelog entry. The selection replaces the list's
preset categories, as its own filter does.

### FlexForm and registration

`Configuration/FlexForms/ProgramFinderSettings.xml` holds:

- `settings.listPid`: `group` on `pages`, `minitems` and `maxitems` 1,
  required. FormEngine enforces `required` in the browser; the DataHandler
  does not check it on a relation field (verified on v13 and v14:
  `validateValueForRequired()` is called by the scalar field types, not for
  group, select, category or inline), so a
  record written by an import can lack it and the finder then renders no
  form. FormEngine submits `pages_<uid>`, the DataHandler stores the bare uid
  for a single allowed table, which is what the controller casts;
- `settings.filter.categoryTypes`: `selectMultipleSideBySide` from the
  category type items provider `CategoryTypeItemsProcFunc` of ACE-736. An
  empty field falls back to the site-wide
  `plugin.tx_academicprograms.settings.filter.categoryTypes` through the
  existing `ignoreFlexFormSettingsIfEmpty` entry - an entry in the extension
  block applies to every plugin of the extension unless a plugin block sets
  its own - so it covers the finder too, and `finderAction()` offers
  `degree,topic` (a class constant) when that is empty as well. The resolver
  `FilterTypeResolver` of ACE-736 drops types without categories, as for the
  list;
- `settings.preselectedCategories`: `category`, `oneToMany` without `MM`, so
  the FlexForm stores the uid list inline, restricted to typed categories
  like the list's `settings.categories`.

A select holds one value, so the first preselected category of a type in the
stored list wins; the category tree of the field stores the checked
categories in tree order, so that is the one higher in the tree.
A preselected category whose type is not offered is ignored, and so is one
that is disabled: a browser does not submit a disabled option, and the
visitor would search for something other than what the select shows.

### Decided: the finder uses the filter type key of the list

The finder stores its category types under `settings.filter.categoryTypes`,
the key the program list uses for its per-element override of the site-wide
filter types. One name for one concept in programs: the site-wide setting is
the default and the element's field overrides it, as for the list. Storage comes
from the `pages` and `recursive` fields of the record, added with
`addToAllTCAtypes()` like the list's.

### Decided: the finder has its own storage fields

The finder keeps its own `pages` and `recursive` fields, and the
documentation states that the finder and its target list point at the same
storage. Reading the storage of the list element instead was rejected:
`settings.listPid` names a page, not a content element, and that page can
carry several list elements or none yet, so "the target list's storage" is
not well defined. It would also cost a `tt_content` query and a FlexForm
parse of another record on every uncached finder render.

A component set `Configuration/Sets/ProgramFinder/` with its page TSconfig
(re-enabling the type and adding the wizard entry), the static page TSconfig
registration in `pages.php`, the TypoScript folder with
`include_static_file.txt`, and the aggregate set's dependency follow the list
set. `Configuration/page.tsconfig` hides the new type.

Guessed layout — a sketch, not a design:

```text
GUESSED  finder element, e.g. in a home page hero
+----------------------------------------------------------+
| Find your study program                                  |
| Degree   [ Bachelor        v]   Interest [ All       v]  |
|                                     [ Show programs -> ] |
+----------------------------------------------------------+
  -> submits to settings.listPid, the list opens pre-filtered
```

## Risks / Trade-offs

- [The finder posts into another plugin's argument namespace] → The functional
  test pins the target page, the namespace and the field names; routing work
  (ACE-623) has to keep them.
- [Registration in projects] → A project registration loaded later collides
  with this one, verified on v13 and v14: `addPlugin()` replaces an item of
  the same value, `addTcaSelectItem()` or a direct write adds it a second
  time; an assigned data structure replaces this one; `configurePlugin()`
  replaces the entry of the same controller class, its uncached actions
  included, while a controller of its own is added behind the upstream
  default; a subclass or XCLASS of `ProgramController` with a
  `finderAction()` of its own overrides this one, fatally when the signature
  is incompatible; and a project `Program/Finder.html` in the template root
  is rendered by this action. The `Breaking` changelog says so and names what
  to delete; the upgrade configuration check reports the XCLASS case only.
- [Finder and target list use different storage] → Options then disagree
  with the list; the documentation says to point both at the same storage.

## Migration Plan

The three projects that register the type delete their TCA item, plugin
configuration, FlexForm, controller code and TSconfig for it and keep their
records; one of them replaces its hard-coded Bachelor uid with the
preselection setting. The project with an element of its own migrates it (its
target page field becomes `settings.listPid`) as described in the integrator
migration guide.

## Open Questions

None.
