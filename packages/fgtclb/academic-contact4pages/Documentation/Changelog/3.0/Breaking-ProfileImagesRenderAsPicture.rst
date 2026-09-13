..  _breaking-contacts4pages-profile-images-render-as-picture:

=======================================================
Breaking: Profile images render as a responsive picture
=======================================================

Description
===========

The contacts content element renders every contact through the profile card
of `EXT:academic_persons`, :file:`Partials/Profile/Item.html`, which renders
the profile image through the responsive image partial of `EXT:academic_base`
from 3.0 on. The image of a contact is therefore a :html:`<picture>` with WebP
sources and a fallback :html:`<img>` with the classes `card-img-top img-fluid`,
and a contact whose profile has no image shows the placeholder of the persons
plugins.

The plugin view registers
:file:`EXT:academic_base/Resources/Private/Partials/` with the partial root
path key `-1`, below the keys `5` and `10` it uses already, and its settings
take the placeholder from the constant
`plugin.tx_academicpersons.image.placeholder.default`, the same one the
persons plugins read.

Impact
======

*   CSS that selects the image as a direct child of the card no longer
    matches.
*   Contacts without a profile image show the placeholder where they showed
    nothing.
*   A project that replaces the partial root paths of the plugin completely,
    and a page template that renders the contacts of the
    :php:`\FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor`
    through `Profile/Item` in a view of its own, fail with an exception on the
    partial `Academic/Image` that the view cannot resolve.

Affected Installations
======================

Every installation that renders the contacts content element, or the contacts
of the data processor through the profile card.

Migration
=========

#.  Adjust CSS that addresses the contact image.
#.  To keep contacts without a profile image empty, set
    `plugin.tx_academicpersons.image.placeholder.default` to an empty value.
#.  A view that renders `Profile/Item` - the plugin view with replaced partial
    root paths, or the page view of a page template - lists the path of
    `EXT:academic_base` below the others:

    ..  code-block:: typoscript

        plugin.tx_academiccontacts4pages.view.partialRootPaths {
            -1 = EXT:academic_base/Resources/Private/Partials/
        }

#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, TypoScript, ext:academic_contacts4pages
