..  include:: /Includes.rst.txt
:navigation-title: Configuration
..  _configuration:

=============
Configuration
=============

..  _site-set:

Include the site set
====================

..  note::

    Site sets are only available when the extension is used in TYPO3 v13,
    :ref:`Static Templates <static-templates>` need to be used in TYPO3 v12
    and can also be used in TYPO3 v13 when still using classic :sql:`sys_template`
    TypoScript records.

This extension comes with a site set called `fgtclb/academic-persons-default`. To use it include
this set in your site configuration via

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-persons-default

See also: `TYPO3 Explained, Using a site set as dependency in a site <https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`_.


..  _static-templates:

Include static templates
========================

For TYPO3 v12 or when still having classic :sql:`sys_template` TypoScript record
:ref:`Static TypoScript <static-typoscript>` and `:ref:`static-pagetsconfig` are
still provided to allow easy adoption.

..  tip::

    With TYPO3 v13 we recommend to use
    `TYPO3 site set as dependency in a site <https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`_

..  _static-typoscript:

Include static TypoScript
-------------------------

..  note::

    @todo

..  _static-pagetsconfig:

Include static PageTsConfig
---------------------------

..  note::

    @todo
