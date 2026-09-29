..  _important-1790709369:

======================================================================
Important: Contacts follow the image settings of the persons extension
======================================================================

Description
===========

`EXT:academic_persons` chooses the crop variant of a profile image per view,
and a placeholder per gender, through site settings. The contacts of a page
are rendered through its :file:`Profile/Item` partial, and the setup of this
extension maps those settings into its own plugin settings, as it already
mapped the default placeholder:

*   `plugin.tx_academicpersons.image.list.cropVariant`, the crop variant of
    the lists, which the contacts of a page use too.
*   `plugin.tx_academicpersons.image.placeholder.mr`,
    `plugin.tx_academicpersons.image.placeholder.ms` and
    `plugin.tx_academicpersons.image.placeholder.diverse`, the placeholder of
    a contact of that gender without an image.

A contact therefore shows the same crop and the same placeholder as the
profile does in a list.

Impact
======

Nothing changes with the defaults: the crop variant is `default`, and the
gender placeholders are empty, which falls back to the default placeholder.
A site that sets them for the persons lists gets them for the contacts of its
pages as well. The settings are described in the :guilabel:`Profile image`
chapter of `EXT:academic_persons`.

..  index:: Frontend, TypoScript, ext:academic_contacts4pages
