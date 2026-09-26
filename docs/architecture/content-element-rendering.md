# Content element rendering

Every academic extension puts markup on a page through a `tt_content` record,
and there are two ways it does that. Which one an extension uses decides where
the frame, the spacing and the header of that record come from — and, on TYPO3
v14, whether the header renders at all.

## The two shapes

| Shape                          | Used by                                          | The view is                                                   |
|--------------------------------|--------------------------------------------------|---------------------------------------------------------------|
| A copy of `lib.contentElement` | `academic_study_plan`                            | a `FLUIDTEMPLATE` with data processors, no Extbase controller |
| An Extbase plugin              | the twenty plugins of the other eight extensions | the controller's view, configured by `plugin.tx_<key>.view`   |

The study plan is the only content element of the first shape. Its TypoScript
copies `lib.contentElement` with `=<`, which carries the root paths, the
settings **and** the data processors of the original over, and adds its own
`templateName` and processor on top.

## What the `Default` layout does

`lib.contentElement` renders `Layouts/Default` — `Default.html` on TYPO3 v13,
`Default.fluid.html` on v14, with identical markup. That layout, and nothing
else, renders four things an editor configures on the record:

- the frame wrapper `<div id="c{uid}" class="frame frame-{frame_class} …">`,
  with the `frame-layout-{layout}` class that is the fourth field of the
  `frames` palette,
- the `frame-space-before-…` and `frame-space-after-…` classes,
- the `c{uid}` anchor, which is what a link to the element jumps to — as a bare
  `<a>` when the frame is "No frame",
- the sections `Before`, `Header`, `Main`, `Footer` and `After`. Four of them
  fall back to a partial of EXT:fluid_styled_content when the template defines
  no section of that name — `DropIn/Before/All`, `Header/All`, `Footer/All` and
  `DropIn/After/All`, the third of which is where `linkToTop` renders. `Main`
  has no fallback, which is why a template that uses the layout has to define
  it.

A template of the first shape that carries **no** `<f:layout name="Default" />`
gets none of them, however complete its Appearance tab looks in the backend.
That was the study plan until ACE-702: it rendered `Header/All` itself, so the
header appeared and the frame, spacing and anchor silently did not.

The layout is resolved through the `layoutRootPaths` of `lib.contentElement`,
so it comes from whichever package provides that object. On a
`bk2k/bootstrap-package` site that is not EXT:fluid_styled_content at all: the
site package redefines `lib.contentElement` and points `layoutRootPaths.0` at
its own `Resources/Private/Layouts/ContentElements/`, where a `Default.html` of
its own renders the sections `Header` and `Main` inside a `bk2k:frame` — a
different frame, with background colours and image variants the core layout has
no concept of.

That is the reason to render through the layout rather than to rebuild the frame
markup in the template: the element follows the site it is installed in. The
extension's own `layoutRootPath` constant points at a directory that does not
exist, and Fluid skips a root path it cannot read, so it changes nothing either
way.

## Who renders the header of a plugin

A plugin content element renders through the same layout. Its CType is
`tt_content.<CType> =< lib.contentElement` with the `Generic` template, so the
`Default` layout renders the `Header` section around the plugin output, as it
does for any other element. A plugin template that renders `Header/All` as well
shows the header twice: with an explicit header layout, the header and the
subheader appear twice; with the header layout "Default", the plugin leaves an
empty `<header></header>` behind. The partial takes the heading level for
"Default" from `settings.defaultHeaderType`, which the settings of
`lib.contentElement` carry and plugin settings do not.

Whether the layout renders the header is a property of the site, not of the
extension. The layouts of EXT:fluid_styled_content and `bk2k/bootstrap-package`
render it; a site package may ship a `Default` layout without a `Header`
section and render the header in its element templates instead. A plugin
template can therefore not decide on its own, and one that renders the header
does it behind a switch per extension, off by default:

| Plugin setting                        | Mapped from                                                                                                                                      |
|---------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| `settings.renderContentElementHeader` | the constant `plugin.tx_<key>.renderContentElementHeader`, default `0`; a site setting too where the extension declares site settings of its own |
| `settings.defaultHeaderType`          | the constant `styles.content.defaultHeaderType` of EXT:fluid_styled_content                                                                      |

The second one is the constant the `lib.contentElement` of
EXT:fluid_styled_content reads. Where the constants of EXT:fluid_styled_content
are not included, it is undefined, and the header layout "Default" then
renders an empty `<header>` in the plugin. That is the case on a
`bk2k/bootstrap-package` site, whose `lib.contentElement` reads a constant of
its own and which includes no TypoScript of EXT:fluid_styled_content.
TypoScript has no fallback for an undefined constant, so a site in that
position that switches the header on sets the plugin setting itself.

Seven extensions render the header this way, and each has the switch once, for
all of its plugins:

| Extension                 | Plugins                                                                    | Site setting | Path of `Header/All` |
|---------------------------|----------------------------------------------------------------------------|--------------|----------------------|
| `academic_jobs`           | list, detail, new job form                                                 | yes          | key `0`              |
| `academic_bite_jobs`      | list                                                                       | no           | key `0`              |
| `academic_persons`        | list, list and detail, card, detail, selected profiles, selected contracts | yes          | key `-2`             |
| `academic_partners`       | partner list, map, partnerships list, partnerships teaser                  | yes          | key `-2`             |
| `academic_programs`       | program list, program details, program finder                              | yes          | key `-2`             |
| `academic_projects`       | project list, selected projects                                            | yes          | key `-2`             |
| `academic_contacts4pages` | contacts list                                                              | no           | key `-2`             |

The partial path is the one of EXT:fluid_styled_content,
`EXT:fluid_styled_content/Resources/Private/Partials/`. `academic_jobs` and
`academic_bite_jobs` have had it at key `0` all along. In the other five, `-1` is
taken by the [shared partials](shared-partials.md) and `0` by the extension's
own path in four of them, so the path sits at `-2`, below every path of a
project: a `Header/All` of a site package wins over it. None of the five
requires EXT:fluid_styled_content. Nothing reads the partial while the switch
is off, and a site that switches it on either has EXT:fluid_styled_content or
provides its own `Header/All`.

The editing plugin of `academic_persons_edit` has no switch; its templates
render no content element header. A test of a plugin header counts the headings
in the DOM through `ContentElementHeaderAssertionTrait` of the
[testing helper](../testing/testing-helper.md), because an assertion that the
header text appears passes on a header that renders twice.

## The `record` view variable on TYPO3 v14

TYPO3 v14 rewrote the EXT:fluid_styled_content header partial. `Header/All`
resolves the header and subheader with `{record -> f:render.text(field: …)}`
instead of reading `{data.header}`, and that ViewHelper needs a record object.
TYPO3 v13 reads `data` and ignores the variable.

Where the record comes from differs by shape:

- **`lib.contentElement`** brings it itself. The `dataProcessing` of the
  EXT:fluid_styled_content definition contains the core `record-transformation`
  processor, and the `=<` copy inherits it along with the root paths and the
  settings. The study plan therefore needs no PHP at all for the header the
  layout renders. What it inherits is whatever the *providing* package defines,
  so on a site package that replaces `lib.contentElement` wholesale — as
  `bk2k/bootstrap-package` does — the processors are that package's, and a
  version of it that supports TYPO3 v14 has to bring the record itself for its
  own content elements anyway.
- **An Extbase plugin view** assigns `data` and no record, so a template that
  renders the partial raises an exception on v14 while it still renders on v13.
  Those controllers use
  `FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait` and assign
  `record` next to `data`. Assigning it is version agnostic — v13 ignores it.

This is a **version split that only one core version reports**. A template that
renders `Header/All` without the record is green on v13 and fatal on v14, which
is why everything that reaches that partial has a functional test asserting a
header the editor entered actually appears:

| Reaches `Header/All`                                                                    | How                | Test                                      |
|-----------------------------------------------------------------------------------------|--------------------|-------------------------------------------|
| `academic_jobs` `Job/List.html`, `Job/Show.html`                                        | behind the switch  | `AcademicJobsListAndDetailPluginTest`     |
| `academic_jobs` `Job/New.html`                                                          | behind the switch  | `AcademicJobsNewJobFormPluginTest`        |
| `academic_bite_jobs` `BiteJobs/List.html`                                               | behind the switch  | `AcademicBiteJobsListPluginTest`          |
| `academic_persons` `Profile/{List,Card,Detail,SelectedProfiles,SelectedContracts}.html` | behind the switch  | `AcademicPersonsContentElementHeaderTest` |
| `academic_partners` `Partner/{List,Map,PartnershipsList,PartnershipsTeaser}.html`       | behind the switch  | `AcademicPartnersPluginTest`              |
| `academic_programs` `Program/List.html`, `Details/Show.html`                            | behind the switch  | `AcademicProgramsPluginTest`              |
| `academic_programs` `Program/Finder.html`                                               | behind the switch  | `AcademicProgramsFinderTest`              |
| `academic_projects` `Project/List.html`                                                 | behind the switch  | `AcademicProjectsProjectListPluginTest`   |
| `academic_contacts4pages` `Contacts/List.html`                                          | behind the switch  | `AcademicContacts4PagesListPluginTest`    |
| `academic_study_plan` `AcademicStudyPlan.html`                                          | through the layout | `AcademicStudyPlanContentElementTest`     |

Those plugin templates are the whole list of templates that render the partial
directly, and each test renders them with the switch on and a fixture layout
without a header, which is the case that needs the record: every controller
behind them assigns it. The study plan reaches the partial through the
layout's `Header` section instead. `academic_persons_edit` assigns the record
pre-emptively; its templates render no content element header. A project that
replaces a list template with one rendering the header unconditionally, as
projects did for the contacts list, gets the record too - it only has to take
the header out of the element's layout, or the header renders twice;
`AcademicContacts4PagesListPluginTest` renders such a fixture setup and asserts
the header appears once.

## Testing the appearance settings

A rendering test asserts the frame markup through the DOM rather than as a
substring, because the class list is the contract with the layout:

```php
$this->assertSame(
    [
        'frame',
        'frame-ruler-before',
        'frame-type-academic_study_plan',
        'frame-layout-1',
        'frame-space-before-large',
        'frame-space-after-small',
    ],
    $this->classesOf($this->renderHomePage(), '//div[@id="c1"]'),
);
```

Pinning the whole list is deliberate. It is what turns a core change to the
layout into a red test instead of a surprise on a project, and asserting only
`assertStringContainsString('frame-ruler-before', …)` would pass on markup the
element never produced.

The test has to load EXT:fluid_styled_content and include its TypoScript, since
that is where `lib.contentElement` and the layout come from. The two are in
different places: the extension is loaded in
`AcademicStudyPlanContentElementTest::setUp()`, the TypoScript is included by
`setUpTestCase()` with the extension's own.

## See also

- [TypoScript and site sets](typoscript-and-site-sets.md) — how the content
  element setup is delivered twice from one copy.
- [Core version aware code](core-version-aware-code.md) — the other differences
  between the supported core versions.
- [Shared partials](shared-partials.md) — the partials `academic_base` ships and
  the root path key they need.
- [Functional tests](../testing/functional-tests.md) — the rendering test
  harness these tests are built on.
