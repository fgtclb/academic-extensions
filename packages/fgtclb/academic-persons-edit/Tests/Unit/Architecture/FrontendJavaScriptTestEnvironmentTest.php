<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FrontendJavaScriptTestEnvironmentTest extends TestCase
{
    private const EXTENSION_ROOT = __DIR__ . '/../../..';

    #[Test]
    public function frontendAssetsUseTheRepositoryBuildInfrastructure(): void
    {
        $this->assertDirectoryDoesNotExist(
            self::EXTENSION_ROOT . '/Resources/Public/Development',
        );
        $this->assertFileExists(
            self::EXTENSION_ROOT . '/../../../Build/Scripts/runTests.sh',
        );
        $this->assertFileExists(
            self::EXTENSION_ROOT . '/../../../Build/esbuild.mjs',
        );

        $packageFile = self::EXTENSION_ROOT . '/Resources/Public/JavaScript/package.json';
        $packageSource = file_get_contents($packageFile);
        $this->assertIsString($packageSource);
        try {
            $package = json_decode($packageSource, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->fail('Invalid JavaScript module package: ' . $exception->getMessage());
        }
        $this->assertIsArray($package);
        $this->assertSame('module', $package['type'] ?? null);
        $this->assertTrue($package['private'] ?? false);
    }

    #[Test]
    public function inlineProfilePartialsAreGroupedByFeatureResponsibility(): void
    {
        $partialRoot = self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile';
        $expectedPartials = [
            'Documents/ContractContactEditor.html',
            'Documents/ContractContacts.html',
            'Documents/Editor.html',
            'Documents/Sections.html',
            'Field/Checkbox.html',
            'Field/Editable.html',
            'Field/Select.html',
            'Image/Card.html',
            'Image/Editor.html',
            'Profile/About.html',
            'Profile/Fields.html',
            'Profile/Personal.html',
        ];
        foreach ($expectedPartials as $expectedPartial) {
            $this->assertFileExists($partialRoot . '/' . $expectedPartial);
        }

        $obsoletePartials = [
            'Documents/View.html',
            'Field.html',
            'Field/Renderer.html',
            'Field/Types/Checkbox.html',
            'Field/Types/Ckeditor.html',
            'Field/Types/CombinedLink.html',
            'Field/Types/Input.html',
            'Field/Types/Select.html',
            'Field/Types/Textarea.html',
            'Forms/Content.html',
            'Forms/Profile.html',
            'Image/View.html',
            'Profile/Items.html',
            'Sections/Documents.html',
            'Settings/Sync.html',
            'Show/Image.html',
            'Special/TitleFields.html',
        ];
        foreach ($obsoletePartials as $obsoletePartial) {
            $this->assertFileDoesNotExist($partialRoot . '/' . $obsoletePartial);
        }
    }

    #[Test]
    public function inlineProfileExposesStableEditingHooksAndInlineEditors(): void
    {
        $typeScript = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/profile.ts',
        );
        $template = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Templates/InlineProfile/Index.html',
        );
        $documentEditor = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/Editor.html',
        );
        $documentSections = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/Sections.html',
        );
        $documentActions = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/Actions.html',
        );
        $profileInformationDocuments = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/ProfileInformation.html',
        );
        $contractDocuments = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/Contract.html',
        );
        $contractContacts = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/ContractContacts.html',
        );
        $contractContactEditor = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/ContractContactEditor.html',
        );
        $documentHeader = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/Header.html',
        );
        $profileInformationRow = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/ProfileInformationRow.html',
        );
        $contractRow = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/ContractRow.html',
        );
        $documentTypeScript = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/profile/documents.ts',
        );
        $imageEditor = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Image/Editor.html',
        );
        $imageCard = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Image/Card.html',
        );
        $imageTypeScript = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/profile/image.ts',
        );
        $syncTypeScript = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/profile/sync.ts',
        );
        $dependencyTypes = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/_dependencies.d.ts',
        );
        $this->assertIsString($typeScript);
        $this->assertIsString($template);
        $this->assertIsString($documentEditor);
        $this->assertIsString($documentSections);
        $this->assertIsString($documentActions);
        $this->assertIsString($profileInformationDocuments);
        $this->assertIsString($contractDocuments);
        $this->assertIsString($contractContacts);
        $this->assertIsString($contractContactEditor);
        $this->assertIsString($documentHeader);
        $this->assertIsString($profileInformationRow);
        $this->assertIsString($contractRow);
        $this->assertIsString($documentTypeScript);
        $this->assertIsString($imageEditor);
        $this->assertIsString($imageCard);
        $this->assertIsString($imageTypeScript);
        $this->assertIsString($syncTypeScript);
        $this->assertIsString($dependencyTypes);
        $this->assertStringContainsString('data-user="{profile.uid}"', $template);
        $this->assertStringContainsString('data-ie-document-view', $documentActions);
        $this->assertStringContainsString('data-ie-document-drag', $documentActions);
        $this->assertStringContainsString('const addCollapseTargetSelector', $documentTypeScript);
        $this->assertStringContainsString('const itemCollapseTargetSelector', $documentTypeScript);
        $this->assertStringContainsString('documentState.open = true;', $documentTypeScript);
        $this->assertStringContainsString('const openContractContact = async (', $documentTypeScript);
        $this->assertStringContainsString('const submitContractContact = async ()', $documentTypeScript);
        $this->assertStringContainsString('const sortContractContact = async (', $documentTypeScript);
        $this->assertStringContainsString(
            "documentState.pending = false;\n      await initializeDocumentEditors();",
            $documentTypeScript,
        );
        $this->assertStringContainsString('initializePopover(editor);', $documentTypeScript);
        $this->assertStringContainsString('data-ie-contract-contact-add', $contractContacts);
        $this->assertStringContainsString('data-ie-helptext', $contractContactEditor);
        $this->assertStringContainsString('data-ie-contract-contact-sort="down"', $contractContacts);
        $this->assertStringContainsString('data-ie-contract-contact-sort="up"', $contractContacts);
        $this->assertStringContainsString('data-ie-contract-contact-view', $contractContacts);
        $this->assertStringContainsString('data-ie-contract-contact-edit', $contractContacts);
        $this->assertStringContainsString('data-ie-contract-contact-delete', $contractContacts);
        $this->assertStringContainsString(
            "contractContact.mode === 'add' && contractContact.section === section.identifier",
            $contractContacts,
        );
        $this->assertStringContainsString(
            "contractContact.mode !== 'add' && contractContact.section === section.identifier && contractContact.record === item.uid",
            $contractContacts,
        );
        $this->assertSame(
            2,
            substr_count(
                $contractContacts,
                '<f:render partial="InlineProfile/Documents/ContractContactEditor" />',
            ),
        );
        $this->assertMatchesRegularExpression(
            '/data-ie-contract-contact-edit>.*contractContact\.record === item\.uid.*data-ie-contract-contact-editor.*<\/article>/s',
            $contractContacts,
        );
        $this->assertLessThan(
            strpos($contractContacts, 'data-ie-contract-contact-sort="down"'),
            strpos($contractContacts, 'data-ie-contract-contact-view'),
        );
        $this->assertLessThan(
            strpos($contractContacts, 'data-ie-contract-contact-sort="up"'),
            strpos($contractContacts, 'data-ie-contract-contact-sort="down"'),
        );
        $this->assertLessThan(
            strpos($contractContacts, 'data-ie-contract-contact-delete'),
            strpos($contractContacts, 'data-ie-contract-contact-sort="up"'),
        );
        $this->assertLessThan(
            strpos($contractContacts, 'data-ie-contract-contact-edit>'),
            strpos($contractContacts, 'data-ie-contract-contact-delete'),
        );
        $this->assertStringContainsString('const collapseDocument = (): void => {', $documentTypeScript);
        $this->assertStringContainsString('trigger === button', $documentTypeScript);
        $this->assertStringContainsString('const finishDocumentClose = (): void => {', $documentTypeScript);
        $this->assertStringNotContainsString('setView(', $documentTypeScript);
        $this->assertStringContainsString('const getDocumentRows =', $documentTypeScript);
        $this->assertStringContainsString('Array.from(items.children)', $documentTypeScript);
        $this->assertStringContainsString(
            'template?.querySelector<HTMLElement>(itemSelector)',
            $documentTypeScript,
        );
        $this->assertStringNotContainsString('template?.content.querySelector', $documentTypeScript);
        foreach ([$profileInformationDocuments, $contractDocuments] as $documentList) {
            $this->assertStringContainsString('data-ie-document-item-template', $documentList);
            $this->assertStringNotContainsString(
                '<template data-ie-document-item-template>',
                $documentList,
            );
        }
        $this->assertStringContainsString(
            "condition: '{section.items -> f:count()} > 0'",
            $documentSections,
        );
        $this->assertStringContainsString('role="status"', $documentSections);
        $this->assertStringContainsString('data-ie-document-add-collapse-target', $documentSections);
        $this->assertStringContainsString(
            'key="inlineProfile.documents.empty.{section.identifier}"',
            $documentSections,
        );
        $this->assertStringContainsString('default="{defaultEmptyMessage}"', $documentSections);
        $this->assertStringContainsString('data-ie-document-list-header', $documentHeader);
        $this->assertStringContainsString(
            "condition: '{items -> f:count()} > 0'",
            $documentHeader,
        );
        $this->assertStringContainsString(
            'key="inlineProfile.documents.actionsHeading"',
            $documentHeader,
        );
        $this->assertStringContainsString(
            'data-ie-document-item-collapse-target',
            $profileInformationRow,
        );
        $this->assertStringContainsString(
            'data-ie-document-item-collapse-target',
            $contractRow,
        );
        $this->assertStringContainsString('data-ie-open-image-view', $imageCard);
        $this->assertStringContainsString('data-ie-image-preview', $imageCard);
        $this->assertStringContainsString('data-ie-image-view-container', $imageEditor);
        $this->assertStringContainsString('academic-persons-inline-edit__image-editor', $imageEditor);
        $this->assertStringContainsString('academic-persons-inline-edit__image-editor-content', $imageEditor);
        $this->assertStringContainsString('data-ie-image-view-preview', $imageEditor);
        $this->assertStringContainsString('data-ie-upload-image', $imageEditor);
        $this->assertStringContainsString('data-ie-image-editor-target', $template);
        $this->assertStringNotContainsString('data-image-editing="0"', $template);
        $this->assertStringContainsString('academic-persons-inline-edit__image-preview-column', $template);
        $this->assertStringContainsString('data-ie-image-preview-column', $template);
        $imageEditorTargetPosition = strpos($template, 'data-ie-image-editor-target');
        $profileViewPosition = strpos($template, 'data-ie-profile-view');
        $this->assertIsInt($imageEditorTargetPosition);
        $this->assertIsInt($profileViewPosition);
        $this->assertLessThan($profileViewPosition, $imageEditorTargetPosition);
        $this->assertStringContainsString('const imageController = createImageEditing(root);', $typeScript);
        $this->assertStringContainsString('image.editing = true;', $imageTypeScript);
        $this->assertStringContainsString('closing: boolean;', $imageTypeScript);
        $this->assertStringContainsString('image.closing = true;', $imageTypeScript);
        $this->assertStringContainsString('image.closing = false;', $imageTypeScript);
        $this->assertStringContainsString('imageEditorTargetSelector', $imageTypeScript);
        $this->assertStringContainsString('imagePreviewColumnSelector', $imageTypeScript);
        $this->assertStringContainsString('scrollIntoView({', $imageTypeScript);
        $this->assertStringContainsString('globalThis.scrollTo({', $imageTypeScript);
        $this->assertStringContainsString('focus({ preventScroll: true })', $imageTypeScript);
        $this->assertStringContainsString('const finishImageClose = (): void => {', $imageTypeScript);
        $this->assertSame(2, substr_count($imageTypeScript, 'requestAnimationFrame('));
        $this->assertStringContainsString('hasSelection: boolean;', $imageTypeScript);
        $this->assertStringContainsString('image.hasSelection = selectedFile !== null;', $imageTypeScript);
        $this->assertMatchesRegularExpression(
            '/!image\.cropperRequested\s*\|\|\s*!image\.hasSelection\s*\|\|\s*image\.previewUrl === ""/',
            $imageTypeScript,
        );
        $this->assertStringNotContainsString('resolvePersistedImageFile', $imageTypeScript);
        $this->assertStringNotContainsString('fetch(persistedPreviewUrl', $imageTypeScript);
        $this->assertStringNotContainsString('initializeImageEditing', $imageTypeScript);
        $this->assertStringNotContainsString('initializeSkipSync', $syncTypeScript);
        $this->assertStringNotContainsString('initializeImageEditing', $dependencyTypes);
        $this->assertStringNotContainsString('initializeSkipSync', $dependencyTypes);
        $this->assertStringNotContainsString('setView("image")', $imageTypeScript);
        $this->assertStringContainsString('data-ie-document-view-container', $documentEditor);
        $this->assertStringContainsString('academic-persons-inline-edit__document-collapse', $documentEditor);
        $this->assertStringContainsString(
            'const documentController = createDocumentEditing(root);',
            $typeScript,
        );
        $this->assertStringNotContainsString('const setView =', $typeScript);
        $this->assertFileDoesNotExist(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/Modal.html',
        );
        $this->assertFileDoesNotExist(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Image/Modal.html',
        );
    }

    #[Test]
    public function profileEditingExposesAccessibleAsyncHooksWithoutDocumentAutosave(): void
    {
        $commonTypeScript = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/profile/common.ts',
        );
        $documentTypeScript = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/profile/documents.ts',
        );
        $fieldTypeScript = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/TypeScript/frontend/profile/fields.ts',
        );
        $documentEditor = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/Editor.html',
        );
        $contractContactEditor = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Documents/ContractContactEditor.html',
        );
        $fieldControls = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Field/Control.html',
        );
        $fieldEditor = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/InlineProfile/Field/Editable.html',
        );
        $this->assertIsString($commonTypeScript);
        $this->assertIsString($documentTypeScript);
        $this->assertIsString($fieldTypeScript);
        $this->assertIsString($documentEditor);
        $this->assertIsString($contractContactEditor);
        $this->assertIsString($fieldControls);
        $this->assertIsString($fieldEditor);

        $this->assertStringContainsString('body.setAttribute("aria-busy", "true")', $commonTypeScript);
        $this->assertStringContainsString('body.setAttribute("aria-busy", "false")', $commonTypeScript);
        $this->assertMatchesRegularExpression(
            '/export const requestJson.+finally \{\s+setRequestBusy\(false\);/s',
            $commonTypeScript,
        );
        $openDocumentStart = strpos($documentTypeScript, 'const openDocument = async');
        $closeDocumentStart = strpos($documentTypeScript, 'const closeDocument =', $openDocumentStart ?: 0);
        $this->assertIsInt($openDocumentStart);
        $this->assertIsInt($closeDocumentStart);
        $openDocumentSource = substr($documentTypeScript, $openDocumentStart, $closeDocumentStart - $openDocumentStart);
        $this->assertStringNotContainsString('submitDocument', $openDocumentSource);
        $this->assertStringNotContainsString('addEventListener("blur"', $documentTypeScript);
        $this->assertStringNotContainsString('addEventListener("change"', $documentTypeScript);
        $this->assertStringContainsString('data-ie-document-heading', $documentEditor);
        $this->assertStringContainsString('data-ie-contract-contact-heading', $contractContactEditor);
        $this->assertStringContainsString('focusTarget?.focus({ preventScroll: true })', $documentTypeScript);
        foreach ([$documentEditor, $contractContactEditor] as $dynamicForm) {
            $this->assertStringContainsString('role="alert"', $dynamicForm);
        }
        $this->assertStringContainsString('aria-describedby="{elementId}-error"', $fieldControls);
        $this->assertStringContainsString('id="{elementId}-error"', $fieldEditor);
        $this->assertStringContainsString('field.setAttribute("aria-invalid", "true")', $fieldTypeScript);
        $this->assertStringContainsString('field.setAttribute("aria-invalid", "false")', $fieldTypeScript);
    }
}
