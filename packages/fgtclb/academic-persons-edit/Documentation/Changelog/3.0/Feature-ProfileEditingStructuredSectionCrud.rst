..  _feature-profile-editing-structured-section-crud:

=========================================================
Feature: ProfileEditing structured-section CRUD over AJAX
=========================================================

Description
===========

Every writable structured profile section provides an add button beside its
heading. Each contract or profile-information row provides the actions declared
by its section settings. The existing
``academic-persons-inline-edit-open``, ``academic-persons-inline-edit-down`` and
``academic-persons-inline-edit-up`` icons are used for the corresponding
actions.

The workflows stay inside ``ProfileController``. The JSON actions
``documentForm``, ``createDocument``, ``updateDocument``, ``deleteDocument``
and ``sortDocument`` authenticate the current frontend user and resolve records
only through that user's profile and the requested section.

The frontend uses one scoped Bootstrap modal for add, view, edit and delete.
Successful mutations update rows, empty states, alternating backgrounds and
sort controls in place without a page reload. Lists configured with both sort
directions additionally support drag-and-drop reordering through the same sort
endpoint. HTML document fields use a full-width CKEditor 5 control. Rich-text
output is sanitized and inserted through parsed DOM nodes.

Impact
======

Template overrides of the structured sections must retain the
``data-ie-document-*`` and ``data-item-*`` hooks. API access requires
authenticated profile ownership, section membership and the configured action
capability. ``readonly`` blocks every mutation independently of rendered UI.

..  index:: AJAX, Contracts, Fluid, Frontend, Profile information, NotScanned, ext:academic_persons_edit
