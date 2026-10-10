## Context

Every frontend script of the academic extensions is registered by the
template or partial that renders its markup, through the asset collector.
There is no TypoScript key that loads an ES module, so a switch cannot live in
the page setup. The study plan already had one (ACE-703): a boolean site
setting, a constant of the same path, mapped to `settings.assets.js` and
checked around the registration. See proposal.md for why the others need it.

## Goals / Non-Goals

**Goals:** one switch per extension, the same name, default and shape as the
study plan's, checked exactly where a script is registered, the markup
unchanged when it is off.

**Non-Goals:** a third state for a plugin configured without the shipped
TypoScript, and the scripts of the development site package.

## Decisions

**One switch per extension, named `plugin.tx_<plugin namespace>.assets.js`.**
It covers every script of the extension: the CKEditor CDN script and the
module that configures it in `academic_jobs`, the list and the finder module
in `academic_programs`. An installation that replaces the scripts of an
extension replaces them for its markup. Rejected: a switch per module, which
only multiplies settings, and a FlexForm field per element, because bringing
a script of one's own is a decision of the site.

**Declared where the extension declares its settings.** The aggregate set for
`academic_jobs`, `academic_persons` and `academic_programs`, the map set for
`academic_partners`, the profile editing set for `academic_persons_edit`.
Rejected: always the component set of the element, which would split the
settings of jobs, persons and programs over two places in the settings
editor. The constant carries the same default, which the delivery tests of
the sets assert.

**Checked by an `f:if` around every registration, not in the controller.** A
template override that copies the condition keeps the switch, one that does
not keeps loading, visible in the file. `academic_programs` registers the list
module in the list template and in the three partials of its form, so each of
the four checks it.

**The map partial takes the switch as an argument.** A partial sees its
arguments only, and a partner page template renders it as well. The plugin
passes `settings.assets`, the data processor `partner-data` adds `mapAssets`
from the same constant for a `FLUIDTEMPLATE` or `PAGEVIEW` page, because a
`PAGEVIEW` page object reads no `settings`. Without the argument the partial
loads the script: a map that silently stays empty is the worse failure. The
partial reads the argument into a variable first, so an absent argument and a
switched off one stay apart. The stylesheets of the map libraries are inside
the same condition, they are part of the script.

**Tests assert the bare specifier.** Each extension gets a functional test
that renders its element through the site set and the static template: the
set declares a bool that defaults to true, the script loads by default, and
it is absent with the site setting or the constant off while the markup is
present. The bare specifier of a module reaches the page only when it was
registered.

## Risks / Trade-offs

- [A template override keeps loading its own copy of a registration] →
  every manual section and Feature entry names the files to wrap.
- [A partner page template written before the switch ignores it] → it keeps
  loading the script, as documented, and the manual shows the call with
  `assets: mapAssets`.
- [A plugin configured without the shipped TypoScript loads no script] →
  documented in `docs/architecture/frontend-javascript-loading.md`, with the
  partner map as the exception because of the partial's fallback.
