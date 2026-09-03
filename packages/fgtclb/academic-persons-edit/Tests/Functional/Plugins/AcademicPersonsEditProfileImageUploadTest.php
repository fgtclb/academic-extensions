<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;

/**
 * TYPO3 v13's CLI upload permission check requires a real HTTP upload.
 *
 * @todo Drop the group once TYPO3 v13 support ends.
 */
#[Group('not-core-13')]
final class AcademicPersonsEditProfileImageUploadTest extends AbstractFrontendProfilePluginTestCase
{
    #[Test]
    public function profileEditingImageUploadPersistsAndReturnsImageMetadata(): void
    {
        $this->setUpProfileEditingTestCase();
        $submitData = $this->extractImageFormSubmissionData($this->renderProfileEditingPage());
        $fixture = __DIR__ . '/Fixtures/Uploads/profile-image.png';
        $temporaryFile = $this->instancePath . '/typo3temp/'
            . uniqid('profile-editing-image-', false) . '.upload';
        copy($fixture, $temporaryFile);
        $uploadedFiles = [];
        $this->addNestedFormValue(
            $uploadedFiles,
            $submitData['fileInputName'],
            new UploadedFile(
                $temporaryFile,
                (int)filesize($temporaryFile),
                UPLOAD_ERR_OK,
                basename($fixture),
                'application/octet-stream',
            ),
        );
        $response = $this->submitProfileImageForm(
            $submitData['action'],
            $submitData['body'],
            $uploadedFiles,
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(
            [
                'success' => true,
                'profile' => self::PROFILE_ID,
                'hasImage' => true,
                'imageAlternative' => 'Max Müllermann',
                'imageTitle' => 'Max Müllermann',
            ],
            json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        $this->assertSame(1, $this->getPersistedProfileImageCount());
        $this->assertCount(1, $this->getStoredFiles());
    }
}
