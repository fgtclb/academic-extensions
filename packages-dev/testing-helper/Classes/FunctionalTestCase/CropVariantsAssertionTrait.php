<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use TYPO3\CMS\Backend\Form\Element\ImageManipulationElement;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reads the crop variants the image cropper offers an editor for the image of a record.
 *
 * The crop configuration of a file field is spread over three places: the field itself,
 * the `columnsOverrides` of a record type, and the `overrideChildTca` FormEngine applies to
 * each file reference. What the cropper then shows is decided by the cropper element: it
 * falls back to a core default when no variant is configured, and it fits the crop area an
 * editor stored into the ratio of its variant. An assertion on `$GLOBALS['TCA']` therefore
 * checks one of those places and none of the merges.
 *
 * These helpers compile the record form the way FormEngine does when the record is opened,
 * take the crop configuration of the first file reference of a field, and hand it to the
 * cropper element together with the crop that reference stores and its file. What they
 * return is what the cropper passes on to the browser, with one exception: page TSconfig
 * (`TCEFORM.sys_file_reference.crop.config`) is merged in when the field renders, which
 * these helpers do not do.
 *
 * The record needs a file reference in the field, the file has to exist in the storage with
 * its width in the metadata, and the backend user needs to be set up. Without a width the
 * cropper does not fit the crop areas into their ratios, and an assertion on an area would
 * pass for the wrong reason.
 */
trait CropVariantsAssertionTrait
{
    /**
     * The crop variants the cropper offers for the first file reference of a field, keyed
     * by variant name, in the order the cropper lists them.
     *
     * @return array<string, array<string, mixed>>
     */
    private function offeredCropVariants(string $tableName, int $uid, string $fieldName): array
    {
        return $this->cropVariantsOfTheCropper($tableName, $uid, $fieldName, true);
    }

    /**
     * The crop variants the cropper would offer for the same file reference if the field
     * configured none: what TYPO3 shows on a field no extension touched, and what a
     * configured `default` variant has to repeat for crops stored before it to keep their
     * meaning.
     *
     * @return array<string, array<string, mixed>>
     */
    private function cropVariantsWithoutConfiguration(string $tableName, int $uid, string $fieldName): array
    {
        return $this->cropVariantsOfTheCropper($tableName, $uid, $fieldName, false);
    }

    /**
     * The aspect ratios of a variant the cropper offers, as their value keyed by their id.
     *
     * @param array<string, mixed> $cropVariant
     * @return array<string, float>
     */
    private function aspectRatiosOf(array $cropVariant): array
    {
        $ratios = [];
        foreach ($cropVariant['allowedAspectRatios'] ?? [] as $id => $ratio) {
            $ratios[(string)$id] = (float)$ratio['value'];
        }

        return $ratios;
    }

    /**
     * The first file reference of a field, compiled as FormEngine compiles it once the
     * editor has expanded it. A collapsed file reference is compiled with the columns of
     * its title only, the crop field is not among them, so the helper records the reference
     * as expanded in the backend user's inline state, as the backend does when the editor
     * opens it, and compiles the record again.
     *
     * @return array<string, mixed>
     */
    private function expandedFileReference(string $tableName, int $uid, string $fieldName): array
    {
        $children = $this->compileRecordForm($tableName, $uid)['processedTca']['columns'][$fieldName]['children'] ?? [];
        $this->assertIsArray($children);
        $this->assertNotSame([], $children, sprintf('The field "%s" of %s:%d has no file reference.', $fieldName, $tableName, $uid));
        $fileReferenceUid = (int)($children[0]['databaseRow']['uid'] ?? 0);

        $GLOBALS['BE_USER']->uc['inlineView'] = json_encode([$tableName => [$uid => ['sys_file_reference' => [$fileReferenceUid]]]]);
        $children = $this->compileRecordForm($tableName, $uid)['processedTca']['columns'][$fieldName]['children'] ?? [];
        $this->assertIsArray($children);
        $this->assertIsArray($children[0] ?? null);
        $this->assertTrue((bool)($children[0]['isInlineChildExpanded'] ?? false), 'The file reference did not expand.');

        return $children[0];
    }

    /**
     * The record form as FormEngine compiles it when an editor opens the record.
     *
     * @return array<string, mixed>
     */
    private function compileRecordForm(string $tableName, int $uid): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => $tableName,
                'vanillaUid' => $uid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }

    /**
     * The cropper resolves its configuration in two protected methods: the first drops the
     * core default as soon as a variant is configured and removes disabled variants and
     * ratios, the second reads the stored crop into it and fits each crop area into the
     * ratio of its variant. Calling them is what keeps these helpers in line with the
     * installed core, rather than with a copy of its rules.
     *
     * @return array<string, array<string, mixed>>
     */
    private function cropVariantsOfTheCropper(string $tableName, int $uid, string $fieldName, bool $asConfigured): array
    {
        $fileReference = $this->expandedFileReference($tableName, $uid, $fieldName);
        $cropConfiguration = $fileReference['processedTca']['columns']['crop']['config'] ?? null;
        $this->assertIsArray($cropConfiguration, 'The file reference has no crop field.');
        if (!$asConfigured) {
            unset($cropConfiguration['cropVariants']);
        }
        $fileUid = (int)($fileReference['databaseRow']['uid_local'][0]['uid'] ?? 0);
        $this->assertGreaterThan(0, $fileUid, 'The file reference has no file.');
        $file = $this->get(ResourceFactory::class)->getFileObject($fileUid);
        $storedCrop = (string)($fileReference['databaseRow']['crop'] ?? '');

        $element = $this->get(ImageManipulationElement::class);
        $configuration = (new \ReflectionMethod($element, 'populateConfiguration'))->invoke($element, $cropConfiguration);
        $configuration = (new \ReflectionMethod($element, 'processConfiguration'))->invokeArgs($element, [$configuration, &$storedCrop, $file]);
        $this->assertIsArray($configuration);
        $this->assertIsArray($configuration['cropVariants'] ?? null);

        return $configuration['cropVariants'];
    }
}
