..  _feature-configurable-document-rows-and-actions:

=================================================
Feature: Configurable document rows and actions
=================================================

Description
===========

Entries below :yaml:`documentSections` can now define ordered
:yaml:`rowFields` and :yaml:`actions` lists. ``DocumentSection`` preserves the
normalized lists and provides capability helpers for row rendering, creation
and drag sorting. Unknown values and duplicates are discarded.

Profile-information rows support ``from``, ``to``, ``year``, ``title`` and
``description``; contract rows support ``from``, ``to`` and ``position``.
Supported row actions are ``view``, ``down``, ``up``, ``delete`` and ``edit``.
A section marked :yaml:`readonly` can only allow ``view``. Both ``up`` and
``down`` are required before an editor may offer arbitrary drag-and-drop
reordering.

The shipped profile-information descriptions use the :yaml:`html` validation
flag so frontend editors can select the rich-text control and apply the same
sanitization contract as direct Profile rich text.

Impact
======

Site packages overriding :yaml:`documentSections` should add both lists to each
section. The settings cache identifier changed; flush TYPO3 caches after the
extension update.

..  index:: Configuration, Drag and drop, Frontend, NotScanned, ext:academic_persons
