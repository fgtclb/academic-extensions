..  _important-contacts4pages-plugin-assigns-record-view-variable:

======================================================
Important: The plugin assigns a `record` view variable
======================================================

Description
===========

TYPO3 v14 rewrote the header partial of `EXT:fluid_styled_content`. Where v13
`Header/All.html` reads `{data.header}`, the v14 `Header/All.fluid.html` renders
header and subheader with `{record -> f:render.text(...)}`, and that ViewHelper
requires a record object. Without one the partial aborts with

..  code-block:: text

    The record argument must be an instance of ... Given: null

The :guilabel:`Person’s Contacts on the Page` content element assigns a `record`
view variable, built from the `tt_content` row of the current content element,
as the plugins of `EXT:academic_jobs` and `EXT:academic_bite_jobs` do. The
shipped :file:`Contacts/List.html` renders the partial when a site switches the
header on, see :ref:`feature-1790440965`, and hands the variable on to it. TYPO3
v13 ignores it, its header partial keeps reading `data`.

Impact
======

Nothing was removed or renamed. A list template of a site package that renders
the core header partial, for example with
`<f:render partial="Header/All" arguments="{_all}" />`, or uses `{record}`
directly, works on TYPO3 v14 as well. The partial root path of
`EXT:fluid_styled_content` and the default header type the partial needs are
part of the shipped setup, see :ref:`configuration-content-element-header`.

Affected Installations
======================

Installations running the content element on TYPO3 v14 with a list template of
their own that renders the header. TYPO3 v13 installations are unaffected.

Migration
=========

None required. A project that copied the list template only to render the
header can switch the header on instead and drop the copy, see
:ref:`feature-1790440965`.

.. index:: Fluid, Frontend, NotScanned, ext:academic_contacts4pages
