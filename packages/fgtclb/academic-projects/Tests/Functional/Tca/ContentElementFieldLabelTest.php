<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Tca;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The page selection of both project plugins carries a label of its own instead of the
 * core "Startingpoint", and the selected projects element names it after what it does.
 *
 * The selected projects element added its configuration tab twice, the second time with
 * the label override. The second call found the fields in place already and dropped
 * them, so the override never applied (ACE-877).
 */
final class ContentElementFieldLabelTest extends AbstractAcademicProjectsTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function pageSelectionLabelDataProvider(): \Generator
    {
        yield 'projects' => ['academicprojects_projectlist', 'Selected pages for startingpoints'];
        yield 'selected projects' => ['academicprojects_projectlistsingle', 'Selected projects to display'];
    }

    #[Test]
    #[DataProvider('pageSelectionLabelDataProvider')]
    public function pageSelectionCarriesTheLabelOfTheElement(string $contentElementType, string $expectedLabel): void
    {
        $showItem = (string)($GLOBALS['TCA']['tt_content']['types'][$contentElementType]['showitem'] ?? '');
        $pagesEntries = array_values(array_filter(
            GeneralUtility::trimExplode(',', $showItem, true),
            static fn(string $entry): bool => GeneralUtility::trimExplode(';', $entry)[0] === 'pages',
        ));

        $this->assertCount(1, $pagesEntries, 'The page selection is not part of the form exactly once.');
        $label = GeneralUtility::trimExplode(';', $pagesEntries[0])[1] ?? '';
        $this->assertSame(
            $expectedLabel,
            $this->get(LanguageServiceFactory::class)->create('default')->sL($label),
        );
    }
}
