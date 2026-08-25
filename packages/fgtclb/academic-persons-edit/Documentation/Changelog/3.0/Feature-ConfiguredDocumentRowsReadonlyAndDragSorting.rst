..  _feature-configured-document-rows-readonly-and-drag-sorting:

=============================================================
Feature: Configured document rows, read-only and drag sorting
=============================================================

Description
===========

The inline structured-section view now consumes ``rowFields`` and ``actions``
from each typed ``DocumentSection``. Fluid renders only the selected row values
and only the selected controls, both in configuration order. Read-only sections
show existing records and their configured view action but do not render an add
control or mutation controls.

The JSON endpoints enforce the same capabilities. A read-only or otherwise
disallowed create, update, delete or sort request returns HTTP 403. Form schema
requests carry their intended mode so loading an edit form is also checked.

Sections that allow both ``up`` and ``down`` retain the arrow buttons and gain a
drag handle. Dragging submits the complete UID order; the endpoint rejects
missing, duplicate, foreign or cross-section records before assigning normalized
sorting values. The complete source row is used as the browser drag image. A
strong source outline, active-list outline and before/after insertion line make
both the moved record and the resulting position visible before it is dropped.

Document fields marked with the ``html`` validator flag use TYPO3's CKEditor 5
inside the shared modal. Textareas occupy the full Bootstrap row, and modal
pending state now restores the submit button's actual previous state. Add,
view, edit and delete modal headings use a non-empty record ``title`` and fall
back to the translated section heading when no title exists. Delete mode removes
``btn-primary`` and ``btn-success`` from the submit control and applies
``btn-danger``; all modal controls use square ``rounded-0`` corners.

Impact
======

Template overrides must pass ``rowFields``, ``actions`` and ``sortable`` through
the document partial tree and retain ``data-section-sortable`` plus the drag
hooks. Clients calling ``documentForm`` must submit ``mode`` as ``add``,
``view``, ``edit`` or ``delete``.

..  index:: AJAX, CKEditor, Drag and drop, Frontend, NotScanned, ext:academic_persons_edit
