..  _feature-profile-editing-component-and-speaking-public-links:

============================================================
Feature: ProfileEditing component and speaking public links
============================================================

Description
===========

ProfileEditing owns its TypoScript, AJAX page type, site set and page TSconfig
in the stable ``Configuration/*/ProfileEditing`` paths. The aggregate site set
``fgtclb/academic-persons-edit`` depends on the component site set
``fgtclb/academic-persons-edit-profile-editing``.

The assigned-profile :guilabel:`View` action targets the public
``academicpersons_detail`` content element. Configure its page through
``plugin.tx_academicpersons.detailPid``. Importing
:file:`EXT:academic_persons/Configuration/Routes/Detail.yaml` can optionally
turn the resulting Detail-plugin URI into a speaking link.

CropperJS enforces the configured aspect ratio while keeping the selection
movable so editors can choose the visible image area.

Impact
======

Projects using the aggregate site set receive the component automatically.
Projects selecting individual components must add
``fgtclb/academic-persons-edit-profile-editing``. Classic TypoScript users can
select :guilabel:`Academic Persons Edit: Profile editing`.

..  index:: Frontend editing, Routing, Site set, TypoScript
