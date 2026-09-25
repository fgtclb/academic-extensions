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

| Reaches `Header/All`                             | How                | Test                                  |
|--------------------------------------------------|--------------------|---------------------------------------|
| `academic_jobs` `Job/List.html`, `Job/Show.html` | renders it itself  | `AcademicJobsListAndDetailPluginTest` |
| `academic_jobs` `Job/New.html`                   | renders it itself  | `AcademicJobsNewJobFormPluginTest`    |
| `academic_bite_jobs` `BiteJobs/List.html`        | renders it itself  | `AcademicBiteJobsListPluginTest`      |
| `academic_study_plan` `AcademicStudyPlan.html`   | through the layout | `AcademicStudyPlanContentElementTest` |

Those four plugin templates are the whole list of templates that render the
partial directly; the study plan reaches it through the layout's `Header`
section instead. No other plugin template renders a content element header at
all, so no other controller needs the record for its own templates. Two
assign it anyway through the same trait: `academic_persons_edit`
pre-emptively, and `academic_contacts4pages`, because projects replace its
list template with one that renders the header. That only works where the
project also takes the header out of the element's layout, or the header
renders twice; `AcademicContacts4PagesListPluginTest` renders such a fixture
setup and asserts the header appears once. The remaining plugins get the header
from the layout of `lib.contentElement`, like any content element.

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
