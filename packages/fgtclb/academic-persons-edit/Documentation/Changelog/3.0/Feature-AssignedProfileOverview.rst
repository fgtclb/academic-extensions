..  _feature-assigned-profile-overview:

==================================
Feature: Assigned profile overview
==================================

Description
===========

The :guilabel:`Inline profile editing` content element now opens with a list of
all profiles assigned to the authenticated frontend user. Each row contains a
profile-image thumbnail or placeholder, the complete name, the configured site
language title and two actions.

:guilabel:`Edit` opens the selected profile in the existing inline editor. The
profile UID is resolved through the frontend-user assignment before the editor
is rendered, so changing the request cannot expose another profile.
:guilabel:`View` opens the public ``academic_persons`` Detail plugin on the page
configured through ``plugin.tx_academicpersons.detailPid``.

The editor heading has also been reorganized. The complete name, the
:guilabel:`Private` synchronization switch and the :guilabel:`Edit all` toggle
share a full-width responsive row above the content grid. The left column is
headed :guilabel:`Profile image`; the right column remains
:guilabel:`Personal data`.

Impact
======

Existing InlineProfile content elements automatically use the overview as
their default action. Projects overriding InlineProfile templates should add a
``List.html`` override and adapt links to pass ``profileUid`` to ``index``.
The target page must contain the ``academicpersons_detail`` content element and
``plugin.tx_academicpersons.detailPid`` must point to that page.

..  index:: Detail view, Frontend editing, Language, Profile list
