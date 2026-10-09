## MODIFIED Requirements

### Requirement: The semester glyph switch keeps working

The expand and the collapse glyph of a semester header SHALL carry the classes
`icon-tx-academicbase-action-expand` and `icon-tx-academicbase-action-collapse`
and the class `icon`, which a site stylesheet selects, so that with a
stylesheet like the one of the development instances a closed semester shows
the expand glyph, an open one the collapse glyph, and a wide viewport neither.
This SHALL hold for an override of the semester part that renders the two
identifiers from the frontend icon registration. A stylesheet like that one
sizes every icon of the study plan, so a replacement drawing without a size of
its own stays visible.

#### Scenario: Narrow viewport

- **WHEN** a visitor on a narrow viewport opens a semester of the study plan
  on a site whose stylesheet switches the glyphs by those classes
- **THEN** its header shows the collapse glyph instead of the expand glyph

#### Scenario: Wide viewport

- **WHEN** a visitor views the study plan on a wide viewport of such a site
- **THEN** no semester header shows a glyph

#### Scenario: Override of the semester part

- **WHEN** an integrator overrides the semester part and renders the two
  glyphs from the frontend icon registration under their identifiers
- **THEN** the glyph switch works as with the shipped part

#### Scenario: A replacement drawing without a size

- **WHEN** a site package of such a site replaces the close glyph with a
  drawing that has no width and no height of its own
- **THEN** the close button of a module dialog still shows it, in the size
  of the other icons
