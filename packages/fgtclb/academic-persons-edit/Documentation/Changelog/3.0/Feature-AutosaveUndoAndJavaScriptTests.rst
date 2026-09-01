
..  _feature-autosave-undo-and-javascript-tests:

================================================
Feature: Autosave undo and isolated JavaScript tests
================================================

Editable ``select`` and ``checkbox`` profile fields now render a compact undo
action. It reuses the generic ``data-ie-cancel`` behavior, restores the last
successfully persisted value and closes the field editor without issuing an
additional request. Read-only and disabled controls do not render the action.

The frontend modules expose their stateless helpers as named ES module exports.
This keeps the production entry point unchanged while allowing focused unit and
DOM interaction tests. A standalone Jest/jsdom project is available in
:file:`Resources/Public/Development/` and can be run with ``npm i`` followed by
``npm test``. The production source directory contains its own minimal
``package.json`` with ``type: module`` so Node and Jest interpret the sibling
JavaScript sources with the same ES-module semantics as TYPO3's browser module
loader.

..  index:: Frontend, JavaScript, Testing
