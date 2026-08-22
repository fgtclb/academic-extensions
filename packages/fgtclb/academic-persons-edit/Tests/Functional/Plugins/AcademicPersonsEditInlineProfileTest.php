<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use DOMDocument;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
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

    private function getInlineProfilePartial(string $relativePath): string
    {
        $content = file_get_contents(
            __DIR__ . '/../../../Resources/Private/Partials/InlineProfile/' . $relativePath . '.html',
        );
        $this->assertIsString($content);

        return $content;
    }

    private function getInlineProfileFluidSources(): string
    {
        $paths = [
            __DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html',
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/InlineProfile/*.html') ?: []),
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/InlineProfile/*/*.html') ?: []),
        ];
        sort($paths);

        $sources = '';
        foreach ($paths as $path) {
            $content = file_get_contents($path);
            $this->assertIsString($content);
            $sources .= $content;
        }

        return $sources;
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

    /**
     * @return array{action: string, body: array<string, mixed>, fileInputName: string}
     */
    private function extractImageFormSubmissionData(string $content): array
    {
        $this->assertSame(
            1,
            preg_match(
                '@<form\b(?=[^>]*academic-persons-inline-edit__image-form)'
                    . '(?=[^>]*action="([^"]+)")[^>]*>(.*?)</form>@s',
                $content,
                $formMatch,
            ),
            'The inline image form is missing.',
        );

        $action = html_entity_decode($formMatch[1]);
        if (str_starts_with($action, '/')) {
            $action = 'https://www.acme.com' . $action;
        }

        $body = [];
        preg_match_all(
            '@<input\b(?=[^>]*type="hidden")(?=[^>]*name="([^"]+)")'
                . '(?=[^>]*value="([^"]*)")[^>]*>@s',
            $formMatch[2],
            $hiddenFields,
            PREG_SET_ORDER,
        );
        foreach ($hiddenFields as $hiddenField) {
            $this->addNestedFormValue(
                $body,
                html_entity_decode($hiddenField[1]),
                html_entity_decode($hiddenField[2]),
            );
        }

        $this->assertSame(
            1,
            preg_match(
                '@<input\b(?=[^>]*type="file")(?=[^>]*name="([^"]+)")[^>]*>@s',
                $formMatch[2],
                $fileInputMatch,
            ),
            'The inline image form has no file input.',
        );

        return [
            'action' => $action,
            'body' => $body,
            'fileInputName' => html_entity_decode($fileInputMatch[1]),
        ];
    }

    /**
     * @param array<string, mixed> $target
     */
    private function addNestedFormValue(array &$target, string $name, mixed $value): void
    {
        $position = strpos($name, '[');
        if ($position === false) {
            $target[$name] = $value;
            return;
        }

        preg_match_all('@\[([^]]*)]@', $name, $matches);
        $keys = array_merge([substr($name, 0, $position)], $matches[1]);
        $current = &$target;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $uploadedFiles
     */
    private function submitInlineImageForm(
        string $action,
        array $body,
        array $uploadedFiles = [],
    ): ResponseInterface {
        $stream = new Stream('php://temp', 'rw');
        $stream->write(http_build_query($body));
        $stream->rewind();

        $request = (new InternalRequest($action))
            ->withMethod('POST')
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($stream)
            ->withParsedBody($body);
        if ($uploadedFiles !== []) {
            $request = $request->withUploadedFiles($uploadedFiles);
        }

        return $this->requestAsFrontendUser($request);
    }

    #[Test]
    public function imageModalTemplateUsesBootstrapAndAjaxValidationOnly(): void
    {
        $template = $this->getInlineProfilePartial('Image/Modal');

        $this->assertStringContainsString('class="modal fade"', $template);
        $this->assertStringContainsString('data-ie-upload-image', $template);
        $this->assertStringContainsString('data-ie-delete-image', $template);
        $this->assertStringContainsString('data-ie-image-error', $template);
        $this->assertSame(3, substr_count($template, '<button'));
        $this->assertStringContainsString('key="actions.save"', $template);
        $this->assertStringNotContainsString('key="actions.add"', $template);
        $this->assertStringNotContainsString('key="actions.replace"', $template);
        $this->assertStringNotContainsString('data-add-label', $template);
        $this->assertStringNotContainsString('data-replace-label', $template);
        $this->assertStringNotContainsString('<dialog', $template);
        $this->assertStringNotContainsString('style=', $template);
        $this->assertStringNotContainsString('f:form.validationResults', $template);
    }

    #[Test]
    public function frontendModuleSupportsPreservedGridAndScopedComponentUi(): void
    {
        $module = file_get_contents(__DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile.js');
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html');
        $fluidSources = $this->getInlineProfileFluidSources();
        $this->assertIsString($module);
        $this->assertIsString($template);

        $this->assertStringContainsString('Array.from(root.querySelectorAll(fieldSelector))', $module);
        $this->assertStringContainsString('root.addEventListener("click"', $module);
        $this->assertStringContainsString('root.querySelector(imageModalSelector)', $module);
        $this->assertStringContainsString('root.querySelector("[data-ie-status-toast]")', $module);
        $this->assertStringContainsString('"[data-ie-edit-button-template]"', $module);
        $this->assertStringContainsString('@ckeditor/ckeditor5-editor-classic', $module);
        $this->assertStringContainsString('richTextInitialValues.set(field, createdEditor.getData())', $module);
        $this->assertStringContainsString('persistedValues.set(field, initialValue)', $module);
        $this->assertStringContainsString('setFieldValue(field, "");', $module);
        $this->assertStringContainsString('toggleEditField(root, fieldId, true);', $module);
        $this->assertStringContainsString('setFieldValue(field, persistedValues.get(field) ?? "");', $module);
        $this->assertStringContainsString('toggleEditField(root, field.id, false);', $module);
        $this->assertStringContainsString('renderRichTextPreview(root, field, fieldValue);', $module);
        $this->assertStringContainsString('content.replaceChildren(fragment);', $module);
        $this->assertStringContainsString('previewSelectedFile(file);', $module);
        $this->assertStringContainsString('commitSelectedPreview(file);', $module);
        $this->assertStringContainsString('const formData = new FormData(form);', $module);
        $this->assertGreaterThan(
            strpos($module, 'const formData = new FormData(form);'),
            strpos($module, 'setRequestPending(true, uploadButton);'),
        );
        $this->assertStringNotContainsString('.innerHTML', $module);
        $this->assertStringNotContainsString('nextElementSibling', $module);
        $this->assertSame(2, substr_count($fluidSources, 'data-ie-fields-form'));
        $this->assertStringContainsString('data-ie-content-fields-form', $fluidSources);
        $this->assertStringContainsString('data-ie-cancel-all', $fluidSources);
        $this->assertStringContainsString('partial="InlineProfile/Image/Modal"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Forms/Profile"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Forms/Content"', $template);
        $this->assertSame(1, substr_count($template, 'class="col-12 col-lg-4"'));
        $this->assertSame(1, substr_count($template, 'class="col-12 col-lg-8"'));
        $this->assertSame(1, substr_count($template, '<div class="col-12">'));
    }

    #[Test]
    public function contentFieldsRenderDirectRichTextPreviewsAndThreeFieldActions(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $this->assertSame(
            5,
            preg_match_all('@\bdata-ie-rich-text(?=[\s=>])@', $content),
        );
        $this->assertSame(5, substr_count($content, 'data-ie-editor-container'));
        $this->assertSame(
            5,
            preg_match_all('@\bdata-ie-rich-text-preview(?=[\s>])@', $content),
        );
        $this->assertSame(
            5,
            preg_match_all('@\bdata-ie-rich-text-preview-content(?=[\s>])@', $content),
        );

        $fieldActionCount = substr_count($content, 'data-ie-field-actions');
        $this->assertGreaterThanOrEqual(5, $fieldActionCount);
        $this->assertSame($fieldActionCount, substr_count($content, 'data-ie-dismiss'));
        $this->assertSame($fieldActionCount, substr_count($content, 'data-ie-save'));
        $this->assertSame(
            $fieldActionCount,
            preg_match_all('@\bdata-ie-cancel(?=[\s>])@', $content),
        );
        $this->assertStringContainsString('Delete content', $content);
        $this->assertStringContainsString('Cancel', $content);
        $this->assertStringContainsString('Save', $content);
        $this->assertStringContainsString('No content', $content);

        $field = $this->getInlineProfilePartial('Field');
        $preview = $this->getInlineProfilePartial('Field/Preview');
        $control = $this->getInlineProfilePartial('Field/Control');
        $actions = $this->getInlineProfilePartial('Field/Actions');
        $this->assertStringContainsString('align-items-start', $field);
        $this->assertStringContainsString('<f:form.textarea', $control);
        $this->assertStringContainsString('<f:form.textfield', $control);
        $this->assertStringContainsString('data-ie-rich-text-preview', $preview);
        $this->assertStringContainsString('flex-shrink-0', $actions);
        $this->assertStringNotContainsString('position-absolute', $preview);
        $this->assertStringNotContainsString('style=', $field . $preview . $control . $actions);
    }

    #[Test]
    public function richTextAjaxUpdatePersistsAndReturnsOnlySanitizedMarkup(): void
    {
        $this->setUpInlineProfileTestCase();
        $updateUrl = $this->extractDataUrl(
            $this->renderInlineProfilePage(),
            'data-update-url',
        );
        $response = $this->postJson(
            $updateUrl,
            [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'coreCompetences' => '<script>alert(1)</script>'
                        . '<p onclick="alert(2)"><strong>Secure content</strong></p>'
                        . '<a href="javascript:alert(3)">Unsafe link</a>',
                ],
            ],
        );
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $storedValue = (string)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT core_competences FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                [self::PROFILE_ID],
            )
            ->fetchOne();
        $this->assertSame($storedValue, $body['data']['coreCompetences']);
        $this->assertStringContainsString('<p><strong>Secure content</strong></p>', $storedValue);
        $this->assertStringNotContainsString('<script', $storedValue);
        $this->assertStringNotContainsString('onclick', $storedValue);
        $this->assertStringNotContainsString('javascript:', $storedValue);
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
        $this->assertStringContainsString('Save', $content);
        $this->assertStringNotContainsString('data-add-label', $content);
        $this->assertStringNotContainsString('data-replace-label', $content);
        $this->assertStringContainsString('d-none', $this->extractDeleteButtonOpeningTag($content));
    }

    #[Test]
    public function profileWithImageRendersSaveAndDeleteActions(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedProfileImage();

        $content = $this->renderInlineProfilePage();
        $this->assertMatchesRegularExpression(
            '@<img\b[^>]+src="[^"]*profile-image[^"]*\.png"@',
            $content,
        );
        $this->assertStringContainsString('Save', $content);
        $this->assertStringNotContainsString('d-none', $this->extractDeleteButtonOpeningTag($content));
    }

    #[Test]
    public function imageUploadWithoutAFileNeverReturnsSuccess(): void
    {
        $this->setUpInlineProfileTestCase();
        $submitData = $this->extractImageFormSubmissionData($this->renderInlineProfilePage());

        $response = $this->submitInlineImageForm($submitData['action'], $submitData['body']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(
            [
                'success' => false,
                'error' => 'image_upload_missing',
                'message' => 'No new profile image was received.',
            ],
            json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        $this->assertSame(0, $this->getPersistedProfileImageCount());
        $this->assertSame([], $this->getStoredFiles());
    }

    #[Test]
    #[Group('not-core-13')]
    public function inlineImageAjaxUploadPersistsAndReturnsHasImageTrue(): void
    {
        $this->setUpInlineProfileTestCase();
        $submitData = $this->extractImageFormSubmissionData($this->renderInlineProfilePage());

        $fixture = __DIR__ . '/Fixtures/Uploads/profile-image.png';
        $temporaryFile = $this->instancePath . '/typo3temp/' . uniqid('inline-profile-image-', false) . '.upload';
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

        $response = $this->submitInlineImageForm(
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
            ],
            json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        $this->assertSame(1, $this->getPersistedProfileImageCount());
        $this->assertCount(1, $this->getStoredFiles());
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
    public function inlineEditorLabelsAreShippedInBothLanguages(): void
    {
        $expectedEnglish = [
            'inlineProfile.image.modal.title' => 'Edit profile image',
            'inlineProfile.image.modal.open' => 'Edit profile image',
            'inlineProfile.image.modal.close' => 'Close image modal',
            'inlineProfile.image.modal.hint.select' =>
                'Select an image to preview it. The profile image changes only after you save.',
            'inlineProfile.image.modal.deleteHint' =>
                'The profile image is removed permanently unless another record still uses the file.',
            'inlineProfile.image.upload.label' => 'Choose image',
            'inlineProfile.image.status.uploaded' => 'The profile image has been saved.',
            'inlineProfile.image.status.missing' =>
                'No new profile image was received. Please select the image again.',
            'inlineProfile.image.status.deleted' => 'The profile image has been deleted.',
            'inlineProfile.status.editorError' =>
                'The rich text editor could not be loaded. Please reload the page and try again.',
            'inlineProfile.actions.clear' => 'Delete content',
            'inlineProfile.actions.group' => 'Field actions',
            'inlineProfile.content.empty' => 'No content',
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
