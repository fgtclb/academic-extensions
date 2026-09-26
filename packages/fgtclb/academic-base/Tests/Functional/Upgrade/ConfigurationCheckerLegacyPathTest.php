<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Upgrade;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\AcademicBase\Upgrade\ConfigurationChecker;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFinding;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFindingKind;
use PHPUnit\Framework\Attributes\Test;

/**
 * The static template paths that four academic extensions offered up to version 2.3.
 *
 * They are deprecated and keep delivering until 4.0, so a record that stores one, or
 * imports one of their files, is not broken and the check has nothing to report about it.
 * The check decides by the files a folder holds, as core does, so these values pass for
 * the same reason any working value passes - this test is what notices if one of the
 * folders loses its files.
 */
final class ConfigurationCheckerLegacyPathTest extends AbstractAcademicBaseTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'fgtclb/academic-bite-jobs',
        'fgtclb/academic-contacts4pages',
        'fgtclb/academic-persons-edit',
        'fgtclb/academic-study-plan',
    ];

    private const LEGACY_PATHS = [
        'EXT:academic_bite_jobs/Configuration/TypoScript',
        'EXT:academic_contacts4pages/Configuration/TypoScript/',
        'EXT:academic_persons_edit/Configuration/TypoScript',
        'EXT:academic_study_plan/Configuration/TypoScript/Default',
    ];

    #[Test]
    public function aStoredLegacyPathIsNotReported(): void
    {
        $this->insertTypoScriptRecord(includeStaticFile: implode(',', self::LEGACY_PATHS));

        $this->assertSame([], $this->messagesOfKind(ConfigurationFindingKind::StaticTemplate));
    }

    #[Test]
    public function anImportOfTheLegacyFilesIsNotReported(): void
    {
        $constants = '';
        $setup = '';
        foreach (self::LEGACY_PATHS as $path) {
            $constants .= sprintf("@import '%s/constants.typoscript'\n", rtrim($path, '/'));
            $setup .= sprintf("@import '%s/setup.typoscript'\n", rtrim($path, '/'));
        }
        $this->insertTypoScriptRecord(constants: $constants, setup: $setup);

        $this->assertSame([], $this->messagesOfKind(ConfigurationFindingKind::TypoScriptImport));
    }

    private function insertTypoScriptRecord(string $constants = '', string $setup = '', string $includeStaticFile = ''): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert('sys_template', [
            'uid' => 1,
            'pid' => 1,
            'title' => 'Probe',
            'root' => 1,
            'clear' => 0,
            'constants' => $constants,
            'config' => $setup,
            'include_static_file' => $includeStaticFile,
        ]);
    }

    /**
     * @return list<string>
     */
    private function messagesOfKind(ConfigurationFindingKind $kind): array
    {
        return array_values(array_map(
            static fn(ConfigurationFinding $finding): string => $finding->message,
            array_filter(
                $this->get(ConfigurationChecker::class)->check(),
                static fn(ConfigurationFinding $finding): bool => $finding->kind === $kind,
            ),
        ));
    }
}
