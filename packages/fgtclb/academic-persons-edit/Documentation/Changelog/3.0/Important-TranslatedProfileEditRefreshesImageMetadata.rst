.. _important-translated-profile-edit-refreshes-image-metadata:

=================================================================
Important: A translated profile edit refreshes its image metadata
=================================================================

Description
===========

Two changes to the image metadata a profile edit writes, and they are unrelated
in cause:

**An edit made in a translated language refreshes its own image metadata.** The
frontend editing skips :php:`\FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent`
for a profile fetched as translation overlay, because the slug generation and
the translation synchronisation both run from the default-language record. That
skip took the image metadata with it: after a name change made in a translated
language, the :sql:`sys_file_reference` row of that very record kept the old
name, and nothing else rewrote it — the Extbase persistence of the frontend
editing never reaches the :php:`DataHandler` hook that covers a backend save.
The row is now refreshed directly, for the localized record the edit belongs to.
The event is still not dispatched, so slug generation and translation
synchronisation remain default-language business.

A frontend request acting in a workspace, and a record whose localized row
cannot be resolved, are skipped without a message: the profile data is written
by then, and a metadata refresh must not turn a successful save into an error.

**The metadata record of the file follows the profile name too.** It was filled
once by the upload and never again; :composer:`fgtclb/academic-persons` now
rewrites its :sql:`title` and :sql:`alternative` with the composed name on every
save of the profile — changelog entry *Important: The file's own metadata
follows the profile name* of that extension, which also states what that means
for a file shared between the languages of a profile and for a value a backend
editor typed there.

Impact
======

A person who edits a profile in a translated language and changes the name sees
the ``alt`` and ``title`` text of the image follow, as it already did for an
edit of the default language. Nothing has to be configured for it, and it is
independent of ``profile.allowedLanguages``: the refresh is not a
synchronisation, it writes the record that was edited.

The composed name is unchanged — the ordered non-empty values of :sql:`title`,
:sql:`first_name`, :sql:`middle_name` and :sql:`last_name`, joined with single
spaces — and so is where it is written for an edit of the default language.

Affected Installations
======================

Installations using the frontend profile editing with translated profiles, and
installations that maintain the metadata of profile image files editorially.

.. index:: FAL, Frontend, Localization, ext:academic_persons_edit
