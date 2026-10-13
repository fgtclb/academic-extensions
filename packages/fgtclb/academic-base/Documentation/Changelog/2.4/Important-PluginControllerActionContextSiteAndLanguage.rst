.. _important-ace-748-academic-base:

============================================================================
Important: The plugin action context never fails on the site or the language
============================================================================

Description
===========

:php:`getSite()` and :php:`getLanguage()` of
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext`
returned whatever the request carried under the attributes ``site`` and
``language``. A value of another kind made them fail with a :php:`\TypeError`,
although both are declared to return :php:`null` when the value is missing.
This included the :php:`NullSite` the backend stores for page 0 and for a
page outside any site, which implements :php:`SiteInterface` but is no
:php:`Site`.

Both getters now return :php:`null` for such a value, as
:php:`getExtbaseRequestParameters()` already did for the ``extbase``
attribute. A request that carries a site and a language, or none, gives the
same result as before.

:php:`getApplicationType()` is unchanged and still throws TYPO3's
:php:`\RuntimeException` with code 1606222812 for a request that carries no
application type, and its :php:`\LogicException` with code 1678875015 for a
type that is neither frontend nor backend. Every request the frontend or the
backend handled carries a type the getter resolves. The interface
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
now states this.

Impact
======

An event listener reading the site or the language from the context of an
academic plugin gets :php:`null` instead of an exception when the request
carries something else under that name. A plugin rendered on a page of a site
is not affected.

.. index:: PHP-API, ext:academic_base
