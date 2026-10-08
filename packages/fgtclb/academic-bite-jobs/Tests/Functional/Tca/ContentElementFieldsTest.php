<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Tca;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The fields the content element form of the job list offers.
 *
 * The job list reads its postings from the b-ite API and never from a record
 * storage page, so the "Record Storage Page" field had no effect and is not
 * offered any more (ACE-832). The plugin configuration stays.
 */
final class ContentElementFieldsTest extends AbstractAcademicBiteJobsTestCase
{
    #[Test]
    public function jobListOffersThePluginConfigurationAndNoRecordStoragePage(): void
    {
        $fields = $this->fieldsOf('academicbitejobs_list');

        $this->assertContains('pi_flexform', $fields);
        $this->assertNotContains('pages', $fields);
        $this->assertNotContains('recursive', $fields);
    }

    /**
     * @return list<string> The field names of the type, palettes resolved.
     */
    private function fieldsOf(string $contentElementType): array
    {
        $tca = $GLOBALS['TCA']['tt_content'];
        $showItem = (string)($tca['types'][$contentElementType]['showitem'] ?? '');
        $this->assertNotSame('', $showItem, sprintf('The type "%s" is not registered.', $contentElementType));

        $fields = [];
        foreach (GeneralUtility::trimExplode(',', $showItem, true) as $entry) {
            [$name, , $palette] = array_pad(GeneralUtility::trimExplode(';', $entry), 3, '');
            if ($name === '--palette--') {
                $paletteItems = (string)($tca['palettes'][$palette]['showitem'] ?? '');
                foreach (GeneralUtility::trimExplode(',', $paletteItems, true) as $paletteEntry) {
                    $fields[] = GeneralUtility::trimExplode(';', $paletteEntry)[0];
                }
                continue;
            }
            $fields[] = $name;
        }

        return $fields;
    }
}
