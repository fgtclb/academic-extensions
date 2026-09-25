## Context

Verified on `main`, first on 2026-09-12 and again on 2026-09-25:

- Every plugin is registered as a content element, so each CType is
  `tt_content.<CType> =< lib.contentElement` with the `Generic` template. Its
  `Default` layout renders the header section around the plugin output on a
  site with the layout of EXT:fluid_styled_content or of
  `bk2k/bootstrap-package`. The contacts plugin test
  `listPluginRendersContentElementHeader` shows the header with the plugin
  template rendering none.
- On such a site a plugin template that renders `Header/All` as well shows
  the header and the subheader twice with an explicit header layout, and an
  empty `<header>` with "Default" - probed for the jobs templates, see
  `ace-729-jobs-header-rendered-twice`, which adds a per-extension switch.
- The projects behind this change use a content element layout that renders
  the frame and `Main` only; their element templates render headers. On such
  a site no plugin shows a header, which is why they copy plugin templates.
- `Header/All` is a partial of EXT:fluid_styled_content. Of the five
  extensions, none requires `typo3/cms-fluid-styled-content` and none has its
  partial path. The existing partial path keys are `-1`/`0`/`1` (persons,
  partners, programs), `-1`/`0` (projects) and `-1`/`5`/`10`
  (contacts4pages); `-1` is the shared partial path of academic_base in all
  five (ACE-646).
- On TYPO3 v14 the header partial renders through `record`
  (`GetCurrentContentRecordMethodTrait`, academic_base, `@api`, ACE-270); v13
  reads `data`. The contacts controller assigns it since ACE-728; the persons,
  partners, programs and projects controllers do not. The persons and
  contacts controllers are final, the partners, programs and projects
  controllers are not.
- The role heading partials do not print the subheader: both use it only to
  pick the heading level, `Partner/Header` together with `grouped`.
  The requirement "role headings do not repeat the subheader" of the earlier
  version of this change rested on a misreading and is dropped.

## Goals / Non-Goals

**Goals:**

- A site whose layout leaves the header out gets plugin headers by one
  setting per extension, without template copies.
- No change for a site whose layout renders the header.

**Non-Goals:**

- Changing the jobs, bite jobs or study plan templates (the jobs change
  does that).

## Decisions

### The switch of the jobs change, per extension

Each extension gets `settings.renderContentElementHeader`, mapped from its
own constant (`plugin.tx_academicpersons.renderContentElementHeader` and so
on, default `0`) and declared as a site setting where the extension declares
its others. Each template renders
`<f:render partial="Header/All" arguments="{_all}"/>` first, inside
`<f:if condition="{settings.renderContentElementHeader}">`. Same name and
semantics as in `academic_jobs` and `academic_bite_jobs`, so a site switches
them all the same way.

Rejected (2026-09-25): the unconditional header of the earlier version - it
doubles the header on every site with the core or bootstrap package layout.
Rejected: a switch shared through academic_base, which delivers no
TypoScript (see the jobs change).

### `record` and the default header type

Each action assigns `record` through the trait next to `data`, in
`ProfileController` (five actions, including the early returns of
`selectedProfilesAction()` and `selectedContractsAction()`),
`PartnerController`, `ProgramController`, `DetailsController` and
`ProjectController`. Assigning it is harmless with the switch off. Each
plugin setup maps `settings.defaultHeaderType` as the jobs change does, so
the header layout "Default" renders a heading when switched on.

### The header partial path below every project slot, no new requirement

Same rule as the image partial of `cross-cutting-01`: a negative key, so a
project can still override `Header/All` through its constant slot. `-1` is
taken by the shared partials of academic_base in all five extensions, so the
header path takes `-2`. The
extensions do not require `typo3/cms-fluid-styled-content`: with the switch
off nothing reads the partial, and a site that switches it on either has the
extension or provides its own `Header/All`. The configuration chapters say so.

### Changelog

A `Feature-` entry per extension in `Documentation/Changelog/3.0/`, `main`
only: nothing renders differently until a site switches it on. (The earlier
version planned a `Breaking-` entry for the unconditional header.)

### What a visitor sees with the switch on

Guessed layout — a sketch, not a design:

```text
+ site layout without a header section ---+
| <h2>Our professors</h2>  <- Header/All  |
| A B C ... Z                              |
| [card] [card] [card]                     |
+------------------------------------------+
```

## Risks / Trade-offs

- [A site switches it on although its layout renders the header] -> Double
  header; the configuration chapters say when to use it.
- [A plugin wrapper already renders a header in some project] -> Only with
  the switch on; named in the Feature entries.

## Open Questions

None.
