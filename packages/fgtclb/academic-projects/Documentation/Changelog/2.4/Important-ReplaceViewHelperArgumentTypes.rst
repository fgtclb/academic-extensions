.. _important-1785882400:

==============================================================
Important: The replace view helper declares its argument types
==============================================================

Description
===========

`FGTCLB\\AcademicProjects\\ViewHelpers\\Format\\ReplaceViewHelper` documents and
implements array support for three of its arguments, but registered all three as
`string`:

..  code-block:: php

    $this->registerArgument('content', 'string', 'Content in which to perform replacement. Array supported.');
    $this->registerArgument('substring', 'string', 'Substring to replace. Array supported.', true);
    $this->registerArgument('replacement', 'string', 'Replacement to insert. Array supported.', false, '');

They are registered as `mixed` now, which is the type the view helper always
behaved as: it branches on `is_scalar()` and casts to `(array)` otherwise,
before handing the values to `str_replace()` or `str_ireplace()`.

Impact
======

**Nothing changes on TYPO3 v12 and v13.** Both let any value through - Fluid 2
does not validate a registered argument type, and Fluid 4 validates it with
`LenientArgumentProcessor`, which accepts everything that is not an object of a
wrong class. The arrays this view helper documents worked on both from the
start.

The declaration is corrected because TYPO3 v14 does reject it. Fluid 5 validates
with `StrictArgumentProcessor`, and an array handed to a `string` argument raises
`InvalidArgumentValueException` there. The `3.x` branch carries the same change
for that reason, and this branch follows it so the class and its tests stay
identical in both.

`caseSensitive` and `returnCount` keep their `boolean` type.

References
==========

*   `str_replace <https://www.php.net/manual/en/function.str-replace.php>`__ -
    the function the values are handed to, which accepts arrays.

.. index:: Fluid, Frontend, PHP-API, NotScanned
