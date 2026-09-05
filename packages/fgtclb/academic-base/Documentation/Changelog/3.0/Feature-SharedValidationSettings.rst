..  _feature-shared-validation-settings:

===========================================
Feature: Shared validation settings classes
===========================================

Description
===========

`EXT:academic_base` now ships the primitives behind a YAML driven validation
configuration - the mechanism `EXT:academic_persons` uses to declare, per
record type and per field, whether a field is required, read only or disabled,
and to apply that to both the backend FormEngine and a frontend editing form
from one file. They lived in `EXT:academic_persons` and
`EXT:academic_persons_edit` before, carrying a note that they belonged here.

The classes, all in the namespace :php:`FGTCLB\AcademicBase\Settings`:

..  list-table::
    :header-rows: 1

    *   -   Class
        -   Purpose
    *   -   :php:`Validation`
        -   One field: its flags, the Extbase validator class names to run and
            the TCA :php:`config` fragment to merge.
    *   -   :php:`ValidationSet`
        -   The validations of one record type, keyed by property name.
    *   -   :php:`ValidationNormalizer`
        -   Turns the ``required`` / ``readonly`` / ``disabled`` / ``email`` /
            ``number`` / ``date`` flag lists of a settings file into those
            objects. ``date`` sets the frontend input type only and leaves
            the TCA type of the column alone. The same release adds
            ``url``, ``tel``, ``textarea`` and ``html`` - see *Feature:
            Validation flags and character limits*.
    *   -   :php:`SettingsFileLoader`
        -   Reads one settings file from every active package, merges them on
            the top level (the last package wins per key) and caches the
            normalised object in the core cache.
    *   -   :php:`TcaValidationMerger`
        -   Merges the TCA fragments of a set into a table TCA array.
    *   -   :php:`Exception\UnknownValidatorException`,
            :php:`Exception\UnsuitableValidatorException`
        -   The exceptions a validation engine built on these raises.

The Fluid ViewHelper :php:`FGTCLB\AcademicBase\ViewHelpers\ValidationEnsureViewHelper`
moved along with them. It resolves the validation of one field from a set and
falls back to an unconstrained default, so a template can bind the
``disabled``, ``required`` and ``readonly`` attributes without checking whether
the field was configured. It is declared as

..  code-block:: html

    xmlns:p="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"

All of these are :php:`@internal`: they are shared between the extensions of
this set, and their API may change without notice.

Impact
======

Extensions of this set can build their own validation configuration on the
shared classes instead of re-implementing the loader, the flag vocabulary and
the TCA merge. `EXT:academic_persons` is the first to do so. The behaviour of
its validation settings does not change; see its own changelog for the moved
class names.

.. index:: PHP-API, TCA, Fluid, ext:academic_base
