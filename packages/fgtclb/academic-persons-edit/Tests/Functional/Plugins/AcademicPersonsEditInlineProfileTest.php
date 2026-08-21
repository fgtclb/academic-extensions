<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use DOMDocument;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Covers the AJAX contracts and both image-modal states of the
 * `academicpersonsedit_inlineprofile` content element.
 */
final class AcademicPersonsEditInlineProfileTest extends AbstractProfileEditingPluginTestCase
{
    private function setUpInlineProfileTestCase(): void
    {
        $this->setUpTestCase();
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                ['CType' => 'academicpersonsedit_inlineprofile'],
                ['uid' => 1],
            );
    }

    private function renderInlineProfilePage(): string
    {
        return $this->getPageAsFrontendUser('https://www.acme.com/home');
    }

    private function extractDataUrl(string $content, string $attribute): string
    {
        $pattern = sprintf('@\b%s="([^"]+)"@', preg_quote($attribute, '@'));
        $this->assertSame(
            1,
            preg_match($pattern, $content, $match),
            sprintf('The rendered component has no "%s" URL.', $attribute),
        );

        $url = html_entity_decode($match[1]);
        return str_starts_with($url, '/') ? 'https://www.acme.com' . $url : $url;
    }

    private function extractDeleteButtonOpeningTag(string $content): string
    {
        $this->assertSame(
            1,
            preg_match('@<button\b(?=[^>]*data-ie-delete-image)[^>]*>@s', $content, $match),
            'The image modal has no delete button.',
        );

        return $match[0];
    }

    private function getPersistedProfileImageCount(): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT image FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                [self::PROFILE_ID],
            )
            ->fetchOne();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postJson(string $url, array $payload): ResponseInterface
    {
        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode($payload, JSON_THROW_ON_ERROR));
        $body->rewind();

        return $this->requestAsFrontendUser(
            (new InternalRequest($url))
                ->withMethod('POST')
                ->withAddedHeader('Content-Type', 'application/json')
                ->withBody($body),
        );
    }

    #[Test]
    public function imageModalTemplateUsesBootstrapAndAjaxValidationOnly(): void
    {
        $file = __DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html';
        $template = file_get_contents($file);
        $this->assertIsString($template);

        $this->assertStringContainsString('class="modal fade"', $template);
        $this->assertStringContainsString('data-bs-toggle="modal"', $template);
        $this->assertStringContainsString('data-ie-upload-image', $template);
        $this->assertStringContainsString('data-ie-delete-image', $template);
        $this->assertStringNotContainsString('<dialog', $template);
        $this->assertStringNotContainsString('style=', $template);
        $this->assertStringNotContainsString('f:form.validationResults', $template);
    }

    #[Test]
    public function frontendModuleSupportsPreservedGridAndScopedComponentUi(): void
    {
        $module = file_get_contents(__DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile.js');
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html');
        $this->assertIsString($module);
        $this->assertIsString($template);

        $this->assertStringContainsString('Array.from(root.querySelectorAll(fieldSelector))', $module);
        $this->assertStringContainsString('root.addEventListener("click"', $module);
        $this->assertStringContainsString('root.querySelector(imageModalSelector)', $module);
        $this->assertStringContainsString('root.querySelector("[data-ie-status-toast]")', $module);
        $this->assertStringContainsString('"[data-ie-edit-button-template]"', $module);
        $this->assertStringNotContainsString('nextElementSibling', $module);
        $this->assertSame(2, substr_count($template, 'data-ie-fields-form'));
        $this->assertStringContainsString('data-ie-content-fields-form', $template);
        $this->assertSame(1, substr_count($template, 'class="col-12 col-lg-4"'));
        $this->assertSame(1, substr_count($template, 'class="col-12 col-lg-8"'));
        $this->assertSame(1, substr_count($template, '<div class="col-12">'));
    }

    #[Test]
    public function inlinePluginDoesNotRegisterLegacyRedirectImageActions(): void
    {
        $file = __DIR__ . '/../../../ext_localconf.php';
        $configuration = file_get_contents($file);
        $this->assertIsString($configuration);
        $inlineConfiguration = strstr($configuration, "'InlineProfile',");
        $this->assertIsString($inlineConfiguration);

        $this->assertStringContainsString("'uploadImage'", $inlineConfiguration);
        $this->assertStringContainsString("'deleteImage'", $inlineConfiguration);
        foreach (['editImage', 'addImage', 'removeImage', 'toggleSkipSync'] as $legacyAction) {
            $this->assertStringNotContainsString(sprintf("'%s'", $legacyAction), $inlineConfiguration);
        }
    }

    #[Test]
    public function profileWithoutImageRendersBootstrapModalAndDedicatedAjaxUrls(): void
    {
        $this->setUpInlineProfileTestCase();

        $content = $this->renderInlineProfilePage();
        $decodedContent = urldecode(html_entity_decode($content));

        $this->assertStringContainsString('data-academic-persons-inline-edit', $content);
        $this->assertStringContainsString('data-ie-open-image-modal', $content);
        $this->assertStringContainsString('data-bs-toggle="modal"', $content);
        $this->assertStringContainsString('data-ie-image-modal', $content);
        $this->assertMatchesRegularExpression(
            '@<div\b(?=[^>]*class="[^"]*\bmodal\b[^"]*\bfade\b)(?=[^>]*data-ie-image-modal)[^>]*>@s',
            $content,
        );
        $this->assertSame(
            1,
            preg_match('@<div\b(?=[^>]*data-ie-image-modal)[^>]*>@s', $content, $modalTag),
        );
        $this->assertStringNotContainsString('style=', $modalTag[0]);
        $this->assertStringNotContainsString('<dialog', $content);
        $this->assertMatchesRegularExpression(
            '@<form\b(?=[^>]*academic-persons-inline-edit__image-form)(?=[^>]*enctype="multipart/form-data")[^>]*>@s',
            $content,
        );
        $this->assertStringContainsString('data-ie-upload-image', $content);
        $this->assertStringContainsString('spinner-border', $content);
        $this->assertMatchesRegularExpression(
            '@<button\b(?=[^>]*data-ie-upload-image)(?=[^>]*disabled)[^>]*>@s',
            $content,
        );
        $this->assertStringContainsString('[action]=update', $decodedContent);
        $this->assertStringContainsString('[action]=updateSkipSync', $decodedContent);
        $this->assertStringContainsString('[action]=uploadImage', $decodedContent);
        $this->assertStringContainsString('[action]=deleteImage', $decodedContent);
        $this->assertStringContainsString('Add', $content);
        $this->assertStringContainsString('d-none', $this->extractDeleteButtonOpeningTag($content));
    }

    #[Test]
    public function profileWithImageRendersReplaceAndDeleteActions(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedProfileImage();

        $content = $this->renderInlineProfilePage();

        $this->assertMatchesRegularExpression(
            '@<img\b[^>]+src="[^"]*profile-image[^"]*\.png"@',
            $content,
        );
        $this->assertStringContainsString('Replace', $content);
        $this->assertStringNotContainsString('d-none', $this->extractDeleteButtonOpeningTag($content));
    }

    #[Test]
    public function synchronizationAjaxEndpointPersistsOnlyTheCheckboxState(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $syncUrl = $this->extractDataUrl($content, 'data-skip-sync-url');

        $response = $this->postJson(
            $syncUrl,
            [
                'profile' => self::PROFILE_ID,
                'data' => ['skipSync' => true],
            ],
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            [
                'success' => true,
                'profile' => self::PROFILE_ID,
                'skipSync' => true,
            ],
            json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        $storedValue = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT skip_sync FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                [self::PROFILE_ID],
            )
            ->fetchOne();
        $this->assertSame(1, (int)$storedValue);
    }

    #[Test]
    public function deleteImageAjaxEndpointDeletesRelationAndReturnsJson(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedProfileImage();
        $imagePath = $this->instancePath . '/fileadmin' . self::IMAGE_IDENTIFIER;
        $this->assertFileExists($imagePath);
        $this->assertSame(1, $this->getPersistedProfileImageCount());

        $deleteUrl = $this->extractDataUrl(
            $this->renderInlineProfilePage(),
            'data-delete-image-url',
        );
        $response = $this->postJson(
            $deleteUrl,
            [
                'profile' => self::PROFILE_ID,
                'data' => [],
            ],
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(
            [
                'success' => true,
                'profile' => self::PROFILE_ID,
                'deleted' => true,
                'hasImage' => false,
            ],
            json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        $this->assertFileDoesNotExist($imagePath);
        $this->assertSame(0, $this->getPersistedProfileImageCount());
        $this->assertSame([], $this->getStoredFiles());
    }

    #[Test]
    public function imageModalLabelsAreShippedInBothLanguages(): void
    {
        $expectedEnglish = [
            'inlineProfile.image.modal.title' => 'Edit profile image',
            'inlineProfile.image.modal.open' => 'Edit profile image',
            'inlineProfile.image.modal.close' => 'Close image modal',
            'inlineProfile.image.modal.hint.add' => 'Select an image to add it to the profile.',
            'inlineProfile.image.modal.hint.replace' => 'Select a new image to replace the current profile image.',
            'inlineProfile.image.modal.deleteHint' =>
                'The profile image is removed permanently unless another record still uses the file.',
            'inlineProfile.image.upload.label' => 'Choose image',
            'inlineProfile.image.status.uploaded' => 'The profile image has been saved.',
            'inlineProfile.image.status.deleted' => 'The profile image has been deleted.',
        ];
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        foreach ($expectedEnglish as $key => $translation) {
            $this->assertSame(
                $translation,
                $languageService->sL(
                    'LLL:EXT:academic_persons_edit/Resources/Private/Language/locallang.xlf:' . $key,
                ),
            );
        }

        $file = __DIR__ . '/../../../Resources/Private/Language/de.locallang.xlf';
        $document = new DOMDocument();
        $this->assertTrue($document->load($file));
        $germanTranslations = [];
        foreach ($document->getElementsByTagName('trans-unit') as $unit) {
            $target = $unit->getElementsByTagName('target')->item(0);
            if ($target !== null) {
                $germanTranslations[(string)$unit->getAttribute('id')] = trim($target->textContent);
            }
        }
        foreach (array_keys($expectedEnglish) as $key) {
            $this->assertArrayHasKey($key, $germanTranslations, sprintf('No German translation for "%s".', $key));
            $this->assertNotSame('', $germanTranslations[$key]);
        }
    }
}
