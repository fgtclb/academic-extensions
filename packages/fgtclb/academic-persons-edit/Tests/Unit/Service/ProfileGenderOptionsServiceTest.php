<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Service;

use FGTCLB\AcademicPersonsEdit\Service\ProfileGenderOptionsService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProfileGenderOptionsServiceTest extends UnitTestCase
{
    private bool $tcaWasDefined = false;

    /** @var mixed */
    private $tcaBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tcaWasDefined = array_key_exists('TCA', $GLOBALS);
        $this->tcaBackup = $GLOBALS['TCA'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->tcaWasDefined) {
            $GLOBALS['TCA'] = $this->tcaBackup;
        } else {
            unset($GLOBALS['TCA']);
        }
        parent::tearDown();
    }

    #[Test]
    public function allowedValuesAreReadFromGenderTcaAndEmptyOptionsAreSkipped(): void
    {
        $this->setConfiguredItems([
            ['label' => 'Female', 'value' => 'ms'],
            ['label' => 'Male', 'value' => 'mr'],
            ['label' => 'Diverse', 'value' => 'diverse'],
        ]);

        $this->assertSame(
            ['ms', 'mr', 'diverse'],
            (new ProfileGenderOptionsService())->getAllowedValues(),
        );
    }

    #[Test]
    public function noConfiguredItemsProduceNoAllowedValues(): void
    {
        $GLOBALS['TCA'] = [];

        $this->assertSame(
            [],
            (new ProfileGenderOptionsService())->getAllowedValues(),
        );
    }

    #[Test]
    public function nonArrayItemsProduceNoAllowedValues(): void
    {
        $GLOBALS['TCA'] = [
            'tx_academicpersons_domain_model_profile' => [
                'columns' => [
                    'gender' => [
                        'config' => [
                            'items' => 'invalid',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame(
            [],
            (new ProfileGenderOptionsService())->getAllowedValues(),
        );
    }

    #[Test]
    public function configuredValueIsAllowedWithStrictComparison(): void
    {
        $this->setConfiguredItems([
            ['label' => 'Female', 'value' => 'female'],
        ]);
        $subject = new ProfileGenderOptionsService();

        $this->assertTrue($subject->isAllowed('female'));
        $this->assertFalse($subject->isAllowed('Female'));
        $this->assertFalse($subject->isAllowed('unknown'));
    }

    #[Test]
    public function emptyValueIsAlwaysAllowedForClearingTheProperty(): void
    {
        $GLOBALS['TCA'] = [];

        $this->assertTrue(
            (new ProfileGenderOptionsService())->isAllowed(''),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function setConfiguredItems(array $items): void
    {
        $GLOBALS['TCA'] = [
            'tx_academicpersons_domain_model_profile' => [
                'columns' => [
                    'gender' => [
                        'config' => [
                            'items' => $items,
                        ],
                    ],
                ],
            ],
        ];
    }
}
