# Content element rendering

Every academic extension puts markup on a page through a `tt_content` record,
and there are two ways it does that. Which one an extension uses decides where
the frame, the spacing and the header of that record come from.

## The two shapes

| Shape                          | Used by                                   | The view is                                                   |
|--------------------------------|-------------------------------------------|---------------------------------------------------------------|
| A copy of `lib.contentElement` | `academic_study_plan`                     | a `FLUIDTEMPLATE` with data processors, no Extbase controller |
| An Extbase plugin              | the plugins of the other eight extensions | the controller's view, configured by `plugin.tx_<key>.view`   |

The study plan is the only content element of the first shape. Its TypoScript
copies `lib.contentElement` with `=<`, which carries the root paths, the
settings and the data processors of the original over, and adds its own
`templateName` and processor on top.

## What the `Default` layout does

`lib.contentElement` renders `Layouts/Default.html`. That layout, and nothing
else, renders four things an editor configures on the record:

- the frame wrapper `<div id="c{uid}" class="frame frame-{frame_class} …">`,
  with the `frame-layout-{layout}` class that is the fourth field of the
  `frames` palette,
- the `frame-space-before-…` and `frame-space-after-…` classes,
- the `c{uid}` anchor, which is what a link to the element jumps to — as a bare
  `<a>` when the frame is "No frame", and what a section index menu links to,
- the sections `Before`, `Header`, `Main`, `Footer` and `After`. Four of them
  fall back to a partial of EXT:fluid_styled_content when the template defines
  no section of that name — `DropIn/Before/All`, `Header/All`, `Footer/All` and
  `DropIn/After/All`, the third of which is where `linkToTop` renders. `Main`
  has no fallback, which is why a template that uses the layout has to define
  it.

A template of the first shape that carries **no** `<f:layout name="Default" />`
gets none of them, however complete its Appearance tab looks in the backend.
That was the study plan until ACE-702: it rendered `Header/All` itself, so the
header appeared and the frame, spacing, anchor and "to top" link silently did
not.

The file is identical on both core versions this branch supports — the v12.4
and v13.4 copies of `Layouts/Default.html` are byte for byte the same — so
there is nothing version aware about this.

The layout is resolved through the `layoutRootPaths` of `lib.contentElement`,
so it comes from whichever package provides that object. On a
`bk2k/bootstrap-package` site that is not EXT:fluid_styled_content at all: the
site package redefines `lib.contentElement` and points `layoutRootPaths.0` at
its own `Resources/Private/Layouts/ContentElements/`, where a `Default.html` of
its own renders the sections `Header` and `Main` inside a `bk2k:frame` — a
different frame, with background colours and image variants the core layout has
no concept of.

That is the reason to render through the layout rather than to rebuild the
frame markup in the template: the element follows the site it is installed in.
The extension's own `layoutRootPath` constant points at a directory that does
not exist, and Fluid skips a root path it cannot read, so it changes nothing
either way.

A missing layout is fatal, not silent — Fluid raises
`InvalidTemplateResourceException`. That is not a new dependency: `f:render`
treats `optional` as a property of a *section*, never of a partial, so the
template's earlier `Header/All` render was just as hard a requirement on
whoever provides `lib.contentElement`.

## Testing the appearance settings

A rendering test asserts the frame markup through the DOM rather than as a
substring, because the class list is the contract with the layout:

```php
$this->assertSame(
    [
        'frame',
        'frame-ruler-before',
        'frame-type-academic_study_plan',
        'frame-layout-0',
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
- [Core version aware code](core-version-aware-code.md) — the differences
  between the supported core versions, of which this is not one.
- [Functional tests](../testing/functional-tests.md) — the harness these tests
  are built on.
