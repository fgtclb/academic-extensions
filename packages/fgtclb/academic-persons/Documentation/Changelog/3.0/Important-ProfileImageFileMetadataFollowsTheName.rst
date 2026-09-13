.. _important-profile-image-file-metadata-follows-the-name:

===========================================================
Important: The file's own metadata follows the profile name
===========================================================

Description
===========

:php:`\FGTCLB\AcademicPersons\Service\ProfileImageMetadataService` wrote the
composed name of a profile record to the profile's own :sql:`sys_file_reference`
row only. The :sql:`sys_file_metadata` record of the file behind it was filled
once, by the frontend upload that created the file, and no later change of the
name reached it.

That record is now written on **every** save of a profile that has an image — a
backend save, a localization and a frontend edit alike — with the composed name
in :sql:`title` and :sql:`alternative`, and it replaces what those two columns
carry. :sql:`copyright` is not touched: the frontend upload remains its only
writer, and still fills it only where it found it empty. A record that does not
exist yet is created.

Both writes stay announced, and they are announced separately:
:php:`\FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent` is
dispatched for the file's metadata record first and for the relation row after
it, and the two are independent — a listener that empties the field map of one
has decided nothing about the other.

A profile without a name leaves the metadata record untouched rather than
blanking it, and a metadata write that fails is logged instead of turning an
otherwise successful save of the profile into an error.

Impact
======

The name of the person reaches the file itself and stays current there, so it is
rendered as ``alt`` and ``title`` text wherever the file is used — a content
element, a file collection, an RTE link — and not only where the profile's own
reference is rendered. Two consequences of that are deliberate:

*   **The record carries the name of whichever language was saved last.** A file
    is shared between the languages of a profile, so there is no
    language-correct value it could hold. The language-correct text sits on the
    :sql:`sys_file_reference` row of each language, and that is what the
    frontend renders; the file's own record is the fallback for everything that
    renders the file without that reference.

*   **A file used by more than one record is described by the profile saved
    last.** Nothing ties a file to a single profile: one placeholder portrait or
    a group photo may be the image of several profiles and be used in a content
    element besides. Saving any one of those profiles rewrites the file's own
    :sql:`title` and :sql:`alternative` to that profile's name, and every usage
    that renders the file without a reference override then shows that name.
    Installations that share image files between profiles should either give
    each profile its own file or keep the record with a listener, as below.

*   **A value a backend editor typed into the record is replaced** on the next
    save of the profile. The record is no longer the editor's from the upload
    on, which is how the changelog entry *Important: An uploaded profile image
    carries its metadata* of :composer:`fgtclb/academic-persons-edit` described
    it. An installation that maintains :sql:`title` or :sql:`alternative` of
    profile images editorially keeps them with a listener on the
    :sql:`sys_file_metadata` dispatch of
    :php:`ModifyProfileImageMetadataEvent`: drop the field from the map to keep
    the record as it is, or set the value the installation wants.

Affected Installations
======================

Installations whose profiles carry an image, in particular those that maintain
file metadata editorially or read :sql:`sys_file_metadata` of profile images
elsewhere.

.. index:: Database, FAL, ext:academic_persons
