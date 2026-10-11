..  _important-1785882400:

==============================================================
Important: The replace view helper declares its argument types
==============================================================

Description
===========

:php:`FGTCLB\AcademicProjects\ViewHelpers\Format\ReplaceViewHelper` documents
and implements array support for three of its arguments, but registered all
three as :php:`string`:

..  code-block:: php

    $this->registerArgument('content', 'string', 'Content in which to perform replacement. Array supported.');
    $this->registerArgument('substring', 'string', 'Substring to replace. Array supported.', true);
    $this->registerArgument('replacement', 'string', 'Replacement to insert. Array supported.', false, '');

They are registered as :php:`mixed` now, which is the type the view helper
always behaved as: it branches on :php:`is_scalar()` and casts to
:php:`(array)` otherwise, before handing the values to :php:`str_replace()` or
:php:`str_ireplace()`.

:html:`caseSensitive` and :html:`returnCount` keep their :php:`boolean` type.

Impact
======

**Nothing changes on TYPO3 v12 and v13.** Both let any value through. Fluid 2
of TYPO3 v12 does not validate a registered argument type, and Fluid 4 of
TYPO3 v13 validates it with :php:`LenientArgumentProcessor`, which accepts
everything that is not an object of a wrong class. The arrays this view helper
documents worked on both from the start.

The declaration is corrected because a stricter argument validation, as later
Fluid versions apply it, rejects an array handed to a :php:`string` argument.

Affected Installations
======================

None. The entry documents a corrected declaration without a visible effect.

References
==========

*   `str_replace <https://www.php.net/manual/en/function.str-replace.php>`__,
    the function the values are handed to, which accepts arrays.

..  index:: Fluid, Frontend, PHP-API, NotScanned, ext:academic_projects
