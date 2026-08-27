<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Yaml\Yaml;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AcademicPersonsSettingsFactoryTest extends UnitTestCase
{
    #[Test]
    public function shippedConfigurationContainsOnlyThePublicProfileMap(): void
    {
        $this->assertSame(['profile'], array_keys($this->getShippedConfiguration()));
    }

    #[Test]
    public function separatedPublicProfileSchemaUsesANewCacheEntry(): void
    {
        $factory = (new ReflectionClass(AcademicPersonsSettingsFactory::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AcademicPersonsSettingsFactory::class, 'academicPersonsSettingsIdentifier');
        $method->setAccessible(true);
        $this->assertSame('AcademicPersons_Settings_PublicProfileSchema_v5', $method->invoke($factory));
    }

    #[Test]
    public function shippedProfileDefinesTheOrderedPublicDetailLayout(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame(
            [
                'left' => ['menuSections'],
                'right' => [
                    'headline',
                    'position',
                    'profileImage',
                    'contact',
                    'subline',
                    'profileEntries',
                    'menuSectionsDatas',
                ],
            ],
            $settings->publicProfile->structure,
        );
        $this->assertSame(
            ['title', 'firstName', 'middleName', 'lastName'],
            $settings->publicProfile->details['headline'],
        );
        $this->assertSame(
            [
                'researchProjects' => 'scientificResearch',
                'academicCareer' => 'vita',
                'membershipsCommitteeActivities' => 'memberships',
                'networkCooperation' => 'cooperation',
                'publications' => 'publications',
                'lectures' => 'lectures',
            ],
            $settings->publicProfile->details['menuSectionsDatas'],
        );
    }

    #[Test]
    public function publicProfileListsAndMapsAreNormalizedWithoutChangingOrder(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'structure' => [
                    'left' => [' menuSections ', '', 'menuSections', 123, ' headline '],
                    'right' => 'profileEntries',
                    0 => ['ignoredColumn'],
                ],
                'details' => [
                    'headline' => [' title ', '', 'title', false, ' firstName '],
                    'position' => ['special' => ' datasFromContracts ', 'empty' => ' ', 0 => 'ignored'],
                    'subline' => 'LLL:EXT:site/Resources/Private/Language/locallang.xlf:profile.subline',
                    'menuSectionsDatas' => [
                        'researchProjects' => ' scientificResearch ',
                        'empty' => ' ',
                        0 => 'ignored',
                        'invalid' => false,
                    ],
                    'invalid' => false,
                    '' => ['ignoredDetail'],
                ],
            ],
        ]);
        $this->assertSame(['left' => ['menuSections', 'headline']], $settings->publicProfile->structure);
        $this->assertSame(
            [
                'headline' => ['title', 'firstName'],
                'position' => ['special' => 'datasFromContracts'],
                'subline' => 'LLL:EXT:site/Resources/Private/Language/locallang.xlf:profile.subline',
                'menuSectionsDatas' => ['researchProjects' => 'scientificResearch'],
            ],
            $settings->publicProfile->details,
        );
    }

    #[Test]
    public function editSectionsAreNotLoadedIntoThePublicSettingsGraph(): void
    {
        $configuration = $this->getShippedConfiguration();
        $configuration['documentSections'] = [
            'publications' => [
                'label' => 'Publications',
                'type' => 'publication',
                'fieldName' => 'publications',
            ],
        ];
        $settings = $this->normalize($configuration);
        $this->assertSame([], $settings->profileSections);
        $this->assertSame([], $settings->specialFields);
        $this->assertSame([], $settings->contractContactSections);
        $this->assertSame([], $settings->documentSections);
        $this->assertSame($configuration, $settings->raw);
    }

    #[Test]
    public function publicSettingsSurviveThePhpCacheRoundTrip(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $restored = eval('return ' . var_export($settings, true) . ';');
        $this->assertInstanceOf(AcademicPersonsSettings::class, $restored);
        $this->assertEquals($settings, $restored);
        $this->assertNotSame($settings, $restored);
    }

    /**
     * @return array<string, mixed>
     */
    private function getShippedConfiguration(): array
    {
        $configuration = Yaml::parseFile(__DIR__ . '/../../../Configuration/AcademicPersons/Settings.yaml');
        $this->assertIsArray($configuration);
        return $configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function normalize(array $configuration): AcademicPersonsSettings
    {
        $factory = (new ReflectionClass(AcademicPersonsSettingsFactory::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AcademicPersonsSettingsFactory::class, 'normalize');
        $method->setAccessible(true);
        $settings = $method->invoke($factory, $configuration);
        $this->assertInstanceOf(AcademicPersonsSettings::class, $settings);
        return $settings;
    }
}
