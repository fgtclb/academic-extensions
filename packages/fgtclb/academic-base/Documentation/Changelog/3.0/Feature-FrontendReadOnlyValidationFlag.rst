.. _feature-1790531592:

=========================================================
Feature: The validation normaliser knows frontendreadonly
=========================================================

Description
===========

The validation normaliser of :guilabel:`academic_base` recognises the flag
:yaml:`frontendreadonly`. The field is read-only in the frontend editor, and
it has no frontend validator for :yaml:`required`, while the TCA configuration it
produces stays as it would be without the flag: no :php:`readOnly`, and
:php:`required` and :php:`minitems` when the field is :yaml:`required`.

The settings of :guilabel:`academic_persons` are where the flag is used. Its
manual describes it for integrators.

Impact
======

Nothing changes for a flag list without :yaml:`frontendreadonly`.

.. index:: Backend, Frontend, ext:academic_base
