<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Covers the profile and structured-section AJAX contracts as well as the
 * image editor of the `academicpersonsedit_inlineprofile` content element.
 */
final class AcademicPersonsEditInlineProfileTest extends AbstractFrontendProfilePluginTestCase
{
    private function setUpInlineProfileTestCase(): void
    {
        $this->setUpFrontendProfileTestCase(
            __DIR__ . '/Fixtures/AcademicPersonsEditInlineProfile/inlineProfilePage.csv',
        );
    }

    private function renderInlineProfilePage(): string
    {
        $listPage = $this->getPageAsFrontendUser('https://www.acme.com/home');
        return $this->getPageAsFrontendUser(
            $this->extractPluginActionLink(
                $listPage,
                'tx_academicpersonsedit_inlineprofile',
                'index',
                'profileUid',
                self::PROFILE_ID,
            ),
        );
    }

    private function seedStructuredDocumentSections(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsEditInlineProfile/structuredDocumentSections.csv');
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update(
                'tx_academicpersons_domain_model_profile',
                [
                    'contracts' => 2,
                    'cooperation' => 2,
                    'lectures' => 1,
                    'memberships' => 2,
                    'press_media' => 0,
                    'publications' => 1,
                    'scientific_research' => 1,
                    'vita' => 2,
                ],
                ['uid' => self::PROFILE_ID],
            );
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
            ...(glob(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/*.html') ?: []),
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/InlineProfile/*.html') ?: []),
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/InlineProfile/*/*.html') ?: []),
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/InlineProfile/*/*/*.html') ?: []),
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

    private function getInlineProfileJavaScriptSources(): string
    {
        $paths = [
            __DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile.js',
            ...(glob(__DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile/*.js') ?: []),
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
            'The image view has no delete button.',
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
    public function imageEditorTemplateExposesStableAjaxHooks(): void
    {
        $template = $this->getInlineProfilePartial('Image/Editor');
        $this->assertStringContainsString('data-ie-image-view-container', $template);
        $this->assertStringContainsString('academic-persons-inline-edit__image-editor', $template);
        $this->assertStringContainsString('academic-persons-inline-edit__image-editor-content', $template);
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
        $this->assertStringNotContainsString('f:form.validationResults', $template);
    }

    #[Test]
    public function imageCardExposesAccessibleEditHook(): void
    {
        $template = $this->getInlineProfilePartial('Image/Card');
        $this->assertStringContainsString('key="inlineProfile.image.heading"', $template);
        $this->assertStringNotContainsString('data-ie-profile-name', $template);
        $this->assertStringContainsString('data-ie-image-preview', $template);
        $this->assertMatchesRegularExpression(
            '@<button\b(?=[^>]*data-ie-open-image-view)[^>]*>@s',
            $template,
        );
        $this->assertStringContainsString(
            'identifier="academic-persons-inline-edit-camera"',
            $template,
        );
        $this->assertStringContainsString('title="{f:translate(', $template);
        $this->assertStringContainsString('aria-label="{f:translate(', $template);
        $this->assertStringContainsString('<f:if condition="{image.writable}">', $template);
        $this->assertStringNotContainsString('imageEditable', $template);
        $this->assertStringNotContainsString('academic-persons-edit-add-image', $template);
    }

    #[Test]
    public function stickyImageUsesDynamicPageHeaderOffset(): void
    {
        $module = $this->getInlineProfileJavaScriptSources();
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html');
        $this->assertIsString($module);
        $this->assertIsString($template);
        $this->assertStringContainsString('data-ie-sticky-image', $template);
        $this->assertStringContainsString('pageHeader.getBoundingClientRect().height', $module);
        $this->assertStringContainsString('typeof ResizeObserver === "function"', $module);
        $this->assertStringContainsString('new ResizeObserver(updateOffset)', $module);
        $this->assertStringContainsString(
            'resizeObserver.observe(pageHeader, { box: "border-box" });',
            $module,
        );
        $this->assertStringContainsString('globalThis.addEventListener("resize", updateOffset);', $module);
        $this->assertStringContainsString('initializeStickyImageOffset(root);', $module);
    }

    #[Test]
    public function editAllTogglesAllEditorsWithoutGlobalFooterActions(): void
    {
        $module = $this->getInlineProfileJavaScriptSources();
        $header = $this->getInlineProfilePartial('Header');
        $profileForm = $this->getInlineProfilePartial('Profile/Personal');
        $fluidSources = $this->getInlineProfileFluidSources();
        $this->assertIsString($module);
        $this->assertFileDoesNotExist(
            __DIR__ . '/../../../Resources/Private/Partials/InlineProfile/Profile/FooterActions.html',
        );
        $this->assertStringContainsString('data-ie-edit-all-label="{f:translate(', $header);
        $this->assertStringContainsString('data-ie-close-all-label="{f:translate(', $header);
        $this->assertStringContainsString('key: \'inlineProfile.btnCloseAll\'', $header);
        $this->assertStringContainsString('data-ie-edit-all-button-label', $header);
        $this->assertStringContainsString('data-ie-profile-header', $header);
        $this->assertStringContainsString('data-ie-profile-name', $header);
        $this->assertStringContainsString('data-ie-sync-form', $header);
        $this->assertStringContainsString('aria-pressed="false"', $header);
        $this->assertStringContainsString('key="inlineProfile.section.personal"', $profileForm);
        $this->assertStringNotContainsString('partial="InlineProfile/Header"', $profileForm);
        $this->assertStringNotContainsString('InlineProfile/Profile/FooterActions', $profileForm);
        $this->assertStringNotContainsString('data-ie-footer-button-area', $fluidSources);
        $this->assertStringNotContainsString('data-ie-cancel-all', $fluidSources);
        $this->assertStringContainsString('const setEditAllButtonState = (root, active) => {', $module);
        $this->assertStringContainsString(
            'button.setAttribute("aria-pressed", String(active));',
            $module,
        );
        $this->assertStringContainsString('editAllActive = !editAllActive;', $module);
        $this->assertStringContainsString('closeFields(root, fields);', $module);
        $this->assertStringContainsString(
            'form.addEventListener("submit", (event) => event.preventDefault());',
            $module,
        );
        $this->assertStringNotContainsString('footerButtonAreaSelector', $module);
        $this->assertStringNotContainsString('[data-ie-cancel-all]', $module);
        $this->assertStringNotContainsString('savesAllFields', $module);
    }

    #[Test]
    public function frontendModuleExposesScopedInlineEditingContracts(): void
    {
        $module = $this->getInlineProfileJavaScriptSources();
        $documentsModule = file_get_contents(
            __DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile/documents.js',
        );
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html');
        $fluidSources = $this->getInlineProfileFluidSources();
        $this->assertIsString($module);
        $this->assertIsString($documentsModule);
        $this->assertIsString($template);
        $this->assertStringContainsString('const documentController = createDocumentEditing(root);', $module);
        $this->assertStringContainsString('const imageController = createImageEditing(root);', $module);
        $this->assertStringContainsString('Array.from(root.querySelectorAll(fieldSelector))', $module);
        $this->assertStringContainsString('root.addEventListener("click"', $module);
        $this->assertStringContainsString('root.querySelector("[data-ie-status-toast]")', $module);
        $this->assertStringContainsString('"[data-ie-edit-button-template]"', $module);
        $this->assertStringContainsString('@ckeditor/ckeditor5-editor-classic', $module);
        $this->assertStringContainsString('const initialValue = createdEditor.getData();', $module);
        $this->assertStringContainsString('richTextInitialValues.set(field, initialValue);', $module);
        $this->assertStringContainsString('richTextAcceptedValues.set(field, initialValue);', $module);
        $this->assertStringContainsString(
            'updateRichTextCharacterCounter(root, field, initialValue);',
            $module,
        );
        $this->assertStringContainsString('persistedValues.set(field, initialValue)', $module);
        $this->assertStringContainsString('setFieldValue(field, "");', $module);
        $this->assertStringContainsString('toggleEditField(root, editButton.dataset.ieFor, true);', $module);
        $this->assertStringContainsString('toggleEditField(root, button.dataset.ieFor, true);', $module);
        $this->assertStringContainsString('toggleEditField(root, field.id, true);', $module);
        $this->assertStringContainsString('setFieldValue(field, persistedValues.get(field) ?? "");', $module);
        $this->assertStringContainsString('toggleEditField(root, field.id, false);', $module);
        $this->assertStringContainsString('renderRichTextPreview(root, field, fieldValue);', $module);
        $this->assertStringContainsString('const renderProfileName = (root) => {', $module);
        $this->assertStringContainsString('heading.dataset.ieProfileNameFieldIds', $module);
        $this->assertSame(2, substr_count($module, 'renderProfileName(root);'));
        $this->assertStringContainsString('content.replaceChildren(fragment);', $module);
        $this->assertStringContainsString('URL.createObjectURL(selectedFile);', $module);
        $this->assertStringContainsString('image.hasSelection = selectedFile !== null;', $module);
        $this->assertStringNotContainsString('fetch(persistedPreviewUrl', $module);
        $this->assertStringContainsString('const uploadFile = image.cropperRequested', $module);
        $this->assertStringContainsString(
            'await createCroppedImageFile(image.cropperEnabled ? cropper : null, file)',
            $module,
        );
        $this->assertStringContainsString(
            'formData.set(input.name, uploadFile, uploadFile.name);',
            $module,
        );
        $this->assertStringContainsString('commitUploadedPreview(', $module);
        $this->assertStringContainsString('result.imageAlternative,', $module);
        $this->assertStringContainsString('result.imageTitle', $module);
        $this->assertStringContainsString('const formData = new FormData(form);', $module);
        $this->assertStringContainsString('image.editing = true;', $module);
        $this->assertStringContainsString('imageEditorTargetSelector', $module);
        $this->assertStringContainsString('imagePreviewColumnSelector', $module);
        $this->assertStringContainsString('scrollIntoView({', $module);
        $this->assertStringContainsString('requestAnimationFrame(() => {', $module);
        $this->assertStringContainsString('focus({ preventScroll: true })', $module);
        $this->assertStringNotContainsString('initializeImageEditing', $module);
        $this->assertStringNotContainsString('initializeSkipSync', $module);
        $this->assertStringNotContainsString('setView("image");', $module);
        $this->assertStringNotContainsString('setView(', $module);
        $this->assertStringContainsString('documentState.open = true;', $module);
        $this->assertStringContainsString('documentState.target = getDocumentCollapseTargetSelector(', $module);
        $this->assertStringContainsString('template.querySelector(itemSelector)', $documentsModule);
        $this->assertStringNotContainsString('template.content.querySelector', $documentsModule);
        $this->assertStringContainsString(
            'section.dataset.sectionKind === "contract" ? "contract" : "document"',
            $module,
        );
        $this->assertStringNotContainsString('imageModalSelector', $module);
        $this->assertStringNotContainsString('bootstrap.Modal', $module);
        $this->assertDoesNotMatchRegularExpression('/\.innerHTML\s*=/', $module);
        $this->assertStringNotContainsString('nextElementSibling', $module);
        $this->assertSame(2, substr_count($fluidSources, 'data-ie-fields-form'));
        $this->assertStringContainsString('data-ie-content-fields-form', $fluidSources);
        $this->assertStringNotContainsString('data-ie-cancel-all', $fluidSources);
        $this->assertStringNotContainsString('InlineProfile/Profile/FooterActions', $fluidSources);
        $this->assertStringContainsString('data-user="{profile.uid}"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Image/Editor"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Documents/Editor"', $template);
        $this->assertStringContainsString(
            'data-ie-document-add-collapse-target',
            $fluidSources,
        );
        $this->assertStringContainsString(
            'data-ie-document-item-collapse-target',
            $fluidSources,
        );
        $this->assertSame(
            2,
            preg_match_all('@\bdata-ie-document-item-template(?=[\s>])@', $fluidSources),
        );
        $this->assertStringNotContainsString(
            '<template data-ie-document-item-template>',
            $fluidSources,
        );
        $this->assertStringNotContainsString('partial="InlineProfile/Image/Modal"', $template);
        $this->assertStringNotContainsString('partial="InlineProfile/Documents/Modal"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Header"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Profile/Personal"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Profile/About"', $template);
        $this->assertStringContainsString('academic-persons-inline-edit__image-preview-column', $template);
        $this->assertStringContainsString('data-ie-image-preview-column', $template);
    }

    #[Test]
    public function generatedFrontendEntryDelegatesToDedicatedFeatureModules(): void
    {
        $entry = file_get_contents(
            __DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile.js',
        );
        $this->assertIsString($entry);
        $this->assertStringContainsString(
            'Generated from Resources/Private/TypeScript',
            $entry,
        );
        foreach (['common', 'documents', 'fields', 'image', 'sticky-image', 'sync'] as $module) {
            $this->assertStringContainsString(
                '@fgtclb/academic-persons-edit/frontend/profile/' . $module . '.js',
                $entry,
            );
        }
        $this->assertStringNotContainsString('@ckeditor/', $entry);
        $this->assertFileExists(
            __DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile/rich-text.js',
        );
        $this->assertLessThan(80, substr_count($entry, "\n"));
    }

    #[Test]
    public function contentFieldsRenderDirectRichTextPreviewsAndThreeFieldActions(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $this->assertSame(
            5,
            preg_match_all('@(?<!:)\bdata-ie-rich-text(?=[\s=>])@', $content),
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
        $autosaveControlCount = substr_count($content, 'data-ie-autosave-on-change');
        $autosaveUndoCount = substr_count($content, 'data-ie-autosave-undo');
        $this->assertGreaterThanOrEqual(5, $fieldActionCount);
        $this->assertSame($autosaveControlCount, $autosaveUndoCount);
        $this->assertSame($fieldActionCount, substr_count($content, 'data-ie-dismiss'));
        $this->assertSame($fieldActionCount, substr_count($content, 'data-ie-save'));
        $this->assertSame(
            $fieldActionCount + $autosaveUndoCount,
            preg_match_all('@\bdata-ie-cancel(?=[\s>])@', $content),
        );
        $this->assertStringContainsString('Delete content', $content);
        $this->assertStringContainsString('Cancel', $content);
        $this->assertStringContainsString('Save', $content);
        $this->assertStringContainsString('No content', $content);
        $field = $this->getInlineProfilePartial('Field/Editable');
        $fields = $this->getInlineProfilePartial('Profile/Fields');
        $preview = $this->getInlineProfilePartial('Field/Preview');
        $control = $this->getInlineProfilePartial('Field/Control');
        $this->assertStringContainsString('<f:form.textarea', $control);
        $this->assertStringContainsString('<f:form.textfield', $control);
        $this->assertStringContainsString('data-ie-rich-text-preview', $preview);
        $this->assertStringContainsString('data-ie-rich-text-heading', $field);
        $this->assertStringContainsString(
            'arguments="{elementId: elementId, besideHeading: true}"',
            $field,
        );
        $this->assertSame(1, substr_count($field, 'besideHeading: true'));
        $this->assertSame(1, substr_count($field, 'besideHeading: false'));
        $this->assertSame(1, substr_count($fields, 'richText: true'));
        $this->assertSame(2, substr_count($fields, 'textarea: true'));
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $richTextControls = $xpath->query('//*[@data-ie-rich-text]');
        $this->assertNotFalse($richTextControls);
        foreach ($richTextControls as $richTextControl) {
            $headings = $xpath->query(
                'ancestor::*[@data-ie-field-editor][1]/*[@data-ie-rich-text-heading]',
                $richTextControl,
            );
            $this->assertNotFalse($headings);
            $this->assertCount(1, $headings);
            $heading = $headings->item(0);
            $this->assertInstanceOf(\DOMElement::class, $heading);
            $headingActions = $xpath->query('.//*[@data-ie-field-actions]', $heading);
            $this->assertNotFalse($headingActions);
            $this->assertCount(1, $headingActions);
        }
        $limitedControl = $xpath->query('//*[@id="inline-profile-1-miscellaneous"]');
        $this->assertNotFalse($limitedControl);
        $this->assertCount(1, $limitedControl);
        $this->assertSame(
            '1000',
            $limitedControl->item(0)?->attributes?->getNamedItem('data-ie-character-limit')?->nodeValue,
        );
        $characterCounters = $xpath->query(
            '//*[@data-ie-character-counter and @data-ie-for="inline-profile-1-miscellaneous"]',
        );
        $this->assertNotFalse($characterCounters);
        $this->assertCount(1, $characterCounters);
        $this->assertSame('0 / 1000', trim($characterCounters->item(0)?->textContent ?? ''));
    }

    #[Test]
    public function documentDragSortingUsesPointerPositionForInsertion(): void
    {
        $module = $this->getInlineProfileJavaScriptSources();
        $this->assertIsString($module);
        $this->assertStringContainsString('event.dataTransfer.setDragImage(row, offsetX, offsetY);', $module);
        $this->assertStringContainsString('bounds.top + bounds.height / 2', $module);
    }

    #[Test]
    public function profileFormsConsumeValidationFromTheirOwnVisualSection(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html');
        $profileForm = $this->getInlineProfilePartial('Profile/Personal');
        $contentForm = $this->getInlineProfilePartial('Profile/About');
        $this->assertIsString($template);
        $this->assertStringContainsString('section: profileSections.information', $template);
        $this->assertStringContainsString('section: profileSections.aboutme', $template);
        $this->assertStringContainsString('items: section.items', $profileForm);
        $this->assertStringContainsString('items: section.items', $contentForm);
        $this->assertStringNotContainsString('InlineProfile/Sections/Personal', $profileForm);
        $this->assertStringNotContainsString('InlineProfile/Sections/Content', $contentForm);
        $this->assertStringContainsString('data-profile-section="information"', $content);
        $this->assertStringContainsString('data-profile-section="aboutme"', $content);
    }

    #[Test]
    public function configuredProfileAndSpecialFieldsDriveTheRenderedInlineControls(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $this->assertStringContainsString('id="inline-profile-1-firstName"', $content);
        $this->assertStringContainsString('id="inline-profile-1-website"', $content);
        $this->assertStringContainsString('type="url"', $content);
        $this->assertStringContainsString(
            'data-ie-profile-name-field-ids="title firstName middleName lastName"',
            $content,
        );
        $this->assertStringContainsString('data-ie-sync-form', $content);
        $this->assertStringContainsString('data-ie-image-preview', $content);
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $previewHelptexts = $xpath->query(
            '//*[@data-ie-field-preview or @data-ie-group-preview]//*[@data-ie-helptext]',
        );
        $this->assertNotFalse($previewHelptexts);
        $this->assertCount(0, $previewHelptexts);
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        foreach ([
            'firstName' => 'helptext.firstName',
            'miscellaneous' => 'helptext.miscellaneous',
            'skipSync' => 'helptext.skipSync',
        ] as $identifier => $translationKey) {
            $helptexts = $xpath->query(sprintf(
                '//*[@data-ie-helptext and @data-ie-for="inline-profile-%d-%s"]',
                self::PROFILE_ID,
                $identifier,
            ));
            $this->assertNotFalse($helptexts);
            $this->assertGreaterThanOrEqual(1, $helptexts->length);
            foreach ($helptexts as $helptext) {
                $this->assertInstanceOf(\DOMElement::class, $helptext);
                $this->assertSame(
                    $languageService->sL(
                        'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:'
                            . $translationKey,
                    ),
                    $helptext->getAttribute('data-bs-content'),
                );
            }
        }
    }

    #[Test]
    public function profileNameAndGlobalControlsRenderAboveTheImageAndPersonalHeadings(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $headers = $xpath->query('//*[@data-ie-profile-header]');
        $this->assertNotFalse($headers);
        $this->assertCount(1, $headers);
        $header = $headers->item(0);
        $this->assertInstanceOf(\DOMElement::class, $header);
        foreach ([
            './/*[@data-ie-profile-name]',
            './/*[@data-ie-sync-form]',
            './/*[@data-academic-persons-inline-edit-edit-all-btn]',
        ] as $query) {
            $elements = $xpath->query($query, $header);
            $this->assertNotFalse($elements);
            $this->assertCount(1, $elements);
        }
        $imageHeading = $xpath->query('//*[@id="inline-profile-1-image-heading"]');
        $personalHeading = $xpath->query('//*[@id="inline-profile-1-personal-heading"]');
        $this->assertNotFalse($imageHeading);
        $this->assertNotFalse($personalHeading);
        $this->assertSame('Profile image', trim($imageHeading->item(0)?->textContent ?? ''));
        $this->assertSame('Personal data', trim($personalHeading->item(0)?->textContent ?? ''));
        $this->assertLessThan(
            strpos($content, 'inline-profile-1-image-heading'),
            strpos($content, 'data-ie-profile-header'),
        );
        $this->assertLessThan(
            strpos($content, 'inline-profile-1-personal-heading'),
            strpos($content, 'data-ie-profile-header'),
        );
        $this->assertStringContainsString('Back to profile overview', $content);
    }

    #[Test]
    public function editableSelectControlsUseExplicitFieldActions(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        foreach (['gender'] as $identifier) {
            $elementId = 'inline-profile-' . self::PROFILE_ID . '-' . $identifier;
            $controls = $xpath->query(sprintf('//*[@id="%s"]', $elementId));
            $this->assertNotFalse($controls);
            $this->assertSame(
                1,
                $controls->length,
                sprintf('Missing unique control for "%s".', $identifier),
            );
            $control = $controls->item(0);
            $this->assertInstanceOf(\DOMElement::class, $control);
            $this->assertFalse(
                $control->hasAttribute('disabled'),
                sprintf('Editable control "%s" must not be rendered disabled.', $identifier),
            );
            $this->assertFalse(
                $control->hasAttribute('data-ie-autosave-on-change'),
                sprintf('Editable control "%s" must not opt into autosave-on-change.', $identifier),
            );
            $undoButtons = $xpath->query(sprintf(
                '//*[@data-ie-autosave-undo and @data-ie-cancel and @data-ie-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($undoButtons);
            $this->assertSame(
                0,
                $undoButtons->length,
                sprintf('Unexpected autosave undo button for "%s".', $identifier),
            );
            $fieldActions = $xpath->query(sprintf(
                '//*[@data-ie-field-actions and @data-ie-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($fieldActions);
            $this->assertSame(1, $fieldActions->length, sprintf('Missing field actions for "%s".', $identifier));
            foreach (['data-ie-dismiss', 'data-ie-cancel', 'data-ie-save'] as $actionAttribute) {
                $actionButtons = $xpath->query(sprintf(
                    '//*[@%s and @data-ie-for="%s"]',
                    $actionAttribute,
                    $elementId,
                ));
                $this->assertNotFalse($actionButtons);
                $this->assertSame(
                    1,
                    $actionButtons->length,
                    sprintf('Missing "%s" action for "%s".', $actionAttribute, $identifier),
                );
            }
            $editButtons = $xpath->query(sprintf(
                '//*[@data-academic-persons-inline-edit-activate-btn and @data-ie-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($editButtons);
            $this->assertSame(1, $editButtons->length, sprintf('Missing edit button for "%s".', $identifier));
            $editButton = $editButtons->item(0);
            $this->assertInstanceOf(\DOMElement::class, $editButton);
            $this->assertSame($elementId . '-editor', $editButton->getAttribute('aria-controls'));
            $editors = $xpath->query(sprintf('//*[@id="%s-editor" and @data-ie-field-editor]', $elementId));
            $this->assertNotFalse($editors);
            $this->assertSame(1, $editors->length, sprintf('Missing editor for "%s".', $identifier));
        }
    }

    #[Test]
    public function structuredDocumentSectionsHonorConfiguredRowsActionsAndReadonlyState(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderInlineProfilePage();
        $aboutPosition = strpos($content, 'inline-profile-1-about-heading');
        $contractsPosition = strpos($content, 'data-section-key="contracts"');
        $this->assertIsInt($aboutPosition);
        $this->assertIsInt($contractsPosition);
        $this->assertGreaterThan($aboutPosition, $contractsPosition);
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $sections = $xpath->query('//*[@data-ie-document-section]');
        $this->assertNotFalse($sections);
        $documentSections = $this->get(AcademicPersonsSettingsFactory::class)->get()->documentSections;
        $this->assertCount(count($documentSections), $sections);
        $sectionKeys = [];
        $configuredSections = array_values($documentSections);
        foreach ($sections as $position => $section) {
            $sectionKeys[] = $section->attributes?->getNamedItem('data-section-key')?->nodeValue;
            $this->assertSame(
                (string)$position,
                $section->attributes?->getNamedItem('data-section-position')?->nodeValue,
            );
            $addButtons = $xpath->query('.//button[@data-ie-document-add]', $section);
            $this->assertNotFalse($addButtons);
            $sectionSettings = $configuredSections[$position];
            $this->assertCount($sectionSettings->allowsCreate() ? 1 : 0, $addButtons);
            $items = $xpath->query('.//*[@data-ie-document-items]/*[@data-ie-document-item]', $section);
            $this->assertNotFalse($items);
            $listHeaders = $xpath->query('.//*[@data-ie-document-list-header]', $section);
            $this->assertNotFalse($listHeaders);
            $this->assertCount(1, $listHeaders);
            foreach ($items as $item) {
                $actions = $xpath->query('.//*[@data-ie-document-actions]/button', $item);
                $this->assertNotFalse($actions);
                $this->assertCount(
                    count($sectionSettings->getAllowedActions()) + ($sectionSettings->allowsDragSorting() ? 1 : 0),
                    $actions,
                );
                $actualActions = [];
                foreach ($actions as $actionElement) {
                    $this->assertInstanceOf(\DOMElement::class, $actionElement);
                    $actualActions[] = match (true) {
                        $actionElement->hasAttribute('data-ie-document-drag') => 'drag',
                        $actionElement->hasAttribute('data-ie-document-view') => 'view',
                        $actionElement->hasAttribute('data-ie-document-sort') => $actionElement->getAttribute('data-ie-document-sort'),
                        $actionElement->hasAttribute('data-ie-document-delete') => 'delete',
                        $actionElement->hasAttribute('data-ie-document-edit') => 'edit',
                        default => 'unknown',
                    };
                }
                $expectedActions = $sectionSettings->allowsDragSorting() ? ['drag'] : [];
                array_push($expectedActions, ...$sectionSettings->getAllowedActions());
                $this->assertSame($expectedActions, $actualActions);
                $rowValueElements = $xpath->query('.//*[@data-ie-document-value or @data-ie-document-title]', $item);
                $this->assertNotFalse($rowValueElements);
                $actualRowFields = [];
                foreach ($rowValueElements as $rowValueElement) {
                    $this->assertInstanceOf(\DOMElement::class, $rowValueElement);
                    $actualRowFields[] = $rowValueElement->hasAttribute('data-ie-document-title')
                        ? 'title'
                        : $rowValueElement->getAttribute('data-ie-document-value');
                }
                $expectedRowFields = array_map(
                    static fn(string $field): string => $sectionSettings->isContractSection()
                        ? match ($field) {
                            'from' => 'validFrom',
                            'to' => 'validTo',
                            default => $field,
                        }
                    : match ($field) {
                        'from' => 'yearStart',
                        'to' => 'yearEnd',
                        'description' => 'bodytext',
                        default => $field,
                    },
                    $sectionSettings->rowFields,
                );
                $this->assertSame($expectedRowFields, $actualRowFields);
            }
            $this->assertSame(
                $sectionSettings->fieldName,
                $section->attributes->getNamedItem('data-section-field-name')?->nodeValue,
            );
            $this->assertSame(
                $sectionSettings->type,
                $section->attributes->getNamedItem('data-section-record-type')?->nodeValue,
            );
            $this->assertSame(
                $sectionSettings->readOnly ? '1' : '0',
                $section->attributes->getNamedItem('data-section-readonly')?->nodeValue,
            );
            $this->assertSame(
                $sectionSettings->allowsDragSorting() ? '1' : '0',
                $section->attributes->getNamedItem('data-section-sortable')?->nodeValue,
            );
        }
        $this->assertSame(array_keys($documentSections), $sectionKeys);
        $documentsPartial = $this->getInlineProfilePartial('Documents/Sections');
        $this->assertStringContainsString('key="{section.label}"', $documentsPartial);
        $this->assertStringNotContainsString('key="profile.{section.identifier}"', $documentsPartial);
        $this->assertStringContainsString('data-ie-document-add', $documentsPartial);
        $actionsPartial = $this->getInlineProfilePartial('Documents/Actions');
        foreach ([
            'academic-persons-inline-edit-grip',
            'academic-persons-inline-edit-open',
            'academic-persons-inline-edit-down',
            'academic-persons-inline-edit-up',
            'academic-persons-inline-edit-delete',
            'academic-persons-inline-edit-pencil',
        ] as $iconIdentifier) {
            $this->assertStringContainsString($iconIdentifier, $actionsPartial);
        }
        $actionPositions = array_map(
            static fn(string $hook): int|false => strpos($actionsPartial, $hook),
            [
                'data-ie-document-view',
                'data-ie-document-sort="down"',
                'data-ie-document-sort="up"',
                'data-ie-document-delete',
                'data-ie-document-edit',
            ],
        );
        foreach ($actionPositions as $actionPosition) {
            $this->assertIsInt($actionPosition);
        }
        $this->assertSame($actionPositions, array_values(array_unique($actionPositions)));
        $sortedActionPositions = $actionPositions;
        sort($sortedActionPositions);
        $this->assertSame($sortedActionPositions, $actionPositions);
        $contractItems = $xpath->query(
            '//*[@data-section-key="contracts"]//*[@data-ie-document-items]/*[@data-ie-document-item]',
        );
        $this->assertNotFalse($contractItems);
        $this->assertCount(2, $contractItems);
        foreach ($contractItems as $contractItem) {
            $contractActions = $xpath->query('.//*[@data-ie-document-actions]/button', $contractItem);
            $this->assertNotFalse($contractActions);
            $this->assertCount(6, $contractActions);
            $this->assertTrue($contractActions->item(0)?->attributes?->getNamedItem('data-ie-document-drag') !== null);
            $this->assertTrue($contractActions->item(1)?->attributes?->getNamedItem('data-ie-document-view') !== null);
            $this->assertSame('down', $contractActions->item(2)?->attributes?->getNamedItem('data-ie-document-sort')?->nodeValue);
            $this->assertSame('up', $contractActions->item(3)?->attributes?->getNamedItem('data-ie-document-sort')?->nodeValue);
            $this->assertTrue($contractActions->item(4)?->attributes?->getNamedItem('data-ie-document-delete') !== null);
            $this->assertTrue($contractActions->item(5)?->attributes?->getNamedItem('data-ie-document-edit') !== null);
        }
        $this->assertSame('10', $contractItems->item(0)?->attributes?->getNamedItem('data-item-sorting')?->nodeValue);
        $emptyPressMedia = $xpath->query('//*[@data-section-key="pressMedia"]//*[@data-ie-document-empty-state]');
        $this->assertNotFalse($emptyPressMedia);
        $this->assertCount(1, $emptyPressMedia);
        $expectedValues = [
            'Rectorate',
            'Professor',
            'Cooperation first',
            'Social work lecture',
            'Biodiversity monitoring association',
            'Vita first',
            'Publication first',
            'Research first',
        ];
        foreach ($expectedValues as $expectedValue) {
            $this->assertStringContainsString($expectedValue, $content);
        }
        $this->assertStringContainsString('No press releases have been added yet.', $content);
    }

    #[Test]
    public function profileInformationDocumentAjaxActionsCoverFormCreateUpdateSortAndDelete(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderInlineProfilePage();
        $formUrl = $this->extractDataUrl($content, 'data-document-form-url');
        $createUrl = $this->extractDataUrl($content, 'data-create-document-url');
        $updateUrl = $this->extractDataUrl($content, 'data-update-document-url');
        $sortUrl = $this->extractDataUrl($content, 'data-sort-document-url');
        $deleteUrl = $this->extractDataUrl($content, 'data-delete-document-url');
        $formResponse = $this->postJson($formUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'cooperation', 'record' => 1, 'mode' => 'edit'],
        ]);
        $this->assertSame(200, $formResponse->getStatusCode(), (string)$formResponse->getBody());
        $formBody = json_decode((string)$formResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($formBody['success'] ?? null);
        $this->assertSame(1, $formBody['record'] ?? null);
        $this->assertSame(
            ['title', 'link', 'year', 'yearStart', 'yearEnd', 'yearOnly', 'bodytext'],
            array_column($formBody['fields'] ?? [], 'name'),
        );
        $formFieldsByName = array_column($formBody['fields'] ?? [], null, 'name');
        $this->assertTrue($formFieldsByName['bodytext']['richText'] ?? false);
        $this->assertSame(500, $formFieldsByName['bodytext']['characterLimit'] ?? null);
        $this->assertSame('date', $formFieldsByName['year']['type'] ?? null);
        $this->assertTrue($formFieldsByName['year']['required'] ?? false);
        $this->assertFalse($formFieldsByName['yearStart']['required'] ?? true);
        $this->assertFalse($formFieldsByName['yearEnd']['required'] ?? true);
        foreach (['year', 'yearStart', 'yearEnd', 'yearOnly'] as $dateField) {
        }
        $this->assertTrue($formFieldsByName['yearOnly']['compactCheckbox'] ?? false);
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        foreach ([
            'title' => 'title',
            'year' => 'year',
            'yearStart' => 'from',
            'yearEnd' => 'to',
            'bodytext' => 'description',
        ] as $helptextField => $translationKey) {
            $this->assertSame(
                $languageService->sL(
                    'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:'
                        . 'helptext.documentSections.' . $translationKey,
                ),
                $formFieldsByName[$helptextField]['helptext'] ?? null,
                sprintf('Missing translated helptext for document field "%s".', $helptextField),
            );
        }
        foreach (['link', 'yearOnly'] as $fieldWithoutHelptext) {
            $this->assertSame('', $formFieldsByName[$fieldWithoutHelptext]['helptext'] ?? null);
        }
        $missingYearResponse = $this->postJson($createUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'fields' => ['title' => 'Missing required date'],
            ],
        ]);
        $this->assertSame(422, $missingYearResponse->getStatusCode());
        $missingYearBody = json_decode(
            (string)$missingYearResponse->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertArrayHasKey('year', $missingYearBody['errors'] ?? []);
        $this->assertArrayNotHasKey('yearStart', $missingYearBody['errors'] ?? []);
        $this->assertArrayNotHasKey('yearEnd', $missingYearBody['errors'] ?? []);
        $overLimitResponse = $this->postJson($createUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'fields' => [
                    'title' => 'Description over its configured limit',
                    'year' => '2027-01-01',
                    'bodytext' => '<p><strong>' . str_repeat('a', 501) . '</strong></p>',
                ],
            ],
        ]);
        $this->assertSame(422, $overLimitResponse->getStatusCode());
        $overLimitBody = json_decode(
            (string)$overLimitResponse->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame(
            ['The text must not exceed 500 characters.'],
            $overLimitBody['errors']['bodytext'] ?? null,
        );
        $createResponse = $this->postJson($createUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'fields' => [
                    'title' => 'AJAX cooperation',
                    'link' => 'https://example.com/ajax-cooperation',
                    'year' => '2027-01-01',
                    'yearOnly' => true,
                    'bodytext' => '<p><strong>Created inline</strong></p>',
                ],
            ],
        ]);
        $this->assertSame(200, $createResponse->getStatusCode(), (string)$createResponse->getBody());
        $createBody = json_decode((string)$createResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $createdUid = (int)($createBody['item']['uid'] ?? 0);
        $this->assertGreaterThan(0, $createdUid, (string)$createResponse->getBody());
        $this->assertSame('AJAX cooperation', $createBody['item']['display']['title'] ?? null);
        $storedCreatedRecord = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile_information')
            ->executeQuery(
                'SELECT profile, type, title, year, year_start, year_end, year_only, sorting'
                    . ' FROM tx_academicpersons_domain_model_profile_information'
                    . ' WHERE uid = ? AND deleted = 0',
                [$createdUid],
            )
            ->fetchAssociative();
        $this->assertIsArray($storedCreatedRecord);
        $this->assertSame(self::PROFILE_ID, (int)$storedCreatedRecord['profile']);
        $this->assertSame('cooperation', $storedCreatedRecord['type']);
        $this->assertSame('AJAX cooperation', $storedCreatedRecord['title']);
        $this->assertSame('2027-01-01', $storedCreatedRecord['year']);
        $this->assertNull($storedCreatedRecord['year_start']);
        $this->assertNull($storedCreatedRecord['year_end']);
        $this->assertSame(1, (int)$storedCreatedRecord['year_only']);
        $this->assertSame(30, (int)$storedCreatedRecord['sorting']);
        $updateResponse = $this->postJson($updateUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'record' => $createdUid,
                'fields' => [
                    'title' => 'AJAX cooperation updated',
                    'bodytext' => '<p><strong>Updated inline</strong><script>alert(1)</script></p>',
                ],
            ],
        ]);
        $this->assertSame(200, $updateResponse->getStatusCode(), (string)$updateResponse->getBody());
        $updateBody = json_decode((string)$updateResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('AJAX cooperation updated', $updateBody['item']['display']['title'] ?? null);
        $this->assertStringContainsString('<strong>Updated inline</strong>', $updateBody['item']['display']['bodytext'] ?? '');
        $this->assertStringNotContainsString('<script', $updateBody['item']['display']['bodytext'] ?? '');
        $sortResponse = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'record' => $createdUid,
                'direction' => 'up',
            ],
        ]);
        $this->assertSame(200, $sortResponse->getStatusCode(), (string)$sortResponse->getBody());
        $sortBody = json_decode((string)$sortResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([1, $createdUid, 2], $sortBody['order'] ?? null);
        $dragSortResponse = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'order' => [$createdUid, 2, 1],
            ],
        ]);
        $this->assertSame(200, $dragSortResponse->getStatusCode(), (string)$dragSortResponse->getBody());
        $dragSortBody = json_decode((string)$dragSortResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([$createdUid, 2, 1], $dragSortBody['order'] ?? null);
        $incompleteDragSortResponse = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'order' => [$createdUid, 2],
            ],
        ]);
        $this->assertSame(400, $incompleteDragSortResponse->getStatusCode());
        $wrongSectionResponse = $this->postJson($updateUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'publications',
                'record' => $createdUid,
                'fields' => ['title' => 'Must not be persisted'],
            ],
        ]);
        $this->assertSame(404, $wrongSectionResponse->getStatusCode());
        $deleteResponse = $this->postJson($deleteUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'cooperation', 'record' => $createdUid],
        ]);
        $this->assertSame(200, $deleteResponse->getStatusCode(), (string)$deleteResponse->getBody());
        $deleteBody = json_decode((string)$deleteResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($createdUid, $deleteBody['deleted'] ?? null);
        $activeCount = (int)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile_information')
            ->executeQuery(
                'SELECT COUNT(*) FROM tx_academicpersons_domain_model_profile_information'
                    . ' WHERE uid = ? AND deleted = 0',
                [$createdUid],
            )
            ->fetchOne();
        $this->assertSame(0, $activeCount);
    }

    #[Test]
    public function contractDocumentAjaxActionsCoverFormCreateUpdateSortAndDelete(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderInlineProfilePage();
        $formUrl = $this->extractDataUrl($content, 'data-document-form-url');
        $createUrl = $this->extractDataUrl($content, 'data-create-document-url');
        $updateUrl = $this->extractDataUrl($content, 'data-update-document-url');
        $sortUrl = $this->extractDataUrl($content, 'data-sort-document-url');
        $deleteUrl = $this->extractDataUrl($content, 'data-delete-document-url');
        $formResponse = $this->postJson($formUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => 1, 'mode' => 'view'],
        ]);
        $this->assertSame(200, $formResponse->getStatusCode(), (string)$formResponse->getBody());
        $formBody = json_decode((string)$formResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('contract', $formBody['kind'] ?? null);
        $this->assertSame(
            [
                'position',
                'organisationalUnit',
                'functionType',
                'validFrom',
                'validTo',
                'location',
                'room',
                'officeHours',
                'publish',
            ],
            array_column($formBody['fields'] ?? [], 'name'),
        );
        $contractFieldsByName = array_column($formBody['fields'] ?? [], null, 'name');
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        foreach (array_keys($contractFieldsByName) as $fieldName) {
            $translationFieldName = match ($fieldName) {
                'validFrom' => 'validFrom',
                'validTo' => 'validTo',
                default => $fieldName,
            };
            $this->assertSame(
                $languageService->sL(
                    'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:'
                        . 'helptext.contracts.' . $translationFieldName,
                ),
                $contractFieldsByName[$fieldName]['helptext'] ?? null,
                sprintf('Missing translated helptext for Contract field "%s".', $fieldName),
            );
        }
        $this->assertTrue($contractFieldsByName['officeHours']['richText'] ?? false);
        $this->assertSame(
            ['physicalAddresses', 'emailAddresses', 'phoneNumbers'],
            array_column($formBody['contactSections'] ?? [], 'identifier'),
        );
        $this->assertSame(
            'Campus Road 14',
            $formBody['contactSections'][0]['items'][0]['summary'][0]['value'] ?? null,
        );
        $editFormResponse = $this->postJson($formUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => 1, 'mode' => 'edit'],
        ]);
        $this->assertSame(200, $editFormResponse->getStatusCode(), (string)$editFormResponse->getBody());
        $addFormResponse = $this->postJson($formUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => 0, 'mode' => 'add'],
        ]);
        $this->assertSame(200, $addFormResponse->getStatusCode(), (string)$addFormResponse->getBody());
        $createResponse = $this->postJson($createUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'contracts',
                'fields' => [
                    'position' => 'Visiting professor',
                    'organisationalUnit' => '',
                    'functionType' => '',
                    'validFrom' => '2026-01-15',
                    'validTo' => '2026-12-15',
                    'location' => '',
                    'room' => 'B 1.23',
                    'officeHours' => '<p>By appointment</p>',
                    'publish' => true,
                ],
            ],
        ]);
        $this->assertSame(200, $createResponse->getStatusCode(), (string)$createResponse->getBody());
        $createBody = json_decode((string)$createResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $createdUid = $createBody['item']['uid'] ?? null;
        $this->assertIsInt($createdUid);
        $this->assertGreaterThan(0, $createdUid);
        $updateResponse = $this->postJson($updateUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'contracts',
                'record' => $createdUid,
                'fields' => ['position' => 'Guest professor'],
            ],
        ]);
        $this->assertSame(200, $updateResponse->getStatusCode(), (string)$updateResponse->getBody());
        $updateBody = json_decode((string)$updateResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Guest professor', $updateBody['item']['display']['position'] ?? null);
        $sortResponse = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => $createdUid, 'direction' => 'up'],
        ]);
        $this->assertSame(200, $sortResponse->getStatusCode(), (string)$sortResponse->getBody());
        $sortBody = json_decode((string)$sortResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([1, $createdUid, 2], $sortBody['order'] ?? null);
        $deleteResponse = $this->postJson($deleteUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => $createdUid],
        ]);
        $this->assertSame(200, $deleteResponse->getStatusCode(), (string)$deleteResponse->getBody());
        $this->assertSame(
            0,
            (int)$this->getConnectionPool()
                ->getConnectionForTable('tx_academicpersons_domain_model_contract')
                ->executeQuery(
                    'SELECT COUNT(*) FROM tx_academicpersons_domain_model_contract'
                        . ' WHERE uid = ? AND deleted = 0',
                    [$createdUid],
                )
                ->fetchOne(),
        );
    }

    #[Test]
    public function contractContactAjaxActionsCoverAddressEmailAndPhoneNumberCrud(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderInlineProfilePage();
        $formUrl = $this->extractDataUrl($content, 'data-contract-contact-form-url');
        $createUrl = $this->extractDataUrl($content, 'data-create-contract-contact-url');
        $updateUrl = $this->extractDataUrl($content, 'data-update-contract-contact-url');
        $deleteUrl = $this->extractDataUrl($content, 'data-delete-contract-contact-url');
        $sortUrl = $this->extractDataUrl($content, 'data-sort-contract-contact-url');
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        /** @var array<string, array{table: string, field: string, fields: array<string, string>, updatedValue: string}> $cases */
        $cases = [
            'physicalAddresses' => [
                'table' => 'tx_academicpersons_domain_model_address',
                'field' => 'street',
                'fields' => [
                    'street' => 'New Road',
                    'streetNumber' => '12a',
                    'additional' => 'Third floor',
                    'zip' => '41061',
                    'city' => 'Mönchengladbach',
                    'state' => 'NRW',
                    'country' => 'DE',
                    'type' => 'business',
                ],
                'updatedValue' => 'Updated Road',
            ],
            'emailAddresses' => [
                'table' => 'tx_academicpersons_domain_model_email',
                'field' => 'email',
                'fields' => ['email' => 'new@example.com', 'type' => 'business'],
                'updatedValue' => 'updated@example.com',
            ],
            'phoneNumbers' => [
                'table' => 'tx_academicpersons_domain_model_phone_number',
                'field' => 'phoneNumber',
                'fields' => ['phoneNumber' => '+49 2161 654321', 'type' => 'mobile'],
                'updatedValue' => '+49 2161 999999',
            ],
        ];
        foreach ($cases as $section => $case) {
            $addFormResponse = $this->postJson($formUrl, [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'contract' => 1,
                    'section' => $section,
                    'record' => 0,
                    'mode' => 'add',
                ],
            ]);
            $this->assertSame(200, $addFormResponse->getStatusCode(), (string)$addFormResponse->getBody());
            $addFormBody = json_decode(
                (string)$addFormResponse->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $contactFieldsByName = array_column($addFormBody['fields'] ?? [], null, 'name');
            foreach ($contactFieldsByName as $fieldName => $field) {
                $this->assertNotSame(
                    '',
                    $field['helptext'] ?? '',
                    sprintf('Missing translated helptext for %s field "%s".', $section, $fieldName),
                );
            }
            if ($section === 'physicalAddresses') {
                $this->assertSame('select', $contactFieldsByName['country']['type'] ?? null);
                $countryOptions = array_column(
                    $contactFieldsByName['country']['options'] ?? [],
                    null,
                    'value',
                );
                $this->assertSame(
                    $languageService->sL('LLL:EXT:core/Resources/Private/Language/Iso/countries.xlf:DE.name'),
                    $countryOptions['DE']['label'] ?? null,
                );
                $invalidCountryFields = $case['fields'];
                $invalidCountryFields['country'] = 'ZZ';
                $invalidCountryResponse = $this->postJson($createUrl, [
                    'profile' => self::PROFILE_ID,
                    'data' => [
                        'contract' => 1,
                        'section' => $section,
                        'fields' => $invalidCountryFields,
                    ],
                ]);
                $this->assertSame(422, $invalidCountryResponse->getStatusCode());
                $invalidCountryBody = json_decode(
                    (string)$invalidCountryResponse->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
                $this->assertSame(
                    ['The selected value is not available.'],
                    $invalidCountryBody['errors']['country'] ?? null,
                );
            }
            $createResponse = $this->postJson($createUrl, [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'contract' => 1,
                    'section' => $section,
                    'fields' => $case['fields'],
                ],
            ]);
            $this->assertSame(200, $createResponse->getStatusCode(), (string)$createResponse->getBody());
            $createBody = json_decode((string)$createResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $createdUid = $createBody['item']['uid'] ?? null;
            $this->assertIsInt($createdUid);
            $updatedFields = $case['fields'];
            $updatedFields[$case['field']] = $case['updatedValue'];
            $updateResponse = $this->postJson($updateUrl, [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'contract' => 1,
                    'section' => $section,
                    'record' => $createdUid,
                    'fields' => $updatedFields,
                ],
            ]);
            $this->assertSame(200, $updateResponse->getStatusCode(), (string)$updateResponse->getBody());
            $updateBody = json_decode((string)$updateResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($case['updatedValue'], $updateBody['item']['values'][$case['field']] ?? null);
            $viewResponse = $this->postJson($formUrl, [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'contract' => 1,
                    'section' => $section,
                    'record' => $createdUid,
                    'mode' => 'view',
                ],
            ]);
            $this->assertSame(200, $viewResponse->getStatusCode(), (string)$viewResponse->getBody());
            $viewBody = json_decode((string)$viewResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($case['updatedValue'], $viewBody['fields'][0]['value'] ?? null);
            $sortResponse = $this->postJson($sortUrl, [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'contract' => 1,
                    'section' => $section,
                    'record' => $createdUid,
                    'direction' => 'up',
                ],
            ]);
            $this->assertSame(200, $sortResponse->getStatusCode(), (string)$sortResponse->getBody());
            $sortBody = json_decode((string)$sortResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([$createdUid, 1], $sortBody['order'] ?? null);
            $deleteResponse = $this->postJson($deleteUrl, [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'contract' => 1,
                    'section' => $section,
                    'record' => $createdUid,
                ],
            ]);
            $this->assertSame(200, $deleteResponse->getStatusCode(), (string)$deleteResponse->getBody());
            $this->assertSame(
                0,
                (int)$this->getConnectionPool()
                    ->getConnectionForTable($case['table'])
                    ->executeQuery(
                        sprintf('SELECT COUNT(*) FROM %s WHERE uid = ? AND deleted = 0', $case['table']),
                        [$createdUid],
                    )
                    ->fetchOne(),
            );
        }
        $otherContractContactResponse = $this->postJson($formUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'contract' => 1,
                'section' => 'emailAddresses',
                'record' => 2,
                'mode' => 'view',
            ],
        ]);
        $this->assertSame(404, $otherContractContactResponse->getStatusCode());
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
        $this->assertIsArray($body, (string)$response->getBody());
        $this->assertTrue($body['success'] ?? null, (string)$response->getBody());
        $this->assertIsArray($body['data'] ?? null, (string)$response->getBody());
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
    public function richTextAjaxUpdateRejectsConfiguredProfileCharacterLimit(): void
    {
        $this->setUpInlineProfileTestCase();
        $connection = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile');
        $persistedValue = (string)$connection->executeQuery(
            'SELECT miscellaneous FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
            [self::PROFILE_ID],
        )->fetchOne();
        $updateUrl = $this->extractDataUrl($this->renderInlineProfilePage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            [
                'profile' => self::PROFILE_ID,
                'data' => [
                    'miscellaneous' => '<p><strong>' . str_repeat('a', 1001) . '</strong></p>',
                ],
            ],
        );
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(422, $response->getStatusCode(), (string)$response->getBody());
        $this->assertFalse($body['success'] ?? null);
        $this->assertSame('validation_failed', $body['error'] ?? null);
        $this->assertSame(
            ['The text must not exceed %d characters.'],
            $body['errors']['miscellaneous'] ?? null,
        );
        $this->assertSame(
            $persistedValue,
            (string)$connection->executeQuery(
                'SELECT miscellaneous FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                [self::PROFILE_ID],
            )->fetchOne(),
        );
    }

    #[Test]
    public function inlineUpdateRejectsAProfileFieldMarkedReadOnlyInItsSection(): void
    {
        $this->setUpInlineProfileTestCase();
        $updateUrl = $this->extractDataUrl($this->renderInlineProfilePage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            ['profile' => self::PROFILE_ID, 'data' => ['firstName' => 'Manipulated']],
        );
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(422, $response->getStatusCode(), (string)$response->getBody());
        $this->assertFalse($body['success']);
        $this->assertSame('invalid_profile_data', $body['error']);
        $this->assertSame('Unknown profile property "firstName".', $body['message']);
        $storedValue = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT first_name FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                [self::PROFILE_ID],
            )
            ->fetchOne();
        $this->assertSame('Max', $storedValue);
    }

    #[Test]
    public function inlineUpdatePropagatesSectionValidationErrorsWithStatus422(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update(
                'tx_academicpersons_domain_model_profile',
                ['gender' => 'ms'],
                ['uid' => self::PROFILE_ID],
            );
        $updateUrl = $this->extractDataUrl($this->renderInlineProfilePage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            ['profile' => self::PROFILE_ID, 'data' => ['gender' => '']],
        );
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);
        $this->assertSame(422, $response->getStatusCode(), (string)$response->getBody());
        $this->assertFalse($body['success']);
        $this->assertSame('validation_failed', $body['error']);
        $this->assertIsArray($body['errors'] ?? null);
        $this->assertNotEmpty($body['errors']['gender'] ?? []);
        $storedValue = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT gender FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                [self::PROFILE_ID],
            )
            ->fetchOne();
        $this->assertSame('ms', $storedValue);
    }

    #[Test]
    public function inlineAcademicTitleUpdatePersistsTheConfiguredNameComponent(): void
    {
        $this->setUpInlineProfileTestCase();
        $updateUrl = $this->extractDataUrl($this->renderInlineProfilePage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            ['profile' => self::PROFILE_ID, 'data' => ['title' => 'Prof. Dr.']],
        );
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['title' => 'Prof. Dr.'], $body['data'] ?? null);
        $storedTitle = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT title FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                [self::PROFILE_ID],
            )
            ->fetchOne();
        $this->assertSame('Prof. Dr.', $storedTitle);
    }

    #[Test]
    public function inlinePluginDoesNotRegisterLegacyRedirectImageActions(): void
    {
        $file = __DIR__ . '/../../../ext_localconf.php';
        $configuration = file_get_contents($file);
        $this->assertIsString($configuration);
        $inlineConfiguration = strstr($configuration, "'InlineProfile',");
        $this->assertIsString($inlineConfiguration);
        $this->assertMatchesRegularExpression(
            "@InlineProfileController::class => implode\\(',', \\[\\s*'list',\\s*'index',@",
            $inlineConfiguration,
        );
        $this->assertStringContainsString("'uploadImage'", $inlineConfiguration);
        $this->assertStringContainsString("'deleteImage'", $inlineConfiguration);
        foreach ([
            'documentForm',
            'createDocument',
            'updateDocument',
            'deleteDocument',
            'sortDocument',
            'contractContactForm',
            'createContractContact',
            'updateContractContact',
            'deleteContractContact',
            'sortContractContact',
        ] as $documentAction) {
            $this->assertStringContainsString(sprintf("'%s'", $documentAction), $inlineConfiguration);
        }
        foreach (['editImage', 'addImage', 'removeImage', 'toggleSkipSync'] as $legacyAction) {
            $this->assertStringNotContainsString(sprintf("'%s'", $legacyAction), $inlineConfiguration);
        }
        $legacyControllers = [
            'ProfileInformationController',
            'ContractController',
            'PhysicalAddressController',
            'EmailAddressController',
            'PhoneNumberController',
        ];
        foreach ($legacyControllers as $legacyController) {
            $this->assertStringNotContainsString($legacyController . '::class', $inlineConfiguration);
        }
    }

    #[Test]
    public function inlinePluginTestSetupDoesNotBootstrapProfileEditing(): void
    {
        $this->assertSame(
            AbstractFrontendProfilePluginTestCase::class,
            get_parent_class(self::class),
        );
        $fixture = file_get_contents(
            __DIR__ . '/Fixtures/AcademicPersonsEditInlineProfile/inlineProfilePage.csv',
        );
        $this->assertIsString($fixture);
        $this->assertStringContainsString('academicpersonsedit_inlineprofile', $fixture);
        $this->assertStringNotContainsString('academicpersonsedit_profileediting', $fixture);
    }

    #[Test]
    public function profileWithoutImageRendersInlineEditorHooksAndDedicatedAjaxUrls(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $decodedContent = urldecode(html_entity_decode($content));
        $this->assertStringContainsString('data-academic-persons-inline-edit', $content);
        $this->assertStringContainsString('data-user="1"', $content);
        $this->assertStringContainsString('data-ie-open-image-view', $content);
        $this->assertStringContainsString('data-ie-image-view-container', $content);
        $this->assertStringContainsString('data-ie-image-editor-target', $content);
        $this->assertStringContainsString('data-image-render-type="cropper"', $content);
        $this->assertStringContainsString('data-has-image="0"', $content);
        $configuredRatio = $this->get(AcademicPersonsSettingsFactory::class)
            ->get()
            ->raw['special']['image']['settings']['ratio'] ?? null;
        $this->assertIsString($configuredRatio);
        $this->assertNotSame('', trim($configuredRatio));
        $this->assertStringContainsString(
            'data-image-cropper-ratio="'
                . htmlspecialchars($configuredRatio, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '"',
            $content,
        );
        $this->assertStringNotContainsString('data-ie-image-modal', $content);
        $this->assertStringNotContainsString('data-ie-document-modal', $content);
        $this->assertStringNotContainsString('data-bs-toggle="modal"', $content);
        $this->assertStringNotContainsString('<dialog', $content);
        $this->assertMatchesRegularExpression(
            '@<form\b(?=[^>]*enctype="multipart/form-data")[^>]*>@s',
            $content,
        );
        $this->assertStringContainsString('academic-persons-inline-edit__image-form', $content);
        $this->assertStringContainsString('data-ie-upload-image', $content);
        $this->assertStringContainsString('[action]=update', $decodedContent);
        $this->assertStringContainsString('[action]=updateSkipSync', $decodedContent);
        $this->assertStringContainsString('[action]=uploadImage', $decodedContent);
        $this->assertStringContainsString('[action]=deleteImage', $decodedContent);
        $this->assertStringContainsString('[action]=documentForm', $decodedContent);
        $this->assertStringContainsString('[action]=createDocument', $decodedContent);
        $this->assertStringContainsString('[action]=updateDocument', $decodedContent);
        $this->assertStringContainsString('[action]=deleteDocument', $decodedContent);
        $this->assertStringContainsString('[action]=sortDocument', $decodedContent);
        $this->assertStringContainsString('[action]=contractContactForm', $decodedContent);
        $this->assertStringContainsString('[action]=createContractContact', $decodedContent);
        $this->assertStringContainsString('[action]=updateContractContact', $decodedContent);
        $this->assertStringContainsString('[action]=deleteContractContact', $decodedContent);
        $this->assertStringContainsString('[action]=sortContractContact', $decodedContent);
        $this->assertStringContainsString('data-ie-document-view-container', $content);
        $this->assertStringContainsString('data-ie-document-add-collapse-target', $content);
        $this->assertStringContainsString('data-ie-document-item-collapse-target', $content);
        $this->assertStringContainsString('data-ie-contract-contact-section', $content);
        $this->assertStringContainsString('data-ie-contract-contact-editor', $content);
        $this->assertStringContainsString('Save', $content);
        $this->assertStringNotContainsString('data-add-label', $content);
        $this->assertStringNotContainsString('data-replace-label', $content);
        $this->assertStringContainsString('data-ie-delete-image', $this->extractDeleteButtonOpeningTag($content));
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
        $this->assertStringContainsString('data-has-image="1"', $content);
        $this->assertStringContainsString('Save', $content);
        $this->assertStringContainsString('data-ie-delete-image', $this->extractDeleteButtonOpeningTag($content));
    }

    #[Test]
    public function imageUploadWithoutAFileNeverReturnsSuccess(): void
    {
        $this->setUpInlineProfileTestCase();
        $submitData = $this->extractImageFormSubmissionData($this->renderInlineProfilePage());
        $storedFilesBeforeRequest = $this->getStoredFiles();
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
        $this->assertSame($storedFilesBeforeRequest, $this->getStoredFiles());
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
                'imageAlternative' => 'Max Müllermann',
                'imageTitle' => 'Max Müllermann',
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

    /**
     * The sorting values of one document section, keyed by record uid and ordered
     * the way the database holds them.
     *
     * @return array<int, int>
     */
    private function getPersistedDocumentSorting(string $type): array
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile_information')
            ->executeQuery(
                'SELECT uid, sorting FROM tx_academicpersons_domain_model_profile_information'
                . ' WHERE profile = ? AND type = ? AND deleted = 0 ORDER BY sorting ASC, uid ASC',
                [self::PROFILE_ID, $type],
            )
            ->fetchAllAssociative();
        $sorting = [];
        foreach ($rows as $row) {
            $sorting[(int)$row['uid']] = (int)$row['sorting'];
        }
        return $sorting;
    }

    /**
     * Both branches of "sortDocument" write what they answer.
     *
     * The response of the action is built from the in-memory objects and the existing
     * coverage asserts only that, so it reports the new order whether or not the same
     * order reached the database. This asserts the rows. Neither branch flushes by
     * itself - the step-wise one delegates to `ListSortingService`, which marks the
     * records through the persistence manager and leaves them there, and the reorder
     * branch does the same inline - so what makes the two agree today is the
     * `persistAll()` of `Extbase\Core\Bootstrap::resetSingletons()` at the end of every
     * plugin dispatch, plus the flush of `persistAndDispatchProfileUpdate()`. This pins
     * the outcome rather than either mechanism.
     */
    #[Test]
    public function sortDocumentPersistsTheNewOrderOfASection(): void
    {
        $this->setUpInlineProfileTestCase();
        $this->seedStructuredDocumentSections();
        $sortUrl = $this->extractDataUrl($this->renderInlineProfilePage(), 'data-sort-document-url');
        $this->assertSame([1 => 10, 2 => 20], $this->getPersistedDocumentSorting('cooperation'));
        $stepResponse = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'cooperation', 'record' => 2, 'direction' => 'up'],
        ]);
        $this->assertSame(200, $stepResponse->getStatusCode(), (string)$stepResponse->getBody());
        $stepBody = json_decode((string)$stepResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($stepBody['changed'] ?? null);
        $this->assertSame([2, 1], $stepBody['order'] ?? null);
        $this->assertSame([2 => 10, 1 => 20], $this->getPersistedDocumentSorting('cooperation'));
        $reorderResponse = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'cooperation', 'order' => [1, 2]],
        ]);
        $this->assertSame(200, $reorderResponse->getStatusCode(), (string)$reorderResponse->getBody());
        $reorderBody = json_decode((string)$reorderResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($reorderBody['changed'] ?? null);
        $this->assertSame([1, 2], $reorderBody['order'] ?? null);
        $this->assertSame([1 => 10, 2 => 20], $this->getPersistedDocumentSorting('cooperation'));
    }

    #[Test]
    public function inlineEditorLabelsAreShippedInBothLanguages(): void
    {
        $expectedEnglish = [
            'list.language' => 'Language',
            'list.language.all' => 'All languages',
            'list.language.unknown' => 'Language %d',
            'inlineProfile.backToList' => 'Back to profile overview',
            'inlineProfile.btnEditAll' => 'Edit all',
            'inlineProfile.btnCloseAll' => 'Close all',
            'inlineProfile.image.heading' => 'Profile image',
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
            'inlineProfile.visibility.private' => 'Private',
            'inlineProfile.visibility.public' => 'Public',
            'inlineProfile.documents.start' => 'Start',
            'inlineProfile.documents.from' => 'From',
            'inlineProfile.documents.to' => 'To',
            'inlineProfile.documents.year' => 'Date',
            'inlineProfile.documents.title' => 'Title',
            'inlineProfile.documents.position' => 'Position',
            'inlineProfile.documents.actionsHeading' => 'Actions',
            'inlineProfile.documents.empty' => 'No entries have been added yet.',
            'inlineProfile.documents.empty.contracts' => 'No contracts have been added yet.',
            'inlineProfile.documents.empty.cooperation' => 'No cooperation entries have been added yet.',
            'inlineProfile.documents.empty.lectures' => 'No lectures have been added yet.',
            'inlineProfile.documents.empty.memberships' => 'No memberships have been added yet.',
            'inlineProfile.documents.empty.pressMedia' => 'No press releases have been added yet.',
            'inlineProfile.documents.empty.vita' => 'No vita entries have been added yet.',
            'inlineProfile.documents.empty.publications' => 'No publications have been added yet.',
            'inlineProfile.documents.empty.scientificResearch' =>
                'No scientific research entries have been added yet.',
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
        $document = new \DOMDocument();
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
        $this->assertSame('Alle bearbeiten', $germanTranslations['inlineProfile.btnEditAll']);
        $this->assertSame('Alle schließen', $germanTranslations['inlineProfile.btnCloseAll']);
        $this->assertSame('Sprache', $germanTranslations['list.language']);
        $this->assertSame('Zurück zur Profilübersicht', $germanTranslations['inlineProfile.backToList']);
        $this->assertSame('Profilbild', $germanTranslations['inlineProfile.image.heading']);
        $this->assertSame('Von', $germanTranslations['inlineProfile.documents.from']);
        $this->assertSame('Bis', $germanTranslations['inlineProfile.documents.to']);
        $this->assertSame('Datum', $germanTranslations['inlineProfile.documents.year']);
        $this->assertSame('Aktionen', $germanTranslations['inlineProfile.documents.actionsHeading']);
        $this->assertSame(
            'Es wurden noch keine Einträge hinterlegt.',
            $germanTranslations['inlineProfile.documents.empty'],
        );
        $this->assertSame(
            'Es wurden noch keine Pressemitteilungen hinterlegt.',
            $germanTranslations['inlineProfile.documents.empty.pressMedia'],
        );
    }
}
