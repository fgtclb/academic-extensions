<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Service;

use FGTCLB\AcademicPersonsEdit\Service\ProfileGenderOptionsService;
use FGTCLB\AcademicPersonsEdit\Tests\Functional\AbstractAcademicPersonsEditTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ProfileGenderOptionsServiceTest extends AbstractAcademicPersonsEditTestCase
{
    private static function createTestTcaConfiguration(): void
    {
        $GLOBALS['TCA'] = [
            'tx_academicpersons_domain_model_profile' => [
                'columns' => [
                    'gender' => [
                        'config' => [
                            'items' => [
                                ['label' => 'Please select', 'value' => ''],
                                ['label' => 'Female', 'value' => 'ms'],
                                ['label' => 'Male', 'value' => 'mr'],
                                ['label' => 'Diverse', 'value' => 'diverse'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function getValuesFromTca(): array
    {
        return $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['gender']['config']['items'];
    }

    private static function getValueFromTca(int $index): array
    {
        return $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['gender']['config']['items'][$index];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTcaConfiguration();
    }

    #[Test]
    public function getOptionsReturnsConfiguredValuesAndLabels(): void
    {
        $profileGenderOptionsService = new ProfileGenderOptionsService();
        $actualOptions = $profileGenderOptionsService->getOptions();
        $expectedOptions = [];
        foreach (self::getValuesFromTca() as $item) {
            if ($item['value'] !== '') {
                $expectedOptions[$item['value']] = $item['label'];
            }
        }
        $this->assertSame($expectedOptions, $actualOptions);
    }

    #[Test]
    public function checkIfIsAllowedFunctionWorksAsExpected(): void
    {
        $profileGenderOptionsService = new ProfileGenderOptionsService();
        $this->assertTrue($profileGenderOptionsService->isAllowed(self::getValueFromTca(1)['value']));
        $this->assertFalse($profileGenderOptionsService->isAllowed(self::generateRandomString()));
    }

    private function generateRandomString(int $length = 10): string
    {
        return bin2hex(random_bytes($length));
    }
}
