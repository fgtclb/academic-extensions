..  _feature-get-current-content-record-method-trait:

=========================================================
Feature: `GetCurrentContentRecordMethodTrait` for plugins
=========================================================

Description
===========

The new trait
`FGTCLB\\AcademicBase\\Controller\\GetCurrentContentRecordMethodTrait` provides
`getCurrentContentRecord()` for Extbase action controllers. It builds the record
of the current content element from the `tt_content` row of the content object
renderer, so it can be assigned as `record` view variable next to the usual
`data` array:

..  code-block:: php

    $this->view->assignMultiple([
        'data' => $this->getCurrentContentObjectRenderer()?->data,
        'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
    ]);

TYPO3 v14 renders the header of the shared `EXT:fluid_styled_content` header
partial with `{record -> f:render.text(...)}`, which requires a record object.
Content elements based on `lib.contentElement` get it from the
`record-transformation` data processor, Extbase plugin views do not. Without the
variable, plugin templates that render that partial abort on TYPO3 v14.

`TYPO3\\CMS\\Core\\Domain\\RecordFactory` behaves identically in TYPO3 v13 and
v14, therefore the trait needs no core version aware handling. TYPO3 v13 ignores
the variable, because its header partial reads `data`.

Impact
======

Extensions building on `EXT:academic_base` can use the trait to make their
plugins render on TYPO3 v14 and to give their templates access to a record
object.

.. index:: Fluid, Frontend, PHP-API, NotScanned
