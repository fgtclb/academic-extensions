..  _breaking-removed-file-upload-converter:

=======================================
Breaking: Removed `FileUploadConverter`
=======================================

Description
===========

The custom Extbase type converter
:php:`\FGTCLB\AcademicBase\Extbase\Property\TypeConverter\FileUploadConverter`
and its `extbase.type_converter` service registration are removed, together with
the language files holding its error messages:

*   `EXT:academic_base/Resources/Private/Language/locallang.xlf`
*   `EXT:academic_base/Resources/Private/Language/de.locallang.xlf`

Both files only contained the labels `upload.error.150530345`,
`upload.error.150530346`, `upload.error.150530347`, `upload.error.150530348` and
`validation.error.1471708998`, which the converter emitted.
`locallang_be.xlf` is unaffected.

The converter was replaced by the native Extbase file upload handling introduced
in TYPO3 v13.3 (`FileUploadConfiguration`, see TYPO3 feature :issue:`103511`),
which covers the same use case with core validators and core error messages. The
last two consumers were migrated first: `EXT:academic_jobs` and
`EXT:academic_persons_edit`.

The class was flagged :php:`@internal` and explicitly excluded from the breaking
policy and semver. The removal is documented as breaking nevertheless, following
the precedent of the `ImageUploadConverter` removal in `EXT:academic_jobs` 2.1,
because projects may have used it regardless.

Impact
======

Registering the converter for an own argument, referencing the class, its
`CONFIGURATION_*` constants or the removed labels raises a PHP error or resolves
to an empty string.

Uploads handled by the shipped plugins are unaffected in configuration, but
change in behaviour — stored file names, mime type detection and the point in
time the file is stored all differ. Those changes are described in the
`Important` entries of `EXT:academic_jobs` and `EXT:academic_persons_edit`.

Affected Installations
======================

Installations that use the converter in own code, or that override one of the
removed labels.

Migration
=========

Use the native Extbase file upload handling. Build a
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration` and add it to the
argument, either through the :php:`#[FileUpload]` attribute or programmatically
in an `initialize<Action>Action()` method when the configuration comes from
TypoScript:

..  code-block:: php

    public function initializeAddImageAction(): void
    {
        $argument = $this->arguments->getArgument('profile');
        $configuration = (new FileUploadConfiguration('image'))
            ->setMaxFiles(1)
            ->setUploadFolder('1:/user_upload/');

        $fileSizeValidator = GeneralUtility::makeInstance(FileSizeValidator::class);
        $fileSizeValidator->setOptions(['maximum' => '2M']);
        $configuration->addValidator($fileSizeValidator);

        $argument->getFileHandlingServiceConfiguration()
            ->addFileUploadConfiguration($configuration);
        $argument->getPropertyMappingConfiguration()->skipProperties('image');
    }

The converter options map onto the core API as follows:

..  list-table::
    :header-rows: 1

    *   -   Converter option
        -   Replacement
    *   -   `uploadFolder`
        -   :php:`FileUploadConfiguration::setUploadFolder()`
    *   -   `validationFileSizeMaximum`
        -   :php:`FileSizeValidator` option `maximum`
    *   -   `validationMimeTypeAllowedMimeTypes`
        -   :php:`MimeTypeValidator` option `allowedMimeTypes`
    *   -   `targetFileNameWithoutExtension`
        -   no replacement, the core API generates the stored file name

:php:`MimeTypeValidator` throws for an empty `allowedMimeTypes` option, so
register it only when an allow-list is configured to keep an empty setting
meaning "no restriction".

.. index:: FAL, PHP-API, NotScanned
