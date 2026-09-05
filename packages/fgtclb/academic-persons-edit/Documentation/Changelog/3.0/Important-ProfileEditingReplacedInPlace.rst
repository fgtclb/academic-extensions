..  _important-profile-editing-replaced-in-place:

====================================================
Important: Profile editing is replaced in place
====================================================

Description
===========

The ProfileEditing content element now uses the JSON-based profile editor and
``ProfileController`` exclusively. The former dedicated profile, contract,
profile-information and contact controllers, their Fluid trees and their
supporting services are removed.

The customer-facing integration identity stays unchanged:

*   CType ``academicpersonsedit_profileediting``,
*   Extbase plugin name ``ProfileEditing``,
*   request namespace ``tx_academicpersonsedit_profileediting``,
*   component site set
    ``fgtclb/academic-persons-edit-profile-editing``, and
*   configuration paths below ``Configuration/*/ProfileEditing``.

The temporary, unreleased ``academicpersonsedit_inlineprofile`` CType and its
separate configuration component are not shipped.

Impact
======

Existing ProfileEditing content records use the new editor after the extension
is updated. Editors do not have to replace content elements, and integrators do
not have to change the plugin namespace or site-set dependency.

Projects overriding the former Fluid files must port those overrides to the
``Profile`` template and partial tree described in
:ref:`breaking-adapted-frontend-editing-fluid-files`.

..  index:: Backend, CType, Extbase, Frontend editing, Migration
