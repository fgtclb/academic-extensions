<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Tca;

use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pins InlineProfile as the only editing content element offered in the backend.
 *
 * ProfileEditing remains as source and runtime compatibility reference until the inline
 * migration is complete. Its hidden record type keeps existing elements renderable, but the
 * missing select item and icon prevent editors from creating new legacy elements.
 */
final class ContentElementRegistrationTest extends AbstractAcademicPersonsEditTestCase
{
    private const REMOVED_CONTENT_TYPE = 'academicpersonsedit_profileswitcher';
    private const LEGACY_CONTENT_TYPE = 'academicpersonsedit_profileediting';
    private const INLINE_CONTENT_TYPE = 'academicpersonsedit_inlineprofile';

    /**
     * @return list<string>
     */
    private function getContentTypeValues(): array
    {
        $values = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [] as $item) {
            $values[] = (string)($item['value'] ?? '');
        }
        return $values;
    }

    #[Test]
    public function inlineProfilePluginIsSelectable(): void
    {
        $this->assertContains(self::INLINE_CONTENT_TYPE, $this->getContentTypeValues());
        $this->assertArrayHasKey(
            self::INLINE_CONTENT_TYPE,
            $GLOBALS['TCA']['tt_content']['types'],
        );
    }

    #[Test]
    public function legacyProfileEditingPluginKeepsRuntimeTypeWithoutBeingSelectable(): void
    {
        $this->assertNotContains(self::LEGACY_CONTENT_TYPE, $this->getContentTypeValues());
        $this->assertArrayHasKey(
            self::LEGACY_CONTENT_TYPE,
            $GLOBALS['TCA']['tt_content']['types'],
        );
        $this->assertArrayNotHasKey(
            self::LEGACY_CONTENT_TYPE,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] ?? [],
        );
    }

    #[Test]
    public function contentElementWizardOffersOnlyInlineProfileEditing(): void
    {
        $pageTsConfig = file_get_contents(__DIR__ . '/../../../Configuration/TSconfig/page.tsconfig');
        $this->assertIsString($pageTsConfig);
        $this->assertStringContainsString(self::INLINE_CONTENT_TYPE, $pageTsConfig);
        $this->assertStringNotContainsString(self::LEGACY_CONTENT_TYPE, $pageTsConfig);
    }

    #[Test]
    public function legacyProfileEditingImplementationRemainsAvailableAsReference(): void
    {
        $extensionRoot = __DIR__ . '/../../..';
        foreach ([
            '/Classes/Controller/ProfileController.php',
            '/Classes/Controller/ProfileInformationController.php',
            '/Classes/Controller/ContractController.php',
            '/Resources/Private/Templates/Profile/Edit.html',
            '/Resources/Private/Templates/ProfileInformation/Edit.html',
            '/Resources/Private/Templates/Contract/Edit.html',
            '/Tests/Functional/Plugins/AbstractProfileEditingPluginTestCase.php',
        ] as $referenceFile) {
            $this->assertFileExists($extensionRoot . $referenceFile);
        }
    }

    #[Test]
    public function profileSwitcherPluginIsNotSelectable(): void
    {
        $this->assertNotContains(self::REMOVED_CONTENT_TYPE, $this->getContentTypeValues());
    }

    #[Test]
    public function profileSwitcherPluginHasNoRecordType(): void
    {
        // Without this entry the page module renders a warning badge for a leftover record
        // instead of a header shaped form, which is what makes it visible to an editor.
        $this->assertArrayNotHasKey(
            self::REMOVED_CONTENT_TYPE,
            $GLOBALS['TCA']['tt_content']['types'],
        );
    }

    #[Test]
    public function profileSwitcherPluginHasNoTypeIcon(): void
    {
        $this->assertArrayNotHasKey(
            self::REMOVED_CONTENT_TYPE,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] ?? [],
        );
    }
}
