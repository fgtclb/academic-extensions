.. _important-ace-853-academic-contacts4pages:

=======================================================
Important: Contact names leave out an empty middle name
=======================================================

Description
===========

The contacts plugin renders every contact with the partial
:file:`Partials/Profile/Item.html` of `EXT:academic_persons`. Its heading
composed the name of the profile from first name, middle name and last name
with a space between every two parts, so a profile without a middle name was
rendered with two spaces between first and last name.

`EXT:academic_persons` now leaves the middle name out together with its space
when a profile has none.

Impact
======

The page looks the same, browsers collapse the spaces. The heading text in the
markup holds the name with single spaces. Code or tests that match the rendered
markup may have to be adjusted.

Affected Installations
======================

Every installation that renders contacts of profiles without a middle name.
Nothing has to be done on update.

A site that overrides :file:`Partials/Profile/Item.html` keeps the old
composition. To take over the change, set the :html:`header` argument of both
headings in the copy to:

..  code-block:: html

    header: '{profile.firstName}{f:if(condition: profile.middleName, then: \' {profile.middleName}\')} {profile.lastName}',

.. index:: Frontend, Fluid, ext:academic_contacts4pages
