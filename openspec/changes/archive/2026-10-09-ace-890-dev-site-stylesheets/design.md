## Context

ACE-818 gave the frontend markup of the academic extensions speaking `ace-*`
classes, meant to be styled by the site package. The study plan, the partner
map, the public profile, the profile lists and the profile editor still
registered a stylesheet of their own from their templates, the study plan with a site setting to
switch it off. The issue asks for the rules to leave the extensions and to
live on, combined, in the development site package `academics_dev_site`,
loaded on every page of both trees of the development instances.

## Goals / Non-Goals

**Goals:** no extension registers a stylesheet of its own; the instances look
as before, in `/` and `/legacy/`, on v13 and v14; the setting that switched
the study plan stylesheet is gone; an integrator finds the rules to copy.

**Non-Goals:** the script settings (ACE-891), any markup or class change, the
library stylesheets of the map.

## Decisions

**The SCSS sources move with `git mv` and keep their rules, with one
exception.**
Each source becomes a partial below
`packages-dev/dev-site/Resources/Private/Scss/frontend/`, named after the
extension directory, two for `academic-persons` (detail view and lists), and
one entry `academic-extensions.scss` uses all five.
The build already discovers `packages-dev/*` and skips partials as entry
points, so neither `Build/esbuild.mjs` nor `checkJsBuildClean` changes. The
compiled file was compared with the five removed ones: the rules are identical
apart from the z-index rules of the map. Leaflet's stylesheets are registered
by the map partial and now come after the page's stylesheet instead of before
the removed `map.css`, so those rules name `.leaflet-container` as well to
keep winning over Leaflet's single class rules, which a DDEV check in a browser
confirmed. Moving rather than rewriting keeps git's
rename detection working for the commits of ACE-818 that still change the
study plan rules underneath this change. Rejected: one stylesheet per
source in dev-site, which would need five links per page for no gain.

**Two routes, one file.** `Configuration/TypoScript/Stylesheet.typoscript` holds
the single `page.includeCSS` line. The static template folder of the package,
which the `/legacy/` root `sys_template` already includes, imports it next to
the page object, so the seed does not change. The `/` tree is themed by
`bk2k/bootstrap-package` and gets it from a new set
`fgtclb/academics-dev-site-stylesheet`, named last in both committed site
configurations so that it comes after the theme. Rejected: putting the line
into the page object, because the page object set is what `LegacyDeliveryTest`
puts in place of the theme. The page object moved into
`PageObject.typoscript`, which the set imports alone, so the `/` side of that
test only links the stylesheet while the committed site configuration names
the stylesheet set. Rejected as well: a second static template folder, which
would have meant a change of the seed and the committed database templates.

**The Leaflet stylesheets stay in `Partner/Map.html`.** The map does not work
without them, so they are part of the script, not of the styling.

**Tests assert absence by inventory.** The functional tests of the four
extensions, for every view that registered a stylesheet, assert that no `<link rel="stylesheet">` is rendered at all, or for
the map exactly the three library stylesheets, rather than the absence of one
file name, so a stylesheet registered under another name fails too. The test
that compared the classes of the shipped study plan stylesheet with the
rendered markup moves to the development site package, because a test of a
split out package cannot read a file of `packages-dev/`. There
`StylesheetClassesTest` covers the study plan, the public profile and the
profile lists, reading the classes from each partial, since the compiled file
does not tell which partial a rule came from, and checks the map partial
against the stylesheets of Leaflet. The profile editor is left out: the editor
needs a logged in owner, its partial also corrects theme classes, and it
styles the widget CropperJS builds and the transition classes the editor
derives from a prefix. The check of the study plan icon rules that read the
removed study plan stylesheet moves along as `StudyPlanControlIconRulesTest`,
a unit test of the compiled file.

## Risks / Trade-offs

- An installation without a stylesheet of its own loses more than looks: the
  map has no height, semester and fold-out headers show both glyphs, regions
  the profile editor hides stay visible and its cropper cannot be dragged. The
  shipped icons keep their own `1em` size, only a replacement drawing without
  a size collapses. Each Breaking entry and manual names these parts.
- A site setting `plugin.tx_academicstudyplan.assets.css` left in a site
  configuration is ignored, a test pins that.
