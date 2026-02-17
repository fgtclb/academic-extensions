..  include:: /Includes.rst.txt

..  _breaking-1771365480:

============================================
Breaking: #108800 - Remove translation files
============================================

Description
===========

To unify the academic extensions the usage of `locallang_db` files was
removed and the labels were moved to the `locallang_be` files.

Impact
======

TYPO3 instances with extensions providing translation overrides or
using the `locallang_db` keys with the full syntax directly no
longer replaces or provide them or leading to untranslated key
display.

Affected installations
======================

TYPO3 instances with extensions providing translation overrides or
using the `locallang_db` keys with the full syntax directly.

Migration
=========

Use the keys from `locallang_be` files.

..  important::

    Furthermore many label ids have changed to unify the naming in
    the academic extensions.

..  index:: Backend, Frontend, Fluid
