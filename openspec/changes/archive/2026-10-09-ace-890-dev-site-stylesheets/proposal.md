## Why

The academic extensions render speaking `ace-*` classes since ACE-818, so their
markup is meant to be styled by the site package. Four of them still bring a
stylesheet of their own, which an installation then has to override or switch
off. ACE-890 stops shipping them and keeps the rules as the site package part
of the development instances.

## What Changes

- **BREAKING** `academic_study_plan` (`packages/fgtclb/academic-study-plan/`)
  ships no stylesheet. The template registers none, and the site setting and
  TypoScript constant `plugin.tx_academicstudyplan.assets.css` are removed.
  The switch of the script, `plugin.tx_academicstudyplan.assets.js`, stays.
- **BREAKING** `academic_partners` (`packages/fgtclb/academic-partners/`)
  ships no stylesheet for the map. The stylesheets of Leaflet and its marker
  cluster plugin stay and load with the map, which needs them.
- **BREAKING** `academic_persons` (`packages/fgtclb/academic-persons/`) ships
  no stylesheet for the public profile and none for the profile lists.
- **BREAKING** `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit/`) ships no stylesheet for the
  profile editor.
- `academics_dev_site` (`packages-dev/dev-site/`, never released) carries the
  rules, one SCSS partial per extension compiled into one stylesheet, linked on
  every page of the `/` and the `/legacy/` tree of both development instances.

Every change applies to TYPO3 v13 and v14 alike, there is no difference
between the two.

## Capabilities

### New Capabilities

- `academic-persons/frontend-assets`: what the public profile and the profile
  lists bring to a page.
- `academic-persons-edit/frontend-assets`: what the profile editor brings to a
  page.

### Modified Capabilities

- `academic-study-plan/frontend-assets`: the element brings its script and no
  stylesheet, the stylesheet switch is gone.
- `academic-study-plan/frontend-markup-contract`: the glyph switch is the
  site stylesheet's, not a shipped one's.
- `academic-partners/map-libraries`: a map page links the stylesheets of the
  libraries and none of its own.

## Impact

Templates of the four extensions, the study plan site set, constants and
setup, their functional tests, the four manuals and a Breaking changelog entry
each. The development site package gains SCSS sources, a compiled stylesheet,
a site set and an import in its static template, and both instance site
configurations name that set. The frontend build picks the new sources up
without configuration.

## Non-goals

- Configuring the loading of the scripts, which ACE-891 generalises.
- Changing the markup or the classes of any extension.
- Moving the stylesheets of the map libraries, which ACE-891 puts under the
  script setting.
- Releasing the development site package or making it a dependency of any
  extension.
