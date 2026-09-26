<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\TypoScript;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\StaticTemplateTypoScriptTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The static template path this extension offered up to version 2.3.
 *
 * Installations store it in their TypoScript records, and site packages import the files
 * in it. Version 2.4 moves the TypoScript into component folders, and a stored path or an
 * import that finds no file would deliver nothing, without an error. The path is
 * deprecated and keeps delivering until 4.0.
 *
 * "All components" brings the TypoScript of EXT:academic_persons along, whose constants the
 * setup of this extension reads. The path of version 2.3 never did: a record of that
 * version stores the persons entry next to it. The path keeps it that way, so a record of
 * 2.3 reads the persons TypoScript once, as it always did.
 */
final class LegacyStaticTemplatePathTest extends AbstractAcademicContacts4PagesTestCase
{
    use StaticTemplateTypoScriptTrait;

    private const LEGACY_PATH = 'EXT:academic_contacts4pages/Configuration/TypoScript/';
    private const PERSONS = 'EXT:academic_persons/Configuration/TypoScript/Default';
    private const PERSONS_SETUP_FILE = 'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript';
    private const ALL_COMPONENTS = 'EXT:academic_contacts4pages/Configuration/TypoScript/Full';
    private const COMPONENT_SETUP_FILE = 'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript';
    private const LEGACY_CONSTANTS_IMPORT = "@import 'EXT:academic_contacts4pages/Configuration/TypoScript/constants.typoscript'";
    private const LEGACY_SETUP_IMPORT = "@import 'EXT:academic_contacts4pages/Configuration/TypoScript/setup.typoscript'";
    private const COMPONENT_CONSTANTS_IMPORT = "@import 'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript'";
    private const COMPONENT_SETUP_IMPORT = "@import 'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript'";

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LegacyStaticTemplatePath/records.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * A record of version 2.3 stores the persons entry and the path of this extension,
     * in that order. The whole TypoScript is compared, settings and setup, so a component
     * added to "All components" later and missing here turns this red.
     */
    #[Test]
    public function aStoredLegacyPathNextToAcademicPersonsDeliversWhatAllComponentsDelivers(): void
    {
        $allComponents = $this->typoScriptOfTemplateRecord(self::ALL_COMPONENTS);

        $this->assertSame(
            'EXT:academic_persons/Resources/Private/Partials/',
            $allComponents['setup']['plugin.']['tx_academiccontacts4pages.']['view.']['partialRootPaths.']['5'] ?? null,
            '"All components" does not deliver the plugin configuration, so the comparison below proves nothing.',
        );
        $this->assertSame($allComponents, $this->typoScriptOfTemplateRecord(self::PERSONS . ',' . self::LEGACY_PATH));
    }

    #[Test]
    public function aStoredLegacyPathNextToAcademicPersonsReadsEachFileOnce(): void
    {
        $readFiles = $this->setupFilesOfTemplateRecord(self::PERSONS . ',' . self::LEGACY_PATH);

        $this->assertCount(1, array_keys($readFiles, self::COMPONENT_SETUP_FILE, true), implode(', ', $readFiles));
        $this->assertCount(1, array_keys($readFiles, self::PERSONS_SETUP_FILE, true), implode(', ', $readFiles));
    }

    /**
     * The path delivers the TypoScript of this extension and nothing else, as it did in
     * version 2.3. The constant is the one the setup of this extension reads for the
     * profile links.
     */
    #[Test]
    public function aStoredLegacyPathDoesNotBringAcademicPersonsAlong(): void
    {
        $this->assertArrayHasKey(
            'plugin.tx_academicpersons.detailPid',
            $this->typoScriptOfTemplateRecord(self::ALL_COMPONENTS)['settings'],
        );
        $this->assertSame(
            $this->typoScriptOfTemplateRecord('', self::COMPONENT_CONSTANTS_IMPORT, self::COMPONENT_SETUP_IMPORT),
            $this->typoScriptOfTemplateRecord(self::LEGACY_PATH),
        );
    }

    /**
     * The files of the path of version 2.3 deliver what the files of the component
     * folder deliver, which is what the 2.4 changelog names as their replacement.
     */
    #[Test]
    public function anImportOfTheLegacyFilesDeliversWhatAnImportOfTheComponentFilesDelivers(): void
    {
        $component = $this->typoScriptOfTemplateRecord('', self::COMPONENT_CONSTANTS_IMPORT, self::COMPONENT_SETUP_IMPORT);

        $this->assertSame(
            'EXT:academic_persons/Resources/Private/Partials/',
            $component['setup']['plugin.']['tx_academiccontacts4pages.']['view.']['partialRootPaths.']['5'] ?? null,
            'The files of the component folder do not deliver the plugin configuration, so the comparison below proves nothing.',
        );
        $this->assertSame(
            $component,
            $this->typoScriptOfTemplateRecord('', self::LEGACY_CONSTANTS_IMPORT, self::LEGACY_SETUP_IMPORT),
        );
    }

    /**
     * The backend form drops a stored static template that is not among the items of the
     * field, and saving the record writes what the form kept. The second value is not
     * registered, and shows that a dropped value is noticed here.
     */
    #[Test]
    public function theBackendFormKeepsAStoredLegacyPath(): void
    {
        $this->assertSame(
            [self::LEGACY_PATH],
            $this->staticTemplatesTheFormKeeps(self::LEGACY_PATH . ',EXT:academic_contacts4pages/Configuration/TypoScript/NotRegistered'),
        );
    }
}
