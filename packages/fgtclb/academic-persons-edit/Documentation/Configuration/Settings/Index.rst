..  index:: Configuration; Editor settings, Configuration; Validation
..  _configuration-editor-settings:

===============================
Profile editor and validation
===============================

:file:`Configuration/AcademicsPersonsEdit/Settings.yaml` belongs exclusively
to :guilabel:`academic_persons_edit`. It contains the ordered editing schema:

``profile``
    Direct Profile fields grouped into visual sections.
``special``
    Composed or dedicated components such as the title, image and sync switch.
``contractContact``
    Address, email and phone fields owned by Contract records.
``documentSections``
    Structured collections, row presentation, actions and section-local
    validators.

The public detail layout is a different settings graph in
:file:`academic-persons/Configuration/AcademicPersons/Settings.yaml`. Its
top-level key is also named :yaml:`profile`, but the factories, cache entries
and merge namespaces are separate. Neither plugin reads the other plugin's
Settings file.

Loading and overrides
=====================

The edit factory reads the same relative path from every active package and
merges maps at the top level. A site package can therefore override the edit
configuration by providing
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml`. Replacing one of the
four maps replaces that complete map; repeat every entry which must remain.

Flush TYPO3 caches after a change. The edit graph uses its own cache identifier
and cannot reuse a public-profile settings cache entry.

Field validators
================

Regular fields declare an ordered list of flags:

..  code-block:: yaml

    profile:
      website:
        section: information
        fieldType: input
        renderType: combinedLink
        validators:
          - url

Supported flags are:

..  list-table::
    :header-rows: 1

    *   - Flag
        - Effect
    *   - ``required``
        - Adds ``NotEmptyValidator`` plus the frontend HTML, marker and JSON
          metadata.
    *   - ``readonly``
        - Prevents editing in the frontend editor.
    *   - ``disabled``
        - Disables editing and implies ``readonly``. A locked field can never
          remain required.
    *   - ``email``
        - Adds ``EmailAddressValidator`` and email input metadata.
    *   - ``url``
        - Adds ``UrlValidator`` and URL input metadata.
    *   - ``number``, ``tel`` and ``date``
        - Select the matching frontend input type.
    *   - ``textarea`` and ``html``
        - Select text-area input; ``html`` also activates sanitized rich text.

Rules remain attached to the configured profile, contact or document section.
They are not collected from a sibling section. Only submitted fields are
validated during a partial AJAX update.

Profile CKEditor character limit
================================

A profile field configured as CKEditor may declare its character limit directly
on the field:

..  code-block:: yaml

    profile:
      miscellaneous:
        section: aboutme
        fieldType: textarea
        renderType: ckeditor
        characterLimit: 500
        validators:
          - html

A positive integer ``characterLimit`` adds a live ``current / limit`` counter
and prevents the editor from accepting additional visible characters beyond
the limit. The partial AJAX update is checked independently by the same
server-side Extbase validation. HTML tags do not count. The property is ignored
when ``renderType`` is not ``ckeditor`` or its value is invalid or non-positive.

This deliberately differs from document descriptions, which keep their
existing nested ``editor.limit`` schema. Both forms normalize to the same typed
validation metadata and neither one changes backend TCA.

Document validators and aliases
===============================

Document aliases map presentation names to DTO and database properties:

..  list-table::
    :header-rows: 1

    *   - Settings key
        - DTO property
        - Domain/database field
    *   - ``from``
        - ``yearStart``
        - ``year_start``
    *   - ``to``
        - ``yearEnd``
        - ``year_end``
    *   - ``year``
        - ``year``
        - ``year``
    *   - ``description``
        - ``bodytext``
        - ``bodytext``

The shipped date rules are deliberately dynamic:

..  code-block:: yaml

    documentSections:
      cooperation:
        validators:
          from:
            - date
          to:
            - date
          year:
            - required
            - date

Consequently ``year`` receives a required attribute and marker while ``from``
and ``to`` remain optional. Removing or adding ``required`` in a site override
changes JSON metadata, modal markup and Extbase validation together; no field
name is hard-coded as mandatory. Backend TCA is not read from this file and
therefore never changes with these flags.

The richer description form remains supported:

..  code-block:: yaml

    description:
      editor:
        limit: 100
        type: ckeditor

``editor.type: ckeditor`` is normalized to the ``html`` validation/input
metadata so the JSON modal and server-side sanitizer agree. A positive integer
``editor.limit`` additionally defines the maximum number of visible
characters. HTML tags do not consume that allowance; entities, non-breaking
spaces and repeated whitespace are normalized before counting. The modal
returns the limit in its JSON field metadata, displays a live ``current / limit``
counter and prevents CKEditor from accepting further characters beyond it.
Both the document JSON actions and the Extbase form-data validator enforce the
same limit on the server.

``editor.limit`` is frontend validation metadata, not TCA configuration. It is
ignored for non-CKEditor controls, and removing it or setting it to a non-
positive value disables both the counter and the additional validation without
changing backend FormEngine.

Backend TCA isolation
=====================

The domain extension owns the backend TCA independently. This settings file is
used only by the frontend editor and never changes ``$GLOBALS['TCA']``. Adding
or removing ``required``, ``readonly`` or another edit flag therefore has no
effect on FormEngine in TYPO3 13 or TYPO3 14.

The stable domain TCA still describes the three date-only columns with native
date configuration. That storage definition is independent of the edit
settings and is not overridden by this extension.

Modal date row
==============

``year``, ``yearStart``, ``yearEnd`` and ``yearOnly`` each receive
``col-12 col-md-3``. They therefore stack on small screens and share one row
from the medium breakpoint. The checkbox uses a compact nested Bootstrap
``form-check mt-auto`` inside a ``d-flex`` column so its baseline aligns with
the date controls. Its validation feedback remains inside that form-check.
