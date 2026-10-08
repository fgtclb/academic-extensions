.. _important-form-partials-mark-rejected-fields:

=================================================
Important: The form partials mark rejected fields
=================================================

Description
===========

The shared form partials below
:file:`Resources/Private/Partials/Academic/Form/` read the validation results of
a field, but showed no message, and the class ``is-invalid`` sat on the element
around the field only.

A field that failed validation now gets ``is-invalid`` itself, in place of the
default class ``f3-form-error`` of the form field ViewHelpers, and the
attributes ``aria-invalid="true"`` and
``aria-describedby="<objectName>.<identifier>-error"``. :file:`FieldWrapper.html`
renders the messages of the field right after it in the element of that id,
with the class ``invalid-feedback``. The new partial :file:`ErrorMessage.html`
looks each message up as the label ``create.<objectName>.<identifier>.error.<code>``,
then ``create.error.<code>``, of the extension passed as ``extensionName``, and
falls back to the message of the validator. All three get the arguments of the
error. The title of the required mark is the label ``create.required`` of that
extension, ``required`` when it has none.

:file:`DateTime.html` shows a value it is given as ``Y-m-d``, the only format a
date input accepts, instead of ``d.m.Y``.

See :ref:`templates-form`.

Impact
======

An extension that renders its form through these partials ships the labels
``create.required`` and ``create.error.<code>`` to get translated messages.
:guilabel:`academic_jobs` does. A project that overrides one of the partials
keeps its own output until it adopts the changes. TYPO3 v13 and v14 behave
alike here.

.. index:: Frontend, Fluid, ext:academic_base
