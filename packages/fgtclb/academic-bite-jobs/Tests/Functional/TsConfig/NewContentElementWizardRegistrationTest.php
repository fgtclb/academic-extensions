<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\TsConfig;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins the two halves of the "hide by default, enable per component" contract.
 *
 * "Configuration/page.tsconfig" of an extension is auto-included for the whole
 * installation since TYPO3 v12.0 (Feature: #96614), so it is where the content
 * element is hidden. It is brought back by the page TSconfig of the component,
 * which is delivered either by the site set "fgtclb/academic-bite-jobs-list" or,
 * as here, by the entry "Configuration/TCA/Overrides/pages.php" registers for the
 * page field "Page TSconfig".
 *
 * No site is written by this test on purpose. That is what makes it a test of the
 * static registration rather than of the set.
 */
final class NewContentElementWizardRegistrationTest extends AbstractAcademicBiteJobsTestCase
{
    #[Test]
    public function contentElementIsHiddenWithoutTheComponentPageTsConfig(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContentElementWizardRegistration/pages.csv');

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);

        $this->assertContains('academicbitejobs_list', $this->removedContentElementTypes(1));
        $this->assertArrayNotHasKey(
            'academicbitejobs_list.',
            $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [],
        );
    }

    /**
     * The fixture holds a site root without the registration and a subpage with it, and
     * the test asserts both: without it the content element is hidden, with it it is
     * offered. Asserting only the second half would stay green if the global hide were
     * deleted, which is the half of the contract this extension owns.
     */
    #[Test]
    public function contentElementIsOfferedWithTheRegisteredPageTsConfig(): void
    {
        $this->importCSVDataSet(
            __DIR__ . '/Fixtures/NewContentElementWizardRegistration/pagesWithRegisteredPageTsConfig.csv'
        );

        $this->assertContains(
            'academicbitejobs_list',
            $this->removedContentElementTypes(1),
            'The content element is not hidden on the page without the registered page TSconfig.',
        );
        $this->assertNotContains(
            'academicbitejobs_list',
            $this->removedContentElementTypes(2),
            'The registered page TSconfig did not bring the content element back.',
        );
    }

    /**
     * The element definition carries the title, the icon and the CType the wizard
     * preselects.
     */
    #[Test]
    public function wizardItemIsRegisteredWithTheRegisteredPageTsConfig(): void
    {
        $this->importCSVDataSet(
            __DIR__ . '/Fixtures/NewContentElementWizardRegistration/pagesWithRegisteredPageTsConfig.csv'
        );

        $group = BackendUtility::getPagesTSconfig(2)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.'] ?? [];
        $elements = $group['elements.'] ?? [];

        $this->assertArrayHasKey('academicbitejobs_list.', $elements);
        $this->assertSame(
            'LLL:EXT:academic_bite_jobs/Resources/Private/Language/locallang_be.xlf:plugin.bite.list.label',
            $elements['academicbitejobs_list.']['title'] ?? null,
        );
        $this->assertSame('bitejobs_list', $elements['academicbitejobs_list.']['iconIdentifier'] ?? null);
        $this->assertSame(
            ['CType' => 'academicbitejobs_list'],
            $elements['academicbitejobs_list.']['tt_content_defValues.'] ?? null,
        );
    }

    /**
     * The group header is shipped by "academic_base" and is global, so it is there
     * whether or not any component of this extension is enabled.
     */
    #[Test]
    public function contentElementGroupHeaderIsRegisteredWithoutASiteSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContentElementWizardRegistration/pages.csv');

        $group = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.'] ?? [];

        $this->assertSame(
            'LLL:EXT:academic_base/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
            $group['header'] ?? null,
        );
    }

    /**
     * @return list<string> The content element types "TCEFORM.tt_content.CType.removeItems"
     *                      hides on the given page.
     */
    private function removedContentElementTypes(int $pageId): array
    {
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageId);

        return GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? ''),
            true,
        );
    }
}
