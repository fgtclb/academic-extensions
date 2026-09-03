.. _important-multilingual-inline-profile-images:

=====================================================
Important: Multilingual inline profile image handling
=====================================================

Description
===========

The inline profile editor maintains a separate image for every profile
translation. A translated profile initially keeps its image synchronized with
the default-language profile through TYPO3's ``l10n_state``. The first upload
or deletion in that language changes the image state to ``custom``. From that
point on, replacement and deletion affect only that translation. Translated
profiles retain the image editing controls so editors can select a
language-specific image.

All relation changes are written through TYPO3 DataHandler. Core initially
localizes the default image relation together with a new profile translation,
and propagates later default-image changes while the translated field remains
in the ``parent`` state. The first upload in that language replaces this
localized relation with an independent one, sets the state to ``custom`` and
creates a separate FAL file. Replacing a clean, independent relation keeps its
``sys_file_reference`` uid and assigns the new file to it.
Duplicate or localized legacy references are reduced to one independent
reference. An old physical file is deleted through its resource storage only
after no active reference uses it anymore. Image metadata is derived from the
name and FAL relation of the current language profile.

The upgrade wizard
``academicPersonsEdit_repairLocalizedProfileImages`` detects shared files
inside a profile's translation family, localized or duplicate references,
dangling relations and inconsistent image counters. It creates a separate file
for an affected translation where necessary and rebuilds the relation through
DataHandler.

Impact
======

Editors can use different profile images per language. Changing or deleting an
image in one language leaves all other profile translations untouched.

.. index:: Frontend, Backend, FAL, Localization, ext:academic_persons_edit
