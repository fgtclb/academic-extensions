..  _breaking-profile-editing-rendered-by-web-components:

=======================================================
Breaking: Profile editing is rendered by web components
=======================================================

Description
===========

The profile editing view of :ref:`profile-editing` was rendered by a Vue 3
runtime bundled with the extension: Fluid emitted the markup of every editor
with ``v-if``, ``v-for``, ``v-model`` and ``v-on:`` attributes in it, and the
runtime compiled that markup in the browser and re-rendered it on every change.
The runtime is removed. What renders the same view now is five custom elements.
Two of them build their markup with `Lit <https://lit.dev>`__, which TYPO3
delivers through the import map of ``EXT:core``; the other three render nothing
and drive markup Fluid rendered. Nothing is bundled with the extension for it.

The consequence for an integrator is not the framework. It is that markup which
used to be written in Fluid - and could therefore be overridden - is now built
in the browser, from a response, by an element.

Override points that no longer exist
------------------------------------

..  list-table::
    :header-rows: 1

    *   - File
        - What happened to it
    *   - :file:`Resources/Private/Partials/Profile/Documents/ContractContacts.html`
        - Removed. The contacts of a contract - addresses, e-mail addresses and
          phone numbers, with their sort and action controls - are rendered by
          ``<academic-persons-edit-contract-contacts>`` from the
          ``contactSections`` of the ``documentForm`` response.
    *   - :file:`Resources/Private/Partials/Profile/Documents/ContractContactEditor.html`
        - Removed. The add, view, edit and delete form of one contact is
          rendered by the same element, from the ``contractContactForm``
          response.
    *   - :file:`Resources/Private/Partials/Profile/Documents/Editor.html`
        - Kept, and emptied. The 266 lines that rendered the heading, the
          fields, the errors and the buttons of a document or contract editor
          are gone; the file is the mount point
          ``<academic-persons-edit-document-editor>`` and renders nothing else.
          The editor is built from the ``fields`` of the ``documentForm``
          response, which the server decides and a template cannot know.
    *   - :file:`Resources/Private/Partials/Profile/Image/Editor.html`
        - Kept, and reduced. The ``<f:form>`` with its ``__trustedProperties``
          signature and its ``<f:form.upload>`` are unchanged and stay server
          rendered - only the server can sign that form. Everything the
          twenty-seven directives around it derived - which panel is shown, what
          the delete confirmation says, whether the spinner runs - is written by
          ``<academic-persons-edit-image-editor>`` instead. Overriding the file
          still works; changing the ``data-pe-*`` hooks in it breaks the editor.
    *   - :file:`Resources/Private/Partials/Profile/Documents/Actions.html`,
          :file:`…/Sections.html`, :file:`Resources/Private/Partials/Profile/Header.html`
        - Kept. Their ``v-on:`` bindings are replaced by listeners delegated on
          the plugin root, which match ``data-pe-document-add``,
          ``-view``, ``-edit``, ``-delete``, ``-sort`` and ``data-pe-sync-form``.
          A control that keeps its hook keeps working; a control rebuilt without
          one is inert.
    *   - :file:`Resources/Private/Templates/Profile/Index.html`
        - Kept, and extended by one block: a ``<template data-pe-icon="…">`` per
          icon a browser rendered editor draws. ``<core:icon>`` resolves an
          identifier through the icon registry and a browser cannot ask it, so
          the elements clone their icons out of that block. Removing it renders
          the editors without icons.

The new contract
----------------

Five custom elements replace those partials, and their names, events and hooks
are public API from this release on. The name prefix is the extension key with
its underscores replaced.

..  list-table::
    :header-rows: 1

    *   - Element
        - What it owns
    *   - ``<academic-persons-edit-profile-editing>``
        - One editor. It wraps the plugin root, reads its ``data-*`` contract
          once and starts everything below it.
    *   - ``<academic-persons-edit-image-editor>``
        - The image editor over the server rendered upload form.
    *   - ``<academic-persons-edit-document-editor>``
        - One open document or contract editor, in ``add``, ``view``, ``edit``
          or ``delete`` mode.
    *   - ``<academic-persons-edit-contract-contacts>``
        - The contacts of one contract and the editor of one contact.
    *   - ``<academic-persons-edit-rich-text>``
        - One rich text field and the CKEditor 5 instance on it.

Four events report what an open editor did, and one is dispatched upwards by any
descendant that wants a status shown:

..  list-table::
    :header-rows: 1

    *   - Event
        - Meaning
    *   - ``pe:status``
        - Write one of the two live regions; detail ``{ type, message? }``.
    *   - ``pe:document-close``
        - The cancel button of an open editor was pressed.
    *   - ``pe:document-submit``
        - Its form was submitted; the browser's own submission is prevented.
    *   - ``pe:document-input``
        - A control changed; detail ``{ name, value }``.
    *   - ``pe:document-closed``
        - The close transition is over and the element may be removed.

Two families of attributes carry the rest, and both are unchanged in meaning:

*   The plugin root of :file:`Templates/Profile/Index.html` keeps its ``data-*``
    contract - thirteen endpoints, the profile uid and the editor language, five
    image settings, twenty messages and nine labels. It is read once, when the
    editor starts.
*   Every control below it keeps its ``data-pe-*`` hook. The controls the
    elements render carry the same ones the removed partials did:
    ``data-pe-document-field``, ``data-pe-document-form``,
    ``data-pe-document-heading``, ``data-pe-rich-text``,
    ``data-pe-contract-contact-section``, ``-item``, ``-add``, ``-view``,
    ``-edit``, ``-delete``, ``-sort``, ``-cancel``, ``-save``, ``-field`` and
    ``-editor``, and the image editor's ``data-pe-image-*`` set.

**The elements render into the light DOM.** None of them has a shadow root:
``createRenderRoot()`` returns the element itself. A project's stylesheet, the
theme's Bootstrap classes and any CSS written against the markup of the removed
partials reach every control an element renders, exactly as before - there is no
style encapsulation to work around and no ``::part()`` to declare. The class
names on the rendered controls are as much part of this contract as the hooks
are.

The elements, their properties and their events are listed above because this
entry is where the contract is published: :ref:`profile-editing` still describes
the view as it was rendered before the port and is rewritten separately.

Impact
======

*   An override of :file:`Documents/ContractContacts.html` or
    :file:`Documents/ContractContactEditor.html` has no effect: the files are
    gone, nothing renders them, and the project keeps a copy of a view that does
    not exist.
*   An override of :file:`Documents/Editor.html` that carries the old markup has
    no effect either. The file is a mount point; markup inside it is replaced by
    what the element renders.
*   A Fluid override anywhere in the view that still carries ``v-if``,
    ``v-for``, ``v-model``, ``v-on:``, ``<Teleport>`` or ``<Transition>`` fails
    **silently**: nothing reads those attributes any more, so the control simply
    does nothing when it is used.
*   CSS and JavaScript written against the rendered markup keep working as long
    as they select classes and ``data-pe-*`` hooks. A selector that relies on the
    element structure of the removed partials - a descendant chain, an
    ``:nth-child()`` - may not match any more.
*   Page weight drops: the 172 KB Vue runtime that was loaded on every profile
    editing page is not shipped any more, and Lit is delivered by TYPO3.

Affected Installations
======================

Installations of the :guilabel:`Profile editing` content element of
`EXT:academic_persons_edit` that override one of the named Fluid files, or that
style or script the editor against its markup. An installation that uses the
shipped templates is not affected: the rendered result is the same view.

Migration
=========

#.  Delete the overrides of :file:`Documents/ContractContacts.html`,
    :file:`Documents/ContractContactEditor.html` and
    :file:`Documents/Editor.html`. What they changed has to be re-applied as
    CSS against the rendered markup, which the elements keep in the light DOM
    for that reason.
#.  Remove every ``v-*`` attribute, ``<Teleport>`` and ``<Transition>`` from the
    remaining overrides. They are inert, not an error.
#.  Keep the ``data-pe-*`` hooks and the ``<template data-pe-icon="…">`` block
    of :file:`Templates/Profile/Index.html` when overriding a file that carries
    them.
#.  Re-check selectors that depend on the structure of the removed partials.

..  index:: Fluid, Frontend, JavaScript, Template, ext:academic_persons_edit, NotScanned
