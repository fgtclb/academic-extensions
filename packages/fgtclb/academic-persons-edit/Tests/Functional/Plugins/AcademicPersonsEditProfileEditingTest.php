<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Covers the profile and structured-section AJAX contracts as well as the
 * image editor of the `academicpersonsedit_profileediting` content element.
 */
final class AcademicPersonsEditProfileEditingTest extends AbstractFrontendProfilePluginTestCase
{
    private function seedStructuredDocumentSections(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsEditProfileEditing/structuredDocumentSections.csv');
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

    private function getProfileEditingPartial(string $relativePath): string
    {
        $content = file_get_contents(
            __DIR__ . '/../../../Resources/Private/Partials/Profile/' . $relativePath . '.html',
        );
        $this->assertIsString($content);
        return $content;
    }

    private function getProfileEditingFluidSources(): string
    {
        $paths = [
            ...(glob(__DIR__ . '/../../../Resources/Private/Templates/Profile/*.html') ?: []),
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/Profile/*.html') ?: []),
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/Profile/*/*.html') ?: []),
            ...(glob(__DIR__ . '/../../../Resources/Private/Partials/Profile/*/*/*.html') ?: []),
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
            preg_match('@<button\b(?=[^>]*data-pe-delete-image)[^>]*>@s', $content, $match),
            'The image view has no delete button.',
        );
        return $match[0];
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
                ->withAddedHeader('X-Requested-With', 'XMLHttpRequest')
                ->withBody($body),
        );
    }

    #[Test]
    public function imageEditorTemplateExposesStableAjaxHooks(): void
    {
        $template = $this->getProfileEditingPartial('Image/Editor');
        $this->assertStringContainsString('data-pe-image-view-container', $template);
        $this->assertStringContainsString('academic-persons-profile-editing__image-editor', $template);
        $this->assertStringContainsString('academic-persons-profile-editing__image-editor-content', $template);
        $this->assertStringContainsString('data-pe-upload-image', $template);
        $this->assertStringContainsString('data-pe-delete-image', $template);
        $this->assertStringContainsString('data-pe-image-error', $template);
        // Delete, cancel-delete, confirm-delete, close and save.
        $this->assertSame(5, substr_count($template, '<button'));
        $this->assertStringContainsString('data-pe-cancel-delete-image', $template);
        $this->assertStringContainsString('data-pe-confirm-delete-image', $template);
        $this->assertStringContainsString('data-pe-image-delete-actions', $template);
        $this->assertStringContainsString('key="profileEditing.image.editor.deleteConfirm"', $template);
        $this->assertStringContainsString('key="actions.save"', $template);
        $this->assertStringNotContainsString('key="actions.add"', $template);
        $this->assertStringNotContainsString('key="actions.replace"', $template);
        $this->assertStringNotContainsString('data-add-label', $template);
        $this->assertStringNotContainsString('data-replace-label', $template);
        $this->assertStringNotContainsString('<dialog', $template);
        $this->assertStringNotContainsString('f:form.validationResults', $template);
    }

    /**
     * The editor is driven by "<academic-persons-edit-image-editor>" and no
     * longer by a rendering framework, so the hooks it queries are as much part
     * of the contract as the endpoints are - and only this test sees the
     * partial. The behavioural suite drives a transcription of it.
     */
    #[Test]
    public function imageEditorTemplateIsDrivenByItsElement(): void
    {
        $template = $this->getProfileEditingPartial('Image/Editor');
        $this->assertStringContainsString('<academic-persons-edit-image-editor>', $template);
        foreach (
            [
                'data-pe-image-delete-actions',
                'data-pe-delete-image-confirm-question',
                'data-pe-image-fieldset',
                'data-pe-image-cropper-stage',
                'data-pe-image-cropper-source',
                'data-pe-image-selected-preview',
                'data-pe-image-upload-spinner',
            ] as $hook
        ) {
            $this->assertStringContainsString($hook, $template);
        }
        // The upload form is the one thing that cannot move into the browser:
        // the property mapper validates against "__trustedProperties", and only
        // the server can sign it.
        $this->assertStringContainsString('<f:form', $template);
        $this->assertStringContainsString('<f:form.upload', $template);
        // Nothing Vue understands is left in it.
        foreach (['v-if', 'v-else', 'v-show', 'v-bind', 'v-on:', 'v-text', 'v-model', '<Teleport', '<Transition', 'ref="'] as $directive) {
            $this->assertStringNotContainsString($directive, $template);
        }
    }

    /**
     * The document editor is rendered by "<academic-persons-edit-document-editor>"
     * from the fields of the "documentForm" response, so this partial has no
     * markup left to override - and saying so is the only thing a test can
     * assert about a file that renders nothing.
     */
    #[Test]
    public function documentEditorPartialIsOnlyAMountPoint(): void
    {
        $template = $this->getProfileEditingPartial('Documents/Editor');
        $this->assertStringContainsString('academic-persons-edit-document-editor', $template);
        $this->assertStringNotContainsString('<section', $template);
        $this->assertStringNotContainsString('<form', $template);
        $this->assertStringNotContainsString('f:translate', $template);
        foreach (['v-if', 'v-else', 'v-show', 'v-bind', 'v-on:', 'v-text', 'v-model', '<Teleport', '<Transition'] as $directive) {
            $this->assertStringNotContainsString($directive, $template);
        }
    }

    /**
     * The buttons of the list are handled by the delegated click listener of
     * "profile/documents.ts" and no longer by a "v-on:click.stop" that stopped
     * that very listener from ever seeing them.
     */
    #[Test]
    public function documentListButtonsAreHandledByDelegation(): void
    {
        foreach (['Documents/Actions', 'Documents/Sections'] as $partial) {
            $template = $this->getProfileEditingPartial($partial);
            $this->assertStringNotContainsString('v-on:', $template);
        }
    }

    #[Test]
    public function imageCardExposesAccessibleEditHook(): void
    {
        $template = $this->getProfileEditingPartial('Image/Card');
        $this->assertStringContainsString('key="profileEditing.image.heading"', $template);
        $this->assertStringNotContainsString('data-pe-profile-name', $template);
        $this->assertStringContainsString('data-pe-image-preview', $template);
        $this->assertMatchesRegularExpression(
            '@<button\b(?=[^>]*data-pe-open-image-view)[^>]*>@s',
            $template,
        );
        $this->assertStringContainsString(
            'identifier="academic-persons-edit-upload-image"',
            $template,
        );
        $this->assertStringContainsString('title="{f:translate(', $template);
        $this->assertStringContainsString('aria-label="{f:translate(', $template);
        $this->assertStringContainsString('<f:if condition="{image.writable}">', $template);
        $this->assertStringNotContainsString('imageEditable', $template);
        $this->assertStringNotContainsString('academic-persons-edit-add-image', $template);
    }

    #[Test]
    public function stickyImageColumnCarriesItsFrontendHook(): void
    {
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/Profile/Index.html');
        $this->assertIsString($template);
        $this->assertStringContainsString('data-pe-sticky-image', $template);
    }

    #[Test]
    public function editAllTogglesAllEditorsWithoutGlobalFooterActions(): void
    {
        $header = $this->getProfileEditingPartial('Header');
        $profileForm = $this->getProfileEditingPartial('Profile/Personal');
        $fluidSources = $this->getProfileEditingFluidSources();
        $this->assertFileDoesNotExist(
            __DIR__ . '/../../../Resources/Private/Partials/Profile/Profile/FooterActions.html',
        );
        $this->assertStringContainsString('data-pe-edit-all-label="{f:translate(', $header);
        $this->assertStringContainsString('data-pe-close-all-label="{f:translate(', $header);
        $this->assertStringContainsString('key: \'profileEditing.btnCloseAll\'', $header);
        $this->assertStringContainsString('data-pe-edit-all-button-label', $header);
        $this->assertStringContainsString('data-pe-profile-header', $header);
        $this->assertStringContainsString('data-pe-profile-name', $header);
        $this->assertStringContainsString('data-pe-sync-form', $header);
        $this->assertStringContainsString('aria-pressed="false"', $header);
        $this->assertStringContainsString('key="profileEditing.section.personal"', $profileForm);
        $this->assertStringNotContainsString('partial="Profile/Header"', $profileForm);
        $this->assertStringNotContainsString('Profile/Profile/FooterActions', $profileForm);
        $this->assertStringNotContainsString('data-pe-footer-button-area', $fluidSources);
        $this->assertStringNotContainsString('data-pe-cancel-all', $fluidSources);
    }

    #[Test]
    public function contentFieldsRenderDirectRichTextPreviewsAndThreeFieldActions(): void
    {
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();
        $this->assertSame(
            5,
            preg_match_all('@(?<!:)\bdata-pe-rich-text(?=[\s=>])@', $content),
        );
        $this->assertSame(5, substr_count($content, 'data-pe-editor-container'));
        $this->assertSame(
            5,
            preg_match_all('@\bdata-pe-rich-text-preview(?=[\s>])@', $content),
        );
        $this->assertSame(
            5,
            preg_match_all('@\bdata-pe-rich-text-preview-content(?=[\s>])@', $content),
        );
        $fieldActionCount = substr_count($content, 'data-pe-field-actions');
        $autosaveControlCount = substr_count($content, 'data-pe-autosave-on-change');
        $autosaveUndoCount = substr_count($content, 'data-pe-autosave-undo');
        $this->assertGreaterThanOrEqual(5, $fieldActionCount);
        $this->assertSame($autosaveControlCount, $autosaveUndoCount);
        $this->assertSame($fieldActionCount, substr_count($content, 'data-pe-dismiss'));
        $this->assertSame($fieldActionCount, substr_count($content, 'data-pe-save'));
        $this->assertSame(
            $fieldActionCount + $autosaveUndoCount,
            preg_match_all('@\bdata-pe-cancel(?=[\s>])@', $content),
        );
        $this->assertStringContainsString('Delete content', $content);
        $this->assertStringContainsString('Cancel', $content);
        $this->assertStringContainsString('Save', $content);
        $this->assertStringContainsString('No content', $content);
        $field = $this->getProfileEditingPartial('Field/Editable');
        $fields = $this->getProfileEditingPartial('Profile/Fields');
        $preview = $this->getProfileEditingPartial('Field/Preview');
        $control = $this->getProfileEditingPartial('Field/Control');
        $this->assertStringContainsString('<f:form.textarea', $control);
        $this->assertStringContainsString('<f:form.textfield', $control);
        $this->assertStringContainsString('data-pe-rich-text-preview', $preview);
        $this->assertStringContainsString('data-pe-rich-text-heading', $field);
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
        $richTextControls = $xpath->query('//*[@data-pe-rich-text]');
        $this->assertNotFalse($richTextControls);
        foreach ($richTextControls as $richTextControl) {
            $headings = $xpath->query(
                'ancestor::*[@data-pe-field-editor][1]/*[@data-pe-rich-text-heading]',
                $richTextControl,
            );
            $this->assertNotFalse($headings);
            $this->assertCount(1, $headings);
            $heading = $headings->item(0);
            $this->assertInstanceOf(\DOMElement::class, $heading);
            $headingActions = $xpath->query('.//*[@data-pe-field-actions]', $heading);
            $this->assertNotFalse($headingActions);
            $this->assertCount(1, $headingActions);
        }
        $limitedControl = $xpath->query('//*[@id="profile-editing-1-miscellaneous"]');
        $this->assertNotFalse($limitedControl);
        $this->assertCount(1, $limitedControl);
        $this->assertSame(
            '1000',
            $limitedControl->item(0)?->attributes?->getNamedItem('data-pe-character-limit')?->nodeValue,
        );
        $characterCounters = $xpath->query(
            '//*[@data-pe-character-counter and @data-pe-for="profile-editing-1-miscellaneous"]',
        );
        $this->assertNotFalse($characterCounters);
        $this->assertCount(1, $characterCounters);
        $this->assertSame('0 / 1000', trim($characterCounters->item(0)?->textContent ?? ''));
    }

    #[Test]
    public function profileFormsConsumeValidationFromTheirOwnVisualSection(): void
    {
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/Profile/Index.html');
        $profileForm = $this->getProfileEditingPartial('Profile/Personal');
        $contentForm = $this->getProfileEditingPartial('Profile/About');
        $this->assertIsString($template);
        $this->assertStringContainsString('section: profileSections.information', $template);
        $this->assertStringContainsString('section: profileSections.aboutme', $template);
        $this->assertStringContainsString('items: section.items', $profileForm);
        $this->assertStringContainsString('items: section.items', $contentForm);
        $this->assertStringNotContainsString('Profile/Sections/Personal', $profileForm);
        $this->assertStringNotContainsString('Profile/Sections/Content', $contentForm);
        $this->assertStringContainsString('data-profile-section="information"', $content);
        $this->assertStringContainsString('data-profile-section="aboutme"', $content);
    }

    #[Test]
    public function configuredProfileAndSpecialFieldsDriveTheRenderedControls(): void
    {
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();
        $this->assertStringContainsString('id="profile-editing-1-firstName"', $content);
        $this->assertStringContainsString('id="profile-editing-1-website"', $content);
        $this->assertStringContainsString('type="url"', $content);
        $this->assertStringContainsString(
            'data-pe-profile-name-field-ids="title firstName middleName lastName"',
            $content,
        );
        $this->assertStringContainsString('data-pe-sync-form', $content);
        $this->assertStringContainsString('data-pe-image-preview', $content);
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $previewHelptexts = $xpath->query(
            '//*[@data-pe-field-preview or @data-pe-group-preview]//*[@data-pe-helptext]',
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
                '//*[@data-pe-helptext and @data-pe-for="profile-editing-%d-%s"]',
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
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $headers = $xpath->query('//*[@data-pe-profile-header]');
        $this->assertNotFalse($headers);
        $this->assertCount(1, $headers);
        $header = $headers->item(0);
        $this->assertInstanceOf(\DOMElement::class, $header);
        foreach ([
            './/*[@data-pe-profile-name]',
            './/*[@data-pe-sync-form]',
            './/*[@data-academic-persons-profile-editing-edit-all-btn]',
        ] as $query) {
            $elements = $xpath->query($query, $header);
            $this->assertNotFalse($elements);
            $this->assertCount(1, $elements);
        }
        $imageHeading = $xpath->query('//*[@id="profile-editing-1-image-heading"]');
        $personalHeading = $xpath->query('//*[@id="profile-editing-1-personal-heading"]');
        $this->assertNotFalse($imageHeading);
        $this->assertNotFalse($personalHeading);
        $this->assertSame('Profile image', trim($imageHeading->item(0)?->textContent ?? ''));
        $this->assertSame('Personal data', trim($personalHeading->item(0)?->textContent ?? ''));
        $this->assertLessThan(
            strpos($content, 'profile-editing-1-image-heading'),
            strpos($content, 'data-pe-profile-header'),
        );
        $this->assertLessThan(
            strpos($content, 'profile-editing-1-personal-heading'),
            strpos($content, 'data-pe-profile-header'),
        );
        $this->assertStringContainsString('Back to profile overview', $content);
    }

    #[Test]
    public function editableSelectControlsUseExplicitFieldActions(): void
    {
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        foreach (['gender'] as $identifier) {
            $elementId = 'profile-editing-' . self::PROFILE_ID . '-' . $identifier;
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
                $control->hasAttribute('data-pe-autosave-on-change'),
                sprintf('Editable control "%s" must not opt into autosave-on-change.', $identifier),
            );
            $undoButtons = $xpath->query(sprintf(
                '//*[@data-pe-autosave-undo and @data-pe-cancel and @data-pe-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($undoButtons);
            $this->assertSame(
                0,
                $undoButtons->length,
                sprintf('Unexpected autosave undo button for "%s".', $identifier),
            );
            $fieldActions = $xpath->query(sprintf(
                '//*[@data-pe-field-actions and @data-pe-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($fieldActions);
            $this->assertSame(1, $fieldActions->length, sprintf('Missing field actions for "%s".', $identifier));
            foreach (['data-pe-dismiss', 'data-pe-cancel', 'data-pe-save'] as $actionAttribute) {
                $actionButtons = $xpath->query(sprintf(
                    '//*[@%s and @data-pe-for="%s"]',
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
                '//*[@data-academic-persons-profile-editing-activate-btn and @data-pe-for="%s"]',
                $elementId,
            ));
            $this->assertNotFalse($editButtons);
            $this->assertSame(1, $editButtons->length, sprintf('Missing edit button for "%s".', $identifier));
            $editButton = $editButtons->item(0);
            $this->assertInstanceOf(\DOMElement::class, $editButton);
            $this->assertSame($elementId . '-editor', $editButton->getAttribute('aria-controls'));
            $editors = $xpath->query(sprintf('//*[@id="%s-editor" and @data-pe-field-editor]', $elementId));
            $this->assertNotFalse($editors);
            $this->assertSame(1, $editors->length, sprintf('Missing editor for "%s".', $identifier));
        }
    }

    /**
     * Dates in the editor are rendered in the site language's locale, exactly as the
     * public detail view of the same profile renders them - not in the German
     * `d.m.Y` the source branch hard coded into three templates and the controller.
     * The fixture site is `en_US`, so a medium date reads `Jan 1, 2025`.
     */
    #[Test]
    public function documentRowDatesAreRenderedInTheSiteLocale(): void
    {
        $this->setUpProfileEditingTestCase();
        $this->seedStructuredDocumentSections();

        $content = $this->renderProfileEditingPage();

        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $dateValues = $xpath->query('//*[@data-pe-document-value="dateStart"]');
        $this->assertNotFalse($dateValues);
        $this->assertGreaterThan(0, $dateValues->length, 'No document row renders a start date.');
        $renderedDates = [];
        foreach ($dateValues as $dateValue) {
            $renderedDates[] = trim((string)$dateValue->textContent);
        }
        $this->assertContains('Jan 1, 2025', $renderedDates);
        $this->assertNotContains('01.01.2025', $renderedDates);
    }

    #[Test]
    public function structuredDocumentSectionsHonorConfiguredRowsActionsAndReadonlyState(): void
    {
        $this->setUpProfileEditingTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderProfileEditingPage();
        $aboutPosition = strpos($content, 'profile-editing-1-about-heading');
        $contractsPosition = strpos($content, 'data-section-key="contracts"');
        $this->assertIsInt($aboutPosition);
        $this->assertIsInt($contractsPosition);
        $this->assertGreaterThan($aboutPosition, $contractsPosition);
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $sections = $xpath->query('//*[@data-pe-document-section]');
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
            $addButtons = $xpath->query('.//button[@data-pe-document-add]', $section);
            $this->assertNotFalse($addButtons);
            $sectionSettings = $configuredSections[$position];
            $this->assertCount($sectionSettings->allowsCreate() ? 1 : 0, $addButtons);
            $items = $xpath->query('.//*[@data-pe-document-items]/*[@data-pe-document-item]', $section);
            $this->assertNotFalse($items);
            $listHeaders = $xpath->query('.//*[@data-pe-document-list-header]', $section);
            $this->assertNotFalse($listHeaders);
            $this->assertCount(1, $listHeaders);
            foreach ($items as $item) {
                $actions = $xpath->query('.//*[@data-pe-document-actions]/button', $item);
                $this->assertNotFalse($actions);
                $this->assertCount(
                    count($sectionSettings->getAllowedActions()) + ($sectionSettings->allowsDragSorting() ? 1 : 0),
                    $actions,
                );
                $actualActions = [];
                foreach ($actions as $actionElement) {
                    $this->assertInstanceOf(\DOMElement::class, $actionElement);
                    $actualActions[] = match (true) {
                        $actionElement->hasAttribute('data-pe-document-drag') => 'drag',
                        $actionElement->hasAttribute('data-pe-document-view') => 'view',
                        $actionElement->hasAttribute('data-pe-document-sort') => $actionElement->getAttribute('data-pe-document-sort'),
                        $actionElement->hasAttribute('data-pe-document-delete') => 'delete',
                        $actionElement->hasAttribute('data-pe-document-edit') => 'edit',
                        default => 'unknown',
                    };
                }
                $expectedActions = $sectionSettings->allowsDragSorting() ? ['drag'] : [];
                array_push($expectedActions, ...$sectionSettings->getAllowedActions());
                $this->assertSame($expectedActions, $actualActions);
                $rowValueElements = $xpath->query('.//*[@data-pe-document-value or @data-pe-document-title]', $item);
                $this->assertNotFalse($rowValueElements);
                $actualRowFields = [];
                foreach ($rowValueElements as $rowValueElement) {
                    $this->assertInstanceOf(\DOMElement::class, $rowValueElement);
                    $actualRowFields[] = $rowValueElement->hasAttribute('data-pe-document-title')
                        ? 'title'
                        : $rowValueElement->getAttribute('data-pe-document-value');
                }
                $expectedRowFields = array_map(
                    static fn(string $field): string => $sectionSettings->isContractSection()
                        ? match ($field) {
                            'from' => 'validFrom',
                            'to' => 'validTo',
                            default => $field,
                        }
                    : match ($field) {
                        'from' => 'dateStart',
                        'to' => 'dateEnd',
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
        $documentsPartial = $this->getProfileEditingPartial('Documents/Sections');
        $this->assertStringContainsString('key="{section.label}"', $documentsPartial);
        $this->assertStringNotContainsString('key="profile.{section.identifier}"', $documentsPartial);
        $this->assertStringContainsString('data-pe-document-add', $documentsPartial);
        $actionsPartial = $this->getProfileEditingPartial('Documents/Actions');
        foreach ([
            'academic-persons-edit-sort-handle',
            'academic-persons-edit-view',
            'academic-persons-edit-move-down',
            'academic-persons-edit-move-up',
            'academic-persons-edit-delete',
            'academic-persons-edit-edit',
        ] as $iconIdentifier) {
            $this->assertStringContainsString($iconIdentifier, $actionsPartial);
        }
        $actionPositions = array_map(
            static fn(string $hook): int|false => strpos($actionsPartial, $hook),
            [
                'data-pe-document-view',
                'data-pe-document-sort="down"',
                'data-pe-document-sort="up"',
                'data-pe-document-delete',
                'data-pe-document-edit',
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
            '//*[@data-section-key="contracts"]//*[@data-pe-document-items]/*[@data-pe-document-item]',
        );
        $this->assertNotFalse($contractItems);
        $this->assertCount(2, $contractItems);
        foreach ($contractItems as $contractItem) {
            $contractActions = $xpath->query('.//*[@data-pe-document-actions]/button', $contractItem);
            $this->assertNotFalse($contractActions);
            $this->assertCount(6, $contractActions);
            $this->assertTrue($contractActions->item(0)?->attributes?->getNamedItem('data-pe-document-drag') !== null);
            $this->assertTrue($contractActions->item(1)?->attributes?->getNamedItem('data-pe-document-view') !== null);
            $this->assertSame('down', $contractActions->item(2)?->attributes?->getNamedItem('data-pe-document-sort')?->nodeValue);
            $this->assertSame('up', $contractActions->item(3)?->attributes?->getNamedItem('data-pe-document-sort')?->nodeValue);
            $this->assertTrue($contractActions->item(4)?->attributes?->getNamedItem('data-pe-document-delete') !== null);
            $this->assertTrue($contractActions->item(5)?->attributes?->getNamedItem('data-pe-document-edit') !== null);
        }
        $this->assertSame('10', $contractItems->item(0)?->attributes?->getNamedItem('data-item-sorting')?->nodeValue);
        $emptyPressMedia = $xpath->query('//*[@data-section-key="pressMedia"]//*[@data-pe-document-empty-state]');
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
        $this->setUpProfileEditingTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderProfileEditingPage();
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
            ['title', 'link', 'date', 'dateStart', 'dateEnd', 'yearOnly', 'bodytext'],
            array_column($formBody['fields'] ?? [], 'name'),
        );
        $formFieldsByName = array_column($formBody['fields'] ?? [], null, 'name');
        $this->assertTrue($formFieldsByName['bodytext']['richText'] ?? false);
        $this->assertSame(500, $formFieldsByName['bodytext']['characterLimit'] ?? null);
        $this->assertSame('date', $formFieldsByName['date']['type'] ?? null);
        $this->assertTrue($formFieldsByName['date']['required'] ?? false);
        $this->assertFalse($formFieldsByName['dateStart']['required'] ?? true);
        $this->assertFalse($formFieldsByName['dateEnd']['required'] ?? true);
        $this->assertTrue($formFieldsByName['yearOnly']['compactCheckbox'] ?? false);
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        foreach ([
            'title' => 'title',
            'date' => 'date',
            'dateStart' => 'from',
            'dateEnd' => 'to',
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
        $this->assertArrayHasKey('date', $missingYearBody['errors'] ?? []);
        $this->assertArrayNotHasKey('dateStart', $missingYearBody['errors'] ?? []);
        $this->assertArrayNotHasKey('dateEnd', $missingYearBody['errors'] ?? []);
        $overLimitResponse = $this->postJson($createUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'fields' => [
                    'title' => 'Description over its configured limit',
                    'date' => '2027-01-01',
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
                    'date' => '2027-01-01',
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
                'SELECT profile, type, title, date, date_start, date_end, year_only, sorting'
                    . ' FROM tx_academicpersons_domain_model_profile_information'
                    . ' WHERE uid = ? AND deleted = 0',
                [$createdUid],
            )
            ->fetchAssociative();
        $this->assertIsArray($storedCreatedRecord);
        $this->assertSame(self::PROFILE_ID, (int)$storedCreatedRecord['profile']);
        $this->assertSame('cooperation', $storedCreatedRecord['type']);
        $this->assertSame('AJAX cooperation', $storedCreatedRecord['title']);
        $this->assertSame('2027-01-01', $storedCreatedRecord['date']);
        $this->assertNull($storedCreatedRecord['date_start']);
        $this->assertNull($storedCreatedRecord['date_end']);
        $this->assertSame(1, (int)$storedCreatedRecord['year_only']);
        $this->assertSame(30, (int)$storedCreatedRecord['sorting']);
        $updateResponse = $this->postJson($updateUrl, [
            'profile' => self::PROFILE_ID,
            'data' => [
                'section' => 'cooperation',
                'record' => $createdUid,
                'fields' => [
                    'title' => 'AJAX cooperation updated',
                    'date' => '2027-03-04',
                    'yearOnly' => false,
                    'bodytext' => '<p><strong>Updated inline</strong><script>alert(1)</script></p>',
                ],
            ],
        ]);
        $this->assertSame(200, $updateResponse->getStatusCode(), (string)$updateResponse->getBody());
        $updateBody = json_decode((string)$updateResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('AJAX cooperation updated', $updateBody['item']['display']['title'] ?? null);
        // The row display value the controller renders follows the site locale, like
        // the templates above and like the public detail view.
        $this->assertSame('Mar 4, 2027', $updateBody['item']['display']['date'] ?? null);
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
        $this->setUpProfileEditingTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderProfileEditingPage();
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
        $this->setUpProfileEditingTestCase();
        $this->seedStructuredDocumentSections();
        $content = $this->renderProfileEditingPage();
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
        $this->setUpProfileEditingTestCase();
        $updateUrl = $this->extractDataUrl(
            $this->renderProfileEditingPage(),
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
        $this->setUpProfileEditingTestCase();
        $connection = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile');
        $persistedValue = (string)$connection->executeQuery(
            'SELECT miscellaneous FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
            [self::PROFILE_ID],
        )->fetchOne();
        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
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
    public function profileUpdateRejectsAProfileFieldMarkedReadOnlyInItsSection(): void
    {
        $this->setUpProfileEditingTestCase();
        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
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
    public function profileUpdatePropagatesSectionValidationErrorsWithStatus422(): void
    {
        $this->setUpProfileEditingTestCase();
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update(
                'tx_academicpersons_domain_model_profile',
                ['gender' => 'ms'],
                ['uid' => self::PROFILE_ID],
            );
        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
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
    public function academicTitleUpdatePersistsTheConfiguredNameComponent(): void
    {
        $this->setUpProfileEditingTestCase();
        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
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

    /**
     * Every icon identifier a shipped partial asks for has to be registered, and every
     * registered one has to be asked for by something.
     *
     * `<core:icon>` never fails on an unknown identifier: `IconFactory` answers with the
     * `default-not-found` placeholder and the identifier that was asked for is gone from
     * the markup. The Fluid scan catches the identifiers of a state the fixture does not
     * reach - a read-only section renders no save button - which is why it is a scan and
     * not only an assertion on the rendered page.
     */
    #[Test]
    public function everyIconIdentifierOfTheShippedTemplatesIsRegistered(): void
    {
        $registeredIcons = require __DIR__ . '/../../../Configuration/Icons.php';
        $this->assertIsArray($registeredIcons);
        $fluidSources = $this->getProfileEditingFluidSources();
        $this->assertGreaterThan(
            0,
            preg_match_all(
                '@<core:icon\b[^>]*?\bidentifier="([^"]+)"@s',
                $fluidSources,
                $matches,
            ),
            'No icon identifier is used by any profile editing template.',
        );
        $usedIdentifiers = array_values(array_unique($matches[1]));
        sort($usedIdentifiers);
        foreach ($usedIdentifiers as $identifier) {
            $this->assertArrayHasKey(
                $identifier,
                $registeredIcons,
                sprintf('The icon identifier "%s" is used but not registered.', $identifier),
            );
            $source = (string)($registeredIcons[$identifier]['source'] ?? '');
            $this->assertFileExists(
                GeneralUtility::getFileAbsFileName($source),
                sprintf('The icon file of "%s" does not exist.', $identifier),
            );
        }
        $registeredActionIcons = array_values(array_filter(
            array_keys($registeredIcons),
            static fn(string $identifier): bool => str_starts_with($identifier, 'academic-persons-edit-'),
        ));
        sort($registeredActionIcons);
        $this->assertSame(
            $registeredActionIcons,
            $usedIdentifiers,
            'Registered editor icons and the icons the templates use have drifted apart.',
        );
    }

    /**
     * The counterpart of the scan above, on the rendered page: the identifiers really
     * resolve at runtime and are inlined rather than rendered as an <img>, which is what
     * lets a button's own colour reach its glyph.
     */
    #[Test]
    public function theRenderedEditorResolvesEveryIconItAsksFor(): void
    {
        $this->setUpProfileEditingTestCase();
        $this->seedStructuredDocumentSections();

        $content = $this->renderProfileEditingPage();

        $this->assertStringNotContainsString('default-not-found', $content);
        $this->assertStringNotContainsString('Resources/Public/Icons/edit.svg', $content);
        $this->assertStringContainsString('<svg', $content);
        foreach ([
            'academic-persons-edit-add',
            'academic-persons-edit-back',
            'academic-persons-edit-delete',
            'academic-persons-edit-edit',
            'academic-persons-edit-help',
            'academic-persons-edit-move-down',
            'academic-persons-edit-move-up',
            'academic-persons-edit-view',
        ] as $identifier) {
            $this->assertStringContainsString(
                'data-identifier="' . $identifier . '"',
                $content,
                sprintf('The rendered editor does not carry the icon "%s".', $identifier),
            );
        }
    }

    #[Test]
    public function profileEditingPluginRegistersOnlyTheNewControllerActions(): void
    {
        $file = __DIR__ . '/../../../ext_localconf.php';
        $configuration = file_get_contents($file);
        $this->assertIsString($configuration);
        $this->assertStringContainsString("'ProfileEditing'", $configuration);
        $this->assertStringContainsString('[ProfileController::class => $actions]', $configuration);
        $this->assertStringContainsString("'list'", $configuration);
        $this->assertStringContainsString("'index'", $configuration);
        $this->assertStringContainsString("'uploadImage'", $configuration);
        $this->assertStringContainsString("'deleteImage'", $configuration);
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
            $this->assertStringContainsString(sprintf("'%s'", $documentAction), $configuration);
        }
        foreach (['editImage', 'addImage', 'removeImage', 'toggleSkipSync'] as $legacyAction) {
            $this->assertStringNotContainsString(sprintf("'%s'", $legacyAction), $configuration);
        }
        $legacyControllers = [
            'ProfileInformationController',
            'ContractController',
            'PhysicalAddressController',
            'EmailAddressController',
            'PhoneNumberController',
        ];
        foreach ($legacyControllers as $legacyController) {
            $this->assertStringNotContainsString($legacyController . '::class', $configuration);
        }
    }

    #[Test]
    public function profileEditingTestUsesTheStableContentType(): void
    {
        $this->assertSame(
            AbstractFrontendProfilePluginTestCase::class,
            get_parent_class(self::class),
        );
        $fixture = file_get_contents(
            __DIR__ . '/Fixtures/AcademicPersonsEditProfileEditing/profileEditingPage.csv',
        );
        $this->assertIsString($fixture);
        $this->assertStringContainsString('academicpersonsedit_profileediting', $fixture);
    }

    #[Test]
    public function profileWithoutImageRendersEditingHooksAndDedicatedAjaxUrls(): void
    {
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();
        $decodedContent = urldecode(html_entity_decode($content));
        $this->assertStringContainsString('data-academic-persons-profile-editing', $content);
        // The custom element that starts the editor wraps the root the contract
        // is carried by, so that the JavaScript has an owner and the attributes
        // stay where every reader expects them.
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $wrappedRoots = (new \DOMXPath($document))->query(
            '//academic-persons-edit-profile-editing/*[@data-academic-persons-profile-editing]',
        );
        $this->assertNotFalse($wrappedRoots);
        $this->assertCount(1, $wrappedRoots);
        $this->assertStringContainsString('data-profile-uid="1"', $content);
        $this->assertStringNotContainsString('data-user="', $content);
        $this->assertStringContainsString('data-pe-open-image-view', $content);
        $this->assertStringContainsString('data-pe-image-view-container', $content);
        $this->assertStringContainsString('data-pe-image-editor-target', $content);
        // The image editor is rendered where it is shown, inside its target and
        // wrapped in the element that drives it. Vue moved it there with a
        // "<Teleport>"; Fluid renders it there.
        $imageEditors = (new \DOMXPath($document))->query(
            '//*[@data-pe-image-editor-target]'
                . '/academic-persons-edit-image-editor'
                . '/*[@data-pe-image-view-container]',
        );
        $this->assertNotFalse($imageEditors);
        $this->assertCount(1, $imageEditors);
        $this->assertStringContainsString('data-image-render-type="cropper"', $content);
        $this->assertStringContainsString('data-has-image="0"', $content);
        $configuredRatio = $this->get(AcademicPersonsSettingsFactory::class)
            ->get()
            ->getSpecialField('image')?->settings['ratio'] ?? null;
        $this->assertIsString($configuredRatio);
        $this->assertNotSame('', trim($configuredRatio));
        $this->assertStringContainsString(
            'data-image-cropper-ratio="'
                . htmlspecialchars($configuredRatio, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '"',
            $content,
        );
        $this->assertStringNotContainsString('data-pe-image-modal', $content);
        $this->assertStringNotContainsString('data-pe-document-modal', $content);
        $this->assertStringNotContainsString('data-bs-toggle="modal"', $content);
        $this->assertStringNotContainsString('<dialog', $content);
        $this->assertMatchesRegularExpression(
            '@<form\b(?=[^>]*enctype="multipart/form-data")[^>]*>@s',
            $content,
        );
        $this->assertStringContainsString('academic-persons-profile-editing__image-form', $content);
        $this->assertStringContainsString('data-pe-upload-image', $content);
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
        // The collapse targets the document editor is created into. The editor
        // itself is no longer in the rendered page: it is built in the browser
        // from the "documentForm" response by
        // "<academic-persons-edit-document-editor>", so the markup that used to
        // stand here is asserted by the behavioural suite instead.
        $this->assertStringContainsString('data-pe-document-add-collapse-target', $content);
        $this->assertStringContainsString('data-pe-document-item-collapse-target', $content);
        $this->assertStringNotContainsString('data-pe-document-view-container', $content);
        // The icons that editor draws travel as templates, because the icon
        // registry is only reachable from the server.
        $this->assertStringContainsString('data-pe-icon="help"', $content);
        $this->assertStringContainsString('Save', $content);
        $this->assertStringNotContainsString('data-add-label', $content);
        $this->assertStringNotContainsString('data-replace-label', $content);
        $this->assertStringContainsString('data-pe-delete-image', $this->extractDeleteButtonOpeningTag($content));
    }

    #[Test]
    public function profileWithImageRendersSaveAndDeleteActions(): void
    {
        $this->setUpProfileEditingTestCase();
        $this->seedProfileImage();
        $content = $this->renderProfileEditingPage();
        $this->assertMatchesRegularExpression(
            '@<img\b[^>]+src="[^"]*profile-image[^"]*\.png"@',
            $content,
        );
        $this->assertStringContainsString('data-has-image="1"', $content);
        $this->assertStringContainsString('Save', $content);
        $this->assertStringContainsString('data-pe-delete-image', $this->extractDeleteButtonOpeningTag($content));
    }

    #[Test]
    public function imageUploadWithoutAFileNeverReturnsSuccess(): void
    {
        $this->setUpProfileEditingTestCase();
        $submitData = $this->extractImageFormSubmissionData($this->renderProfileEditingPage());
        $storedFilesBeforeRequest = $this->getStoredFiles();
        $response = $this->submitProfileImageForm($submitData['action'], $submitData['body']);
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
    public function synchronizationAjaxEndpointPersistsOnlyTheCheckboxState(): void
    {
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();
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
        $this->setUpProfileEditingTestCase();
        $this->seedProfileImage();
        $imagePath = $this->instancePath . '/fileadmin' . self::IMAGE_IDENTIFIER;
        $this->assertFileExists($imagePath);
        $this->assertSame(1, $this->getPersistedProfileImageCount());
        $deleteUrl = $this->extractDataUrl(
            $this->renderProfileEditingPage(),
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
        $this->setUpProfileEditingTestCase();
        $this->seedStructuredDocumentSections();
        $sortUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-sort-document-url');
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
    public function profileEditorLabelsAreShippedInBothLanguages(): void
    {
        $expectedEnglish = [
            'list.language' => 'Language',
            'list.language.all' => 'All languages',
            'list.language.unknown' => 'Language %d',
            'profileEditing.backToList' => 'Back to profile overview',
            'profileEditing.btnEditAll' => 'Edit all',
            'profileEditing.btnCloseAll' => 'Close all',
            'profileEditing.image.heading' => 'Profile image',
            'profileEditing.image.editor.title' => 'Edit profile image',
            'profileEditing.status.close' => 'Close notification',
            'profileEditing.image.editor.hint.select' =>
            'Select an image to preview it. The profile image changes only after you save.',
            'profileEditing.image.editor.deleteHint' =>
            'The profile image is removed permanently unless another record still uses the file.',
            'profileEditing.image.upload.label' => 'Choose image',
            'profileEditing.image.status.uploaded' => 'The profile image has been saved.',
            'profileEditing.image.status.missing' =>
            'No new profile image was received. Please select the image again.',
            'profileEditing.image.status.deleted' => 'The profile image has been deleted.',
            'profileEditing.status.editorError' =>
            'The rich text editor could not be loaded. Please reload the page and try again.',
            'profileEditing.actions.clear' => 'Delete content',
            'profileEditing.actions.group' => 'Field actions',
            'profileEditing.content.empty' => 'No content',
            'profileEditing.visibility.private' => 'Private',
            'profileEditing.visibility.public' => 'Public',
            'profileEditing.documents.start' => 'Start',
            'profileEditing.documents.from' => 'From',
            'profileEditing.documents.to' => 'To',
            'profileEditing.documents.date' => 'Date',
            'profileEditing.documents.title' => 'Title',
            'profileEditing.documents.position' => 'Position',
            'profileEditing.documents.actionsHeading' => 'Actions',
            'profileEditing.documents.empty' => 'No entries have been added yet.',
            'profileEditing.documents.empty.contracts' => 'No contracts have been added yet.',
            'profileEditing.documents.empty.cooperation' => 'No cooperation entries have been added yet.',
            'profileEditing.documents.empty.lectures' => 'No lectures have been added yet.',
            'profileEditing.documents.empty.memberships' => 'No memberships have been added yet.',
            'profileEditing.documents.empty.pressMedia' => 'No press releases have been added yet.',
            'profileEditing.documents.empty.vita' => 'No vita entries have been added yet.',
            'profileEditing.documents.empty.publications' => 'No publications have been added yet.',
            'profileEditing.documents.empty.scientificResearch' =>
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
        $this->assertSame('Alle bearbeiten', $germanTranslations['profileEditing.btnEditAll']);
        $this->assertSame('Alle schließen', $germanTranslations['profileEditing.btnCloseAll']);
        $this->assertSame('Sprache', $germanTranslations['list.language']);
        $this->assertSame('Zurück zur Profilübersicht', $germanTranslations['profileEditing.backToList']);
        $this->assertSame('Profilbild', $germanTranslations['profileEditing.image.heading']);
        $this->assertSame('Von', $germanTranslations['profileEditing.documents.from']);
        $this->assertSame('Bis', $germanTranslations['profileEditing.documents.to']);
        $this->assertSame('Datum', $germanTranslations['profileEditing.documents.date']);
        $this->assertSame('Aktionen', $germanTranslations['profileEditing.documents.actionsHeading']);
        $this->assertSame(
            'Es wurden noch keine Einträge hinterlegt.',
            $germanTranslations['profileEditing.documents.empty'],
        );
        $this->assertSame(
            'Es wurden noch keine Pressemitteilungen hinterlegt.',
            $germanTranslations['profileEditing.documents.empty.pressMedia'],
        );
    }
}
