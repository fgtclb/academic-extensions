<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use DOMDocument;
use DOMElement;
use DOMXPath;
use FGTCLB\AcademicPersonsEdit\Settings\AcademicPersonsEditSettingsFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Covers the profile and structured-section AJAX contracts as well as both
 * image-modal states of the `academicpersonsedit_inlineprofile` content element.
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
        $this->assertStringContainsString('class="modal fade rounded-0"', $template);
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
    public function imageCardUsesFullWidthBootstrapEditButton(): void
    {
        $template = $this->getInlineProfilePartial('Image/Card');
        $this->assertStringContainsString('key="inlineProfile.image.heading"', $template);
        $this->assertStringNotContainsString('data-ie-profile-name', $template);
        $this->assertStringContainsString('data-ie-image-preview', $template);
        $this->assertMatchesRegularExpression(
            '@<button\b(?=[^>]*data-ie-open-image-modal)'
                . '(?=[^>]*class="[^"]*\bbtn-sm\b[^"]*\bw-100\b'
                . '[^"]*\bd-inline-flex\b)[^>]*>@s',
            $template,
        );
        $this->assertStringContainsString(
            'identifier="academic-persons-inline-edit-camera"',
            $template,
        );
        $this->assertStringContainsString('title="{f:translate(', $template);
        $this->assertStringContainsString('aria-label="{f:translate(', $template);
        $this->assertStringNotContainsString('position-absolute', $template);
        $this->assertStringNotContainsString('academic-persons-edit-add-image', $template);
        $this->assertStringNotContainsString('style=', $template);
    }

    #[Test]
    public function stickyImageUsesDynamicPageHeaderOffset(): void
    {
        $module = $this->getInlineProfileJavaScriptSources();
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html');
        $this->assertIsString($module);
        $this->assertIsString($template);
        $this->assertStringContainsString('data-ie-sticky-image', $template);
        $this->assertStringNotContainsString('style="top:', $template);
        $this->assertStringContainsString(
            'const pageHeaderSelector = "#page-header.navbar-fixed-top";',
            $module,
        );
        $this->assertStringContainsString('pageHeader.getBoundingClientRect().height', $module);
        $this->assertStringContainsString('stickyImage.style.setProperty(', $module);
        $this->assertMatchesRegularExpression(
            '@`\$\{\s*headerOuterHeight\s*\+\s*10\s*\}px`,@',
            $module,
        );
        $this->assertStringContainsString('"important",', $module);
        $this->assertStringContainsString('globalThis.ResizeObserver', $module);
        $this->assertStringContainsString('new HeaderResizeObserver(updateOffset)', $module);
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
        $profileForm = $this->getInlineProfilePartial('Forms/Profile');
        $actions = $this->getInlineProfilePartial('Field/Actions');
        $fluidSources = $this->getInlineProfileFluidSources();
        $this->assertIsString($module);
        $this->assertFileDoesNotExist(
            __DIR__ . '/../../../Resources/Private/Partials/InlineProfile/Forms/FooterActions.html',
        );
        $this->assertStringContainsString('condition: besideHeading', $actions);
        $this->assertStringContainsString("then: 'ms-auto'", $actions);
        $this->assertStringContainsString("else: 'align-self-end'", $actions);
        $this->assertStringContainsString('data-ie-edit-all-label="{f:translate(', $header);
        $this->assertStringContainsString('data-ie-close-all-label="{f:translate(', $header);
        $this->assertStringContainsString('key: \'inlineProfile.btnCloseAll\'', $header);
        $this->assertStringContainsString('data-ie-edit-all-button-label', $header);
        $this->assertStringContainsString('data-ie-profile-header', $header);
        $this->assertStringContainsString('data-ie-profile-name', $header);
        $this->assertStringContainsString('partial="InlineProfile/Settings/Sync"', $header);
        $this->assertStringContainsString('aria-pressed="false"', $header);
        $this->assertStringContainsString('key="inlineProfile.section.personal"', $profileForm);
        $this->assertStringNotContainsString('partial="InlineProfile/Header"', $profileForm);
        $this->assertStringNotContainsString('InlineProfile/Forms/FooterActions', $profileForm);
        $this->assertStringNotContainsString('data-ie-footer-button-area', $fluidSources);
        $this->assertStringNotContainsString('data-ie-cancel-all', $fluidSources);
        $this->assertStringContainsString('const setEditAllButtonState = (root, active) => {', $module);
        $this->assertStringContainsString('button.classList.toggle("active", active);', $module);
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
    public function frontendModuleSupportsPreservedGridAndScopedComponentUi(): void
    {
        $module = $this->getInlineProfileJavaScriptSources();
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
        $this->assertStringContainsString('previewSelectedFile(file);', $module);
        $this->assertStringContainsString('const uploadFile = cropperRequested', $module);
        $this->assertStringContainsString(
            'await createCroppedImageFile(cropperEnabled ? cropper : null, file)',
            $module,
        );
        $this->assertStringContainsString(
            'formData.set(fileInput.name, uploadFile, uploadFile.name);',
            $module,
        );
        $this->assertStringContainsString('commitUploadedPreview(', $module);
        $this->assertStringContainsString('result.imageAlternative,', $module);
        $this->assertStringContainsString('result.imageTitle,', $module);
        $this->assertStringContainsString('const formData = new FormData(form);', $module);
        $this->assertGreaterThan(
            strpos($module, 'const formData = new FormData(form);'),
            strpos($module, 'setRequestPending(true, uploadButton);'),
        );
        $this->assertStringNotContainsString('.innerHTML', $module);
        $this->assertStringNotContainsString('nextElementSibling', $module);
        $this->assertSame(2, substr_count($fluidSources, 'data-ie-fields-form'));
        $this->assertStringContainsString('data-ie-content-fields-form', $fluidSources);
        $this->assertStringNotContainsString('data-ie-cancel-all', $fluidSources);
        $this->assertStringNotContainsString('InlineProfile/Forms/FooterActions', $fluidSources);
        $this->assertStringContainsString('partial="InlineProfile/Image/Modal"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Header"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Forms/Profile"', $template);
        $this->assertStringContainsString('partial="InlineProfile/Forms/Content"', $template);
        $this->assertSame(1, substr_count($template, 'class="col-12 col-lg-4"'));
        $this->assertSame(1, substr_count($template, 'class="col-12 col-lg-8"'));
        $this->assertSame(1, substr_count($template, '<div class="col-12">'));
    }

    #[Test]
    public function frontendEntryDelegatesToDedicatedFeatureModules(): void
    {
        $entry = file_get_contents(
            __DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile.js',
        );
        $this->assertIsString($entry);
        foreach (['common', 'documents', 'fields', 'image', 'sticky-image', 'sync'] as $module) {
            $this->assertStringContainsString('./profile/' . $module . '.js', $entry);
        }
        $this->assertStringNotContainsString('@ckeditor/', $entry);
        $this->assertFileExists(
            __DIR__ . '/../../../Resources/Public/JavaScript/frontend/profile/rich-text.js',
        );
        $this->assertLessThan(40, substr_count($entry, "\n"));
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
        $autosaveControlCount = substr_count($content, 'data-ie-autosave-on-change');
        $autosaveUndoCount = substr_count($content, 'data-ie-autosave-undo');
        $this->assertGreaterThanOrEqual(5, $fieldActionCount);
        $this->assertGreaterThanOrEqual(1, $autosaveControlCount);
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
        $field = $this->getInlineProfilePartial('Field');
        $preview = $this->getInlineProfilePartial('Field/Preview');
        $control = $this->getInlineProfilePartial('Field/Control');
        $actions = $this->getInlineProfilePartial('Field/Actions');
        $this->assertStringContainsString('align-items-start', $field);
        $this->assertStringContainsString('<f:form.textarea', $control);
        $this->assertStringContainsString('<f:form.textfield', $control);
        $this->assertStringContainsString('data-ie-rich-text-preview', $preview);
        $this->assertStringContainsString('flex-shrink-0', $actions);
        $this->assertStringContainsString('align-self-end', $actions);
        $this->assertStringContainsString('ms-auto', $actions);
        $this->assertStringContainsString('data-ie-rich-text-heading', $field);
        $this->assertStringContainsString(
            'arguments="{elementId: elementId, besideHeading: true}"',
            $field,
        );
        $this->assertSame(1, substr_count($field, 'besideHeading: true'));
        $this->assertSame(1, substr_count($field, 'besideHeading: false'));
        $ckeditor = $this->getInlineProfilePartial('Field/Types/Ckeditor');
        $textarea = $this->getInlineProfilePartial('Field/Types/Textarea');
        $this->assertStringContainsString('richText: true', $ckeditor);
        $this->assertStringNotContainsString('richText: true', $textarea);
        $this->assertStringNotContainsString('position-absolute', $preview);
        $this->assertStringNotContainsString('style=', $field . $preview . $control . $actions);
        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new DOMXPath($document);
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
            $this->assertInstanceOf(DOMElement::class, $heading);
            $headingActions = $xpath->query('.//*[@data-ie-field-actions]', $heading);
            $this->assertNotFalse($headingActions);
            $this->assertCount(1, $headingActions);
            $headingAction = $headingActions->item(0);
            $this->assertInstanceOf(DOMElement::class, $headingAction);
            $this->assertStringContainsString('ms-auto', $headingAction->getAttribute('class'));
            $this->assertStringNotContainsString('align-self-end', $headingAction->getAttribute('class'));
        }
    }

    #[Test]
    public function documentDragSortingExposesSourceAndInsertionStates(): void
    {
        $module = $this->getInlineProfileJavaScriptSources();
        $styles = file_get_contents(__DIR__ . '/../../../Resources/Public/Css/additional.css');
        $this->assertIsString($module);
        $this->assertIsString($styles);
        $this->assertStringContainsString('event.dataTransfer.setDragImage?.(row, offsetX, offsetY);', $module);
        $this->assertStringContainsString('bounds.top + bounds.height / 2', $module);
        foreach ([
            '[data-ie-document-items].is-drag-active',
            '[data-ie-document-item].is-dragging',
            '[data-ie-document-item].is-drop-before::before',
            '[data-ie-document-item].is-drop-after::after',
            '[data-ie-document-items].is-drop-at-end::after',
        ] as $selector) {
            $this->assertStringContainsString($selector, $styles);
        }
    }

    #[Test]
    public function fluidViewsUseSquareBootstrapButtonsAndModalSurfaces(): void
    {
        $privateResourcesPath = realpath(__DIR__ . '/../../../Resources/Private');
        $this->assertIsString($privateResourcesPath);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($privateResourcesPath, \FilesystemIterator::SKIP_DOTS),
        );
        $buttonClassCount = 0;
        $modalClassCount = 0;
        $modalSurfaceClasses = [
            'modal',
            'modal-buttons',
            'modal-dialog',
            'modal-content',
            'modal-header',
            'modal-body',
            'modal-footer',
        ];
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'html') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            $this->assertIsString($content);
            $matchCount = preg_match_all('@class="([^"]*)"@', $content, $classMatches, PREG_SET_ORDER);
            $this->assertNotFalse($matchCount);
            foreach ($classMatches as $classMatch) {
                $classes = preg_split('/\s+/', trim($classMatch[1])) ?: [];
                if (in_array('btn', $classes, true) || in_array('btn-close', $classes, true)) {
                    ++$buttonClassCount;
                    $this->assertContains('rounded-0', $classes, $file->getPathname());
                }
                if (array_intersect($modalSurfaceClasses, $classes) !== []) {
                    ++$modalClassCount;
                    $this->assertContains('rounded-0', $classes, $file->getPathname());
                }
            }
        }
        $this->assertGreaterThan(0, $buttonClassCount);
        $this->assertGreaterThan(0, $modalClassCount);
    }

    #[Test]
    public function profileFormsConsumeValidationFromTheirOwnVisualSection(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/InlineProfile/Index.html');
        $profileForm = $this->getInlineProfilePartial('Forms/Profile');
        $contentForm = $this->getInlineProfilePartial('Forms/Content');
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
    }

    #[Test]
    public function profileNameAndGlobalControlsRenderAboveTheImageAndPersonalHeadings(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new DOMXPath($document);
        $headers = $xpath->query('//*[@data-ie-profile-header]');
        $this->assertNotFalse($headers);
        $this->assertCount(1, $headers);
        $header = $headers->item(0);
        $this->assertInstanceOf(DOMElement::class, $header);
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
    public function editableSelectControlsAreRenderedAsInteractiveFields(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new DOMXPath($document);
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
            $this->assertInstanceOf(DOMElement::class, $control);
            $this->assertFalse(
                $control->hasAttribute('disabled'),
                sprintf('Editable control "%s" must not be rendered disabled.', $identifier),
            );
            $this->assertTrue(
                $control->hasAttribute('data-ie-autosave-on-change'),
                sprintf('Editable control "%s" must opt into autosave-on-change.', $identifier),
            );
            $undoButtons = $xpath->query(sprintf(
                '//*[@data-ie-autosave-undo and @data-ie-cancel and @data-ie-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($undoButtons);
            $this->assertSame(
                1,
                $undoButtons->length,
                sprintf('Missing autosave undo button for "%s".', $identifier),
            );
            $editButtons = $xpath->query(sprintf(
                '//*[@data-academic-persons-inline-edit-activate-btn and @data-ie-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($editButtons);
            $this->assertSame(1, $editButtons->length, sprintf('Missing edit button for "%s".', $identifier));
            $editButton = $editButtons->item(0);
            $this->assertInstanceOf(DOMElement::class, $editButton);
            $this->assertSame($elementId . '-editor', $editButton->getAttribute('aria-controls'));
            $editors = $xpath->query(sprintf('//*[@id="%s-editor" and @data-ie-field-editor]', $elementId));
            $this->assertNotFalse($editors);
            $this->assertSame(1, $editors->length, sprintf('Missing editor for "%s".', $identifier));
            $editor = $editors->item(0);
            $this->assertInstanceOf(DOMElement::class, $editor);
            $this->assertStringContainsString('d-none', $editor->getAttribute('class'));
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
        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new DOMXPath($document);
        $sections = $xpath->query('//*[@data-ie-document-section]');
        $this->assertNotFalse($sections);
        $documentSections = $this->get(AcademicPersonsEditSettingsFactory::class)->get()->documentSections;
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
                $section->attributes?->getNamedItem('data-section-field-name')?->nodeValue,
            );
            $this->assertSame(
                $sectionSettings->type,
                $section->attributes?->getNamedItem('data-section-record-type')?->nodeValue,
            );
            $this->assertSame(
                $sectionSettings->readOnly ? '1' : '0',
                $section->attributes?->getNamedItem('data-section-readonly')?->nodeValue,
            );
            $this->assertSame(
                $sectionSettings->allowsDragSorting() ? '1' : '0',
                $section->attributes?->getNamedItem('data-section-sortable')?->nodeValue,
            );
        }
        $this->assertSame(array_keys($documentSections), $sectionKeys);
        $documentsPartial = $this->getInlineProfilePartial('Sections/Documents');
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
            $this->assertCount(1, $contractActions);
            $contractAction = $contractActions->item(0);
            $this->assertInstanceOf(\DOMElement::class, $contractAction);
            $this->assertTrue($contractAction->hasAttribute('data-ie-document-view'));
        }
        $this->assertStringContainsString(
            'bg-body-tertiary',
            (string)$contractItems->item(0)?->attributes?->getNamedItem('class')?->nodeValue,
        );
        $this->assertStringNotContainsString(
            'bg-body-tertiary',
            (string)$contractItems->item(1)?->attributes?->getNamedItem('class')?->nodeValue,
        );
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
            '31.08.2030',
            '31.08.2025',
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
        $this->assertSame(true, $formBody['success'] ?? null);
        $this->assertSame(1, $formBody['record'] ?? null);
        $this->assertSame(
            ['title', 'link', 'year', 'yearStart', 'yearEnd', 'yearOnly', 'bodytext'],
            array_column($formBody['fields'] ?? [], 'name'),
        );
        $formFieldsByName = array_column($formBody['fields'] ?? [], null, 'name');
        $this->assertTrue($formFieldsByName['bodytext']['richText'] ?? false);
        $this->assertSame(100, $formFieldsByName['bodytext']['characterLimit'] ?? null);
        $this->assertSame('date', $formFieldsByName['year']['type'] ?? null);
        $this->assertTrue($formFieldsByName['year']['required'] ?? false);
        $this->assertFalse($formFieldsByName['yearStart']['required'] ?? true);
        $this->assertFalse($formFieldsByName['yearEnd']['required'] ?? true);
        foreach (['year', 'yearStart', 'yearEnd', 'yearOnly'] as $dateField) {
            $this->assertSame('col-12 col-md-3', $formFieldsByName[$dateField]['columnClass'] ?? null);
        }
        $this->assertTrue($formFieldsByName['yearOnly']['compactCheckbox'] ?? false);
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
                    'bodytext' => '<p><strong>' . str_repeat('a', 101) . '</strong></p>',
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
            ['The text must not exceed 100 characters.'],
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
    public function contractDocumentAjaxIsViewOnlyWhenTheSectionIsReadonly(): void
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
        $editFormResponse = $this->postJson($formUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => 1, 'mode' => 'edit'],
        ]);
        $this->assertSame(403, $editFormResponse->getStatusCode());
        $addFormResponse = $this->postJson($formUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => 0, 'mode' => 'add'],
        ]);
        $this->assertSame(403, $addFormResponse->getStatusCode());
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
        $this->assertSame(403, $createResponse->getStatusCode());
        $updateResponse = $this->postJson($updateUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'contracts',
                'record' => 1,
                'fields' => ['position' => 'Guest professor'],
            ],
        ]);
        $this->assertSame(403, $updateResponse->getStatusCode());
        $sortResponse = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => 1, 'direction' => 'down'],
        ]);
        $this->assertSame(403, $sortResponse->getStatusCode());
        $deleteResponse = $this->postJson($deleteUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'contracts', 'record' => 1],
        ]);
        $this->assertSame(403, $deleteResponse->getStatusCode());
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
        $this->assertSame(true, $body['success'] ?? null, (string)$response->getBody());
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
        $this->assertSame(false, $body['success']);
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
        $this->assertSame(false, $body['success']);
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
    public function profileWithoutImageRendersBootstrapModalAndDedicatedAjaxUrls(): void
    {
        $this->setUpInlineProfileTestCase();
        $content = $this->renderInlineProfilePage();
        $decodedContent = urldecode(html_entity_decode($content));
        $this->assertStringContainsString('data-academic-persons-inline-edit', $content);
        $this->assertStringContainsString('data-ie-open-image-modal', $content);
        $this->assertStringContainsString('data-bs-toggle="modal"', $content);
        $this->assertStringContainsString('data-ie-image-modal', $content);
        $this->assertStringContainsString('data-ie-image-render-type="cropper"', $content);
        $configuredRatio = $this->get(AcademicPersonsEditSettingsFactory::class)
            ->get()
            ->raw['special']['image']['settings']['ratio'] ?? null;
        $this->assertIsString($configuredRatio);
        $this->assertNotSame('', trim($configuredRatio));
        $this->assertStringContainsString(
            'data-ie-image-cropper-ratio="'
                . htmlspecialchars($configuredRatio, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '"',
            $content,
        );
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
        $this->assertStringContainsString('[action]=documentForm', $decodedContent);
        $this->assertStringContainsString('[action]=createDocument', $decodedContent);
        $this->assertStringContainsString('[action]=updateDocument', $decodedContent);
        $this->assertStringContainsString('[action]=deleteDocument', $decodedContent);
        $this->assertStringContainsString('[action]=sortDocument', $decodedContent);
        $this->assertStringContainsString('data-ie-document-modal', $content);
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
        $this->assertSame('Alle bearbeiten', $germanTranslations['inlineProfile.btnEditAll']);
        $this->assertSame('Alle schließen', $germanTranslations['inlineProfile.btnCloseAll']);
        $this->assertSame('Sprache', $germanTranslations['list.language']);
        $this->assertSame('Zurück zur Profilübersicht', $germanTranslations['inlineProfile.backToList']);
        $this->assertSame('Profilbild', $germanTranslations['inlineProfile.image.heading']);
        $this->assertSame('Von', $germanTranslations['inlineProfile.documents.from']);
        $this->assertSame('Bis', $germanTranslations['inlineProfile.documents.to']);
        $this->assertSame('Datum', $germanTranslations['inlineProfile.documents.year']);
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
