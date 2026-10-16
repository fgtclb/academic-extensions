..  _important-select-item-event-declares-what-typo3-passes:

===========================================================
Important: The select item event declares what TYPO3 passes
===========================================================

Description
===========

:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` declares the
parameters of an :php:`itemsProcFunc` as a PHPStan array shape, and the item
providers that dispatch the event repeated that shape. Three keys of it were
declared narrower than TYPO3 fills them in the backend form:

..  list-table::
    :header-rows: 1
    :widths: 25 20 55

    *   -   Key
        -   Declared
        -   Passed by TYPO3 v12, v13 and v14
    *   -   :php:`inlineParentUid`, :php:`inlineTopMostParentUid`
        -   :php:`int`
        -   The empty string `''` for a record that is not edited as an
            inline child. A `NEW...` string for a parent that is not saved
            yet. Otherwise the uid of the parent, as an integer or a numeric
            string. The site configuration passes :php:`null` as
            :php:`inlineTopMostParentUid`.
    *   -   :php:`site`
        -   :php:`Site|null`
        -   A :php:`\TYPO3\CMS\Core\Site\Entity\NullSite` for a record
            outside any site, on page 0 for example.
    *   -   :php:`items`
        -   arrays
        -   :php:`\TYPO3\CMS\Core\Schema\Struct\SelectItem` objects for
            the items TYPO3 built before the item provider ran, the static
            items of the field among them. The items a provider adds are
            arrays.

The event declares the shape once now, as the PHPStan type alias
:php:`ItemsProcFuncParameters`, with :php:`int|string` for
:php:`inlineParentUid`, :php:`int|string|null` for
:php:`inlineTopMostParentUid`,
:php:`\TYPO3\CMS\Core\Site\Entity\SiteInterface|null` for :php:`site`,
and :php:`SelectItem` or an array for an item. The item providers of
:composer:`fgtclb/academic-jobs`, :composer:`fgtclb/academic-persons` and
:composer:`fgtclb/academic-contacts4pages` import it. The providers of
:composer:`fgtclb/academic-persons` and
:composer:`fgtclb/academic-contacts4pages` therefore declare :php:`row`
optional as well, as the event does since
:ref:`important-select-item-helper-passes-an-empty-row`.

Only the declaration changes. TYPO3 passed these values before, and the item
providers pass them on unchanged.

Impact
======

A listener that static analysis accepted could fail at runtime for the common
case. One that passes :php:`$parameters['inlineParentUid']` to a parameter
typed :php:`int` raises a :php:`\TypeError` for every record that is not
edited as an inline child. One that calls a method only :php:`Site` has on
:php:`$parameters['site']` fails for a record outside any site. One that
types the item of a closure as :php:`array` raises a :php:`\TypeError` for a
field with static items. Static analysis reports such a listener now.

Cast an inline uid and use it only when it is above 0, check the site before
calling a method of :php:`Site`, and type an item as :php:`array|SelectItem`:

..  code-block:: php

    $parentUid = (int)($parameters['inlineParentUid'] ?? 0);
    if ($parentUid > 0) {
        // The record is edited as an inline child of a saved parent.
    }
    $site = $parameters['site'] ?? null;
    if ($site instanceof \TYPO3\CMS\Core\Site\Entity\Site) {
        $settings = $site->getSettings();
    }
    $values = array_map(
        static fn(array|\TYPO3\CMS\Core\Schema\Struct\SelectItem $item): string => (string)($item['value'] ?? ''),
        $parameters['items'],
    );

A listener or an item provider of a project can import the alias instead of
repeating the shape:

..  code-block:: php

    /**
     * @phpstan-import-type ItemsProcFuncParameters from \FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent
     */

Affected Installations
======================

Installations with a listener of
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`, or with code
that calls one of the item providers of the academic extensions, that is
checked by static analysis. The listener reports point at code that already
failed or misbehaved at runtime.

..  index:: Backend, PHP-API, ext:academic_base
