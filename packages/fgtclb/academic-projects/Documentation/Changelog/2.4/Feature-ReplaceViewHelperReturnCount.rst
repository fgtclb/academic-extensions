..  _feature-1785882300:

===============================================================
Feature: The replace view helper can return the number of edits
===============================================================

Description
===========

:php:`FGTCLB\AcademicProjects\ViewHelpers\Format\ReplaceViewHelper` counted its
replacements and returned the count instead of the replaced content when a
:html:`returnCount` argument was set:

..  code-block:: php

    if ($this->arguments['returnCount'] ?? false) {
        return $count;
    }

That argument was never registered, and Fluid rejects an argument a view helper
does not declare while it parses the template. No template could therefore
reach the branch:

..  code-block:: text

    Undeclared arguments passed to ViewHelper
    FGTCLB\AcademicProjects\ViewHelpers\Format\ReplaceViewHelper: returnCount.
    Valid arguments are: content, substring, replacement, caseSensitive

:html:`returnCount` is a registered boolean argument now, defaulting to
:php:`false`:

..  code-block:: html

    <html xmlns:ap="http://typo3.org/ns/FGTCLB/AcademicProjects/ViewHelpers"
          data-namespace-typo3-fluid="true">
    <ap:format.replace
        content="A research project of the research group"
        substring="research"
        replacement="teaching"
        returnCount="true"
    />
    </html>

renders ``2``.

Impact
======

Nothing changes for a template that does not pass the argument. The default
returns the replaced content, as before. A template that passed
:html:`returnCount` used to raise the parse error above and renders the count
now.

The count is the one :php:`str_replace()` and :php:`str_ireplace()` report, so
it counts occurrences rather than distinct substrings, and it is ``0`` for a
substring that does not occur.

Affected Installations
======================

Installations with templates that use the view helper. No action is required.

References
==========

*   `str_replace <https://www.php.net/manual/en/function.str-replace.php>`__,
    the :php:`$count` parameter this returns.

..  index:: Fluid, Frontend, NotScanned, ext:academic_projects
