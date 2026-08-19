..  _feature-inline-profile-editing:

=========================================
Feature: JSON based inline profile editor
=========================================

Description
===========

The :guilabel:`Inline profile editing` content element now ships a responsive
Fluid form and an ES module that persists profile changes without reloading the
page. Only properties changed in the browser are included in the JSON request.

The endpoint validates the request method and JSON structure, requires an
authenticated frontend user, verifies that the requested profile is assigned
to that user and applies the configured profile validators. Expected failures
are returned as JSON with an appropriate HTTP status code. Field-specific
validation errors are rendered next to the corresponding control.

Gender values are restricted to the options configured for the profile gender
field in TCA. The empty string remains valid so an existing selection can be
cleared.

Impact
======

Installations using the shipped template receive a usable inline profile form.
Projects overriding the inline template should compare their markup with the
new template and preserve the JavaScript hooks described in
:ref:`inline-profile-editing`.

..  index:: Fluid, Frontend, JSON
