..  _feature-profile-editing:

===========================================
Feature: The profile editing view rewritten
===========================================

Description
===========

The :guilabel:`Profile editing` content element renders the whole profile on
one page and saves each change where it is made. The previous form flow -
one page per record, a submission per change, a redirect after every save - is
gone (:ref:`breaking-replaced-profile-editing-plugin`).
:ref:`profile-editing` is the reference; this entry is the overview.

Overview
--------

``list`` renders the profiles assigned to the authenticated frontend user,
with the language each of them belongs to and a link to the public detail page.
``index`` opens one of them for editing. Every other action of the plugin
answers JSON.

Editing in place
----------------

Each field carries an edit control. Activating it turns the rendered value into
an input, saving posts only what changed, and the answer is written back into
the page - no reload, no lost scroll position. A group of fields that belong
together (the name parts, a link and its title) is edited and saved as one.
:guilabel:`Edit all` opens every field of the page at once.

An unsaved field can be restored to the value that is stored, and a checkbox
saves on change and reverts itself when the request fails, so what is on screen
is what is in the database.

Structured sections
-------------------

Contracts and the seven kinds of timeline entry - cooperation, lectures,
memberships, press and media, publications, scientific research and vita - are
compact lists with an editor that folds out below the row it belongs to.
Creating, viewing, editing, deleting and reordering happen without leaving the
page; deleting asks first. Which columns a list shows, which actions it offers
and whether it is read-only is configured per section
(:ref:`breaking-contract-configuration-and-accessible-profile-editing`), and
the server enforces it.

Reordering works with the up and down buttons, with the keyboard, and by
dragging a row onto its new place. A failed request puts the previous order
back.

Contract contacts
-----------------

The addresses, email addresses and phone numbers of a contract are edited
inside the contract's own editor, each kind as its own list with its own
configured fields.

Rich text and character limits
------------------------------

A field configured as ``ckeditor`` opens a CKEditor 5 instance with bold,
italic, lists and links, in the language of the site. A configured character
limit is shown while typing, enforced in the editor and validated again on the
server. Every stored value is sanitized against an allow list - paragraphs,
line breaks, bold, italic, lists and links with an ``http``, ``https``,
``mailto`` or ``tel`` target - so what an editor pastes cannot reach the public
profile as markup the template did not expect.

Profile image
-------------

The image is edited in a panel that folds out over the profile. A newly
selected file is cropped to the configured aspect ratio in the browser before
it is uploaded, so the stored file is the one that is shown. Uploading replaces
the existing relation rather than adding a second one, and deleting asks for a
confirmation first. The upload is validated on the server against the
configured maximum file size and MIME type list - a blanked setting falls back
to the same defaults the file input advertises, it does not disable the check.

Dates
-----

The date fields of a timeline entry are real dates with a date picker
(*Breaking: Profile information years use native dates* in
`EXT:academic_persons`). An entry that only knows its year keeps the
:guilabel:`year only` switch, which decides how the date is rendered.

Synchronisation switch
----------------------

The switch that excludes a profile from the translation synchronisation is
saved through its own endpoint, and reverts itself when the request fails.

Accessibility
-------------

Every control has an accessible name, the fold-out regions carry
``aria-controls``/``aria-expanded``, validation errors are announced through
``aria-describedby``/``aria-invalid``, a failed request is announced through an
assertive live region and a successful one through a polite one, and closing an
editor returns the focus to the control that opened it.

Bundled libraries
-----------------

Two third-party sets are shipped with the extension, each with its licence file
next to it:

*   Cropper.js 2.2.0 (MIT),
    :file:`Resources/Public/JavaScript/vendor/cropperjs/2.2.0/`, the image
    cropper. The ``cropperjs`` entry TYPO3 maps itself is version 1.6.1, an
    incompatible API, so this one is vendored.
*   Bootstrap Icons (MIT), the thirteen control icons of this view, as SVG
    files under :file:`Resources/Public/Icons/` with
    :file:`LICENSE-bootstrap-icons.txt` beside them.

Nothing else is bundled. The view is rendered by five web components, two of
them written against Lit, which TYPO3 delivers through the import map of
``EXT:core``, and CKEditor 5 is loaded from the system extension
``rte_ckeditor`` - see *Important: New
dependencies `cms-rte-ckeditor` and `html-sanitizer`* and
:ref:`breaking-profile-editing-rendered-by-web-components`.

Impact
======

The editing view is replaced for every installation of the content element.
What has to be looked at when updating is listed in
:ref:`breaking-replaced-profile-editing-plugin`.

..  index:: AJAX, CKEditor, Fluid, Frontend, JavaScript, ext:academic_persons_edit, NotScanned
