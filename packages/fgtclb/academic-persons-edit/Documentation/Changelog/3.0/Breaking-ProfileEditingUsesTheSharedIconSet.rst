..  _breaking-profile-editing-uses-the-shared-icon-set:

==================================================
Breaking: Profile editing uses the shared icon set
==================================================

..  seealso::
    The `upgrade chapter of academic_persons
    <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Upgrade/Index.html>`__
    is the order in which the 3.0 changes have to be applied.

Description
===========

The academic extensions draw their icons from one set: Font Awesome Free
solid, drawn in ``currentColor`` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`.
An icon that means the same in several extensions - an action, a state - is
registered once, by `EXT:academic_base`, under an identifier of the scheme
``tx-<extension key without underscores>-<group>-<name>`` (ACE-586).

The profile editing frontend no longer registers action icons of its own. Its
templates render the shared ``tx-academicbase-action-*`` and
``tx-academicbase-state-*`` identifiers, and the sixteen
``academic-persons-edit-*`` identifiers are removed together with their files
and :file:`Resources/Public/Icons/LICENSE-bootstrap-icons.txt`. The content
element icon is replaced as well. The identifiers are renamed without an
alias:

..  list-table::
    :header-rows: 1

    *   -   Removed identifier
        -   Replacement
        -   Shipped in
    *   -   ``persons_edit_icon``
        -   ``tx-academicpersonsedit-plugin-profile-editing``
        -   2.x
    *   -   ``academic-persons-edit-back``
        -   ``tx-academicbase-action-back``
        -   2.x
    *   -   ``academic-persons-edit-delete``
        -   ``tx-academicbase-action-delete``
        -   2.x
    *   -   ``academic-persons-edit-edit``
        -   ``tx-academicbase-action-edit``
        -   2.x
    *   -   ``academic-persons-edit-save``
        -   ``tx-academicbase-action-save``
        -   2.x
    *   -   ``academic-persons-edit-view``
        -   ``tx-academicbase-action-view``
        -   2.x
    *   -   ``academic-persons-edit-add``
        -   ``tx-academicbase-action-add``
        -   3.0 development
    *   -   ``academic-persons-edit-clear``
        -   ``tx-academicbase-action-clear``
        -   3.0 development
    *   -   ``academic-persons-edit-help``
        -   ``tx-academicbase-action-help``
        -   3.0 development
    *   -   ``academic-persons-edit-move-down``
        -   ``tx-academicbase-action-move-down``
        -   3.0 development
    *   -   ``academic-persons-edit-move-up``
        -   ``tx-academicbase-action-move-up``
        -   3.0 development
    *   -   ``academic-persons-edit-sort-handle``
        -   ``tx-academicbase-action-drag``
        -   3.0 development
    *   -   ``academic-persons-edit-undo``
        -   ``tx-academicbase-action-undo``
        -   3.0 development
    *   -   ``academic-persons-edit-upload-image``
        -   ``tx-academicbase-action-upload-image``
        -   3.0 development
    *   -   ``academic-persons-edit-view-close``
        -   ``tx-academicbase-action-view-close``
        -   3.0 development
    *   -   ``academic-persons-edit-visible``
        -   ``tx-academicbase-state-visible``
        -   3.0 development
    *   -   ``academic-persons-edit-hidden``
        -   ``tx-academicbase-state-hidden``
        -   3.0 development

The drawings change with the identifiers. Two of them change in kind rather
than in style: the drag handle is two horizontal lines instead of a grid of
dots, and the help icon is a question mark in a circle instead of an ``i``.

The content element icon is drawn in ``currentColor`` now too, so it follows
the backend colour scheme in the page module and the new content element
wizard instead of keeping a fixed red.

Impact
======

A template override that renders one of the removed identifiers renders the
``default-not-found`` placeholder instead of the icon.

The rendered markup names the identifier twice - as ``data-identifier`` and in
the ``icon-<identifier>`` class core puts on the wrapper - so CSS or JavaScript
selecting ``.icon-academic-persons-edit-save`` or
``[data-identifier="academic-persons-edit-save"]`` no longer matches.

A :file:`Configuration/Icons.php` of a site package that re-registered one of
the removed identifiers to replace its artwork has no effect any more.

Page TSconfig or TCA of a project that names ``persons_edit_icon``, for example
in a new content element wizard entry of its own, shows the placeholder.

Affected Installations
======================

Installations using the :guilabel:`Profile editing` content element that
override its templates, style or script against its icons, or replace its
icons in a site package. Installations that use the shipped templates as they
are need no change.

Migration
=========

Replace every removed identifier by its replacement from the table above - in
template overrides, CSS and JavaScript selectors, TCA and page TSconfig.

To keep own artwork, register the *replacement* identifier in the
:file:`Configuration/Icons.php` of the site package, as described in
:ref:`templates-override-icons`. A shared ``tx-academicbase-*`` identifier
is used by every academic extension, so a replacement registered for it
changes the icon wherever that action or state appears, not only in the
profile editor.

..  index:: Backend, Fluid, Frontend, TCA, TSConfig, NotScanned, ext:academic_persons_edit
