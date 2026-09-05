..  _breaking-separated-public-profile-settings:

===========================================
Breaking: Separated public profile settings
===========================================

Description
===========

:file:`Configuration/AcademicPersons/Settings.yaml` now contains only the
public detail layout below the top-level ``profile`` key. The former
``publicProfile`` key is no longer read. Editing maps and their validators are
owned by :guilabel:`academic_persons_edit` in
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml`.

The public and editing configurations use separate factories and cache
identifiers. The domain TCA remains owned entirely by this extension and is
independent of all frontend validator flags in the edit extension.

Impact
======

Move an existing public ``publicProfile`` map to ``profile``. Move existing
``profile`` field definitions, ``special``, ``contracts`` and
``documentSections`` to the edit-extension path. Flush TYPO3 caches and run the
database schema analyzer after updating both packages.

..  index:: Configuration, Frontend, Validation
