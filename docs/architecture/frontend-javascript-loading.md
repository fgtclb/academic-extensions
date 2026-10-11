# Frontend JavaScript loading

Six extensions load JavaScript on a frontend page, each from the template or
partial that renders the markup the script drives. An installation that brings
its own script for that markup, or does not want a script on a site, would have
to override every one of those files, and then either lose the script with the
override or ship a copy that stops following the original.

So every extension that loads a script has **one switch** for it, and they all
have the same shape. `academic_study_plan` had it first, with ACE-703, and the
others follow it since ACE-891.

## The decision

| Part                  | What it is                                                                                                    |
|-----------------------|---------------------------------------------------------------------------------------------------------------|
| Name                  | `plugin.tx_<plugin namespace>.assets.js`, for example `plugin.tx_academicpartners.assets.js`                  |
| Type and default      | `bool`, `true`: nothing changes for an installation that configures nothing                                   |
| Site setting          | Declared in the `settings.definitions.yaml` of the set the extension declares its other settings with         |
| TypoScript constant   | The same path in `constants.typoscript`, `js = 1`, for a site configured through `sys_template` records       |
| Setting of the view   | `settings.assets.js = {$plugin.tx_<plugin namespace>.assets.js}` in the plugin's `setup.typoscript`           |
| Where it is respected | An `<f:if condition="{settings.assets.js}">` around every `f:asset.module` and `f:asset.script`, nowhere else |
| What off means        | The page loads none of the extension's scripts, and the markup is exactly the same                            |

What follows from it, and the alternatives that were rejected:

- **One switch per extension, not one per module or per element.** The
  programs list and the program finder share one, and so do the rich text
  editor of the job form and the module that configures it. An installation
  that replaces the scripts of an extension replaces them for its markup, and a
  switch per module would only multiply the settings a site has to know. A
  switch per content element, a FlexForm field, was rejected for the same
  reason: whether a site brings its own script is a decision of the site, not
  of an editor.
- **The setting is checked where the script is registered.** Not in a
  controller, not in PHP, not as a variable the controller computes. A
  template override that copies the `f:if` keeps the switch, one that does not
  copy it keeps loading, and both are visible in the file itself.
- **The markup does not change with the switch.** It is the contract a script
  of the installation addresses, see
  [Frontend assets](../development/frontend-assets.md#a-module-finds-its-parts-by-attribute-never-by-class).
  A template must not render a different form or drop a data attribute when
  the switch is off.
- **A stylesheet that belongs to a library goes with the script.** The map of
  `academic_partners` loads the stylesheets of Leaflet and its marker cluster
  plugin. They are useless without the module that imports the libraries, and a
  site that draws the map with a library of its own brings that library's
  stylesheet. They are the only stylesheets an extension still registers: its
  own were moved to the site package of the development instances with
  ACE-890, see
  [Frontend assets](../development/frontend-assets.md#the-stylesheet-of-the-development-instances).
- **The constant and the site setting have the same default.** A site that
  includes the set and the static template reads the constants after the site
  settings, see
  [TypoScript and site sets](typoscript-and-site-sets.md#there-is-no-double-parse-guard-and-that-is-deliberate).
  A boolean site setting arrives in the constants as `1` or as the empty
  string, which `f:if` reads as on and off.

The set follows the rule of
[TypoScript and site sets](typoscript-and-site-sets.md#layout-per-extension):
the switch is declared where the extension already declares its settings, so a
site finds it in the same category of the settings editor. That is the
aggregate set where the extension declares everything there, and the component
set where the script belongs to one component that declares its own settings.

## Which extension loads what

| Extension               | Script                                                                                 | Registered in                                                                                                                          | Set that declares the switch                   | Without a script                                                     |
|-------------------------|----------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------|----------------------------------------------------------------------|
| `academic_jobs`         | CKEditor 4 from its CDN and `frontend/rich-text.js`                                    | `Templates/Job/New.html`                                                                                                               | `fgtclb/academic-jobs`                         | Works: the description fields are plain text areas                   |
| `academic_partners`     | `frontend/map.js`, the Leaflet libraries through the import map, and their stylesheets | `Partials/Partner/Map.html`                                                                                                            | `fgtclb/academic-partners-map`                 | The map stays empty, the partners are only in the hidden list        |
| `academic_persons`      | `frontend/profile.js`                                                                  | `Templates/Profile/Detail.html`                                                                                                        | `fgtclb/academic-persons`                      | The entries of the profile stay folded                               |
| `academic_persons_edit` | `frontend/profile.js`                                                                  | `Templates/Profile/Index.html`                                                                                                         | `fgtclb/academic-persons-edit-profile-editing` | The editor does nothing                                              |
| `academic_programs`     | `frontend/program-list.js`, `frontend/program-finder.js`                               | `Templates/Program/List.html`, `Finder.html`, `Partials/Program/SortingAndFilters.html`, `DemandSorting.html`, `DemandCategories.html` | `fgtclb/academic-programs`                     | Works: the forms are submitted with their buttons                    |
| `academic_study_plan`   | `frontend/academic-study-plan.js`                                                      | `Frontend/Default/Templates/AcademicStudyPlan.html`                                                                                    | `fgtclb/academic-study-plan-content-element`   | The filter, the module dialogs and the semester accordion do nothing |

`academic_base` has no switch, because it registers no module on any page. The
module it publishes through its import map, the frontend icon factory
`@fgtclb/academic-base/frontend/icons.js`, is a library: it is imported by a
module of another package or of the site package and loaded exactly when that
one is, so the switch of the consumer covers it. `academic_bite_jobs`, `academic_contacts4pages`,
`academic_projects`, `academic_persons_sync` and `category_types` load no
frontend script at all.

Each manual documents its switch in a section with the anchor
`configuration-javascript`, the study plan in its section `asset-switches`, and
says whether the element works without a script and what an installation that
switches it off has to provide.

### The map partial takes the switch as an argument

`academic_partners` is the one extension whose script is registered in a
partial that a site package renders as well: a partner page template draws the
map of its partner with `Partner/Map.html`. A partial sees only its arguments,
so it takes the switch as the argument `assets`, the same shape as
`settings.assets`:

- the `Partners Map` content element passes `assets: settings.assets`,
- the data processor `partner-data` of the partner page adds `mapAssets` from
  the same constant, next to `mapSettings`, because a `PAGEVIEW` page object
  reads no `settings`,
- a call **without** the argument loads the script. The partial cannot tell a
  template that forgot the argument from one that wants the script, and a map
  that silently stays empty is the worse of the two failures.

The partial reads the argument into a variable first and checks that, so an
absent `assets` and an `assets.js` that is off stay apart:

```html
<f:variable name="loadScript" value="1" />
<f:if condition="{assets}">
    <f:variable name="loadScript" value="{assets.js}" />
</f:if>
<f:if condition="{loadScript}">
    <f:asset.module identifier="@fgtclb/academic-partners/frontend/map.js" />
</f:if>
```

## What the switch does not reach

- **A template override.** Fluid renders the copy as it is, so its own
  `f:asset` lines keep loading until they are wrapped in the same `f:if`. Every
  Feature entry says so for the files of its extension.
- **A plugin configured without the shipped TypoScript.** An Extbase plugin
  whose `settings` carry no `assets` block reads the switch as off and loads
  nothing, exactly like a hand-written content object of the study plan. This
  is deliberate: guarding it in the template would make the switch a value that
  needs a third state. The `Partners Map` plugin is the exception: it hands
  `settings.assets` to the map partial, which gets nothing then and falls back
  to loading the script, as for a page template without the argument.
- **The page object of the standalone flavour of `academic_persons`.**
  `Configuration/TypoScript/StandalonePage/` loads Bootstrap from its CDN with
  `includeCSS` and `includeJSFooter`. It is a page for an installation without a
  site package, an alternative to a theme, not the script of a plugin, and a
  site that does not want it does not include that set.
- **The classic map libraries of `academic_partners`.**
  `Resources/Public/JavaScript/leaflet.js` and `markerCluster.js` are
  deprecated and loaded by nothing.

## A new script follows the same shape

A module or script added to a frontend template is registered inside the
existing `f:if` of its extension, or inside a new one that checks the same
setting. An extension that loads its first script adds the whole switch:

1. the definition in `settings.definitions.yaml`, `type: bool`, `default: true`,
   with the label and the description saying what off means for its markup,
2. the constant in `constants.typoscript`, `js = 1`, in an `assets` block,
3. `settings.assets.js` in the plugin setup, or on the content object of a
   `FLUIDTEMPLATE` element,
4. the `f:if` around every registration,
5. a functional test that renders the element through both delivery
   mechanisms: on by default, and off through the site setting and through the
   constant, asserting that the bare specifier of the module is absent and the
   markup is not,
6. the section `configuration-javascript` in the manual and a `Feature-*.rst`
   entry.

The tests are `*ScriptSettingTest` in the `Tests/Functional/Plugins/` folder of
each extension, `AcademicStudyPlanSiteSettingsTest` for the study plan. They
assert the bare specifier, `@fgtclb/<package>/frontend/<module>.js`, because it
reaches the page only when the module was registered.

## See also

- [Frontend assets](../development/frontend-assets.md#making-an-asset-optional) -
  the build, how a module is loaded, and the shape of the switch on a
  `FLUIDTEMPLATE` content element.
- [TypoScript and site sets](typoscript-and-site-sets.md) - where a setting is
  declared, and why the two defaults have to agree.
- [Overridable partials](overridable-partials.md) - the override that keeps its
  own asset lines.
