..  _breaking-jobs-shared-icon-set:

===============================================
Breaking: The job icons use the shared icon set
===============================================

Description
===========

Every icon of this extension is replaced (ACE-587).

The glyphs in front of the job properties in the list, in the detail view and
in the contact block are the shared :php:`tx-academicbase-info-*` icons of
EXT:academic_base now. They were eleven files in three different inks - grey
Material Symbols, black Font Awesome drawings and the red plugin brand mark -
delivered as :html:`<img>` tags, which keep the colour of their file. The shared
icons are inlined and drawn in `currentColor`, so they take the colour of the
surrounding text.

The partials built the identifier from the name of the Extbase property
(:html:`academic_jobs-{item}`). The name of a job property is mapped to an
identifier explicitly now, in the new partial
:file:`Resources/Private/Partials/Job/PropertyIcon.html`, which the partials
:file:`Job/Information.html`, :file:`Job/Item.html` and :file:`Job/Contact.html`
render. A property the map does not know renders no icon.

The contact block asked for the identifiers :php:`phone` and :php:`mail`, which
neither TYPO3 v13 nor TYPO3 v14 registers, so every job with a contact phone
number or e-mail address showed the "icon not found" placeholder next to them.
They are :php:`tx-academicbase-info-phone` and :php:`tx-academicbase-info-email`
now.

The content elements and the job record get their own identifiers, following the
scheme :php:`tx-<extension key without underscores>-<group>-<name>`. Both show a
Font Awesome Free briefcase, drawn in `currentColor`, so the content element
icon follows the backend colour scheme like the record icon does.

The old identifiers are removed without an alias:

..  csv-table::
    :header: "Old identifier", "New identifier"

    ":php:`academic_jobs_icon`", ":php:`tx-academicjobs-plugin-jobs`"
    ":php:`tx_academicjobs_domain_model_job` (3.0 development only)", ":php:`tx-academicjobs-record-job`"
    ":php:`academic_jobs-employmentStartDate`", ":php:`tx-academicbase-info-calendar`"
    ":php:`academic_jobs-endtime`", ":php:`tx-academicbase-info-calendar`"
    ":php:`academic_jobs-companyName`", ":php:`tx-academicbase-info-company`"
    ":php:`academic_jobs-sector`", ":php:`tx-academicbase-info-sector`"
    ":php:`academic_jobs-type`", ":php:`tx-academicbase-info-employment`"
    ":php:`academic_jobs-employmentType`", ":php:`tx-academicbase-info-employment`"
    ":php:`academic_jobs-requiredDegree`", ":php:`tx-academicbase-info-degree`"
    ":php:`academic_jobs-contractualRelationship`", ":php:`tx-academicbase-info-contract`"
    ":php:`academic_jobs-workLocation`", ":php:`tx-academicbase-info-location`"
    ":php:`academic_jobs-internationalsWelcome`", ":php:`tx-academicbase-info-international`"
    ":php:`academic_jobs-alumniRecommend`", ":php:`tx-academicbase-info-recommendation`"
    ":php:`academic_jobs-link`", ":php:`tx-academicbase-info-link`"
    ":php:`phone` (never registered)", ":php:`tx-academicbase-info-phone`"
    ":php:`mail` (never registered)", ":php:`tx-academicbase-info-email`"

Five identifiers were registered but never rendered by any template of this
extension, and are removed without a replacement:
:php:`academic_jobs-starttime`, :php:`academic_jobs-contactName`,
:php:`academic_jobs-contactEmail`, :php:`academic_jobs-contactPhone` and
:php:`academic_jobs-contactAdditionalInformation`.

The files below :file:`Resources/Public/Icons/` are removed with them:
:file:`Calendar.svg`, :file:`Company.svg`, :file:`Contract.svg`,
:file:`Email.svg`, :file:`Industry.svg`, :file:`Info.svg`, :file:`Link.svg`,
:file:`Location.svg`, :file:`Person.svg`, :file:`Phone.svg`, :file:`Public.svg`,
:file:`School.svg`, :file:`Star.svg`, :file:`Work.svg`, :file:`jobs_icon.svg`,
:file:`tx_academicjobs_domain_model_job.svg` and the unused
:file:`tx_academicjobs_domain_model_contact.svg`. The new drawing is
:file:`Resources/Public/Icons/plugin/jobs.svg`; its origin and licence are
listed in :file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.
:file:`Extension.svg` is unchanged.

Impact
======

A template, TCA or TSconfig of a site package naming one of the old identifiers
renders the "icon not found" placeholder, and an old identifier registered again
in a :file:`Configuration/Icons.php` of a site package no longer replaces
anything. A reference to one of the removed files fails.

CSS selecting the icon wrapper by its identifier class (:css:`.icon-academic_jobs-*`)
matches nothing. CSS selecting the property icons as images (:css:`img`) matches
nothing either: the drawing is an inline :html:`<svg>` now, sized
:css:`1em` by its own attributes.

Affected Installations
======================

Installations that override :file:`Job/Information.html`, :file:`Job/Item.html`
or :file:`Job/Contact.html`, reference an old identifier or icon file, override
one of the old identifiers, or style the job property icons with CSS.

Migration
=========

Replace each old identifier by its new one from the table above. In an
overridden :file:`Job/Information.html` or :file:`Job/Item.html`, replace the
constructed identifier by the partial:

..  code-block:: html

    <f:render partial="Job/PropertyIcon" arguments="{property: item}" />

To change the glyph of one property, override :file:`Job/PropertyIcon.html`. To
replace a shared glyph everywhere it appears, register its
:php:`tx-academicbase-info-*` identifier again in the
:file:`Configuration/Icons.php` of the site package. Adapt CSS to the
:css:`.icon-tx-academicbase-info-*` classes and to an :html:`<svg>` element.

..  index:: Backend, Frontend, Fluid, TCA, TSConfig, ext:academic_jobs
