..  _feature-settings-driven-profile-editor:

=======================================
Feature: Settings-driven Profile editor
=======================================

Description
===========

The ProfileEditing view now generates its field order, validation metadata and
control selection from the typed :yaml:`profile` settings. ``Index.html`` only
places the configured visual sections. ``Profile/Fields.html`` dispatches to
the input, textarea, CKEditor, select, checkbox or combined-link control
according to ``renderType``. Select options remain authoritative in Profile
TCA.

The :yaml:`special` map supplies the composed profile title, image component
and synchronization switch. The title's configured ``fields`` list is used by
Fluid for the initial heading and by JavaScript after an inline update.

The frontend entry delegates to modules for fields, rich text, synchronization,
image editing, sticky positioning and shared requests/status output. Direct
public Profile email/telephone values and their opt-in flags use the same
generic field endpoint. Contract contact validation stays isolated in
:yaml:`contracts.contactSections.<section>.fields`. The Contract editor itself
uses the ordered :yaml:`contracts.fields` map.

Impact
======

Template overrides should place ``profileSections`` and ``specialFields`` but
must not duplicate the field list. Custom render types need a matching control
below ``Partials/Profile/Field`` and, only when behavior differs from an
existing HTML control, a focused JavaScript module extension.

The implementation boundary is ``ProfileController`` together with the
``Profile`` template and partial tree.

..  index:: AJAX, Configuration, Fluid, Frontend, Inline editing, JavaScript
