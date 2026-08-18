<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\TsConfig;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins that the new content element wizard entry of the plugin this extension
 * registers reaches an installation that does not use site sets.
 *
 * "Configuration/page.tsconfig" of an extension is auto-included for the whole
 * installation since TYPO3 v12.0 (Feature: #96614); a site set is opt-in per site.
 * The import used to live in the set alone, so the wizard entry of the content
 * element - its title, its icon and the CType it preselects - existed only on the
 * sites that had enabled that set, and nowhere else.
 *
 * No site is written by this test on purpose. That is what makes it a test of the
 * global page TSconfig rather than of the set.
 */
final class NewContentElementWizardRegistrationTest extends AbstractAcademicBiteJobsTestCase
{
    #[Test]
    public function wizardItemIsRegisteredWithoutASiteSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContentElementWizardRegistration/pages.csv');

        $elements = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

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
     * An element that is configured but not listed in "show" is not offered by the
     * wizard, so the "addToList" of the same file is as load-bearing as the element
     * definition itself.
     */
    #[Test]
    public function wizardItemIsShownWithoutASiteSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContentElementWizardRegistration/pages.csv');

        $group = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.'] ?? [];

        $this->assertContains(
            'academicbitejobs_list',
            GeneralUtility::trimExplode(',', (string)($group['show'] ?? ''), true),
        );
    }
}
