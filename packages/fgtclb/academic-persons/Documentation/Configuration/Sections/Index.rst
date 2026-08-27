..  index:: Configuration; Public profile
..  _configuration-sections:

=======================
Public profile settings
=======================

:file:`Configuration/AcademicPersons/Settings.yaml` belongs exclusively to
:guilabel:`academic_persons`. Its single top-level :yaml:`profile` map builds
the public detail view. Frontend editing fields, document sections and their
validators are configured separately by :guilabel:`academic_persons_edit` in
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml`.

The split is intentional: a site package can replace the public presentation
without changing which values may be edited, and it can adapt editing rules
without changing the detail layout.

Profile layout
==============

The default :file:`Resources/Private/Templates/Profile/Detail.html` receives
the normalized map as ``publicProfile``. The YAML key remains simply
:yaml:`profile`:

..  code-block:: yaml

    profile:
      structure:
        left:
          - menuSections
        right:
          - headline
          - position
          - profileImage
          - contact
          - subline
          - profileEntries
          - menuSectionsDatas
      details:
        headline:
          - title
          - firstName
          - middleName
          - lastName

``structure``
    Defines the layout columns and the ordered elements in each column. The
    shipped template consumes ``left`` and ``right``. On mobile, the left
    elements are inserted again immediately before ``subline``.
``details``
    Supplies the Profile properties, relation mappings, LLL labels or special
    renderers used by every element.

Supported elements
==================

..  list-table::
    :header-rows: 1

    *   - Identifier
        - Configuration
        - Output
    *   - :yaml:`menuSections`
        - Ordered stable navigation identifiers.
        - Links for configured relations which contain data.
    *   - :yaml:`headline`
        - Ordered direct ``Profile`` properties.
        - Non-empty parts of the public heading.
    *   - :yaml:`position`
        - Special renderer map.
        - Contract positions for :yaml:`special: datasFromContracts`.
    *   - :yaml:`profileImage`
        - Ordered image properties.
        - Configured non-empty profile images.
    *   - :yaml:`contact`
        - Special renderer map.
        - Contract contact data for :yaml:`special: datasFromContracts`.
    *   - :yaml:`subline`
        - LLL translation key.
        - Translated heading and mobile-navigation insertion point.
    *   - :yaml:`profileEntries`
        - Ordered rich-text properties.
        - Non-empty accordion entries.
    *   - :yaml:`menuSectionsDatas`
        - Navigation identifier to Profile relation map.
        - Timeline sections and their related records.

The shipped detail mapping
==========================

..  code-block:: yaml

    profile:
      details:
        menuSections:
          - researchProjects
          - academicCareer
          - membershipsCommitteeActivities
          - networkCooperation
          - publications
          - lectures
        position:
          special: datasFromContracts
        profileImage:
          - image
        contact:
          special: datasFromContracts
        subline: "LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:detail.subline"
        profileEntries:
          - teachingArea
          - coreCompetences
          - supervisedThesis
          - supervisedDoctoralThesis
          - miscellaneous
        menuSectionsDatas:
          researchProjects: scientificResearch
          academicCareer: vita
          membershipsCommitteeActivities: memberships
          networkCooperation: cooperation
          publications: publications
          lectures: lectures

``menuSections`` and ``menuSectionsDatas`` share stable navigation identifiers.
Timeline records use ``year``, ``yearStart``, ``yearEnd``, ``yearOnly``,
``title``, ``link`` and ``bodytext``. ``yearOnly`` affects only formatting and
does not discard month or day from the stored native ``DATE`` value.

Normalization and overrides
===========================

List values are trimmed, deduplicated and kept in order. Invalid or empty
values are ignored. Unknown structure elements render nothing. Empty Profile
properties and relations are skipped.

Settings from active packages are merged at the top level. Because
:yaml:`profile` is the only top-level map, an override replaces its complete
``structure`` and ``details`` maps and must repeat every desired entry. Flush
TYPO3 caches after a change so the typed settings object is rebuilt.

The similarly named :yaml:`profile` map in
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml` is a separate
configuration namespace and is never merged with this public layout.
