<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AcademicPersonsEditSettingsSourceTest extends UnitTestCase
{
    #[Test]
    public function editExtensionDoesNotShipASecondSettingsFile(): void
    {
        $legacyPath = __DIR__ . '/../../../Configuration/AcademicsPersonsEdit/Settings.yaml';
        $centralPath = __DIR__ . '/../../../../academic-persons/Configuration/AcademicPersons/Settings.yaml';
        $this->assertFileDoesNotExist($legacyPath);
        $configuration = Yaml::parseFile($centralPath);
        $this->assertIsArray($configuration);
        $this->assertSame(['profile', 'special', 'contracts', 'documentSections'], array_keys($configuration));
        $this->assertArrayHasKey('structure', $configuration['profile']);
        $this->assertArrayHasKey('details', $configuration['profile']);
        $this->assertArrayHasKey('gender', $configuration['profile']);
        $this->assertSame(1000, $configuration['profile']['miscellaneous']['characterLimit'] ?? null);
        $this->assertSame(['type' => 'contracts'], $configuration['documentSections']['contracts']);
        $this->assertSame(
            [
                'position',
                'organisationalUnit',
                'functionType',
                'validFrom',
                'validTo',
                'location',
                'room',
                'officeHours',
                'publish',
            ],
            array_keys($configuration['contracts']['fields']),
        );
        $this->assertSame(
            ['physicalAddresses', 'emailAddresses', 'phoneNumbers'],
            array_keys($configuration['contracts']['contactSections']),
        );
        foreach ($configuration['contracts']['fields'] as $field) {
            $this->assertIsArray($field);
            $this->assertIsString($field['helptext'] ?? null);
        }
        foreach ($configuration['contracts']['contactSections'] as $section) {
            $this->assertIsArray($section);
            foreach ($section['fields'] ?? [] as $field) {
                $this->assertIsArray($field);
                $this->assertIsString($field['helptext'] ?? null);
            }
        }
    }

    #[Test]
    public function centralFactoryNormalizesPublicAndEditSettingsTogether(): void
    {
        $centralPath = __DIR__ . '/../../../../academic-persons/Configuration/AcademicPersons/Settings.yaml';
        $configuration = Yaml::parseFile($centralPath);
        $this->assertIsArray($configuration);
        $factory = (new \ReflectionClass(AcademicPersonsSettingsFactory::class))->newInstanceWithoutConstructor();
        $settings = $factory->normalizeEditConfiguration($configuration);
        $this->assertNotEmpty($settings->publicProfile->structure);
        $this->assertNotNull($settings->getProfileField('gender'));
        $this->assertNotNull($settings->getProfileField('firstName'));
        $this->assertNotEmpty($settings->documentSections);
    }

}
