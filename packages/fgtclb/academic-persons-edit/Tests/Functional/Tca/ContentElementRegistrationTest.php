<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Tca;

use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ContentElementRegistrationTest extends AbstractAcademicPersonsEditTestCase
{
    private const CONTENT_TYPE = 'academicpersonsedit_profileediting';
    private const REMOVED_INLINE_CONTENT_TYPE = 'academicpersonsedit_inlineprofile';
    private const REMOVED_SWITCHER_CONTENT_TYPE = 'academicpersonsedit_profileswitcher';

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
    public function profileEditingPluginKeepsItsStableContentType(): void
    {
        $this->assertContains(self::CONTENT_TYPE, $this->getContentTypeValues());
        $this->assertArrayHasKey(self::CONTENT_TYPE, $GLOBALS['TCA']['tt_content']['types']);
        $this->assertNotContains(self::REMOVED_INLINE_CONTENT_TYPE, $this->getContentTypeValues());
        $this->assertArrayNotHasKey(self::REMOVED_INLINE_CONTENT_TYPE, $GLOBALS['TCA']['tt_content']['types']);
    }

    #[Test]
    public function contentElementWizardOffersProfileEditingWithTheStableContentType(): void
    {
        $pageTsConfig = file_get_contents(
            __DIR__ . '/../../../Configuration/TSconfig/ProfileEditing/page.tsconfig',
        );
        $this->assertIsString($pageTsConfig);
        $this->assertStringContainsString(self::CONTENT_TYPE, $pageTsConfig);
        $this->assertStringNotContainsString(self::REMOVED_INLINE_CONTENT_TYPE, $pageTsConfig);
    }

    #[Test]
    public function removedProfileSwitcherIsNotRegistered(): void
    {
        $this->assertNotContains(self::REMOVED_SWITCHER_CONTENT_TYPE, $this->getContentTypeValues());
        $this->assertArrayNotHasKey(
            self::REMOVED_SWITCHER_CONTENT_TYPE,
            $GLOBALS['TCA']['tt_content']['types'],
        );
        $this->assertArrayNotHasKey(
            self::REMOVED_SWITCHER_CONTENT_TYPE,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'] ?? [],
        );
    }
}
