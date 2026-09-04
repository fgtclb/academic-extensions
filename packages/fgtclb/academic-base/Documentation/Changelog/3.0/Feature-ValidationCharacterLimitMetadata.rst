..  _feature-validation-character-limit-metadata:

==============================================
Feature: Validation flags and character limits
==============================================

Description
===========

The shared :php:`FGTCLB\AcademicBase\Settings\Validation` value object now
keeps the normalised flag list it was built from (:php:`$flags`) and carries
a readable-text character limit (:php:`$characterLimit`, ``0`` for none).
:php:`isRichText()` answers whether the ``html`` flag is set. Both properties
survive the cached ``var_export()`` round trip and default to an empty list
and ``0``, so an entry written before they existed still restores.

:php:`ValidationNormalizer::normalizeValidation()` understands four more
flags than the ``required`` / ``readonly`` / ``disabled`` / ``email`` /
``number`` / ``date`` set of *Feature: Shared validation settings*, and takes
three optional arguments:

..  list-table::
    :header-rows: 1

    *   -   Flag
        -   Effect
    *   -   ``url``
        -   Adds :php:`UrlValidator` and the input type ``url``.
    *   -   ``tel``
        -   Input type ``tel``. No validator, no TCA change.
    *   -   ``textarea``
        -   Input type ``textarea``. No TCA change.
    *   -   ``html``
        -   Input type ``textarea`` and :php:`isRichText()`. No TCA change.

The optional ``fieldName`` names the database column when it differs from
the underscored identifier, ``renderType`` names the frontend control the
flags refine - a ``select`` stays a ``select`` input type even though no flag
says so, and ``ckeditor`` is a ``textarea`` - and ``characterLimit`` is
carried through as metadata. None of the three, and none of the four flags,
change the TCA fragment: only ``required``, ``email`` and ``number`` do, as
before. Flags are trimmed, lower-cased and de-duplicated in configured order,
and entries that are not strings are dropped.

Impact
======

`EXT:academic_persons` builds its section graph on these arguments: the
fields of its settings file name their control and column, its rich text
fields carry a limit that the editing frontend of `EXT:academic_persons_edit`
shows and enforces server side. The limit counts readable text, not markup,
and is deliberately not copied into the TCA, where ``max`` would count the
markup. Backend FormEngine on TYPO3 v13 and v14 is unaffected by any of it.

An unknown flag used to be dropped and is now kept in :php:`$flags`; it still
has no other effect.

..  index:: PHP-API, Frontend, ext:academic_base
