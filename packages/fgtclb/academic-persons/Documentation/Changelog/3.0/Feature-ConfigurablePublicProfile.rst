..  _feature-configurable-public-profile:

====================================
Feature: Configurable public profile
====================================

Description
===========

The public profile detail view is now assembled from the :yaml:`profile`
map in :file:`Configuration/AcademicPersons/Settings.yaml`. Its ordered
:yaml:`structure` lists select the supported elements for the left and right
layout columns. The :yaml:`details` map supplies the Profile properties,
relation mappings, translation key and special Contract renderers consumed by
those elements.

``AcademicPersonsSettingsFactory`` normalizes the configuration into the typed
``PublicProfileSettings`` object. ``ProfileController::detailAction()`` exposes
that object to Fluid, and the default detail template dispatches each configured
identifier to a dedicated :file:`Profile/PublicProfile/*` partial. Empty
referenced fields and relations are omitted.

The supported elements are ``menuSections``, ``headline``, ``position``,
``profileImage``, ``contact``, ``subline``, ``profileEntries`` and
``menuSectionsDatas``. ``datasFromContracts`` is the supported special renderer
for the position and contact elements. Navigation identifiers are mapped to
Profile relation properties through :yaml:`menuSectionsDatas`, keeping the
navigation and rendered timeline sections aligned.

Impact
======

Site packages can change the public detail layout and select its displayed
Profile fields without replacing the complete Fluid detail template. Settings
are merged at the top level, so an override of :yaml:`profile` must repeat
the complete desired :yaml:`structure` and :yaml:`details` maps. Flush TYPO3
caches after changing the configuration.

..  index:: Configuration, Frontend, Fluid, NotScanned, ext:academic_persons
