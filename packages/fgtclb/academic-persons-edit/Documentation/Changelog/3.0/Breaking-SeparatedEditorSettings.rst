..  _breaking-separated-editor-settings:

===================================
Breaking: Separated editor settings
===================================

Description
===========

The frontend editing schema has moved from
:file:`Configuration/AcademicPersons/Settings.yaml` to the extension-owned
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml`. The ``profile``,
``special``, ``contracts`` and ``documentSections`` maps are loaded and
cached by a dedicated edit factory. They are no longer merged with the public
profile layout from :guilabel:`academic_persons`.

The normalized edit service is injected into all controllers, providers,
sanitizers and validators. It supplies frontend HTML, JSON and server-side
Extbase validation only. It never modifies the stable domain TCA from
:guilabel:`academic_persons`.

Impact
======

Site packages overriding editable fields or document sections must move those
maps to :file:`Configuration/AcademicsPersonsEdit/Settings.yaml`. A public
detail-layout override remains in
:file:`Configuration/AcademicPersons/Settings.yaml` below ``profile``.

Because merging remains top-level, every overridden map must contain all
entries which should remain. Flush all TYPO3 caches after moving the files.

The modal's three dates and compact year-only checkbox now each use
``col-12 col-md-3``. Required attributes and markers continue to follow the
section-local validator flags dynamically.

..  index:: Configuration, Frontend, Validation
