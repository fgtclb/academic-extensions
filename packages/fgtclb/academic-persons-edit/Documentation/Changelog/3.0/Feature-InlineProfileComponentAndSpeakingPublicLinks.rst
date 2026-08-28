..  _feature-inline-profile-component-and-speaking-public-links:

============================================================
Feature: InlineProfile component and speaking public links
============================================================

Description
===========

InlineProfile now owns its TypoScript, AJAX page type, site set and page
TSconfig in dedicated ``Configuration/*/InlineProfile`` paths. Functional tests
load that component explicitly, while retained ProfileEditing reference tests
load only their legacy component. This prevents missing AJAX ``typeNum`` and
Fluid partial configuration after rebases or component restructuring.

The assigned-profile :guilabel:`View` action targets the public
``academicpersons_detail`` content element. Configure its page through
``plugin.tx_academicpersons.detailPid``. Importing
:file:`EXT:academic_persons/Configuration/Routes/Detail.yaml` can optionally
turn the resulting Detail-plugin URI into a speaking link.

CropperJS continues to enforce the configured aspect ratio, but the selection
remains movable so editors can choose the visible image area. A Jest test pins
that behavior.

Impact
======

Projects using the aggregate site set receive the new component automatically.
Projects selecting individual components must add
``fgtclb/academic-persons-edit-inline-profile``. Classic TypoScript users can
select :guilabel:`Academic Persons Edit: Inline profile editing`.

..  index:: Frontend editing, Routing, Site set, TypoScript
