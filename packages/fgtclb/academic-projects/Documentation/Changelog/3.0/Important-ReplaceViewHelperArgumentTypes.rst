.. _important-1785882400:

==================================================================
Important: The replace view helper accepts its arrays again on v14
==================================================================

Description
===========

`FGTCLB\\AcademicProjects\\ViewHelpers\\Format\\ReplaceViewHelper` documents and
implements array support for three of its arguments, but registered all three as
`string`:

..  code-block:: php

    $this->registerArgument('content', 'string', 'Content in which to perform replacement. Array supported.');
    $this->registerArgument('substring', 'string', 'Substring to replace. Array supported.', true);
    $this->registerArgument('replacement', 'string', 'Replacement to insert. Array supported.', false, '');

TYPO3 v14 rejects that. Fluid 5 validates a registered argument type with
`StrictArgumentProcessor`, where Fluid 4 used `LenientArgumentProcessor` and let
anything through:

..  code-block:: text

    InvalidArgumentValueException (1256475113): The argument "substring" was
    registered with type "string", but is of type "array" in view helper
    "FGTCLB\AcademicProjects\ViewHelpers\Format\ReplaceViewHelper".

The three arguments are registered as `mixed` now, which is the type the view
helper always behaved as: it branches on `is_scalar()` and casts to `(array)`
otherwise, before handing the values to `str_replace()` or `str_ireplace()`.

Impact
======

A template passing a list to `content`, `substring` or `replacement` works on
TYPO3 v14 again. Nothing changes for a template passing strings.

`caseSensitive` and `returnCount` keep their `boolean` type - they rely on the
coercion `StrictArgumentProcessor` performs for it.

A `string|array` union was tried first and **is not equivalent**. Fluid 5 matches
its scalar coercion on the whole registered type string, so a union falls through
uncoerced and is then validated against each member. An integer is neither a
string nor an array, and the previous `string` type used to cast it - so the
union would have exchanged one rejected value type for another. With `mixed` the
view helper performs that cast itself, as it always did.

Affected Installations
======================

Installations on TYPO3 v14 whose templates pass an array or a non-string scalar
to this view helper. It is used by no template shipped here.

References
==========

*   `Deprecation: #104789 - renderStatic() for Fluid ViewHelpers
    <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.3/Deprecation-104789-RenderStaticForFluidViewHelpers.html>`__ -
    the migration that brought the tests uncovering this.

.. index:: Fluid, Frontend, PHP-API, NotScanned
