..  _breaking-adapted-frontend-editing-fluid-files:

================================================
Breaking: Replaced frontend editing Fluid files
================================================

Description
===========

Version 3.0 replaces the separate Fluid views of the former profile, contract,
profile-information and contact controllers with one ProfileEditing view.
``ProfileController`` renders
:file:`Resources/Private/Templates/Profile/List.html` and
:file:`Resources/Private/Templates/Profile/Index.html`; the corresponding
partials live below :file:`Resources/Private/Partials/Profile/`.

The former edit, show, image and record-specific templates and partials are no
longer rendered or shipped. The new view persists profile fields, profile
images, contracts, contract contacts and profile-information records through
its JSON endpoints.

Impact
======

Installations using the shipped templates receive the new profile editor
automatically. Project-specific overrides of the removed 2.x Fluid files are
not applied to the new view.

Existing content records are unaffected: the ProfileEditing CType, Extbase
plugin name and request namespace remain stable.

Migration
=========

Port project-specific markup to the ``Profile`` template and partial tree and
retain the documented JavaScript data hooks from :ref:`profile-editing`.

..  index:: Fluid, Frontend, Template
