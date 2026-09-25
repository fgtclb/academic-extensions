..  _important-contacts4pages-plugin-assigns-record-view-variable:

======================================================
Important: The plugin assigns a `record` view variable
======================================================

Description
===========

TYPO3 v14 rewrote the header partial of `EXT:fluid_styled_content`. Where v13
`Header/All.html` reads `{data.header}`, the v14 `Header/All.fluid.html` renders
header and subheader with `{record -> f:render.text(...)}`, and that ViewHelper
requires a record object.

The shipped :file:`Contacts/List.html` does not render that partial: the
header of the content element comes from the layout of `lib.contentElement`
around it. A site package that moves the header into its own list template
instead, and leaves it out of the layout of this content element, renders the
partial there, and on TYPO3 v14 such a template aborted with

..  code-block:: text

    The record argument must be an instance of ... Given: null

The :guilabel:`Contacts for this page` content element now assigns an
additional `record` view variable, built from the `tt_content` row of the
current content element, as the plugins of `EXT:academic_jobs` and
`EXT:academic_bite_jobs` do. TYPO3 v13 ignores it, its header partial keeps
reading `data`.

Impact
======

Nothing was removed or renamed. A template override may render the core header
partial, for example with `<f:render partial="Header/All" arguments="{_all}" />`,
or use `{record}` directly. Rendering the partial takes three things:

*   `EXT:fluid_styled_content/Resources/Private/Partials/` in the partial root
    paths of the plugin,
*   the default header type in the plugin settings, which the partial falls
    back to for the header layout :guilabel:`Default` and which plugin settings
    do not carry:
    :typoscript:`plugin.tx_academiccontacts4pages.settings.defaultHeaderType = {$styles.content.defaultHeaderType}`,
*   a layout for this content element that does not render the header as well —
    with the layout of `EXT:fluid_styled_content` the header appears twice. A
    copy of its :file:`Default` layout without the two `Header` blocks keeps
    the frame, the anchor and the spacing classes:

    ..  code-block:: typoscript
        :caption: TypoScript setup

        tt_content.academiccontacts4pages_list.layoutRootPaths.50 = EXT:my_sitepackage/Resources/Private/Extensions/academic_contacts4pages/Layouts/

Affected Installations
======================

Installations running the content element on TYPO3 v14 with a list template of
their own. TYPO3 v13 installations are unaffected.

Migration
=========

None required.

.. index:: Fluid, Frontend, NotScanned, ext:academic_contacts4pages
