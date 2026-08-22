<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Tca;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins the values an installation stores in "pages.tsconfig_includes".
 *
 * They are not implementation detail: they are written into records, so renaming a
 * registered file silently empties the configuration of every installation that
 * selected it. Whenever an expectation here changes, the extension needs a Breaking
 * changelog entry naming the old and the new value.
 *
 * The registration is the only delivery path that works on TYPO3 v12, where site sets
 * do not exist yet, so this test class covers both core versions.
 *
 * This extension ships no TypoScript, and therefore no static template.
 */
final class StaticRegistrationTest extends AbstractAcademicBaseTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function pageTsConfigFileIsRegisteredDataProvider(): \Generator
    {
        yield 'content element group' => [
            'EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig',
            'Academic Base: Content element group (academic_base)',
        ];
        yield 'all components' => [
            'EXT:academic_base/Configuration/TSconfig/Full/page.tsconfig',
            'Academic Base: All components (academic_base)',
        ];
    }

    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function pageTsConfigFileIsRegistered(string $value, string $label): void
    {
        $this->assertContains(
            ['label' => $label, 'value' => $value],
            $GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'] ?? [],
        );
    }

    /**
     * The registration above is a string, so it stays green when the file it names is
     * renamed or removed - which is the failure this test class exists for. An
     * unresolved page TSconfig include is silent, so the file has to be asserted
     * separately.
     */
    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function registeredPageTsConfigFileExists(string $value, string $label): void
    {
        $this->assertFileExists(
            GeneralUtility::getFileAbsFileName($value),
            sprintf('The file registered as "%s" does not exist.', $label),
        );
    }
}
